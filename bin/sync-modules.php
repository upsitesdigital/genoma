#!/usr/bin/env php
<?php

/**
 * fw:sync-modules
 * Varre app/*\/ e regenera resources/module-registry.ts.
 *
 * Uso: composer fw:sync-modules
 */

$themeDir = dirname(__DIR__);
$appDir   = $themeDir . '/app';
$entries  = [];

foreach (glob($appDir . '/*/') ?: [] as $dir) {
    $slug     = basename($dir);
    $viewFile = "{$dir}{$slug}.view.tsx";

    if (!file_exists($viewFile)) continue;

    // Lê a rota declarada no #[Module(route: '...')] do module.php
    $route      = '/' . $slug;
    $moduleFile = "{$dir}{$slug}.module.php";

    if (file_exists($moduleFile)) {
        $content = file_get_contents($moduleFile);
        if (preg_match("/route:\s*'([^']+)'/", $content, $m)) {
            $route = $m[1];
        }
    }

    $entries[$slug] = $route;
}

// Gera o module-registry.ts
$lines   = [];
$lines[] = "import { lazy, type ComponentType } from 'react'";
$lines[] = '';
$lines[] = 'interface ModuleEntry {';
$lines[] = '  path: string';
$lines[] = '  component: ReturnType<typeof lazy<ComponentType>>';
$lines[] = '}';
$lines[] = '';
$lines[] = 'export const modules: Record<string, ModuleEntry> = {';

foreach ($entries as $slug => $route) {
    $lines[] = "  '{$slug}': {";
    $lines[] = "    path: '{$route}',";
    $lines[] = "    component: lazy(() => import('@/../app/{$slug}/{$slug}.view')),";
    $lines[] = '  },';
}

$lines[] = '}';
$lines[] = '';

file_put_contents($themeDir . '/resources/module-registry.ts', implode("\n", $lines));

$count = count($entries);
$names = implode(', ', array_keys($entries));
echo "\033[32m✓\033[0m module-registry.ts atualizado — {$count} módulo(s): {$names}\n";
