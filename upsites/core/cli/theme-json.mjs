#!/usr/bin/env node
/**
 * Gera o theme.json (raiz do tema) e o assets/css/editor.css a partir dos tokens do
 * src/sass/main.scss (`:root { --primary: #…; }` e `--font-*` do @theme) — o editor de blocos
 * (posts) passa a mostrar a paleta e a fonte do site. Roda sozinho no `npm run build`/`npm run dev`.
 *
 *   node upsites/core/cli/theme-json.mjs           → gera
 *   node upsites/core/cli/theme-json.mjs --check   → só confere se o theme.json está em dia (npm run check)
 *
 * O theme.json declara SÓ presets (cores, fontes) — sem `styles`/`layout`: não muda o CSS do
 * front (o visual do site continua 100% no main.scss). A aparência dentro do editor vem do
 * editor.css (add_editor_style, escopado pelo WP ao editor).
 */
import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs'
import { join } from 'node:path'
import { root } from './make-section.mjs'

const scss = readFileSync(join(root, 'src', 'sass', 'main.scss'), 'utf8')

// cores: 1º bloco :root (tokens do Figma), só valores que são cor
const rootBlock = scss.match(/:root\s*\{([\s\S]*?)\n\}/)?.[1] ?? ''
const COLOR = /^(#[0-9a-f]{3,8}|rgba?\(.+\)|hsla?\(.+\)|oklch\(.+\)|oklab\(.+\))$/i
const LABELS = {
  background: 'Fundo', foreground: 'Texto', primary: 'Primária', 'primary-foreground': 'Texto sobre primária',
  secondary: 'Secundária', 'secondary-foreground': 'Texto sobre secundária', muted: 'Suave',
  'muted-foreground': 'Texto suave', border: 'Borda', card: 'Card', 'card-foreground': 'Texto do card',
}
const title = (slug) => LABELS[slug] ?? slug.replace(/-/g, ' ').replace(/^./, (c) => c.toUpperCase())
const palette = [...rootBlock.matchAll(/--([\w-]+)\s*:\s*([^;]+);/g)]
  .map(([, slug, value]) => [slug, value.trim()])
  .filter(([, value]) => COLOR.test(value))
  .map(([slug, color]) => ({ slug, name: title(slug), color }))

// fontes: --font-* do @theme (ex.: --font-sans: 'Cairo', ui-sans-serif, …)
const fonts = [...scss.matchAll(/--font-([\w-]+)\s*:\s*([^;]+);/g)]
  .filter(([, slug]) => !/^(weight|size)/.test(slug))
  .map(([, slug, family]) => ({ slug, name: title(slug), fontFamily: family.trim() }))
const uniqueFonts = [...new Map(fonts.map((f) => [f.slug, f])).values()]

const themeJson = {
  $schema: 'https://schemas.wp.org/wp/6.5/theme.json',
  version: 2,
  settings: {
    color: { palette, defaultPalette: true, defaultGradients: true },
    ...(uniqueFonts.length ? { typography: { fontFamilies: uniqueFonts } } : {}),
  },
}
const json = JSON.stringify(themeJson, null, 2) + '\n'
const jsonPath = join(root, 'theme.json')

if (process.argv.includes('--check')) {
  const current = existsSync(jsonPath) ? readFileSync(jsonPath, 'utf8') : ''
  if (current !== json) {
    console.error('theme.json desatualizado em relação ao src/sass/main.scss — rode: node upsites/core/cli/theme-json.mjs')
    process.exit(1)
  }
  console.log('theme.json em dia com o main.scss')
  process.exit(0)
}

writeFileSync(jsonPath, json)

// editor.css: só dentro do editor (o WP escopa em .editor-styles-wrapper)
const has = (slug) => palette.some((c) => c.slug === slug)
const v = (slug, fallback) => (has(slug) ? palette.find((c) => c.slug === slug).color : fallback)
const font = uniqueFonts.find((f) => f.slug === 'body') ?? uniqueFonts.find((f) => f.slug === 'sans')
// a mesma fonte do site também dentro do editor: os @font-face locais (src/fonts → assets/fonts;
// o editor.css fica em assets/css, então url('../fonts/…') vale igual) e, em projeto antigo,
// @import de fontes remotas. Comentários (// e /* */) não contam — o modelo no main.scss é comentado.
const scssCode = scss.replace(/\/\*[\s\S]*?\*\//g, '').replace(/^\s*\/\/.*$/gm, '')
const fontFaces = [...scssCode.matchAll(/^@font-face\s*\{[^}]*\}/gm)].map(([block]) => `${block}\n`).join('')
const fontImports = [...scssCode.matchAll(/^@import\s+url\(['"]?(https:\/\/fonts\.[^'")]+)['"]?\);/gm)].map(([, url]) => `@import url('${url}');\n`).join('') + fontFaces
const css = `/* Gerado por upsites/core/cli/theme-json.mjs a partir do src/sass/main.scss — não edite. */
${fontImports}body {
  background: ${v('background', '#ffffff')};
  color: ${v('foreground', '#0a0a0a')};${font ? `\n  font-family: ${font.fontFamily};` : ''}
  line-height: 1.6;
}
a { color: ${v('primary', 'currentColor')}; }
h1, h2, h3, h4, h5, h6 { color: ${v('foreground', 'inherit')}; line-height: 1.25; }
`
mkdirSync(join(root, 'assets', 'css'), { recursive: true })
writeFileSync(join(root, 'assets', 'css', 'editor.css'), css)
console.log(`theme.json: ${palette.length} cores, ${uniqueFonts.length} fonte(s) · assets/css/editor.css`)
