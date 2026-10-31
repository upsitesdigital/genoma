#!/usr/bin/env node
/**
 * PHPCS com as regras de segurança/i18n do WordPress (phpcs.xml.dist).
 *   npm run lint:wpcs     (precisa de `composer install` uma vez — instala o PHPCS + WPCS em vendor/)
 */
import { existsSync } from 'node:fs'
import { join } from 'node:path'
import { spawnSync } from 'node:child_process'
import { root } from './make-section.mjs'

const bin = join(root, 'vendor', 'bin', process.platform === 'win32' ? 'phpcs.bat' : 'phpcs')
if (!existsSync(bin)) {
  console.error('PHPCS não instalado — rode `composer install` na raiz do tema (uma vez).')
  process.exit(1)
}
// shell no Windows: o caminho do .bat entre aspas (pasta com espaço quebraria o comando)
const r = spawnSync(process.platform === 'win32' ? `"${bin}"` : bin, ['--report=emacs', '-q', process.platform === 'win32' ? `"--basepath=${root}"` : `--basepath=${root}`], { cwd: root, stdio: 'inherit', shell: process.platform === 'win32' })
if (r.status === 0) console.log('PHPCS (WordPress: segurança + i18n): 0 problemas')
process.exit(r.status ?? 1)
