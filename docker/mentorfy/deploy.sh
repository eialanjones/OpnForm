#!/usr/bin/env bash
# Blue/green deploy for forms.mentorfy.io. Installed at /opt/opnform/deploy/deploy.sh
# and used as an SSH forced command (command="/opt/opnform/deploy/deploy.sh",restrict).
#
#   deploy <sha40>   pull images, start the idle color, switch the edge vhost, drain the old color
#   rollback         redeploy PREV_* from /opt/opnform/.env (same steps, no pull)
#   status           show state
#
# Exit codes: 0 ok, 1 failed (production untouched or switched back), 2 refused, 3 web live but worker/scheduler failed.
set -Eeuo pipefail
# The GitHub runner may disconnect; keep going and keep logging to the file.
trap '' HUP PIPE

REPO_IMG="ghcr.io/eialanjones/opnform"
BASE="/opt/opnform"
ENV_FILE="${BASE}/.env"
LOG_FILE="/var/log/opnform-deploy.log"
LOCK_FILE="/run/opnform-deploy.lock"
VHOST="/var/lib/openship/edge/sites-enabled/forms-mentorfy-io.conf"
ROUTE="/var/lib/openship/edge/sites-enabled/forms-mentorfy-io.route.json"
EDGE="openship-edge"
EDGE_BACKUP_DIR="${BASE}/deploy/edge-backup"
DOMAIN="forms.mentorfy.io"
IMAGES=(api client ingress)
HEALTH_TIMEOUT=360
PUBLIC_TIMEOUT=30
DRAIN_SECONDS=60
declare -A PORT=([blue]=20010 [green]=20020)
declare -A ENVV=()

GH_USER=""
GH_TOKEN=""
TMP_DOCKER_CONFIG=""
STAGE="none"          # none | idle_up | switched | committed
IDLE=""
ACTIVE=""
BK_VHOST=""
BK_ROUTE=""
WORKER_FAILED=0

log() {
  local line
  line="$(date -Is) $*"
  printf '%s\n' "$line" | tee --output-error=warn -a "$LOG_FILE" || true
}

# Run a command, mirroring its output to the log. Returns the command's own status:
# if the SSH client is gone, tee exits non-zero on EPIPE, which must not count as a failure.
logrun() {
  "$@" 2>&1 | tee --output-error=warn -a "$LOG_FILE" && return 0
  return "${PIPESTATUS[0]}"
}

fail() {
  log "ERROR: $*"
  exit 1
}

on_err() {
  local rc=$1 line=$2 cmd=$3
  log "ERROR: line ${line}: ${cmd} (exit ${rc})"
}
trap 'on_err "$?" "$LINENO" "$BASH_COMMAND"' ERR

# ---------------------------------------------------------------- state files

# Strict KEY=VALUE parser: only whitelisted characters, never sourced.
read_env() {
  ENVV=()
  [[ -f "$ENV_FILE" ]] || return 0
  local line re='^([A-Z][A-Z0-9_]*)=([A-Za-z0-9._:/@+-]*)$'
  while IFS= read -r line || [[ -n "$line" ]]; do
    if [[ "$line" =~ $re ]]; then
      ENVV["${BASH_REMATCH[1]}"]="${BASH_REMATCH[2]}"
    fi
  done < "$ENV_FILE"
  return 0
}

envget() {
  printf '%s' "${ENVV[$1]:-}"
}

# write_env <repo> <tag> <sha> <active> <prev_repo> <prev_tag> <prev_sha>
write_env() {
  local tmp
  tmp="$(mktemp "${BASE}/.env.XXXXXX")"
  chmod 600 "$tmp"
  {
    printf 'OPNFORM_IMAGE_REPO=%s\n' "$1"
    printf 'OPNFORM_TAG=%s\n' "$2"
    printf 'OPNFORM_SHA=%s\n' "$3"
    printf 'OPNFORM_ACTIVE=%s\n' "$4"
    printf 'PREV_IMAGE_REPO=%s\n' "$5"
    printf 'PREV_TAG=%s\n' "$6"
    printf 'PREV_SHA=%s\n' "$7"
  } > "$tmp"
  mv -f "$tmp" "$ENV_FILE"
}

# write_color_env <color> <repo> <tag>
write_color_env() {
  local color=$1 tmp
  mkdir -p "${BASE}/colors"
  tmp="$(mktemp "${BASE}/colors/.${color}.XXXXXX")"
  {
    printf 'OPNFORM_IMAGE_REPO=%s\n' "$2"
    printf 'OPNFORM_TAG=%s\n' "$3"
    printf 'OPNFORM_PORT=%s\n' "${PORT[$color]}"
  } > "$tmp"
  chmod 600 "$tmp"
  mv -f "$tmp" "${BASE}/colors/${color}.env"
}

# Prints the single port used by every proxy_pass in the vhost; fails if none or mixed.
vhost_port() {
  local ports count
  ports="$(grep -oE 'proxy_pass http://127\.0\.0\.1:(20010|20020)\b' "$VHOST" | sed -E 's/.*://' | sort -u || true)"
  [[ -n "$ports" ]] || return 1
  count="$(printf '%s\n' "$ports" | wc -l)"
  [[ "$count" -eq 1 ]] || return 1
  printf '%s' "$ports"
}

# ------------------------------------------------------------------- compose

color_compose() {
  local color=$1
  shift
  docker compose --project-directory "$BASE" -p "opnform-${color}" -f "${BASE}/app.yml" \
    --env-file "${BASE}/colors/${color}.env" "$@"
}

main_compose() {
  (cd "$BASE" && docker compose -p opnform -f docker-compose.yml "$@")
}

down_color() {
  local color=$1
  if [[ ! -f "${BASE}/colors/${color}.env" ]]; then
    log "no colors/${color}.env, nothing to take down for ${color}"
    return 0
  fi
  logrun color_compose "$color" down --remove-orphans || log "warning: down of ${color} reported errors"
  return 0
}

# ---------------------------------------------------------------------- edge

edge_reload() {
  logrun docker exec "$EDGE" nginx -t || return 1
  logrun docker exec "$EDGE" nginx -s reload || return 1
}

# Rewrite in place without replacing the inode (the edge may bind-mount the files).
rewrite_port() {
  local file=$1 from=$2 to=$3 tmp
  tmp="$(mktemp)"
  sed "s/127\\.0\\.0\\.1:${from}\\b/127.0.0.1:${to}/g" "$file" > "$tmp"
  cat "$tmp" > "$file"
  rm -f "$tmp"
}

backup_edge() {
  local ts
  ts="$(date +%Y%m%d-%H%M%S)"
  mkdir -p "$EDGE_BACKUP_DIR"
  BK_VHOST="${EDGE_BACKUP_DIR}/forms-mentorfy-io.conf.${ts}"
  BK_ROUTE="${EDGE_BACKUP_DIR}/forms-mentorfy-io.route.json.${ts}"
  cp -p "$VHOST" "$BK_VHOST"
  cp -p "$ROUTE" "$BK_ROUTE"
  local prefix
  for prefix in forms-mentorfy-io.conf forms-mentorfy-io.route.json; do
    # shellcheck disable=SC2012
    ls -1t "${EDGE_BACKUP_DIR}/${prefix}."* 2>/dev/null | tail -n +11 | while IFS= read -r old; do
      rm -f "$old"
    done || true
  done
}

restore_edge() {
  [[ -n "$BK_VHOST" && -f "$BK_VHOST" ]] || return 0
  cat "$BK_VHOST" > "$VHOST"
  cat "$BK_ROUTE" > "$ROUTE"
  edge_reload || log "warning: edge reload after restore failed, check ${EDGE} by hand"
}

http_code() {
  curl -s -o /dev/null -w '%{http_code}' --max-time 5 "$@" 2>/dev/null || true
}

root_ok() {
  [[ "$1" =~ ^[23][0-9][0-9]$ ]]
}

# ------------------------------------------------------------------- cleanup

on_exit() {
  local rc=$?
  trap - ERR
  set +e
  if [[ -n "$TMP_DOCKER_CONFIG" ]]; then
    rm -rf "$TMP_DOCKER_CONFIG"
  fi
  if [[ "$rc" -ne 0 && "$rc" -ne 3 ]]; then
    case "$STAGE" in
      idle_up)
        log "aborting: taking down idle stack ${IDLE}, production on ${ACTIVE} untouched"
        down_color "$IDLE"
        ;;
      switched)
        log "aborting after edge switch: restoring vhost"
        restore_edge
        down_color "$IDLE"
        log "switched back, production on ${ACTIVE} untouched"
        ;;
    esac
  fi
  exit "$rc"
}
trap on_exit EXIT

# ------------------------------------------------------------------ pipeline

images_present() {
  local repo=$1 tag=$2 name
  for name in "${IMAGES[@]}"; do
    docker image inspect "${repo}/${name}:${tag}" >/dev/null 2>&1 || return 1
  done
}

pull_images() {
  local tag=$1 name
  if [[ -z "$GH_TOKEN" ]]; then
    if images_present "$REPO_IMG" "$tag"; then
      log "no registry token, images ${tag} already present locally: skipping pull"
      return 0
    fi
    fail "no registry token on stdin and images ${tag} are not present locally"
  fi
  TMP_DOCKER_CONFIG="$(mktemp -d)"
  log "logging in to ghcr.io as ${GH_USER}"
  if ! printf '%s' "$GH_TOKEN" | env DOCKER_CONFIG="$TMP_DOCKER_CONFIG" \
      docker login ghcr.io -u "$GH_USER" --password-stdin >> "$LOG_FILE" 2>&1; then
    fail "registry login failed, nothing touched"
  fi
  GH_TOKEN=""
  for name in "${IMAGES[@]}"; do
    log "pulling ${REPO_IMG}/${name}:${tag}"
    if ! logrun env DOCKER_CONFIG="$TMP_DOCKER_CONFIG" docker pull -q "${REPO_IMG}/${name}:${tag}"; then
      fail "pull of ${name}:${tag} failed, nothing touched"
    fi
  done
  rm -rf "$TMP_DOCKER_CONFIG"
  TMP_DOCKER_CONFIG=""
}

sync_source() {
  local sha=$1
  if [[ -z "$sha" ]]; then
    log "warning: no sha known for this version, source checkout skipped"
    return 0
  fi
  if git -C "${BASE}/src" fetch -q origin formsfy && git -C "${BASE}/src" checkout -q --detach "$sha"; then
    log "source at ${BASE}/src checked out to ${sha}"
  else
    log "warning: could not sync ${BASE}/src to ${sha} (only matters for manual build fallback)"
  fi
}

resolve_active() {
  local vp
  read_env
  ACTIVE="$(envget OPNFORM_ACTIVE)"
  case "$ACTIVE" in
    blue) IDLE="green" ;;
    green) IDLE="blue" ;;
    *) fail "OPNFORM_ACTIVE in ${ENV_FILE} is '${ACTIVE}', expected blue or green" ;;
  esac
  vp="$(vhost_port)" || fail "vhost ${VHOST} has no single proxy_pass port"
  [[ "$vp" == "${PORT[$ACTIVE]}" ]] \
    || fail "vhost points to port ${vp} but OPNFORM_ACTIVE=${ACTIVE} (port ${PORT[$ACTIVE]}): refusing to touch anything"
  log "active color: ${ACTIVE} (port ${vp}), idle color: ${IDLE} (port ${PORT[$IDLE]})"
}

start_idle() {
  local repo=$1 tag=$2
  write_color_env "$IDLE" "$repo" "$tag"
  STAGE="idle_up"
  log "cleaning leftovers of ${IDLE}"
  down_color "$IDLE"
  log "starting ${IDLE} with ${repo}:${tag}"
  logrun color_compose "$IDLE" up -d --no-build || fail "could not start ${IDLE} stack"
}

wait_idle_healthy() {
  local port=${PORT[$IDLE]} container="opnform-${IDLE}-app-api-1" deadline health api root
  deadline=$((SECONDS + HEALTH_TIMEOUT))
  log "waiting up to ${HEALTH_TIMEOUT}s for ${IDLE} on port ${port}"
  while (( SECONDS < deadline )); do
    health="$(docker inspect -f '{{.State.Health.Status}}' "$container" 2>/dev/null || true)"
    if [[ "$health" == "healthy" ]]; then
      api="$(http_code "http://127.0.0.1:${port}/api/content/feature-flags")"
      root="$(http_code "http://127.0.0.1:${port}/")"
      if [[ "$api" == "200" ]] && root_ok "$root"; then
        log "${IDLE} is healthy (api ${api}, root ${root})"
        return 0
      fi
    fi
    sleep 3
  done
  log "${IDLE} did not become healthy in ${HEALTH_TIMEOUT}s, last 40 api log lines written to ${LOG_FILE}"
  docker logs --tail 40 "$container" >> "$LOG_FILE" 2>&1 || true
  fail "idle stack ${IDLE} unhealthy, production on ${ACTIVE} untouched"
}

switch_edge() {
  local from=${PORT[$ACTIVE]} to=${PORT[$IDLE]} vp
  backup_edge
  log "edge backup: ${BK_VHOST}"
  STAGE="switched"
  rewrite_port "$VHOST" "$from" "$to"
  rewrite_port "$ROUTE" "$from" "$to"
  vp="$(vhost_port)" || fail "vhost inconsistent after rewrite"
  [[ "$vp" == "$to" ]] || fail "vhost still points to ${vp} after rewrite"
  log "vhost now points to ${to}, testing and reloading edge"
  edge_reload || fail "edge nginx -t / reload failed"
}

public_check() {
  local deadline=$((SECONDS + PUBLIC_TIMEOUT)) api="" root="" probe seen="" probe_seen="no"
  # Unique id in the request URI: it must show up in the new ingress access log,
  # which proves the request reached the new color and not the old one.
  probe="deploy-probe-$(date +%s)-$RANDOM"
  while (( SECONDS < deadline )); do
    api="$(http_code --resolve "${DOMAIN}:443:127.0.0.1" "https://${DOMAIN}/api/content/feature-flags?probe=${probe}")"
    root="$(http_code --resolve "${DOMAIN}:443:127.0.0.1" "https://${DOMAIN}/")"
    if [[ "$api" == "200" ]] && root_ok "$root"; then
      # Capture first: grep -q closing the pipe early can make docker logs fail under pipefail.
      seen="$(docker logs --since 2m "opnform-${IDLE}-ingress-1" 2>&1 || true)"
      if [[ "$seen" == *"$probe"* ]]; then
        probe_seen="yes"
        log "public check ok through ${DOMAIN} (api ${api}, root ${root}, probe seen on ${IDLE} ingress)"
        return 0
      fi
      probe_seen="no"
    fi
    sleep 2
  done
  fail "public check failed through ${DOMAIN} (api ${api:-?}, root ${root:-?}, probe seen on new ingress ${IDLE}: ${probe_seen})"
}

commit_state() {
  local repo=$1 tag=$2 sha=$3 old_repo old_tag old_sha prev_repo prev_tag prev_sha
  old_repo="$(envget OPNFORM_IMAGE_REPO)"
  old_tag="$(envget OPNFORM_TAG)"
  old_sha="$(envget OPNFORM_SHA)"
  prev_repo="$old_repo"
  prev_tag="$old_tag"
  prev_sha="$old_sha"
  if [[ "$old_repo" == "$repo" && "$old_tag" == "$tag" ]]; then
    # Redeploy of the running version: keep the existing rollback target.
    prev_repo="$(envget PREV_IMAGE_REPO)"
    prev_tag="$(envget PREV_TAG)"
    prev_sha="$(envget PREV_SHA)"
    log "same version as current, keeping previous rollback target"
  fi
  write_env "$repo" "$tag" "$sha" "$IDLE" "$prev_repo" "$prev_tag" "$prev_sha"
  STAGE="committed"
  PREV_SUMMARY="${prev_repo}:${prev_tag}"
  log "state committed: ${IDLE} active with ${repo}:${tag}"
}

restart_workers() {
  log "restarting worker and scheduler on the new image"
  if ! logrun main_compose up -d --no-build --no-deps worker scheduler; then
    log "warning: worker/scheduler failed to restart, web is already live"
    WORKER_FAILED=1
  fi
}

drain_old() {
  log "draining ${ACTIVE} for ${DRAIN_SECONDS}s"
  sleep "$DRAIN_SECONDS"
  down_color "$ACTIVE"
  # Removes legacy api/client/ingress containers left in the main project after the migration.
  logrun main_compose up -d --no-build --no-deps --remove-orphans worker scheduler \
    || log "warning: orphan cleanup in main project reported errors"
}

prune_images() {
  local name tag keep recent
  read_env
  keep=" $(envget OPNFORM_TAG) $(envget PREV_TAG) "
  for name in "${IMAGES[@]}"; do
    # docker lists newest first; the 3 newest tags are kept.
    recent="$(docker image ls "${REPO_IMG}/${name}" --format '{{.Tag}}' 2>/dev/null | grep -v '^<none>$' | head -n 3 | tr '\n' ' ' || true)"
    while IFS= read -r tag; do
      [[ -n "$tag" && "$tag" != "<none>" ]] || continue
      if [[ "$keep" == *" ${tag} "* || " ${recent}" == *" ${tag} "* ]]; then
        continue
      fi
      docker image rm "${REPO_IMG}/${name}:${tag}" >/dev/null 2>&1 || true
    done < <(docker image ls "${REPO_IMG}/${name}" --format '{{.Tag}}' 2>/dev/null || true)
  done
  log "image prune done"
}

# Steps 3-9 shared by deploy and rollback.
switch_to() {
  local repo=$1 tag=$2 sha=$3
  sync_source "$sha"
  resolve_active
  start_idle "$repo" "$tag"
  wait_idle_healthy
  switch_edge
  public_check
  commit_state "$repo" "$tag" "$sha"
  restart_workers
  drain_old
  prune_images
}

do_deploy() {
  local sha=$1 tag=${1:0:8}
  log "=== deploy ${sha} (tag ${tag}) ==="
  pull_images "$tag"
  switch_to "$REPO_IMG" "$tag" "$sha"
  log "DEPLOYED ${tag} on ${IDLE} (previous ${PREV_SUMMARY})"
  [[ "$WORKER_FAILED" -eq 0 ]] || exit 3
}

do_rollback() {
  local repo tag sha
  read_env
  repo="$(envget PREV_IMAGE_REPO)"
  tag="$(envget PREV_TAG)"
  sha="$(envget PREV_SHA)"
  [[ -n "$repo" && -n "$tag" ]] || fail "no previous version recorded in ${ENV_FILE}"
  images_present "$repo" "$tag" || fail "images ${repo}:${tag} are missing locally, cannot roll back"
  log "=== rollback to ${repo}:${tag} ==="
  switch_to "$repo" "$tag" "$sha"
  log "DEPLOYED ${tag} on ${IDLE} (previous ${PREV_SUMMARY})"
  [[ "$WORKER_FAILED" -eq 0 ]] || exit 3
}

do_status() {
  echo "--- ${ENV_FILE}"
  cat "$ENV_FILE" 2>/dev/null || echo "(missing)"
  echo "--- vhost port: $(vhost_port 2>/dev/null || echo 'unknown or mixed')"
  echo "--- containers"
  docker ps --filter name=opnform --format 'table {{.Names}}\t{{.Image}}\t{{.Status}}\t{{.Ports}}'
}

PREV_SUMMARY=""

main() {
  local cmd="" arg=""
  if [[ -n "${SSH_ORIGINAL_COMMAND+x}" ]]; then
    local re='^deploy ([0-9a-f]{40})$'
    if [[ "$SSH_ORIGINAL_COMMAND" =~ $re ]]; then
      cmd="deploy"
      arg="${BASH_REMATCH[1]}"
    else
      echo "refused: only 'deploy <40-char commit sha>' is allowed" >&2
      exit 2
    fi
  else
    cmd="${1:-}"
    arg="${2:-}"
  fi

  case "$cmd" in
    status)
      do_status
      return 0
      ;;
    deploy)
      [[ "$arg" =~ ^[0-9a-f]{40}$ ]] || { echo "usage: deploy.sh deploy <sha40> | rollback | status" >&2; exit 2; }
      ;;
    rollback) ;;
    *)
      echo "usage: deploy.sh deploy <sha40> | rollback | status" >&2
      exit 2
      ;;
  esac

  # One line "<user> <token>" on stdin, only when it is not a terminal.
  if [[ "$cmd" == "deploy" && ! -t 0 ]]; then
    IFS=' ' read -r -t 30 GH_USER GH_TOKEN || true
    if [[ -n "$GH_USER$GH_TOKEN" ]]; then
      local user_re='^[][A-Za-z0-9._-]+$' token_re='^[A-Za-z0-9_]+$'
      [[ "$GH_USER" =~ $user_re && "$GH_TOKEN" =~ $token_re ]] \
        || { echo "refused: malformed credentials line" >&2; exit 2; }
    fi
  fi

  touch "$LOG_FILE" 2>/dev/null || true
  exec 9> "$LOCK_FILE"
  if ! flock -w 1200 9; then
    fail "another deploy held the lock for more than 20 minutes"
  fi

  if [[ "$cmd" == "deploy" ]]; then
    do_deploy "$arg"
  else
    do_rollback
  fi
}

main "$@"
