#!/usr/bin/env node
/**
 * Atualiza o ACF PRO embarcado (upsites/core/acf/). A cópia do tema não se atualiza
 * sozinha (o aviso de update fica desligado), então rode isto a cada release do ACF PRO.
 *
 *   npm run acf:version                       → versão embarcada
 *   npm run acf:update -- <pasta-ou-zip>       → mostra o que muda (não grava)
 *   npm run acf:update -- <pasta-ou-zip> --apply
 *
 * <pasta-ou-zip>: o advanced-custom-fields-pro.zip baixado da conta ACF (ou a pasta já
 * extraída, que tenha acf.php). A pasta upsites/core/acf é trocada por inteiro, e de
 * lang/ ficam só as traduções de LOCALES.
 * Depois: npm run check, abrir uma página no wp-admin e testar campos/repeaters.
 */
import { cpSync, existsSync, lstatSync, mkdtempSync, readFileSync, readdirSync, renameSync, rmSync, statSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join, resolve, sep } from 'node:path';
import { spawnSync } from 'node:child_process';

// no Windows, o tar.exe do sistema (bsdtar, lê/grava zip) — o do Git Bash é GNU tar e não lê zip
const TAR = process.platform === 'win32' ? join(process.env.SystemRoot ?? 'C:/Windows', 'System32', 'tar.exe') : 'tar';

const ROOT = resolve(import.meta.dirname, '../../..');
const TARGET = join(ROOT, 'upsites/core/acf');
const LOCALES = ['pt_BR']; // traduções do ACF mantidas em lang/

const version = (dir) => {
  const file = join(dir, 'acf.php');
  if (!existsSync(file)) return null;
  const head = readFileSync(file, 'utf8').slice(0, 2000);
  return {
    version: head.match(/^\s*\*\s*Version:\s*(\S+)/m)?.[1] ?? '?',
    pro: /Plugin Name:\s*Advanced Custom Fields PRO/i.test(head),
  };
};

const current = version(TARGET);
const [source, ...flags] = process.argv.slice(2);

if (!source) {
  console.log(current ? `ACF embarcado: ${current.version}${current.pro ? ' (PRO)' : ''}` : 'ACF embarcado não encontrado em upsites/core/acf');
  process.exit(0);
}

let dir = resolve(source);
let tmp = null;
if (!existsSync(dir)) {
  console.error(`Não encontrado: ${dir}`);
  process.exit(1);
}

// .zip → extrai numa pasta temporária (bsdtar no Windows/macOS; unzip no Linux)
if (statSync(dir).isFile()) {
  tmp = mkdtempSync(join(tmpdir(), 'acf-'));
  const ok = spawnSync(TAR, ['-xf', dir, '-C', tmp]).status === 0
    || spawnSync('unzip', ['-q', dir, '-d', tmp]).status === 0;
  if (!ok) {
    console.error('Não foi possível extrair o zip (tar/unzip).');
    process.exit(1);
  }
  dir = tmp;
}
// o zip tem uma pasta advanced-custom-fields-pro/ dentro
if (!existsSync(join(dir, 'acf.php'))) {
  const inner = readdirSync(dir).map((d) => join(dir, d)).find((d) => existsSync(join(d, 'acf.php')));
  if (inner) dir = inner;
}

const next = version(dir);
const cleanup = () => tmp && rmSync(tmp, { recursive: true, force: true });

if (!next) {
  console.error('A origem não tem acf.php — use o zip do ACF PRO ou a pasta extraída.');
  cleanup();
  process.exit(1);
}
if (!next.pro) {
  console.error('A origem não é o ACF PRO (o tema usa repeater/clone/options page, que só existem no PRO).');
  cleanup();
  process.exit(1);
}

console.log(`ACF PRO: ${current?.version ?? 'nenhum'} → ${next.version}`);

if (!flags.includes('--apply')) {
  console.log('Nada gravado. Rode de novo com --apply para trocar upsites/core/acf.');
  cleanup();
  process.exit(0);
}

// origem dentro do destino (ou o contrário) → apagar o destino apagaria a origem
const inside = (a, b) => a === b || a.startsWith(b + sep);
const src = resolve(dir);
if (inside(src, TARGET) || inside(TARGET, src)) {
  console.error('A origem é a própria pasta upsites/core/acf (ou a contém) — use o zip baixado ou uma pasta fora do tema.');
  cleanup();
  process.exit(1);
}

// o ACF oficial não tem symlink: um no zip apontaria para fora do tema (e iria para o deploy)
const findLink = (d) => {
  for (const e of readdirSync(d, { withFileTypes: true })) {
    const p = join(d, e.name);
    if (lstatSync(p).isSymbolicLink()) return p;
    if (e.isDirectory()) { const hit = findLink(p); if (hit) return hit; }
  }
  return null;
};
const link = findLink(src);
if (link) {
  console.error(`A origem tem um link simbólico (${link}) — não é o zip oficial do ACF PRO. Nada foi alterado.`);
  cleanup();
  process.exit(1);
}

// troca segura: copia ao lado, renomeia, e só então apaga a versão antiga
const fresh = `${TARGET}.new`;
const old = `${TARGET}.old`;
rmSync(fresh, { recursive: true, force: true });
rmSync(old, { recursive: true, force: true });
cpSync(src, fresh, { recursive: true });
// traduções: só as de LOCALES (o painel dos sites é em pt_BR) — as outras ~50 somam ~9 MB no deploy
for (const dir of [join(fresh, 'lang'), join(fresh, 'lang', 'pro')]) {
  if (!existsSync(dir)) continue;
  for (const e of readdirSync(dir, { withFileTypes: true })) {
    if (e.isFile() && e.name !== 'index.php' && !LOCALES.some((l) => e.name.includes(`-${l}.`))) rmSync(join(dir, e.name));
  }
}
if (existsSync(TARGET)) renameSync(TARGET, old);
renameSync(fresh, TARGET);
rmSync(old, { recursive: true, force: true });
cleanup();
console.log(`upsites/core/acf atualizado para ${next.version}. Agora: npm run check e teste os campos no wp-admin.`);
