import { FormSubmissionFormatter } from '~/components/forms/components/FormSubmissionFormatter'
import {
  SCORE_MENTION_ID,
  SCORE_TIER_MENTION_ID,
  formatScore,
  getScoreTiers,
  resolveScoreTier,
} from '~/lib/forms/scoring'

export function useParseMention(content, mentionsAllowed, form, formData) {
  if (!mentionsAllowed || !form || !formData) {
    return content
  }

  const formatter = new FormSubmissionFormatter(form, formData).setOutputStringsOnly()
  const formattedData = formatter.getFormattedData()

  // The score is computed server side and travels back on the submit response.
  // formatScore returns a string on purpose: the `if (value)` check below would
  // drop the mention for a numeric score of 0.
  if (formData.score !== undefined && formData.score !== null) {
    formattedData[SCORE_MENTION_ID] = formatScore(formData.score)
    // The tier resolved by the API wins: respondents never receive the form's
    // tier configuration, so resolving locally would fall back to the defaults
    // and show the wrong label on any form with custom tiers.
    formattedData[SCORE_TIER_MENTION_ID] =
      formData.score_tier ??
      resolveScoreTier(formData.score, getScoreTiers(form))?.label ??
      ''
  }

  // Create a new DOMParser
  const parser = new DOMParser()
  // Parse the content as HTML
  const doc = parser.parseFromString(content, 'text/html')

  // Find all elements with mention attribute
  const mentionElements = doc.querySelectorAll('[mention], [mention=""]')

  mentionElements.forEach(element => {
    const fieldId = element.getAttribute('mention-field-id')
    const fallback = element.getAttribute('mention-fallback')
    const value = formattedData[fieldId]

    if (value) {
      if (Array.isArray(value)) {
        element.textContent = value.join(', ')
      } else {
        element.textContent = value
      }
    } else if (fallback) {
      element.textContent = fallback
    } else {
      element.remove()
    }
  })

  // Return the processed HTML content
  return doc.body.innerHTML
}
