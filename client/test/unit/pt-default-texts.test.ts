import { describe, expect, it } from 'vitest'
import { baseCompile } from '@intlify/message-compiler'
import blocksTypes from '../../data/blocks_types.json'
import ptCommon from '../../i18n/lang/pt/common.json'
import enCommon from '../../i18n/lang/en/common.json'

type BlockDef = { default_block_name?: string, default_values?: { placeholder?: string } }
type Defaults = Record<string, { name?: string, placeholder?: string }>

const types = blocksTypes as Record<string, BlockDef>
const ptDefaults = (ptCommon as any).common.block_defaults as Defaults
const enDefaults = (enCommon as any).common.block_defaults as Defaults

// vue-i18n reads a bare "@" as a linked message, so e-mail placeholders keep it as the literal {'@'}.
const rendered = (msg?: string) => msg?.replaceAll("{'@'}", '@')

// Every string in a locale file must compile, or the Nuxt build fails (unplugin-vue-i18n).
function compileErrors (node: unknown, path = ''): string[] {
  if (typeof node === 'string') {
    const errors: string[] = []
    baseCompile(node, { onError: (e) => { errors.push(`${path}: ${e.message}`) } })
    return errors
  }
  if (node && typeof node === 'object') {
    return Object.entries(node).flatMap(([k, v]) => compileErrors(v, path ? `${path}.${k}` : k))
  }
  return []
}

// Ticket 7f5e6961: new blocks were born as "Your name" / "Type your answer" in a pt account.
describe('default texts of new blocks', () => {
  it('every block type has a pt name and, when it has one, a pt placeholder', () => {
    for (const [type, def] of Object.entries(types)) {
      expect(ptDefaults[type]?.name, type).toBeTruthy()
      if (def.default_values?.placeholder) {
        expect(ptDefaults[type]?.placeholder, type).toBeTruthy()
      }
    }
    expect(ptDefaults.text).toEqual({ name: 'Seu nome', placeholder: 'Digite sua resposta' })
  })

  it('en defaults match blocks_types.json, so English stays as it was', () => {
    for (const [type, def] of Object.entries(types)) {
      expect(enDefaults[type]?.name, type).toBe(def.default_block_name)
      expect(rendered(enDefaults[type]?.placeholder), type).toBe(def.default_values?.placeholder)
    }
  })

  it('toast texts exist in pt and en', () => {
    const pt = (ptCommon as any).common.alerts
    const en = (enCommon as any).common.alerts
    expect(Object.keys(pt).sort()).toEqual(Object.keys(en).sort())
    expect(pt.open_form).toBe('Abrir formulário')
    expect(pt.form_created).toBe('Formulário criado com sucesso.')
    expect(pt.form_deleted).toBe('Formulário excluído.')
    expect(pt.form_duplicated).toMatch(/^Formulário duplicado/)
  })

  it('pt and en common.json compile with vue-i18n (no bare "@")', () => {
    expect(compileErrors(enCommon)).toEqual([])
    expect(compileErrors(ptCommon)).toEqual([])
  })
})
