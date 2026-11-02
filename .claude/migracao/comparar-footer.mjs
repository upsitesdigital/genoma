#!/usr/bin/env node
/**
 * Footer do site (com o banner de CTA): tema React (cookie fw_tema=upwork) × tema v3 (cookie fw_tema={tema-v3}),
 * desktop e mobile, em cada página — o banner muda por página (aba Rodapé + Opções do Tema).
 * O recorte vai do topo do banner (no desktop ele avança 100px acima do <footer>) até o fim do
 * <footer>, na largura da tela; o <main> não entra (pode estar vazio nas páginas ainda não migradas).
 * Salva os dois prints e o diff em .fidelity/footer/{slug}/ e imprime a % de pixels iguais
 * (na altura comum) e a diferença de altura.
 *
 *   node .claude/migracao/comparar-footer.mjs                 # as 7 páginas abaixo
 *   node .claude/migracao/comparar-footer.mjs contato /contato/ [tema-v3]
 *
 * No Git Bash, rode com MSYS_NO_PATHCONV=1 (senão o "/" vira caminho do Windows).
 */
import { mkdirSync, writeFileSync } from 'node:fs'
import { join } from 'node:path'
import { chromium } from 'playwright'
import { PNG } from 'pngjs'
import pixelmatch from 'pixelmatch'

const PAGES = [
  ['home', '/'],
  ['o-genoma', '/o-genoma/'],
  ['veterinarios', '/veterinarios/'],
  ['responsavel', '/responsavel/'],
  ['contato', '/contato/'],
  ['blog', '/blog/'],
  ['pagina-padrao', '/politica-privacidade/'],
]
const [slugArg, pathArg, tema = 'upwork-migracao'] = process.argv.slice(2)
const pages = slugArg ? [[slugArg, pathArg || '/']] : PAGES
const site = 'http://localhost/projetos/genoma/site'

const browser = await chromium.launch()
for (const [slug, path] of pages) {
  const out = join('.fidelity/footer', slug)
  mkdirSync(out, { recursive: true })

  for (const [label, width, height] of [['desktop', 1440, 900], ['mobile', 390, 844]]) {
    const shots = {}
    for (const [name, cookie] of [['react', 'upwork'], ['v3', tema]]) {
      const ctx = await browser.newContext({ viewport: { width, height }, deviceScaleFactor: 1, reducedMotion: 'reduce' })
      if (cookie) await ctx.addCookies([{ name: 'fw_tema', value: cookie, domain: 'localhost', path: '/' }])
      const page = await ctx.newPage()
      const errors = []
      page.on('pageerror', (e) => errors.push(e.message))
      page.on('console', (m) => { if (m.type() === 'error') errors.push(m.text()) })
      // o watcher do gulp reescreve o main.css a cada arquivo salvo: página que veio sem CSS
      // (footer sem o `relative` dos dois temas) é recarregada
      for (let i = 0; i < 5; i++) {
        await page.goto(site + path, { waitUntil: 'networkidle' })
        const styled = await page.evaluate(() => getComputedStyle([...document.querySelectorAll('footer')].pop()).position === 'relative')
        if (styled) break
        if (i === 4) throw new Error(`${name} ${label} ${path}: CSS não carregou`)
        await page.waitForTimeout(1500)
      }
      await page.addStyleTag({ content: 'html{scroll-behavior:auto!important}' })
      // rola até o fim e volta: carrega as imagens lazy (o footer fica no fim da página)
      await page.evaluate(async () => {
        for (let y = 0; y < document.body.scrollHeight; y += 600) { scrollTo({ top: y, behavior: 'instant' }); await new Promise((r) => setTimeout(r, 80)) }
        scrollTo({ top: document.body.scrollHeight, behavior: 'instant' })
        await new Promise((r) => setTimeout(r, 200))
        scrollTo({ top: 0, behavior: 'instant' })
      })
      await page.waitForLoadState('networkidle')
      await page.evaluate(() => document.fonts.ready)
      await page.waitForTimeout(400)
      await page.addStyleTag({ content: 'header,#fw-header{visibility:hidden!important}' })
      // footer no pixel inteiro: a altura do <main> (fracionária e diferente entre os temas nas páginas
      // ainda não migradas) desloca o footer em subpixel e muda o arredondamento do texto e das bordas.
      // Um espaçador antes do item flex do footer (React: o <footer>; v3: o #fw-footer) completa o pixel.
      await page.evaluate(() => {
        const footer = [...document.querySelectorAll('footer')].pop() // o do site é o último (pode haver <footer> dentro do <main>)
        const top = footer.getBoundingClientRect().top + scrollY
        const frac = top - Math.floor(top)
        if (frac < 0.001 || frac > 0.999) return
        let item = footer
        while (item.parentElement && getComputedStyle(item.parentElement).display !== 'flex') item = item.parentElement
        const spacer = document.createElement('div')
        spacer.style.cssText = `height:${1 - frac}px;flex:none`
        item.before(spacer)
      })
      // topo = o elemento visível mais alto do footer (o banner sobe 100px no desktop); fim = o do <footer>
      const box = await page.evaluate(() => {
        const footer = [...document.querySelectorAll('footer')].pop() // o do site é o último (pode haver <footer> dentro do <main>)
        const f = footer.getBoundingClientRect()
        let top = f.top
        for (const el of footer.querySelectorAll('*')) {
          const r = el.getBoundingClientRect()
          if (r.width > 0 && r.height > 0) top = Math.min(top, r.top)
        }
        return { x: 0, y: Math.round(top + scrollY), width: innerWidth, height: Math.round(f.bottom - top) }
      })
      const buf = await page.screenshot({ fullPage: true, clip: box })
      writeFileSync(join(out, `${name}-${label}.png`), buf)
      shots[name] = PNG.sync.read(buf)
      if (errors.length) console.log(`  ${name} ${label}: ${errors.length} erro(s) no console — ${errors.slice(0, 3).join(' | ')}`)
      await ctx.close()
    }
    const { react: a, v3: b } = shots
    const h = Math.min(a.height, b.height)
    const crop = (img) => { const c = new PNG({ width, height: h }); PNG.bitblt(img, c, 0, 0, width, h, 0, 0); return c }
    const ca = crop(a), cb = crop(b), diff = new PNG({ width, height: h })
    const bad = pixelmatch(ca.data, cb.data, diff.data, width, h, { threshold: 0.1 })
    writeFileSync(join(out, `diff-${label}.png`), PNG.sync.write(diff))
    console.log(`footer ${slug} ${label}: ${(100 - (bad / (width * h)) * 100).toFixed(2)}% iguais | altura React ${a.height}px × v3 ${b.height}px`)
  }
}
await browser.close()
