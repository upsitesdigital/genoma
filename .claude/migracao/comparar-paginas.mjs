#!/usr/bin/env node
/**
 * Conteúdo da página (<main>, sem header/footer): tema React (padrão do WP) × tema v3 (cookie fw_tema),
 * desktop e mobile.
 * Salva os dois prints e o diff em .fidelity/paginas/{slug}/ e imprime a % de pixels iguais
 * (na altura comum) e a diferença de altura.
 *
 *   node .claude/migracao/comparar-paginas.mjs home / [tema-v3]
 */
import { mkdirSync, writeFileSync } from 'node:fs'
import { join } from 'node:path'
import { chromium } from 'playwright'
import { PNG } from 'pngjs'
import pixelmatch from 'pixelmatch'

const [slug = 'home', path = '/', tema = 'upwork-migracao'] = process.argv.slice(2)
const site = 'http://localhost/projetos/genoma/site'
const out = join('.fidelity/paginas', slug)
mkdirSync(out, { recursive: true })

const browser = await chromium.launch()
for (const [label, width, height] of [['desktop', 1440, 900], ['mobile', 390, 844]]) {
  const shots = {}
  for (const [name, cookie] of [['react', null], ['v3', tema]]) {
    const ctx = await browser.newContext({ viewport: { width, height }, deviceScaleFactor: 1, reducedMotion: 'reduce' })
    if (cookie) await ctx.addCookies([{ name: 'fw_tema', value: cookie, domain: 'localhost', path: '/' }])
    const page = await ctx.newPage()
    await page.goto(site + path, { waitUntil: 'networkidle' })
    await page.addStyleTag({ content: 'html{scroll-behavior:auto!important}' })
    await page.evaluate(async () => {
      for (let y = 0; y < document.body.scrollHeight; y += 600) { scrollTo({ top: y, behavior: 'instant' }); await new Promise((r) => setTimeout(r, 80)) }
      scrollTo({ top: 0, behavior: 'instant' })
    })
    await page.waitForLoadState('networkidle')
    await page.evaluate(() => document.fonts.ready)
    await page.waitForTimeout(400)
    await page.addStyleTag({ content: 'header,#fw-header{visibility:hidden!important}' })
    const box = await page.evaluate(() => { const r = document.querySelector('main').getBoundingClientRect(); return { x: 0, y: Math.round(r.top + scrollY), width: innerWidth, height: Math.round(r.height) } })
    const buf = await page.screenshot({ fullPage: true, clip: box })
    writeFileSync(join(out, `${name}-${label}.png`), buf)
    shots[name] = PNG.sync.read(buf)
    await ctx.close()
  }
  const { react: a, v3: b } = shots
  const h = Math.min(a.height, b.height)
  const crop = (img) => { const c = new PNG({ width, height: h }); PNG.bitblt(img, c, 0, 0, width, h, 0, 0); return c }
  const ca = crop(a), cb = crop(b), diff = new PNG({ width, height: h })
  const bad = pixelmatch(ca.data, cb.data, diff.data, width, h, { threshold: 0.1 })
  writeFileSync(join(out, `diff-${label}.png`), PNG.sync.write(diff))
  console.log(`${slug} ${label}: ${(100 - (bad / (width * h)) * 100).toFixed(2)}% iguais | altura React ${a.height}px × v3 ${b.height}px`)
}
await browser.close()
