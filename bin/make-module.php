#!/usr/bin/env php
<?php

/**
 * fw:make:module <slug>
 * Cria os 4 arquivos de um módulo e atualiza o module-registry.ts.
 *
 * Uso: composer fw:make:module quem-somos
 */

$slug = $argv[1] ?? null;

if (!$slug) {
    echo "\033[31mErro:\033[0m Informe o slug do módulo.\n";
    echo "Uso: composer fw:make:module <slug>\n";
    exit(1);
}

if (!preg_match('/^[a-z][a-z0-9-]*$/', $slug)) {
    echo "\033[31mErro:\033[0m Slug deve ser kebab-case (ex: quem-somos).\n";
    exit(1);
}

$themeDir = dirname(__DIR__);
$dir      = $themeDir . '/app/' . $slug;

if (is_dir($dir)) {
    echo "\033[31mErro:\033[0m Módulo '{$slug}' já existe em app/{$slug}/.\n";
    exit(1);
}

mkdir($dir, 0755, true);

$pascal = implode('', array_map('ucfirst', explode('-', $slug)));

// ── module.php ───────────────────────────────────────────────────────────────
file_put_contents("{$dir}/{$slug}.module.php", <<<PHP
<?php
declare(strict_types=1);

namespace App\\{$pascal};

use Core\\Framework\\Module;
use Core\\Framework\\Attributes\\Module as Mod;

#[Mod(
    slug: '{$slug}',
    name: '{$pascal}',
    route: '/{$slug}',
    template: true,
    templateLabel: 'Página · {$pascal}',
)]
final class {$pascal}Module extends Module
{
    public function fields(): void
    {
        // acf_add_local_field_group([
        //     'key'      => 'group_{$slug}',
        //     'title'    => '{$pascal}',
        //     'location' => [[['param' => 'page_template', 'operator' => '==', 'value' => 'fw:{$slug}']]],
        //     'fields'   => [],
        // ]);
    }
}
PHP);

// ── controller.php ───────────────────────────────────────────────────────────
file_put_contents("{$dir}/{$slug}.controller.php", <<<PHP
<?php
declare(strict_types=1);

namespace App\\{$pascal};

use Core\\Framework\\Controller;
use Core\\Framework\\Attributes\\Get;
use Core\\Framework\\Attributes\\Cache;

final class {$pascal}Controller extends Controller
{
    #[Get('/{$slug}')]
    #[Get('/{$slug}/:id')]
    #[Cache(ttl: 300)]
    public function index(\\WP_REST_Request \$request): array
    {
        \$pageId = (int) (\$request->get_param('id') ?: 0);

        return [
            // 'campo' => \$this->field(\$pageId, 'nome_do_campo'),
        ];
    }
}
PHP);

// ── schema.ts ────────────────────────────────────────────────────────────────
file_put_contents("{$dir}/{$slug}.schema.ts", <<<TS
export interface {$pascal}Data {
  // Defina os tipos retornados pelo controller
}
TS);

// ── view.tsx ─────────────────────────────────────────────────────────────────
file_put_contents("{$dir}/{$slug}.view.tsx", <<<TSX
import { useModule } from '@/hooks/useModule'
import type { {$pascal}Data } from './{$slug}.schema'

export default function {$pascal}View() {
  const { data, isLoading, error } = useModule<{$pascal}Data>('{$slug}')

  if (isLoading) {
    return <div className="container py-16 text-center text-muted-foreground">Carregando...</div>
  }

  if (error || !data) {
    return <div className="container py-16 text-center text-muted-foreground">Erro ao carregar.</div>
  }

  return (
    <div className="container py-16">
      <h1 className="text-4xl font-bold">{$pascal}</h1>
    </div>
  )
}
TSX);

echo "\033[32m✓\033[0m Módulo '{$slug}' criado em app/{$slug}/\n";
echo "  → {$slug}.module.php\n";
echo "  → {$slug}.controller.php\n";
echo "  → {$slug}.view.tsx\n";
echo "  → {$slug}.schema.ts\n\n";

// Roda o sync-modules para atualizar o registry
echo "\033[33m→\033[0m Atualizando module-registry.ts...\n";
require __DIR__ . '/sync-modules.php';
