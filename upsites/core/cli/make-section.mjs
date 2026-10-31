#!/usr/bin/env node
/**
 * Cria o esqueleto de uma seção em template-parts/includes/{slug}/{section-name}/
 *
 *   npm run make:section -- home about-us
 *   npm run make:section -- home faq --store     (+ faq.store.js: estado/ações da Interactivity API)
 *
 * section-name: sempre em inglês, kebab-case (ex: hero, about-us, exam-list).
 * Encaixar a seção no {slug}.view.php e no controller é feito à parte (t800/t1000).
 */
import { existsSync, mkdirSync, writeFileSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath, pathToFileURL } from 'node:url'

export const root = join(dirname(fileURLToPath(import.meta.url)), '..', '..', '..')

export const isKebab = (s) => /^[a-z][a-z0-9]*(-[a-z0-9]+)*$/.test(s)
export const toPascal = (s) => s.split('-').map((p) => p[0].toUpperCase() + p.slice(1)).join('')
export const toCamel = (s) => s.replace(/-([a-z0-9])/g, (_, c) => c.toUpperCase())

/** Nomes derivados de uma seção — o mesmo padrão que t800/t1000 seguem. */
export function sectionNames(slug, section) {
  return {
    dir: section,
    key: toCamel(section), // chave no retorno do controller ($data['{key}'] no view)
    acf: section.replace(/-/g, '_'), // prefixo de keys/names ACF
    part: `${slug}/${section}`, // Part::render('{part}', …)
    store: `upwork/${slug}-${section}`, // namespace do store (data-wp-interactive)
  }
}

export function makeSection(slug, section, { title = toPascal(section), heading = 'h2', store = false, log = true } = {}) {
  if (!isKebab(slug) || !isKebab(section)) {
    throw new Error('slug e section-name devem ser kebab-case (section-name em inglês). Ex: home about-us')
  }

  const dir = join(root, 'template-parts', 'includes', slug, section)
  if (existsSync(dir)) throw new Error(`template-parts/includes/${slug}/${section}/ já existe.`)

  const n = sectionNames(slug, section)
  // título dentro de comentário PHP de uma linha
  const label = String(title).replace(/\s+/g, ' ').replace(/\*\//g, '* /').trim()
  const interactive = store
    ? `
  data-wp-interactive="${n.store}"
  <?= wp_interactivity_data_wp_context(['open' => false]) ?>`
    : ''
  const files = {
    [`${section}.php`]: `<?php
/**
 * ${label}. Dados: controller do módulo → '${n.key}' (null = "Ocultar seção" → nem chega aqui).
 *
 * @var array{titulo: string|null} $data titulo null = o editor apagou o texto: o elemento não aparece.
 */

defined('ABSPATH') || exit;
?>
<section id="${section}" class="py-16 md:py-24"${interactive}>
  <div class="container">
    <?php if ($data['titulo']) : ?>
      <${heading} class="text-3xl font-bold md:text-5xl"><?= esc_html($data['titulo']) ?></${heading}>
    <?php endif; ?>
  </div>
</section>
`,
    'assets/.gitkeep': '',
  }
  if (store) {
    files[`${section}.store.js`] = `/**
 * ${label} — estado e ações (Interactivity API). Enfileirado sozinho quando a seção é
 * renderizada (Part::render). Estado por instância em data-wp-context; global em
 * wp_interactivity_state('${n.store}', [...]) no PHP.
 */
import { getContext, store } from '@wordpress/interactivity'

store('${n.store}', {
  actions: {
    toggle() {
      const ctx = getContext()
      ctx.open = !ctx.open
    },
  },
})
`
  }

  for (const [file, content] of Object.entries(files)) {
    const path = join(dir, file)
    mkdirSync(dirname(path), { recursive: true })
    writeFileSync(path, content)
    if (log) console.log(`  criado template-parts/includes/${slug}/${section}/${file}`)
  }

  return n
}

if (process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href) {
  const args = process.argv.slice(2)
  const [slug, section] = args.filter((a) => !a.startsWith('--'))
  try {
    const n = makeSection(slug, section, { store: args.includes('--store') })
    console.log(`
Seção criada. Para encaixar:
  view       → template-pages/${slug}/${slug}.view.php, na ordem da página:
               <?php Part::render('${n.part}', $data['${n.key}']); ?>
  campos     → upsites/customizer-register/inc/acf_fields.php, no bloco da página:
               Fields::section('${slug}', '${n.acf}', '{Label}', [ campos field_${slug.replace(/-/g, '_')}_${n.acf}_{nome} / name ${n.acf}_{nome} ], order: N)
  controller → '${n.key}' => $this->section($pageId, '${n.acf}', fn(): array => [ ... ])`)
  } catch (e) {
    console.error(e.message)
    console.error('Uso: npm run make:section -- <slug> <section-name-em-ingles> [--store]')
    process.exit(1)
  }
}
