import { computed, toValue } from "vue"
import blocksTypes from "~/data/blocks_types.json"
import {
  SCORE_MAX,
  SCORE_MODE_ANSWERED,
  fromTenths,
  getWeight,
  toTenths,
} from "~/lib/forms/scoring"

/**
 * Tracks the 10-point weight budget shared by every scorable block of a form.
 *
 * @param {Object|Function} form Ref, getter or plain form object.
 */
export function useFormScoreBudget(form) {
  const properties = computed(() => toValue(form)?.properties ?? [])

  const scorableFields = computed(() =>
    properties.value.filter(
      (field) => blocksTypes[field?.type]?.is_input === true,
    ),
  )

  const totalTenths = computed(() =>
    scorableFields.value.reduce(
      (sum, field) => sum + toTenths(getWeight(field)),
      0,
    ),
  )

  const totalDistributed = computed(() => fromTenths(totalTenths.value))
  const remaining = computed(() =>
    fromTenths(toTenths(SCORE_MAX) - totalTenths.value),
  )
  const isOverBudget = computed(() => totalTenths.value > toTenths(SCORE_MAX))

  /**
   * The most a single block may take: the budget minus everyone else's weight.
   */
  const maxWeightFor = (fieldId) => {
    const own = toTenths(
      getWeight(scorableFields.value.find((field) => field?.id === fieldId)),
    )

    return fromTenths(
      Math.max(0, toTenths(SCORE_MAX) - totalTenths.value + own),
    )
  }

  return {
    scorableFields,
    totalDistributed,
    remaining,
    isOverBudget,
    maxWeightFor,
  }
}

/**
 * Makes sure a field carries a writable scoring object.
 *
 * Mirrors ensureSettingsObject: form data coming from the query cache can be a
 * readonly proxy. Never call this while loading a form -- seeding on load would
 * mark every form dirty as soon as it opens.
 */
export function ensureScoringObject(field) {
  if (!field) return

  const scoring = field.scoring
  if (!scoring || typeof scoring !== "object" || Array.isArray(scoring)) {
    field.scoring = { weight: 0, mode: SCORE_MODE_ANSWERED }
  } else if (Object.isFrozen(scoring) || !Object.isExtensible(scoring)) {
    field.scoring = { weight: 0, mode: SCORE_MODE_ANSWERED, ...scoring }
  }
}
