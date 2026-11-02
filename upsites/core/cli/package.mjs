#!/usr/bin/env node
/**
 * Gera dist/{tema}.zip pronto para subir no servidor (Aparência → Temas → Enviar, ou
 * descompactar em wp-content/themes/). Leva o tema com o build (assets/) e deixa de
 * fora o que é só de desenvolvimento.
 *
 *   npm run package             → npm run build + zip
 *   npm run package -- --no-build   → só o zip (o build já foi feito)
 *
 * assets/ não é versionado: o deploy usa este zip (ou o artefato da pipeline do GitHub).
 */
import { existsSync, lstatSync, mkdirSync, mkdtempSync, readdirSync, rmSync, cpSync, statSync } from 'node:fs';
import { basename, join, relative, resolve, sep } from 'node:path';
import { execSync, spawnSync } from 'node:child_process';
import { tmpdir } from 'node:os';

// no Windows, o tar.exe do sistema (bsdtar, lê/grava zip) — o do Git Bash é GNU tar e não lê zip
const TAR = process.platform === 'win32' ? join(process.env.SystemRoot ?? 'C:/Windows', 'System32', 'tar.exe') : 'tar';

const ROOT = resolve(import.meta.dirname, '../../..');
const NAME = basename(ROOT);
/*
 * Lista do que ENTRA (não do que sai): arquivo novo na raiz (.env, notas, configs de
 * editor) nunca vai para o servidor por engano.
 */
const INCLUDE_DIRS = new Set(['assets', 'template-pages', 'template-parts', 'upsites', 'languages']);
// readme.txt: formato de tema do WP.org (licenças: tema, ACF PRO embarcado, fontes, screenshot)
const INCLUDE_FILE = /^(style\.css|readme\.txt|screenshot\.(png|jpe?g|webp)|theme\.json)$/;
// .php da raiz: só nomes da hierarquia de templates do WP. Outro .php (phpinfo.php, teste.php,
// adminer.php, cópia de wp-config.php…) ABORTA o pacote — nunca vai para o servidor calado.
const THEME_PHP = /^(functions|header|footer|sidebar|index|page|single|singular|archive|search|searchform|404|category|tag|taxonomy|author|date|home|front-page|attachment|comments|embed|privacy-policy|woocommerce)(-[\w-]+)?\.php$/;
// dentro das pastas: sem CLI/testes do framework, fontes TS/SCSS (já compiladas), sourcemaps,
// markdown e dotfiles; nem o readme.txt do ACF PRO embarcado — só repete a licença (já citada no
// readme.txt do tema) e expõe a versão ("Stable tag") a quem procura uma vulnerável
const SKIP_PATH = [join('upsites', 'core', 'cli'), join('upsites', 'core', 'tests'), join('upsites', 'core', 'acf', 'readme.txt')];
const SKIP_FILE = /\.(tsx?|scss|map|md)$/;

if (!process.argv.includes('--no-build')) {
  execSync('npm run build', { cwd: ROOT, stdio: 'inherit', env: { ...process.env, FW_NO_SOURCEMAPS: '1' } });
}

if (!existsSync(join(ROOT, 'assets/css/main.min.css'))) {
  console.error('assets/css/main.min.css não existe — rode npm run build.');
  process.exit(1);
}

const strayPhp = readdirSync(ROOT).filter((f) => f.endsWith('.php') && !THEME_PHP.test(f));
if (strayPhp.length) {
  console.error(`PHP na raiz que não é template do tema: ${strayPhp.join(', ')} — apague ou mova antes de empacotar.`);
  process.exit(1);
}

// cópia limpa numa pasta temporária própria (mkdtemp: nome imprevisível, criada vazia)
const stage = mkdtempSync(join(tmpdir(), 'theme-package-'));
const themeDir = join(stage, NAME);
mkdirSync(themeDir);
for (const entry of readdirSync(ROOT, { withFileTypes: true })) {
  if (entry.isSymbolicLink()) continue;
  const ok = entry.isDirectory() ? INCLUDE_DIRS.has(entry.name) : INCLUDE_FILE.test(entry.name) || THEME_PHP.test(entry.name);
  if (!ok) continue;
  cpSync(join(ROOT, entry.name), join(themeDir, entry.name), {
    recursive: true,
    filter: (src) => {
      const rel = relative(ROOT, src);
      const base = basename(src);
      // symlink levaria para o zip um arquivo de fora do tema
      if (lstatSync(src).isSymbolicLink()) return false;
      if (SKIP_PATH.some((p) => rel === p || rel.startsWith(p + sep))) return false;
      if (base.startsWith('.')) return false;
      return !SKIP_FILE.test(base) || !statSync(src).isFile();
    },
  });
}

const out = join(ROOT, 'dist', `${NAME}.zip`);
mkdirSync(join(ROOT, 'dist'), { recursive: true });
rmSync(out, { force: true });

// bsdtar (Windows 10+, macOS) cria zip com -a; no Linux (GNU tar não grava zip) usa o zip
const tar = process.platform === 'linux' ? null : spawnSync(TAR, ['-a', '-cf', out, NAME], { cwd: stage, stdio: 'ignore' });
if (!tar || tar.status !== 0 || !existsSync(out)) {
  rmSync(out, { force: true });
  const zip = spawnSync('zip', ['-qry', out, NAME], { cwd: stage, stdio: 'inherit' }); // -y: não segue symlink
  if (zip.status !== 0) {
    console.error('Não foi possível criar o zip (nem tar -a nem zip disponíveis).');
    process.exit(1);
  }
}

rmSync(stage, { recursive: true, force: true });
console.log(`Tema empacotado: dist/${NAME}.zip`);
