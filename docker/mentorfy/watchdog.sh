#!/bin/sh
# Docker never restarts a container just for being unhealthy, so this cron
# does it. The restart goes through self-heal/start.sh, which clears the cache.
# Watches the api of the active blue/green color (OPNFORM_ACTIVE in .env).
ENV_FILE=/opt/opnform/.env
active=""
if [ -r "$ENV_FILE" ]; then
  active=$(grep -E '^OPNFORM_ACTIVE=(blue|green)$' "$ENV_FILE" | tail -n 1 | cut -d= -f2)
fi
if [ -n "$active" ]; then
  container="opnform-${active}-app-api-1"
else
  container="opnform-api-1"
fi
status=$(docker inspect -f "{{.State.Health.Status}}" "$container" 2>/dev/null)
if [ "$status" = "unhealthy" ]; then
  echo "$(date -Is) $container unhealthy, restarting" >> /var/log/opnform-watchdog.log
  docker restart "$container" >/dev/null
fi

# Keep the edge vhost aligned with the active color (covers an interrupted deploy
# or anything that rewrote the vhost). Skipped entirely while a deploy holds the lock.
if [ -n "$active" ]; then
  flock -n /run/opnform-deploy.lock true || exit 0
  VHOST=/var/lib/openship/edge/sites-enabled/forms-mentorfy-io.conf
  ROUTE=/var/lib/openship/edge/sites-enabled/forms-mentorfy-io.route.json
  WATCH_LOG=/var/log/opnform-watchdog.log
  if [ "$active" = "blue" ]; then exp=20010; else exp=20020; fi
  cur=$(grep -oE 'proxy_pass http://127\.0\.0\.1:(20010|20020)\b' "$VHOST" 2>/dev/null | sed 's/.*://' | sort -u)
  if [ "$(printf '%s\n' "$cur" | grep -c .)" -eq 1 ] && [ "$cur" != "$exp" ]; then
    running=$(docker inspect -f '{{.State.Running}}' "opnform-${active}-ingress-1" 2>/dev/null)
    if [ "$running" = "true" ]; then
      cp -p "$VHOST" /tmp/opnform-watchdog-vhost.bak
      cp -p "$ROUTE" /tmp/opnform-watchdog-route.bak
      tmp=$(mktemp)
      for f in "$VHOST" "$ROUTE"; do
        sed "s/127\.0\.0\.1:${cur}\b/127.0.0.1:${exp}/g" "$f" > "$tmp" && cat "$tmp" > "$f"
      done
      rm -f "$tmp"
      if docker exec openship-edge nginx -t >/dev/null 2>&1 && docker exec openship-edge nginx -s reload >/dev/null 2>&1; then
        echo "$(date -Is) vhost pointed to ${cur} but active=${active} (${exp}), realigned" >> "$WATCH_LOG"
      else
        cat /tmp/opnform-watchdog-vhost.bak > "$VHOST"
        cat /tmp/opnform-watchdog-route.bak > "$ROUTE"
        echo "$(date -Is) vhost realign to ${exp} failed (nginx -t or reload), vhost and route restored from backups" >> "$WATCH_LOG"
      fi
    fi
  fi
fi
