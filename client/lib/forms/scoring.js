/**
 * Shared score-calculation primitives.
 *
 * Kept free of Vue imports so table cell renderers, the mention parser and the
 * editor composables can all reuse it.
 */

export const SCORE_MAX = 10
export const SCORE_STEP = 0.1

export const SCORE_MODE_ANSWERED = "answered"
export const SCORE_MODE_OPTIONS = "options"
export const SCORE_MODE_PROPORTIONAL = "proportional"
export const SCORE_MODE_LOGIC = "logic"

export const SCORE_MODES = [
  SCORE_MODE_ANSWERED,
  SCORE_MODE_OPTIONS,
  SCORE_MODE_PROPORTIONAL,
  SCORE_MODE_LOGIC,
]

/**
 * `radio` and `toggle_switch` are stored as their `actual_input`, so only
 * `select` and `checkbox` ever reach these lists.
 */
export const OPTION_MODE_TYPES = ["select", "multi_select", "checkbox"]
export const PROPORTIONAL_MODE_TYPES = ["number", "rating", "scale", "slider"]

export const AWARD_SCORE_ACTION = "award-score"
export const ZERO_SCORE_ACTION = "zero-score"
export const SCORING_ACTIONS = [AWARD_SCORE_ACTION, ZERO_SCORE_ACTION]

/**
 * Deliberately the same ids the API uses for its score pseudo fields
 * (FormScoreCalculator::SCORE_FIELD_ID). Property ids are always UUIDs, so
 * there is no collision, and a score mention resolves identically whether it
 * is parsed here or by the PHP MentionParser (redirect URLs, emails, Slack).
 */
export const SCORE_MENTION_ID = "form_score"
export const SCORE_TIER_MENTION_ID = "form_score_tier"

/**
 * Read fallback, mirroring ScoreTierResolver::DEFAULT_TIERS on the API side.
 * The settings page seeds translated labels the first time it is opened.
 */
export const DEFAULT_SCORE_TIERS = [
  { label: "Frio", from: 0, color: "#3b82f6" },
  { label: "Morno", from: 5, color: "#f59e0b" },
  { label: "Quente", from: 8, color: "#ef4444" },
]

/**
 * Synthetic mention entries for the thank-you text and the redirect URL.
 *
 * They declare a real block type so MentionDropdown's `is_input` filter lets
 * them through untouched.
 */
export const buildScoreMentions = (form, t) =>
  isScoringEnabled(form) && formHasScoreWeights(form)
    ? [
        {
          id: SCORE_MENTION_ID,
          name: t("form_blocks.scoring.mention_score"),
          type: "number",
        },
        {
          id: SCORE_TIER_MENTION_ID,
          name: t("form_blocks.scoring.mention_score_tier"),
          type: "text",
        },
      ]
    : []

export const SCORE_TIER_COLORS = [
  "#3b82f6",
  "#f59e0b",
  "#ef4444",
  "#8b5cf6",
  "#10b981",
  "#ec4899",
]

/**
 * Weights use a 0.1 step, so every sum and comparison runs in whole tenths.
 * In plain floats, eight weights of 0.1 add up to 0.7999999999999999, which
 * renders a remaining budget of 9.200000000000001 and trips the over-budget
 * check on a form that is exactly full.
 */
export const toTenths = (value) => Math.round((Number(value) || 0) * 10)

export const fromTenths = (tenths) => Math.round(tenths) / 10

export const clampWeight = (value, max = SCORE_MAX) =>
  fromTenths(Math.min(Math.max(toTenths(value), 0), toTenths(max)))

export const getWeight = (field) => Number(field?.scoring?.weight) || 0

export const getScoreMode = (field) =>
  field?.scoring?.mode || SCORE_MODE_ANSWERED

export const isScoringEnabled = (form) =>
  (form?.settings?.scoring_enabled ?? true) !== false

/**
 * The setting defaults to true, so every score surface also has to check that
 * a block is actually weighted -- otherwise legacy forms grow empty columns,
 * metrics and mentions.
 */
export const formHasScoreWeights = (form) =>
  (form?.properties ?? []).some(
    (property) => Number(property?.scoring?.weight) > 0,
  )

/**
 * The form's tiers, sorted ascending by threshold.
 */
export const getScoreTiers = (form) => {
  const configured = form?.settings?.score_tiers
  const valid = Array.isArray(configured)
    ? configured.filter((tier) => tier && Number.isFinite(Number(tier.from)))
    : []

  return (valid.length ? valid : DEFAULT_SCORE_TIERS)
    .map((tier) => ({
      label: String(tier.label ?? ""),
      from: Number(tier.from),
      color: tier.color ?? null,
    }))
    .sort((a, b) => a.from - b.from)
}

export const resolveScoreTier = (score, tiers) => {
  const value = Number(score)
  if (score === null || score === undefined || !Number.isFinite(value)) {
    return null
  }

  const list =
    Array.isArray(tiers) && tiers.length
      ? [...tiers].sort((a, b) => Number(a.from) - Number(b.from))
      : DEFAULT_SCORE_TIERS

  let match = null
  list.forEach((tier) => {
    if (value >= Number(tier.from)) {
      match = tier
    }
  })

  return match
}

/**
 * Always returns a string. useParseMention drops falsy values, so a numeric 0
 * would delete the mention for exactly the lowest-scoring submissions.
 */
export const formatScore = (score) => {
  const value = Number(score)
  return Number.isFinite(value) ? value.toFixed(1) : ""
}
