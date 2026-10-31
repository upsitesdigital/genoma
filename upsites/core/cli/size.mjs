#!/usr/bin/env node
/**
 * JS (gzip) que cada página baixa — rode depois do `npm run build`.
 *
 *   npm run size
 *
 * O HTML vem pronto do servidor; JS só existe onde há interatividade:
 *  - "comum" = runtime da Interactivity API (do próprio WordPress, ~10 KB gz — não medido aqui),
 *    store do header e o JS manual (assets/js/mainJS.min.js);
 *  - cada módulo = comum + stores ({nome}.store.js) das seções do módulo.
 * Store acima de ~10 KB gz merece olhar (lógica que poderia ir para o PHP).
 */
import { existsSync, readdirSync, readFileSync, statSync } from 'node:fs'
import { join, relative } from 'node:path'
import { gzipSync } from 'node:zlib'
import { root } from './make-section.mjs'

const gz = (file) => gzipSync(readFileSync(file)).length
const kb = (bytes) => `${(bytes / 1024).toFixed(1)} KB`
const rel = (p) => relative(root, p).replace(/\\/g, '/')
const stores = (dir) => {
  const out = []
  if (!existsSync(dir)) return out
  for (const name of readdirSync(dir)) {
    const p = join(dir, name)
    if (statSync(p).isDirectory()) out.push(...stores(p))
    else if (name.endsWith('.store.js')) out.push(p)
  }
  return out
}

const parts = join(root, 'template-parts', 'includes')
const common = [...stores(join(parts, 'layout')), join(root, 'assets', 'js', 'mainJS.min.js')].filter(existsSync)
const commonGz = common.reduce((sum, f) => sum + gz(f), 0)
console.log(`comum (toda página): ${kb(commonGz)} gz — ${common.map(rel).join(', ') || 'nenhum'}`)

const shared = stores(join(parts, 'shared'))
if (shared.length) console.log(`shared (só onde usado): ${shared.map((f) => `${rel(f)} ${kb(gz(f))}`).join(', ')}`)

for (const slug of readdirSync(join(root, 'template-pages')).filter((d) => statSync(join(root, 'template-pages', d)).isDirectory()).sort()) {
  const own = stores(join(parts, slug))
  const ownGz = own.reduce((sum, f) => sum + gz(f), 0)
  const flag = own.some((f) => gz(f) > 10 * 1024) ? '  ← store acima de 10 KB' : ''
  console.log(`  ${slug.padEnd(20)} ${kb(commonGz + ownGz).padStart(9)} (stores ${kb(ownGz)})${flag}`)
}
