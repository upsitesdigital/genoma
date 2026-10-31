#!/usr/bin/env node
/**
 * Mede a fidelidade de uma seção ao frame do Figma (comparação pixel a pixel).
 *
 *   npm run fidelity -- home/hero --figma "https://www.figma.com/design/KEY/Nome?node-id=12-34"
 *   npm run fidelity -- home/hero --figma-png caminho/frame.png       (PNG já exportado, 1x)
 *
 * Opções:
 *   --label desktop|mobile   viewport medido (padrão: desktop) — cada um tem seu contador
 *   --target 99              % mínimo para OK (padrão 99)
 *   --max 5                  tentativas máximas (padrão 5)
 *   --reset                  zera o contador de tentativas desta seção/viewport
 *   --bg #ffffff             cor sobre a qual o PNG do Figma é achatado (padrão: branco)
 *   --click "<seletor CSS>"  clica no elemento antes de capturar (estado aberto: menu do header,
 *                            mega menu, modal) e captura a TELA no tamanho do frame — o que abre
 *                            costuma sair da caixa da parte (painel fixo, dropdown). Use um label
 *                            próprio: --layout header --label menu --click "[aria-controls=site-sidebar]"
 *   --capture element|viewport  o que fotografar (padrão: element; com --click, viewport)
 *   também aceita --shared cta / --layout header no lugar de slug/section
 *   --url http://localhost/meu-site   URL do WP local (padrão: FW_SITE_URL ou o `link admin` do .claude/prompt.yaml)
 *
 * O render vem do WP local: {site}/?fw_preview={grupo}/{nome} (Core\Framework\Preview, só em localhost),
 * com os dados padrão do controller do módulo (conteúdo do Figma).
 *
 * Token da API do Figma: variável de ambiente FIGMA_API_KEY (ou FIGMA_TOKEN) — nunca grave o token no .mcp.json versionado.
 * Saída em .fidelity/{alvo}/{label}/: figma.png, render.png, diff.png (vermelho = diferença), result.json
 * Última linha: FIDELITY {score}% | tentativa {n}/{max} | {OK|ABAIXO|LIMITE} — exit 0 só com OK.
 */
import { execSync } from 'node:child_process'
import { existsSync, mkdirSync, readFileSync, rmSync, writeFileSync } from 'node:fs'
import { join } from 'node:path'
import pixelmatch from 'pixelmatch'
import { PNG } from 'pngjs'
import { frameOffset } from './fidelity-crop.mjs'
import { root } from './make-section.mjs'

// ── argumentos ─────────────────────────────────────────────────
const args = process.argv.slice(2)
const opt = (name, fallback) => {
  const i = args.indexOf(`--${name}`)
  return i === -1 ? fallback : args[i + 1]
}
const flag = (name) => args.includes(`--${name}`)

const target = opt('section', args[0] && !args[0].startsWith('--') ? args[0] : undefined)
const shared = opt('shared')
const layout = opt('layout')
const label = opt('label', 'desktop')
const goal = Number(opt('target', 99))
const max = Number(opt('max', 5))
const click = opt('click')
const capture = opt('capture', click ? 'viewport' : 'element')
if (click !== undefined && (!click || click.startsWith('--'))) fail('--click: informe o seletor CSS do botão, ex.: --click "[aria-controls=site-sidebar]".')
if (!['element', 'viewport'].includes(capture)) fail('--capture: element ou viewport.')

const kebab = (v) => String(v).replace(/([a-z0-9])([A-Z])/g, '$1-$2').replace(/_/g, '-').toLowerCase()
const query = target ? target : shared ? `shared/${kebab(shared)}` : layout ? `layout/${kebab(layout)}` : null
if (!query) fail('Informe a seção (slug/section-name), --shared Nome ou --layout Header.')
// viram nome de pasta em .fidelity/ — sem ../ nem barras extras
if (target && !/^[a-z0-9-]+\/[a-z0-9-]+$/.test(target)) fail('Seção inválida: use slug/section-name (kebab-case).')
if ((shared && !/^[\w-]+$/.test(shared)) || (layout && !/^[\w-]+$/.test(layout))) fail('--shared/--layout: só letras, números, - e _.')
if (!/^[a-z0-9-]+$/.test(label)) fail('--label: só letras minúsculas, números e -.')
const id = target ? target.replace(/\//g, '-') : shared ? `shared-${shared}` : `layout-${layout}`
const out = join(root, '.fidelity', id, label)
mkdirSync(out, { recursive: true })

// ── contador de tentativas ─────────────────────────────────────
const counterFile = join(out, 'attempts.json')
if (flag('reset')) writeFileSync(counterFile, JSON.stringify({ attempts: 0, history: [] }))
const counter = existsSync(counterFile) ? JSON.parse(readFileSync(counterFile, 'utf8')) : { attempts: 0, history: [] }
if (counter.attempts >= max) {
  const best = Math.max(...counter.history.map((h) => h.score))
  console.log(`FIDELITY ${best.toFixed(2)}% | tentativa ${counter.attempts}/${max} | LIMITE — limite de tentativas atingido (melhor: ${best.toFixed(2)}%)`)
  process.exit(2)
}

// ── 1. imagem do Figma ─────────────────────────────────────────
// figma-raw.png = export original; figma.png = versão normalizada (recortada no frame e
// achatada sobre o fundo) — é a que entra na comparação e a que o agente deve abrir.
const figmaPng = join(out, 'figma.png')
const rawPng = join(out, 'figma-raw.png')
const frameMeta = join(out, 'frame.json')
const pngArg = opt('figma-png')
const figmaUrl = opt('figma')
if (pngArg) {
  writeFileSync(rawPng, readFileSync(pngArg))
  if (existsSync(frameMeta)) rmSync(frameMeta)
} else if (figmaUrl) {
  await downloadFigma(figmaUrl, rawPng, frameMeta)
} else if (!existsSync(rawPng) && !existsSync(figmaPng)) {
  fail('Informe --figma <url do frame> ou --figma-png <arquivo> (na primeira medição).')
}
const figma = normalizeFigma(
  PNG.sync.read(readFileSync(existsSync(rawPng) ? rawPng : figmaPng)),
  existsSync(frameMeta) ? JSON.parse(readFileSync(frameMeta, 'utf8')) : null,
  parseColor(opt('bg', '#ffffff')),
)
writeFileSync(figmaPng, PNG.sync.write(figma))

// ── 2. render da seção ─────────────────────────────────────────
// CSS atualizado com as classes da seção (Sass + Tailwind via gulp)
execSync('npx gulp main', { cwd: root, stdio: 'ignore' })

const { chromium } = await import('playwright')

const url = `${siteUrl()}/?fw_preview=${encodeURIComponent(query).replace('%2F', '/')}`
const browser = await chromium.launch()
let render
let renderError
try {
  // reducedMotion: animações de entrada (skill animation) desligadas — mede o estado final
  const page = await browser.newPage({ viewport: { width: figma.width, height: Math.max(figma.height, 400) }, deviceScaleFactor: 1, reducedMotion: 'reduce' })
  await page.goto(url)
  // 'attached': parte só com position fixed (header lateral) deixa o <body> com altura 0 — "invisível"
  await page.waitForSelector('body[data-ready="1"]', { state: 'attached', timeout: 30_000 })
  const error = await page.evaluate(() => document.body.dataset.error)
  if (error) throw new Error(error)
  if (click) {
    const button = page.locator(click).first()
    if (!(await button.count())) throw new Error(`--click: nenhum elemento casa com "${click}"`)
    // botão escondido nesta largura (ex.: hambúrguer lg:hidden num frame de desktop) não clica
    await button.click({ timeout: 5_000 }).catch(() => { throw new Error(`--click: "${click}" não está visível/clicável em ${figma.width}px de largura`) })
    // botão com aria-expanded (menu, acordeão): espera abrir; sem ele, só o próximo quadro
    await page.waitForFunction(
      (sel) => { const b = document.querySelector(sel); return !b?.hasAttribute('aria-expanded') || b.getAttribute('aria-expanded') === 'true' },
      click, { timeout: 5_000 },
    ).catch(() => { throw new Error(`--click: "${click}" não ficou com aria-expanded="true" — o store não abriu?`) })
    await page.evaluate(() => new Promise((r) => requestAnimationFrame(() => requestAnimationFrame(r))))
  }
  const el = page.locator('#preview > *').first()
  const box = capture === 'element' ? await el.boundingBox() : null
  // parte só com position fixed (ex.: header lateral no desktop) não tem caixa própria: a tela inteira
  if (capture === 'element' && (!box || !box.width || !box.height)) console.log('captura: a parte não tem caixa (position fixed?) — usando a tela inteira no tamanho do frame')
  const buffer = capture === 'viewport' || !box?.width || !box?.height
    ? await page.screenshot({ animations: 'disabled' })
    : await el.screenshot({ animations: 'disabled' })
  writeFileSync(join(out, 'render.png'), buffer)
  render = PNG.sync.read(buffer)
} catch (e) {
  renderError = e
} finally {
  await browser.close()
}
// erro do --click é de uso (seletor errado, botão escondido): mensagem curta, sem stack, e a tentativa não conta
if (renderError) {
  if (String(renderError.message).startsWith('--click')) fail(renderError.message)
  throw renderError
}

// ── 3. comparação ──────────────────────────────────────────────
// Fundo = cor predominante da borda do frame do Figma. "Conteúdo" = pixel que foge
// do fundo em qualquer uma das imagens. A nota mede as diferenças só sobre o conteúdo
// (o fundo liso não infla o resultado) e é multiplicada pela proporção de tamanho.
const width = Math.max(figma.width, render.width)
const height = Math.max(figma.height, render.height)
const bg = backgroundOf(figma)
const a = pad(figma, width, height, bg)
const b = pad(render, width, height, bg)
const diff = new PNG({ width, height })
const threshold = Number(opt('threshold', 0.1))
const mismatched = pixelmatch(a.data, b.data, diff.data, width, height, { threshold, includeAA: false })
writeFileSync(join(out, 'diff.png'), PNG.sync.write(diff))

// pixels de anti-aliasing (amarelos no diff) são ignorados pelo pixelmatch — saem também do denominador
let content = 0
for (let i = 0; i < a.data.length; i += 4) {
  const antialias = diff.data[i] === 255 && diff.data[i + 1] === 255 && diff.data[i + 2] === 0
  if (!antialias && (!isBg(a.data, i, bg) || !isBg(b.data, i, bg))) content++
}
const contentScore = content ? Math.max(0, 1 - mismatched / content) : 1
const sizeScore = (Math.min(figma.width, render.width) / width) * (Math.min(figma.height, render.height) / height)
const score = contentScore * sizeScore * 100
counter.attempts += 1
counter.history.push({ attempt: counter.attempts, score: Number(score.toFixed(2)), at: new Date().toISOString() })
writeFileSync(counterFile, JSON.stringify(counter, null, 2))

const result = {
  target: query, label, click: click ?? null, capture, score: Number(score.toFixed(2)), goal,
  attempt: counter.attempts, max,
  figma: { width: figma.width, height: figma.height },
  render: { width: render.width, height: render.height },
  contentScore: Number((contentScore * 100).toFixed(2)),
  sizeScore: Number((sizeScore * 100).toFixed(2)),
  mismatchedPixels: mismatched,
  contentPixels: content,
  files: { figma: join(out, 'figma.png'), render: join(out, 'render.png'), diff: join(out, 'diff.png') },
}
writeFileSync(join(out, 'result.json'), JSON.stringify(result, null, 2))

const status = score >= goal ? 'OK' : counter.attempts >= max ? 'LIMITE' : 'ABAIXO'
if (figma.height !== render.height || figma.width !== render.width) {
  console.log(`tamanho: figma ${figma.width}x${figma.height} × render ${render.width}x${render.height} — diferença de altura indica espaçamento/tipografia fora`)
}
console.log(`conteúdo: ${result.contentScore}% | tamanho: ${result.sizeScore}%`)
console.log(`diff: ${result.files.diff}`)
console.log(`FIDELITY ${score.toFixed(2)}% | tentativa ${counter.attempts}/${max} | ${status}`)
process.exit(status === 'OK' ? 0 : 1)

// ── helpers ────────────────────────────────────────────────────
/**
 * (1) Se o export veio maior que o frame (sombra/blur/glow/imagem vazando), recorta para a
 * bounding box do nó a partir do offset (x, y) entre absoluteBoundingBox e
 * absoluteRenderBounds — ver `frameOffset()` em fidelity-crop.mjs.
 * (2) Achata a transparência sobre `bg` — o render do Chromium é sempre opaco.
 */
function normalizeFigma(png, meta, bg) {
  let src = png
  const box = meta?.box
  if (box && (png.width > Math.round(box.width) || png.height > Math.round(box.height))) {
    const crop = frameOffset(png, box, meta.render)
    if (crop.source !== 'render-bounds') {
      console.log(`recorte: export ${png.width}x${png.height} sem absoluteRenderBounds compatível — recorte centralizado em (${crop.x}, ${crop.y})`)
    }
    src = new PNG({ width: crop.width, height: crop.height })
    PNG.bitblt(png, src, crop.x, crop.y, crop.width, crop.height, 0, 0)
  }
  const flat = new PNG({ width: src.width, height: src.height })
  for (let i = 0; i < src.data.length; i += 4) {
    const alpha = src.data[i + 3] / 255
    for (let c = 0; c < 3; c++) flat.data[i + c] = Math.round(src.data[i + c] * alpha + bg[c] * (1 - alpha))
    flat.data[i + 3] = 255
  }
  return flat
}

function parseColor(value) {
  const hex = String(value).replace('#', '').trim()
  const full = hex.length === 3 ? [...hex].map((c) => c + c).join('') : hex
  if (!/^[0-9a-f]{6}$/i.test(full)) fail(`--bg inválido: ${value} (use #rrggbb)`)
  return [0, 2, 4].map((i) => parseInt(full.slice(i, i + 2), 16))
}

function pad(png, w, h, color) {
  if (png.width === w && png.height === h) return png
  const out = new PNG({ width: w, height: h, fill: true })
  for (let i = 0; i < out.data.length; i += 4) out.data.set(color, i)
  PNG.bitblt(png, out, 0, 0, png.width, png.height, 0, 0)
  return out
}

/** Cor mais frequente na borda da imagem (o fundo da seção). */
function backgroundOf(png) {
  const counts = new Map()
  const add = (x, y) => {
    const i = (y * png.width + x) * 4
    const key = png.data.readUInt32BE(i)
    counts.set(key, (counts.get(key) ?? 0) + 1)
  }
  for (let x = 0; x < png.width; x++) { add(x, 0); add(x, png.height - 1) }
  for (let y = 0; y < png.height; y++) { add(0, y); add(png.width - 1, y) }
  const key = [...counts].sort((p, q) => q[1] - p[1])[0][0]
  return [(key >>> 24) & 255, (key >>> 16) & 255, (key >>> 8) & 255, key & 255]
}

function isBg(data, i, bg) {
  return Math.abs(data[i] - bg[0]) + Math.abs(data[i + 1] - bg[1]) + Math.abs(data[i + 2] - bg[2]) < 24
}

function figmaToken() {
  if (process.env.FIGMA_TOKEN) return process.env.FIGMA_TOKEN
  if (process.env.FIGMA_API_KEY) return process.env.FIGMA_API_KEY
  try {
    // .mcp.json versionado usa "${FIGMA_API_KEY}" (expandido pelo Claude Code) — literal não serve
    const key = JSON.parse(readFileSync(join(root, '.mcp.json'), 'utf8')).mcpServers?.figma?.env?.FIGMA_API_KEY || ''
    return key.startsWith('${') ? '' : key
  } catch {
    return ''
  }
}

async function downloadFigma(link, dest, metaDest) {
  const u = new URL(link)
  const parts = u.pathname.split('/').filter(Boolean) // design|file, KEY, (branch, BRANCHKEY)?, nome
  const fileKey = parts[2] === 'branch' ? parts[3] : parts[1]
  const nodeId = (u.searchParams.get('node-id') ?? '').replace(/-/g, ':')
  if (!fileKey || !nodeId) fail('Link do Figma sem file key ou node-id.')

  const token = figmaToken()
  if (!token) fail('Sem token do Figma: defina a variável de ambiente FIGMA_API_KEY (ou FIGMA_TOKEN), ou exporte o frame em PNG 1x e use --figma-png.')

  const api = await fetch(`https://api.figma.com/v1/images/${fileKey}?ids=${encodeURIComponent(nodeId)}&format=png&scale=1`, {
    headers: { 'X-Figma-Token': token },
  })
  if (!api.ok) fail(`API do Figma respondeu ${api.status}: ${await api.text()}`)
  const imageUrl = (await api.json()).images?.[nodeId]
  if (!imageUrl) fail(`O Figma não gerou imagem para o node ${nodeId}.`)
  const png = await fetch(imageUrl)
  writeFileSync(dest, Buffer.from(await png.arrayBuffer()))

  // bounding box do frame × área renderizada (com efeitos) — usada para recortar o export
  try {
    const res = await fetch(`https://api.figma.com/v1/files/${fileKey}/nodes?ids=${encodeURIComponent(nodeId)}&depth=1`, {
      headers: { 'X-Figma-Token': token },
    })
    const doc = res.ok ? (await res.json()).nodes?.[nodeId]?.document : null
    if (doc?.absoluteBoundingBox) {
      writeFileSync(metaDest, JSON.stringify({ box: doc.absoluteBoundingBox, render: doc.absoluteRenderBounds ?? null }, null, 2))
    } else if (existsSync(metaDest)) rmSync(metaDest)
  } catch {
    if (existsSync(metaDest)) rmSync(metaDest)
  }
}

/** URL do WP local: --url, FW_SITE_URL ou o `link admin` do .claude/prompt.yaml (sem /wp-login.php ou /wp-admin). */
function siteUrl() {
  let url = opt('url') || process.env.FW_SITE_URL || ''
  if (!url) {
    try {
      const yaml = readFileSync(join(root, '.claude', 'prompt.yaml'), 'utf8')
      url = (yaml.match(/^\s*link admin:\s*(\S+)/m) || [])[1] ?? ''
    } catch {
      url = ''
    }
  }
  url = url.replace(/\/(wp-login\.php|wp-admin)(\/.*)?$/, '').replace(/\/+$/, '')
  if (!/^https?:\/\//.test(url)) fail('URL do WP local não encontrada: use --url http://localhost/meu-site, FW_SITE_URL ou preencha "link admin" no .claude/prompt.yaml.')
  return url
}

function fail(msg) {
  console.error(`fidelity: ${msg}`)
  process.exit(3)
}
