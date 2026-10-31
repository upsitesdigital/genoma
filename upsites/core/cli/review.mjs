#!/usr/bin/env node
/**
 * Revisão mecânica de consistência dos módulos e seções.
 *
 *   npm run review            → todos os módulos
 *   npm run review -- home    → só o módulo "home"
 *
 * Saída: uma linha por achado — [ERRO] quebra a convenção / o contrato;
 * [AVISO] provável problema de qualidade ou desempenho. Exit 1 se houver ERRO.
 * É o ponto de partida do subagente r2d2, não substitui a revisão dele.
 */
import { existsSync, readdirSync, readFileSync, statSync } from 'node:fs'
import { join, relative } from 'node:path'
import { isKebab, root, toCamel } from './make-section.mjs'

const only = process.argv[2]
const pagesDir = join(root, 'template-pages')
const partsDir = join(root, 'template-parts', 'includes')

const findings = []
const rel = (p) => relative(root, p).replace(/\\/g, '/')
const read = (p) => (existsSync(p) ? readFileSync(p, 'utf8') : '')
const lineOf = (src, index) => src.slice(0, index).split('\n').length
const report = (level, file, line, msg) => findings.push({ level, at: line ? `${rel(file)}:${line}` : rel(file), msg })
const dirs = (p) => (existsSync(p) ? readdirSync(p).filter((d) => statSync(join(p, d)).isDirectory()) : [])

// Todos os campos ACF ficam em um só arquivo (skill acf, "Onde os campos são registrados")
const FIELDS = join(root, 'upsites', 'customizer-register', 'inc', 'acf_fields.php')
const acf = read(FIELDS)
// Fields::section('{slug}', '{section}', …) · Fields::postType/taxonomy('{slug}', '{cpt|tax}', …)
const calls = [...acf.matchAll(/Fields::(section|postType|taxonomy)\(\s*'([a-z0-9-]+)'\s*,\s*'([a-z0-9_-]+)'/g)]
  .map((m) => ({ kind: m[1], slug: m[2], name: m[3], index: m.index }))
  // Fields::option('{section}', …) — abas de Configuração do Tema (slug "opcoes", não é módulo)
  .concat([...acf.matchAll(/Fields::option\(\s*'([a-z0-9_]+)'/g)].map((m) => ({ kind: 'option', slug: 'opcoes', name: m[1], index: m.index })))
  .sort((a, b) => a.index - b.index)
// fim do trecho de uma chamada: próxima chamada ou próximo cabeçalho de página
const callEnd = (i) => {
  const next = calls.find((c) => c.index > i)?.index ?? acf.length
  const block = acf.indexOf('/* ──', i)
  return Math.min(next, block > i ? block : acf.length)
}

const FIGMA_URL = /figma\.com\/api|figma-alpha-api|s3-[a-z0-9-]+\.amazonaws\.com\/figma|localhost:3845/i
const allKeys = new Map() // key ACF → arquivo (colisão global)
const hexBySection = new Map() // #hex → Set(seções)

// CPTs e taxonomias ficam cada um no seu arquivo único (skill wordpress, "CPTs e taxonomias").
// Os slugs registrados lá habilitam Fields::postType()/Fields::taxonomy() no acf_fields.php.
const CPTS = join(root, 'upsites', 'customizer-register', 'inc', 'register_cpts.php')
const TAXES = join(root, 'upsites', 'customizer-register', 'inc', 'register_taxes.php')
const cptSrc = read(CPTS)
const taxSrc = read(TAXES)
const cptSlugs = new Set([...cptSrc.matchAll(/PostType::add\(\s*'([a-z0-9_-]+)'/g)].map((m) => m[1]))
const taxSlugs = new Set([...taxSrc.matchAll(/Taxonomy::add\(\s*'([a-z0-9_-]+)'/g)].map((m) => m[1]))
// legado: attributes em módulos antigos ainda registram (e são acusados como erro abaixo)
for (const slug of dirs(pagesDir)) {
  const src = read(join(pagesDir, slug, `${slug}.module.php`))
  for (const m of src.matchAll(/#\[PostType\([^\]]*?\bslug:\s*'([a-z0-9_-]+)'/g)) cptSlugs.add(m[1])
  for (const m of src.matchAll(/#\[Taxonomy\([^\]]*?\bslug:\s*'([a-z0-9_-]+)'/g)) taxSlugs.add(m[1])
}

// Profundidade de colchetes em src[0..idx), ignorando strings PHP.
function depthAt(src, idx) {
  let d = 0
  let q = null
  for (let i = 0; i < idx; i++) {
    const c = src[i]
    if (q) { if (c === '\\') i++; else if (c === q) q = null; continue }
    if (c === "'" || c === '"') q = c
    else if (c === '[') d++
    else if (c === ']') d--
  }
  return d
}

let mod = ''
let files = {}
let linkNames = new Set() // names dos campos link do slug atual (controller deve ler com link())

/**
 * Todo campo de conteúdo precisa de `instructions` explicando ao editor o que é e
 * onde aparece (skill acf, "Instruções dos campos"). Só as propriedades do próprio
 * campo contam — não as dos subcampos/layouts dentro dele.
 */
const NO_INSTRUCTIONS = new Set(['tab', 'message', 'accordion'])
const norm = (s) => s.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/g, ' ').trim()
function checkInstructions(entry, key, at) {
  const own = entry.split(/'(?:sub_fields|layouts)'\s*=>/)[0]
  const type = (own.match(/'type'\s*=>\s*'([a-z_]+)'/) || [])[1]
  if (NO_INSTRUCTIONS.has(type)) return

  const ins = own.match(/'instructions'\s*=>\s*'((?:\\.|[^'\\])*)'|'instructions'\s*=>\s*"((?:\\.|[^"\\])*)"/)
  if (!ins) {
    report('ERRO', FIELDS, at, `campo ${key} sem 'instructions' — explique ao editor o que é e onde aparece na página`)
    return
  }
  const text = (ins[1] ?? ins[2] ?? '').trim()
  const label = (own.match(/'label'\s*=>\s*'((?:\\.|[^'\\])*)'/) || [])[1] ?? ''
  const generic = /^(insira|digite|informe|adicione|selecione|escolha|preencha)\b/i.test(text) && text.split(/\s+/).length <= 4
  if (text.length < 20 || norm(text) === norm(label) || generic) {
    report('AVISO', FIELDS, at, `instructions de ${key} genérica ("${text}") — diga o que é, onde aparece e o que acontece se ficar vazio`)
  }
}

// Checagens por campo (seções e grupos de CPT). extra(entry, idx, key, linha) = regra do chamador.
// Seção com o comentário "// legado:" (keys herdadas de um tema em produção) não cobra o prefixo.
function checkFields(chunk, base, prefix, extra) {
  const legacy = /^\s*\/\/ legado:/m.test(chunk)
  const entries = [...chunk.matchAll(/'key'\s*=>\s*'(field_[a-z0-9_-]+)'/g)]
  for (const [j, e] of entries.entries()) {
    const key = e[1]
    const at = lineOf(mod, base + e.index)
    if (!legacy && !key.startsWith(prefix)) report('ERRO', FIELDS, at, `key ${key} fora do padrão ${prefix}{nome}`)
    if (allKeys.has(key)) report('ERRO', FIELDS, at, `key ${key} duplicada (também em ${allKeys.get(key)})`)
    allKeys.set(key, `${rel(FIELDS)}:${at}`)

    const entry = chunk.slice(e.index, entries[j + 1]?.index ?? chunk.length)
    checkInstructions(entry, key, at)
    // listas não têm limite: o editor pode incluir mais boxes/cards/itens (skill acf, "Listas")
    {
      const own = entry.split(/'(?:sub_fields|layouts)'\s*=>/)[0]
      const type = (own.match(/'type'\s*=>\s*'([a-z_]+)'/) || [])[1] ?? ''
      const max = Number((own.match(/'max'\s*=>\s*(\d+)/) || [])[1] ?? 0)
      if (max > 0 && ['repeater', 'flexible_content'].includes(type)) report('ERRO', FIELDS, at, `${type} ${key} limitado a ${max} — remova o 'max': o editor deve poder incluir quantos itens quiser`)
      else if (max > 0 && ['relationship', 'post_object', 'gallery', 'taxonomy', 'user'].includes(type)) report('AVISO', FIELDS, at, `${type} ${key} limitado a ${max} — se a quantidade não for exigência do layout, remova o 'max'`)
      const name = (own.match(/'name'\s*=>\s*'([a-z0-9_]+)'/) || [])[1] ?? ''
      if (/(^|_)(box|card|item|bloco|block|step|etapa|passo|coluna|column|feature|beneficio|servico|logo|slide)_?\d+(_|$)/.test(name)) report('AVISO', FIELDS, at, `campo numerado "${name}" parece item fixo de lista — use um repeater sem limite`)
    }
    // ícone é campo de imagem que o editor troca/remove (skill acf, "Ícones e imagens")
    if (/_(icon|icone|icones|icons)(_|$)/.test(key)) {
      const own = entry.split(/'(?:sub_fields|layouts)'\s*=>/)[0]
      const type = (own.match(/'type'\s*=>\s*'([a-z_]+)'/) || [])[1]
      if (['select', 'radio', 'button_group', 'text'].includes(type ?? '')) report('AVISO', FIELDS, at, `ícone ${key} como '${type}' — use + Fields::icon('{asset do Figma}') para o editor poder subir/remover o arquivo`)
      else if (!/Fields::icon\(|iconField\(/.test(entry) && type === 'image') report('AVISO', FIELDS, at, `ícone ${key} sem Fields::icon() — sem o SVG do Figma como padrão nem upload de SVG`)
    }
    // botão = um campo link (URL + texto + nova aba), com o padrão do Figma (skill acf, "Botões")
    {
      const own = entry.split(/'(?:sub_fields|layouts)'\s*=>/)[0]
      const type = (own.match(/'type'\s*=>\s*'([a-z_]+)'/) || [])[1] ?? ''
      const name = (own.match(/'name'\s*=>\s*'([a-z0-9_]+)'/) || [])[1] ?? ''
      const isButton = /(^|_)(botao|botoes|button|btn)(_|$)/.test(name) // "cta" costuma ser nome de seção, não de botão
      const viaButton = /Fields::button\(\s*\[\s*$/.test(chunk.slice(Math.max(0, e.index - 40), e.index))
      if (type === 'link' || viaButton) {
        linkNames.add(name)
        if (!viaButton) report('AVISO', FIELDS, at, `link ${key} sem o toggle "Ocultar botão" — declare com ...Fields::button([...])`)
        if (!viaButton && !/'return_format'\s*=>\s*'array'/.test(own)) report('ERRO', FIELDS, at, `link ${key} sem return_format 'array' (o front espera LinkData)`)
        const ins = (own.match(/'instructions'\s*=>\s*(['"])((?:\\.|(?!\1).)*)\1/) || [])[2] ?? ''
        if (ins && !/vazio/i.test(ins)) report('AVISO', FIELDS, at, `botão ${key}: diga na instrução o que acontece se ficar vazio — 'Vazio, exibe o padrão "…".' ou 'Vazio, o botão não aparece.'`)
      } else if (isButton && ['text', 'url', 'page_link', 'textarea'].includes(type)) {
        // exceção: rótulo do botão dos cards de uma listagem (URL vem de cada item) — `{x}_botao_texto` sem par de URL
        const label = /_texto$/.test(name) && type === 'text'
        const pairedUrl = new RegExp(`'name'\\s*=>\\s*'${name.replace(/_texto$/, '')}_(link|url)'`).test(chunk)
        if (!label || pairedUrl) report('AVISO', FIELDS, at, `botão ${key} como '${type}' — use um único campo 'link' (URL, texto e nova aba juntos), não texto e URL separados (só o rótulo de botão de card de listagem, com URL vinda do item, fica 'text' {x}_botao_texto)`)
      }
    }
    // redes sociais = repeater (nome, url, ícone) — nunca um campo por rede (skill acf, "Configurações globais")
    {
      const own = entry.split(/'(?:sub_fields|layouts)'\s*=>/)[0]
      const type = (own.match(/'type'\s*=>\s*'([a-z_]+)'/) || [])[1] ?? ''
      const name = (own.match(/'name'\s*=>\s*'([a-z0-9_]+)'/) || [])[1] ?? ''
      if (['url', 'text', 'link'].includes(type) && /(^|_)(instagram|linkedin|facebook|youtube|twitter|tiktok|whatsapp|pinterest|threads)(_|$)/.test(name)) {
        report('AVISO', FIELDS, at, `rede social ${key} como campo solto — use um repeater de redes (nome, url, ícone com Fields::icon) para o editor incluir/remover redes`)
      }
    }
    // texto/imagem apagado pelo editor some do site — sem "Vazio, exibe o padrão" (skill acf, "Defaults")
    {
      const own = entry.split(/'(?:sub_fields|layouts)'\s*=>/)[0]
      const type = (own.match(/'type'\s*=>\s*'([a-z_]+)'/) || [])[1] ?? (/Fields::(image|icon)\(/.test(own) ? 'image' : '')
      const viaButton = /Fields::button\(\s*\[\s*$/.test(chunk.slice(Math.max(0, e.index - 40), e.index))
      const ins = (own.match(/'instructions'\s*=>\s*(['"])((?:\\.|(?!\1).)*)\1/) || [])[2] ?? ''
      if (!viaButton && ['text', 'textarea', 'wysiwyg', 'image'].includes(type) && /vazio[^.]*\b(exibe|usa|mostra|volta)\b[^.]*padr/i.test(ins)) {
        report('AVISO', FIELDS, at, `${key}: texto/imagem apagado no painel deve sumir do site — troque "Vazio, exibe o padrão…" por "Vazio, {o elemento} não aparece."`)
      }
    }
    if (/'type'\s*=>\s*'image'/.test(entry) && !/'return_format'\s*=>\s*'array'/.test(entry)) report('ERRO', FIELDS, at, `imagem ${key} sem return_format 'array'`)
    if (/'type'\s*=>\s*'oembed'/.test(entry)) report('AVISO', FIELDS, at, `${key} usa oembed — prefira 'url' para vídeo`)
    extra?.(entry, e.index, key, at)
  }
}

for (const slug of dirs(pagesDir)) {
  if (only && slug !== only) continue
  const dir = join(pagesDir, slug)
  const snakeSlug = slug.replace(/-/g, '_')
  linkNames = new Set()
  files = {
    module: join(dir, `${slug}.module.php`),
    controller: join(dir, `${slug}.controller.php`),
    view: join(dir, `${slug}.view.php`),
  }

  // shared: só registra campos de componentes compartilhados
  const isShared = slug === 'shared'
  for (const [kind, file] of Object.entries(files)) {
    if (!existsSync(file) && !(isShared && kind !== 'module')) report('ERRO', file, 0, `arquivo ${kind} do módulo não existe`)
  }

  // ── module.php: só attribute, CPTs e boot() — campos ficam no acf_fields.php ──
  const modSrc = read(files.module)
  const legacy = modSrc.search(/->addSection\(|acf_add_local_field_group\s*\(|->(icon|image)Field\(/)
  if (legacy >= 0) report('ERRO', files.module, lineOf(modSrc, legacy), 'campos ACF no module.php — mova para upsites/customizer-register/inc/acf_fields.php (Fields::section)')
  // CPTs/taxonomias só em register_cpts.php / register_taxes.php — nunca no módulo
  for (const f of existsSync(dir) ? readdirSync(dir).filter((n) => n.endsWith('.php')) : []) {
    const src = read(join(dir, f))
    for (const m of src.matchAll(/#\[(PostType|Taxonomy)\(|\bregister_(post_type|taxonomy)(_args)?\b/g)) {
      if (/^\s*(\/\/|\*|\/\*)/.test(src.split('\n')[lineOf(src, m.index) - 1])) continue // comentário
      const tax = m[1] === 'Taxonomy' || m[2] === 'taxonomy'
      report('ERRO', join(dir, f), lineOf(src, m.index), `${m[0]} no módulo — ${tax ? 'taxonomias ficam em upsites/customizer-register/inc/register_taxes.php (Taxonomy::add)' : 'CPTs ficam em upsites/customizer-register/inc/register_cpts.php (PostType::add)'}; ajustes de args vão no parâmetro args:`)
    }
  }

  // ── acf_fields.php: seções e grupos de CPT/taxonomia deste slug ──
  mod = acf
  const mine = calls.filter((c) => c.slug === slug)
  // seção com o comentário "// layout:" é lida pelo header/footer (ex.: banner do rodapé por página),
  // não pelo controller: não tem pasta nem entra no view
  const sections = mine.filter((c) => c.kind === 'section').map((c) => ({ snake: c.name, index: c.index, layout: /^\s*\/\/ layout:/m.test(acf.slice(c.index, callEnd(c.index))) }))
  const orders = sections.map((x) => (acf.slice(x.index, callEnd(x.index)).match(/order:\s*(\d+)/) || [])[1]).filter(Boolean)
  if (sections.length > 1 && new Set(orders).size !== orders.length) report('AVISO', FIELDS, lineOf(acf, sections[0].index), `${slug}: Fields::section() com order repetido — use a posicao de cada seção`)

  for (const g of mine.filter((c) => c.kind !== 'section')) {
    const at = lineOf(acf, g.index)
    const chunk = acf.slice(g.index, callEnd(g.index))
    const isTax = g.kind === 'taxonomy'
    const known = isTax ? taxSlugs : cptSlugs
    if (!known.has(g.name)) {
      report('ERRO', FIELDS, at, `Fields::${g.kind}('${slug}', '${g.name}') — "${g.name}" não é registrado em ${isTax ? 'register_taxes.php (Taxonomy::add)' : 'register_cpts.php (PostType::add)'} (${[...known].join(', ') || 'nenhum'})`)
      continue
    }
    const objSnake = g.name.replace(/-/g, '_')
    if (sections.some((x) => x.snake === objSnake)) report('ERRO', FIELDS, at, `group_${snakeSlug}_${objSnake} colide com Fields::section('${slug}', '${objSnake}') — renomeie a seção`)
    // campos de primeiro nível exigem name {cpt}_{nome}; sub_fields usam {nome}
    const depths = [...chunk.matchAll(/'key'\s*=>\s*'field_/g)].map((m) => depthAt(chunk, m.index))
    const top = Math.min(...depths)
    checkFields(chunk, g.index, `field_${snakeSlug}_${objSnake}_`, (entry, idx, key, fat) => {
      if (depthAt(chunk, idx) !== top) return
      const name = entry.match(/'name'\s*=>\s*'([^']*)'/)?.[1]
      if (!name || !name.startsWith(`${objSnake}_`)) report('ERRO', FIELDS, fat, `campo ${key} com name "${name ?? ''}" — campos de ${isTax ? 'taxonomia' : 'CPT'} usam name ${objSnake}_{nome}`)
    })
  }

  for (const sec of sections) {
    const chunk = acf.slice(sec.index, callEnd(sec.index))
    checkFields(chunk, sec.index, `field_${snakeSlug}_${sec.snake}_`)
    if (/'name'\s*=>\s*'[a-z0-9_]+_ocultar'/.test(chunk)) report('ERRO', FIELDS, lineOf(acf, sec.index), `seção ${slug}/${sec.snake} declara _ocultar à mão — o Fields::section() já cria`)
  }

  // ── controller.php ───────────────────────────────────────────
  const ctrl = read(files.controller)
  if (ctrl) {
    for (const s of sections.filter((x) => !x.layout)) {
      if (!new RegExp(`section\\(\\s*\\$\\w+\\s*,\\s*'${s.snake}'`).test(ctrl)) report('ERRO', files.controller, 0, `seção ${s.snake} não é lida via $this->section($id, '${s.snake}', …)`)
    }
    const pageFn = ctrl.match(/((?:[ \t]*#\[[^\n]*\]\r?\n)*)[ \t]*public function page\(/)
    if (pageFn && !/#\[Cache/.test(pageFn[1])) report('AVISO', files.controller, lineOf(ctrl, pageFn.index), 'page() sem #[Cache] — o PHP chama o controller em toda visualização')
    // formato antigo (dados da página como rota REST, #[Get('/{slug}')] + index): o View só chama
    // o page() — sem ele, a página fica sem dados (404)
    if (!pageFn && files.view && read(files.view)) report('ERRO', files.controller, 0, `controller sem public function page(int $pageId): array — a página não carrega (404); dados da página vão no page(), sem #[Get]; REST só para o que um store busca`)
    for (const m of ctrl.matchAll(/'posts_per_page'\s*=>\s*-1/g)) report('ERRO', files.controller, lineOf(ctrl, m.index), 'posts_per_page => -1 (query sem limite)')
    for (const m of ctrl.matchAll(/get_param\(\s*['"]id['"]\s*\)/g)) report('ERRO', files.controller, lineOf(ctrl, m.index), `get_param('id') também lê ?id= da query string (rascunho por /rota?id=) — use $this->pageId($request)`)
    for (const m of ctrl.matchAll(/get_queried_object\(|is_single\(|is_page\(|the_post\(/g)) report('ERRO', files.controller, lineOf(ctrl, m.index), `${m[0]} no controller — o cache do page() é por id + $params (a main query não entra na chave) e a rota REST não tem main query; use o $pageId e os $params do View::render`)
    if (/\bget_fields\(|\$this->fields\(/.test(ctrl)) report('AVISO', files.controller, 0, 'get_fields() inteiro no retorno — devolva só o que o view usa (o retorno é cacheado; numa rota REST, público)')
    // conteúdo apagado no painel não pode voltar como padrão do Figma (skill acf, "Defaults")
    for (const m of ctrl.matchAll(/->field\(\s*\$\w+\s*,\s*'([a-z0-9_]+)'\s*\)\s*(\?:|\?\?)/g)) {
      report('AVISO', files.controller, lineOf(ctrl, m.index), `${m[1]} com fallback "${m[2]}" — apagar no painel traria o padrão de volta; use $this->text($id, '${m[1]}', 'padrão') (texto) ou $this->rows($id, '${m[1]}') ?? DEFAULTS (repeater)`)
    }
    for (const m of ctrl.matchAll(/->image\([^;]*?\)\s*\?\?/g)) {
      report('AVISO', files.controller, lineOf(ctrl, m.index), `imagem com fallback "??" — apagar no painel traria a padrão de volta; use $this->picture($id, '{name}', '{asset}') (Fields::image)`)
    }
    for (const m of ctrl.matchAll(/\$row\[\s*'([a-z0-9_]+)'\s*\]\s*(\?:|\?\?)\s*['"][^'"]/g)) {
      report('AVISO', files.controller, lineOf(ctrl, m.index), `subcampo ${m[1]} com texto padrão — linha salva com o campo vazio deve ocultar o elemento; use $this->textFrom($row, '${m[1]}')`)
    }
    for (const m of ctrl.matchAll(/->field\(\s*\$\w+\s*,\s*'([a-z0-9_]+)'/g)) {
      if (linkNames.has(m[1])) report('AVISO', files.controller, lineOf(ctrl, m.index), `botão ${m[1]} lido com field() — use $this->link($id, '${m[1]}', ['title' => '{texto do Figma}', 'url' => '{destino}'])`)
    }
  }

  // ── seções em template-parts/includes/{slug}/ ───────────────
  const view = read(files.view)
  const sectionDirs = dirs(join(partsDir, slug))

  for (const name of sectionDirs) {
    const sdir = join(partsDir, slug, name)
    if (!isKebab(name)) report('ERRO', sdir, 0, `section-name "${name}" não é kebab-case em inglês`)
    if (/[^\x00-\x7F]/.test(name)) report('ERRO', sdir, 0, `section-name "${name}" com acento — use inglês`)
    const php = join(sdir, `${name}.php`)
    const storeFile = join(sdir, `${name}.store.js`)
    if (!existsSync(php)) report('ERRO', php, 0, 'template da seção não existe')
    for (const f of readdirSync(sdir).filter((f) => /\.(tsx|jsx)$|\.schema\.ts$/.test(f))) {
      report('ERRO', join(sdir, f), 0, 'resto do React — seções são PHP ({nome}.php) + store da Interactivity API ({nome}.store.js)')
    }

    const snake = name.replace(/-/g, '_')
    const camel = toCamel(name)
    // shared/: componentes reusados — podem ser peças de UI (botão, título, card) sem campos próprios
    if (!isShared && !sections.some((s) => s.snake === snake)) report('ERRO', FIELDS, 0, `seção ${slug}/${name} sem Fields::section('${slug}', '${snake}', …)`)
    const call = new RegExp(`Part::render\\(\\s*'${slug}/${name}'\\s*,\\s*([^)]*)\\)`).exec(view)
    if (view && !call) report('ERRO', files.view, 0, `seção ${name} não está encaixada no view — <?php Part::render('${slug}/${name}', $data['${camel}']); ?>`)
    // os dados da seção vão direto: null (Ocultar seção) → o Part não renderiza
    if (call && !new RegExp(`^\\$data\\[\\s*'${camel}'\\s*\\]$`).test(call[1].trim())) report('ERRO', files.view, lineOf(view, call.index), `seção ${name} deve receber $data['${camel}'] (a chave do controller) — o null de "Ocultar seção" esconde a seção`)

    const src = read(php)
    if (src) {
      if (!isShared && !new RegExp(`id="${name}"`).test(src)) report('AVISO', php, 0, `<section> sem id="${name}"`)
      if (!/defined\(\s*'ABSPATH'\s*\)/.test(src)) report('AVISO', php, 0, "sem defined('ABSPATH') || exit (acesso direto ao arquivo)")
      // saída sem escape: <?= $x ?> / echo $x — use esc_html/esc_attr/esc_url, fw_* ou wp_kses_post
      for (const m of src.matchAll(/(<\?=|\becho)\s*\$(?!this\b)[\w[\]'"-]+\s*(\?>|;)/g)) {
        const line = src.split('\n')[lineOf(src, m.index) - 1]
        if (!/phpcs:ignore/.test(line)) report('ERRO', php, lineOf(src, m.index), 'saída sem escape — use esc_html()/esc_attr()/esc_url(), fw_attrs()/fw_cn()/fw_img() ou wp_kses_post() (HTML confiável: comente com phpcs:ignore e o motivo)')
      }
      for (const m of src.matchAll(/<img\b[^>]*>/g)) {
        const tag = m[0]
        const at = lineOf(src, m.index)
        if (!/\bwidth=/.test(tag) || !/\bheight=/.test(tag)) report('AVISO', php, at, '<img> sem width/height (CLS) — use fw_img($imagem, [...])')
        if (!/loading=|fetchpriority=/i.test(tag)) report('AVISO', php, at, '<img> sem loading="lazy" (ou fetchpriority="high" no hero)')
      }
      for (const m of src.matchAll(/style="/g)) {
        const line = src.split('\n')[lineOf(src, m.index) - 1]
        if (!/background-image|--/.test(line)) report('AVISO', php, lineOf(src, m.index), 'style inline — use classe Tailwind (inline só para valor dinâmico)')
      }
      for (const m of src.matchAll(/class="[^"]*[\w:-]-<\?=/g)) report('AVISO', php, lineOf(src, m.index), 'classe montada por pedaço (bg-<?= … ?>) — o Tailwind não gera; use mapa de classes completas ou fw_cn()')
      for (const m of src.matchAll(/\[#([0-9a-fA-F]{3,8})\]/g)) {
        const hex = m[1].toLowerCase()
        if (!hexBySection.has(hex)) hexBySection.set(hex, new Set())
        hexBySection.get(hex).add(`${slug}/${name}`)
      }
      const h1 = (src.match(/<h1\b/g) || []).length
      if (h1 && name !== 'hero' && !/^hero|-hero$/.test(name)) report('AVISO', php, 0, '<h1> fora do hero — confira se a página não terá dois h1')
      const code = src.replace(/\/\*[\s\S]*?\*\/|<!--[\s\S]*?-->/g, '') // diretiva citada em comentário (PHPDoc) não conta
      if (/data-wp-on|data-wp-bind|data-wp-class|data-wp-each|data-wp-text/.test(code) && !/data-wp-interactive=/.test(code)) report('ERRO', php, 0, 'diretivas data-wp-* sem data-wp-interactive="upwork/…" no elemento de fora')
    }

    const store = read(storeFile)
    if (store) {
      const ns = (store.match(/store\(\s*'([^']+)'/) || [])[1]
      if (!ns) report('ERRO', storeFile, 0, "store sem store('upwork/…', { … }) de @wordpress/interactivity")
      else {
        if (!ns.startsWith('upwork/')) report('AVISO', storeFile, 0, `namespace "${ns}" — use upwork/${slug}-${name}`)
        if (src && !src.includes(`data-wp-interactive="${ns}"`)) report('ERRO', php, 0, `${name}.store.js registra "${ns}", mas o template não tem data-wp-interactive="${ns}"`)
      }
      for (const m of store.matchAll(/from 'jquery'|\$\(|jQuery/g)) report('ERRO', storeFile, lineOf(store, m.index), 'jQuery em store')
      for (const m of store.matchAll(/\.innerHTML\s*=/g)) report('AVISO', storeFile, lineOf(store, m.index), 'innerHTML no store — deixe o DOM com as diretivas (data-wp-text, data-wp-each…)')
    }
  }

  for (const s of sections.filter((x) => !x.layout)) {
    const kebab = s.snake.replace(/_/g, '-')
    if (!isShared && !sectionDirs.includes(kebab)) report('ERRO', FIELDS, lineOf(acf, s.index), `Fields::section('${slug}', '${s.snake}') sem pasta template-parts/includes/${slug}/${kebab}/`)
  }

  // ── URLs do Figma em qualquer arquivo do módulo/seções ───────
  const scan = [dir, join(partsDir, slug)]
  while (scan.length) {
    const p = scan.pop()
    if (!existsSync(p)) continue
    for (const f of readdirSync(p)) {
      const full = join(p, f)
      if (statSync(full).isDirectory()) { if (f !== 'assets') scan.push(full); continue }
      if (!/\.(php|js|css)$/.test(f)) continue
      const src = read(full)
      const m = src.match(FIGMA_URL)
      if (m) report('ERRO', full, lineOf(src, m.index), 'URL do Figma no código — baixe o asset para assets/')
    }
  }
}

// ── arquivo de campos: checagens globais ─────────────────────────
if (!only) {
  if (!acf) report('ERRO', FIELDS, 0, 'arquivo de campos não existe')
  const slugs = new Set(dirs(pagesDir))
  for (const c of calls) if (c.kind !== 'option' && !slugs.has(c.slug)) report('ERRO', FIELDS, lineOf(acf, c.index), `Fields::${c.kind}('${c.slug}', …) — não existe template-pages/${c.slug}/`)
  // Opções do Tema: mesmas regras por campo (instructions, botões, listas, imagens…)
  linkNames = new Set()
  for (const c of calls.filter((x) => x.kind === 'option')) {
    checkFields(acf.slice(c.index, callEnd(c.index)), c.index, `field_opcoes_${c.name}_`)
  }
  for (const m of acf.matchAll(/acf_add_local_field_group\s*\(/g)) report('ERRO', FIELDS, lineOf(acf, m.index), 'grupo ACF registrado direto — use Fields::section() (seção), Fields::postType() ou Fields::taxonomy()')
  if (acf && !/add_action\(\s*'acf\/init'/.test(acf)) report('ERRO', FIELDS, 0, "campos fora do add_action('acf/init', …) — o ACF ainda não carregou")
}

// ── stores: api('/rota…') precisa de um método com #[Get] que case a rota ─────────────
if (!only) {
  const toRegex = (route) => new RegExp('^' + route.replace(/\(\?P<\w+>[^)]*\)/g, '[^/]+').replace(/:\w+\?/g, '[^/]*').replace(/:\w+/g, '[^/]+') + '$', 'i')
  const routes = []
  for (const slug of dirs(pagesDir)) {
    const src = read(join(pagesDir, slug, `${slug}.controller.php`))
    for (const m of src.matchAll(/#\[(?:Get|Route)\(\s*'([^']+)'/g)) routes.push(toRegex(m[1]))
  }

  const stores = []
  const walk = (dir) => {
    for (const d of existsSync(dir) ? readdirSync(dir) : []) {
      const f = join(dir, d)
      if (statSync(f).isDirectory()) walk(f)
      else if (d.endsWith('.store.js')) stores.push(f)
    }
  }
  walk(partsDir)
  for (const file of stores) {
    const src = read(file)
    for (const m of src.matchAll(/\bapi\(\s*([`'"])(\/[^`'"]*)\1/g)) {
      const path = m[2].replace(/\$\{[^}]*\}/g, '1').split('?')[0]
      if (!routes.some((r) => r.test(path))) report('ERRO', file, lineOf(src, m.index), `api('${m[2]}') — nenhum controller tem #[Get] para essa rota (os dados da página vêm do page(), sem REST): crie um método com #[Get('…')]`)
    }
  }
}

for (const [hex, where] of hexBySection) {
  if (where.size >= 2) findings.push({ level: 'AVISO', at: '-', msg: `cor #${hex} repetida em ${where.size} seções (${[...where].join(', ')}) — vire token em src/sass/main.scss` })
}

findings.sort((a, b) => (a.level === b.level ? a.at.localeCompare(b.at) : a.level === 'ERRO' ? -1 : 1))
for (const f of findings) console.log(`[${f.level}] ${f.at} — ${f.msg}`)

const errors = findings.filter((f) => f.level === 'ERRO').length
console.log(`\n${errors} erro(s), ${findings.length - errors} aviso(s).`)
process.exit(errors ? 1 : 0)
