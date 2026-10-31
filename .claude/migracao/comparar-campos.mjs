import fs from 'node:fs'
import { isDeepStrictEqual as eq } from 'node:util'
const [a, b] = process.argv.slice(2).map(f => JSON.parse(fs.readFileSync(f, 'utf8')))
let problems = 0
const say = (...m) => { problems++; console.log('  ✗', ...m) }

console.log('1. Definições')
for (const [key, fa] of Object.entries(a.fields)) {
  const fb = b.fields[key]
  if (!fb) { say(`campo ${key} (${fa.name}) não existe no tema novo`); continue }
  for (const [prop, va] of Object.entries(fa)) {
    if (prop === 'conditional_logic') {
      // o Fields::section acrescenta "seção visível" em AND: cada grupo original tem que continuar lá
      const groupsA = va || [], groupsB = fb[prop] || []
      const ok = groupsA.every((ga, i) => groupsB[i] && ga.every(r => groupsB[i].some(rb => eq(r, rb))))
      if (!ok) say(`${key}: conditional_logic original não preservado`)
      continue
    }
    if (!eq(va, fb[prop])) say(`${key}.${prop}: ${JSON.stringify(va)} → ${JSON.stringify(fb[prop])}`)
  }
}
const extra = Object.keys(b.fields).filter(k => !a.fields[k])
console.log(`  ${Object.keys(a.fields).length} campos do tema React conferidos; ${extra.length} campos novos no v3:`, [...new Set(extra.map(k => b.fields[k].name.replace(/^.*?_ocultar$/, '{seção}_ocultar')))].join(', '))
const extraNotToggle = extra.filter(k => !b.fields[k].name.endsWith('_ocultar'))
if (extraNotToggle.length) say('campos novos que não são toggle:', extraNotToggle.join(', '))

console.log('2. Referências no banco')
if (!eq(a.refs.faltando.sort(), b.refs.faltando.sort())) say('órfãs diferentes entre os temas')
const names = new Set(Object.values(a.fields).filter(f => !f.parent).map(f => f.name))
const orphanCurrent = a.refs.faltando.filter(r => names.has(r.split(' ')[1].slice(1)))
console.log(`  ok=${b.refs.ok} (React: ${a.refs.ok}); órfãs=${b.refs.faltando.length}, iguais nos dois temas; órfãs com name de campo atual: ${orphanCurrent.length}`)
orphanCurrent.slice(0, 5).forEach(r => console.log('   ', r))
const orphanKeys = [...new Set(a.refs.faltando.map(r => r.split(' -> ')[1]))]
console.log(`  keys órfãs distintas: ${orphanKeys.length} (ex.: ${orphanKeys.slice(0, 4).join(', ')})`)

console.log('3. Valores por página')
let fieldsCompared = 0
for (const [page, va] of Object.entries(a.values)) {
  const vb = b.values[page] ?? {}
  for (const [name, v] of Object.entries(va)) {
    fieldsCompared++
    if (!(name in vb)) { say(`página ${page}: ${name} não lido no tema novo`); continue }
    if (!eq(v, vb[name])) say(`página ${page}: ${name} diferente`, JSON.stringify(v).slice(0, 120), '≠', JSON.stringify(vb[name]).slice(0, 120))
  }
  const novos = Object.keys(vb).filter(n => !(n in va))
  if (novos.some(n => !n.endsWith('_ocultar'))) say(`página ${page}: campos novos lidos`, novos.join(','))
}
console.log(`  ${fieldsCompared} valores comparados em ${Object.keys(a.values).length} páginas`)
console.log(problems ? `\n${problems} problema(s)` : '\nOK: nada mudou')
