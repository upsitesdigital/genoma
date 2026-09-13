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
$slugs    = [];

foreach (glob($appDir . '/*/') ?: [] as $dir) {
    $slug     = basename($dir);
    $viewFile = "{$dir}{$slug}.view.tsx";

    if (!file_exists($viewFile)) continue;

    $slugs[] = $slug;
}

// Gera o module-registry.ts
$lines   = [];
$lines[] = "import { lazy, type ComponentType } from 'react'";
$lines[] = '';
$lines[] = 'export const modules: Record<string, ReturnType<typeof lazy<ComponentType>>> = {';

foreach ($slugs as $slug) {
    $lines[] = "  '{$slug}': lazy(() => import('@/../app/{$slug}/{$slug}.view')),";
}

$lines[] = '}';
$lines[] = '';

file_put_contents($themeDir . '/resources/module-registry.ts', implode("\n", $lines));

$count = count($slugs);
$names = implode(', ', $slugs);
echo "\033[32m✓\033[0m module-registry.ts atualizado — {$count} módulo(s): {$names}\n";
