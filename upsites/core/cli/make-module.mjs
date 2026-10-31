#!/usr/bin/env node
/**
 * Cria um módulo em template-pages/{slug}/ com a primeira seção (hero) em
 * template-parts/includes/{slug}/hero/
 *
 *   npm run make:module -- quem-somos "Quem somos"
 *
 * A view ({slug}.view.php) é encontrada pelo View::render() pelo slug — não há registro manual.
 * Campos ACF: o bloco da página é acrescentado em upsites/customizer-register/inc/acf_fields.php.
 * Novas seções: npm run make:section -- {slug} {section-name-em-ingles}
 */
import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { isKebab, makeSection, root, toPascal } from './make-section.mjs'

const [slug, ...nameParts] = process.argv.slice(2)

if (!slug || !isKebab(slug)) {
  console.error('Uso: npm run make:module -- <slug-kebab-case> ["Nome do módulo"]')
  process.exit(1)
}

const pascal = toPascal(slug)
// nome numa linha só e sem fechamento de comentário (vai num /* … */ do acf_fields.php)
const name = (nameParts.join(' ') || pascal.replace(/([a-z])([A-Z])/g, '$1 $2')).replace(/\s+/g, ' ').replace(/\*\//g, '* /').trim()
const key = slug.replace(/-/g, '_')
// nome dentro de string PHP entre aspas simples
const phpName = name.replace(/\\/g, '\\\\').replace(/'/g, "\\'")

const dir = join(root, 'template-pages', slug)

if (existsSync(dir) || existsSync(join(root, 'template-parts', 'includes', slug, 'hero'))) {
  console.error(`template-pages/${slug}/ ou template-parts/includes/${slug}/hero/ já existe.`)
  process.exit(1)
}

const files = {
  [`${slug}.module.php`]: `<?php
declare(strict_types=1);

namespace App\\${pascal};

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

use Core\\Framework\\Module;
use Core\\Framework\\Attributes\\Module as Mod;

#[Mod(slug: '${slug}', name: '${phpName}', route: '/${slug}', template: true, templateLabel: 'Página · ${phpName}')]
final class ${pascal}Module extends Module
{
    // Campos ACF: upsites/customizer-register/inc/acf_fields.php
}
`,
  [`${slug}.controller.php`]: `<?php
declare(strict_types=1);

namespace App\\${pascal};

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

use Core\\Framework\\Controller;
use Core\\Framework\\Attributes\\Cache;

final class ${pascal}Controller extends Controller
{
    /**
     * Dados da página — chamado direto em PHP pelo View::render (sem REST). $pageId vem do
     * WordPress (a página com o modelo deste módulo); 0 = sem página: padrões do Figma.
     * Rota REST só para o que um store busca depois: método próprio com #[Get('/${slug}/…')].
     */
    #[Cache(ttl: 300)]
    public function page(int $pageId): array
    {
        return [
            'hero' => $this->section($pageId, 'hero', fn(): array => [
                'titulo' => $this->text($pageId, 'hero_titulo', '${phpName}'),
            ]),
        ];
    }
}
`,
  [`${slug}.view.php`]: `<?php
/**
 * ${name} — compõe as seções de template-parts/includes/${slug}/ na ordem da página.
 * $data = retorno do ${pascal}Controller::page(); seção null = oculta pelo editor (Part não renderiza).
 *
 * @var array{hero: array|null} $data
 */

use Core\\Framework\\Part;

defined('ABSPATH') || exit;
?>
<main>
  <?php Part::render('${slug}/hero', $data['hero']); ?>
</main>
`,
}

for (const [file, content] of Object.entries(files)) {
  const path = join(dir, file)
  mkdirSync(dirname(path), { recursive: true })
  writeFileSync(path, content)
  console.log(`  criado template-pages/${slug}/${file}`)
}

makeSection(slug, 'hero', { title: name, heading: 'h1' })

// bloco da página no arquivo único de campos, antes do marcador final
const fieldsFile = join(root, 'upsites', 'customizer-register', 'inc', 'acf_fields.php')
const marker = '    // ↑ novas páginas'
const acf = existsSync(fieldsFile) ? readFileSync(fieldsFile, 'utf8') : ''
if (acf.includes(marker)) {
  const block = `    /* ── ${slug} — ${name} ${'─'.repeat(Math.max(3, 70 - slug.length - name.length))} */

    Fields::section('${slug}', 'hero', 'Hero', [
        ['key' => 'field_${key}_hero_titulo', 'label' => 'Título', 'name' => 'hero_titulo', 'type' => 'text',
         'instructions' => 'Título principal da página. Vazio, o título não aparece.', 'default_value' => '${phpName}'],
    ], order: 1);

`
  // função no replace: $&, $' etc. do nome não viram padrões especiais do String.replace
  writeFileSync(fieldsFile, acf.replace(marker, () => block + marker))
  console.log('  campos em upsites/customizer-register/inc/acf_fields.php')
} else {
  console.warn('  ATENÇÃO: marcador "// ↑ novas páginas" não encontrado em acf_fields.php — acrescente a Fields::section() à mão')
}

console.log(`\nMódulo "${name}" criado. No WP: Páginas → Modelo "Página · ${name}".`)
