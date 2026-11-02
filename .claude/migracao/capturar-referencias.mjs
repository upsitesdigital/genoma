#!/usr/bin/env node
/**
 * Captura o site no ar (tema React) como referência da reconstrução: um PNG por seção
 * (`main > section[id]`), mais header e footer, em desktop (1440) e mobile (390).
 *
 *   node .claude/migracao/capturar-referencias.mjs [url do site] [pasta de saída]
 *
 * Padrão: http://localhost/projetos/genoma/site → .fidelity/referencia/{slug}/{id}-{viewport}.png
 * O React é pedido pelo cookie fw_tema=upwork (o tema ativo pode já ser o v3); com o mu-plugin local, o cookie fw_tema
 * não é enviado. Movimento reduzido: o scroll reveal e o autoplay do React ficam desligados.
 * Medir uma seção do v3 contra a referência:
 *   FW_TEMA=upwork-migracao npm run fidelity -- home/hero --figma-png .fidelity/referencia/home/hero-desktop.png --page 7
 */
import { mkdirSync, writeFileSync } from 'node:fs'
import { join } from 'node:path'
import { chromium } from 'playwright'

const site = (process.argv[2] || 'http://localhost/projetos/genoma/site').replace(/\/$/, '')
const out = process.argv[3] || '.fidelity/referencia'
const PAGES = [
  ['home', '/'],
  ['o-genoma', '/o-genoma/'],
  ['veterinarios', '/veterinarios/'],
  ['responsavel', '/responsavel/'],
  ['contato', '/contato/'],
  ['blog', '/blog/'],
  ['resultado-pesquisa', '/resultado-da-busca/?q=exame'],
  ['pagina-padrao', '/politica-privacidade/'],
]
const VIEWPORTS = [['desktop', 1440, 900], ['mobile', 390, 844]]

const browser = await chromium.launch()
const index = []
for (const [label, width, height] of VIEWPORTS) {
  const ctx = await browser.newContext({ viewport: { width, height }, deviceScaleFactor: 1, reducedMotion: 'reduce' })
  await ctx.addCookies([{ name: 'fw_tema', value: 'upwork', domain: 'localhost', path: '/' }]) // React por cookie: o tema ativo pode ser o v3
  const page = await ctx.newPage()
  // post publicado mais recente, para o modelo de post
  const pages = [...PAGES]
  await page.goto(`${site}/wp-json/wp/v2/posts?per_page=1&_fields=link`)
  const post = JSON.parse(await page.innerText('body'))[0]?.link
  if (post) pages.push(['single-post', post.replace(site, '')])

  for (const [slug, path] of pages) {
    await page.goto(site + path, { waitUntil: 'networkidle' })
    await page.addStyleTag({ content: 'html{scroll-behavior:auto!important}' })
    await page.waitForSelector('main section[id], main article', { timeout: 20_000 }).catch(() => {})
    // rola até o fim e volta: carrega imagens lazy
    // rolagem instantânea: o React tem scroll-behavior: smooth, e a captura do header no meio dela sai cortada
    await page.evaluate(async () => {
      for (let y = 0; y < document.body.scrollHeight; y += 600) { scrollTo({ top: y, behavior: 'instant' }); await new Promise((r) => setTimeout(r, 80)) }
      scrollTo({ top: 0, behavior: 'instant' })
    })
    await page.waitForTimeout(300)
    await page.waitForLoadState('networkidle')
    await page.evaluate(() => document.fonts.ready)
    const dir = join(out, slug)
    mkdirSync(dir, { recursive: true })

    if (slug === 'home') {
      await page.locator('header').first().screenshot({ path: join(dir, `../header-${label}.png`) })
      index.push(`header-${label}.png`)
    }
    // header fixo não cobre o topo das seções nas capturas
    const hide = await page.addStyleTag({ content: 'header{visibility:hidden!important}' })
    const ids = await page.$$eval('main section[id]', (els) => els.map((e) => e.id))
    for (const id of [...new Set(ids)]) {
      const file = `${id}-${label}.png`
      await page.locator(`main section[id="${id}"]`).first().screenshot({ path: join(dir, file) })
      index.push(`${slug}/${file}`)
    }
    await page.locator('footer').first().screenshot({ path: join(dir, `footer-${label}.png`) })
    index.push(`${slug}/footer-${label}.png`)
    await hide.evaluate((el) => el.remove())
    console.log(`${label} ${slug}: ${ids.length} seções (${[...new Set(ids)].join(', ')})`)
  }
  await ctx.close()
}
await browser.close()
writeFileSync(join(out, 'index.txt'), index.join('\n') + '\n')
console.log(`${index.length} PNGs em ${out}`)
