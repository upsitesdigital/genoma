#!/usr/bin/env node
/**
 * php -l em todo PHP do tema (exceto o ACF embarcado e node_modules).
 * Rodar: npm run lint:php   (precisa do php no PATH)
 */
import { readdirSync } from 'node:fs';
import { join, relative, resolve } from 'node:path';
import { spawnSync } from 'node:child_process';

const ROOT = resolve(import.meta.dirname, '../../..');
const SKIP = new Set(['node_modules', 'vendor', 'dist', '.git', 'assets', join('upsites', 'core', 'acf')]);

const files = [];
const walk = (dir) => {
  for (const entry of readdirSync(dir, { withFileTypes: true })) {
    const path = join(dir, entry.name);
    if (SKIP.has(relative(ROOT, path))) continue;
    if (entry.isDirectory()) walk(path);
    else if (entry.name.endsWith('.php')) files.push(path);
  }
};
walk(ROOT);

let errors = 0;
for (const file of files) {
  const r = spawnSync('php', ['-l', file], { encoding: 'utf8' });
  if (r.error) {
    console.error('php não encontrado no PATH.');
    process.exit(1);
  }
  if (r.status !== 0) {
    errors++;
    console.error(`✗ ${relative(ROOT, file)}\n${(r.stdout + r.stderr).trim()}\n`);
  }
}

console.log(`php -l: ${files.length - errors}/${files.length} ok`);
process.exit(errors ? 1 : 0);
