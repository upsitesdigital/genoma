#!/usr/bin/env node
/**
 * Portão de uso da janela de 5 h do Claude — pausa o trabalho em 90% e libera
 * depois do reset. Lê ~/.claude/usage-limits.json, gravado pelo usage-statusline.mjs.
 *
 *   node .claude/scripts/usage-gate.mjs          → 1 linha: SEGUIR | PAUSAR | SEM-DADOS
 *   node .claude/scripts/usage-gate.mjs --json   → { status, used, resetsAt, waitSeconds, … }
 *   node .claude/scripts/usage-gate.mjs --hook   → hook PostToolUse: avisa o agente (1x por janela)
 *
 * Limite: CLAUDE_USAGE_PAUSE_AT (padrão 90). Depois do resets_at a janela é nova:
 * libera mesmo que o arquivo ainda mostre o uso antigo.
 */
import { readFileSync, writeFileSync } from 'node:fs'
import { homedir } from 'node:os'
import { join } from 'node:path'

const STATE = join(homedir(), '.claude', 'usage-limits.json')
const WARNED = join(homedir(), '.claude', 'usage-gate-warned.json')
const LIMIT = Number(process.env.CLAUDE_USAGE_PAUSE_AT || 90)
const MARGIN = 60 // s depois do reset, para a janela nova já valer

const read = (f) => { try { return JSON.parse(readFileSync(f, 'utf8')) } catch { return null } }
const hhmm = (s) => new Date(s * 1000).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })

export function gate(now = Math.floor(Date.now() / 1000)) {
  const s = read(STATE)
  const w = s?.fiveHour
  if (!w || !Number.isFinite(w.used)) {
    return { status: 'SEM-DADOS', limit: LIMIT, note: 'status line ainda não gravou o uso (precisa de assinatura Pro/Max e de uma resposta na sessão)' }
  }
  const reset = w.resetsAt
  if (now >= reset) return { status: 'SEGUIR', used: 0, resetsAt: reset, limit: LIMIT, note: `janela nova desde ${hhmm(reset)}` }
  if (w.used >= LIMIT) {
    return { status: 'PAUSAR', used: w.used, resetsAt: reset, waitSeconds: reset - now + MARGIN, limit: LIMIT, note: `retomar às ${hhmm(reset + MARGIN)}` }
  }
  return { status: 'SEGUIR', used: w.used, resetsAt: reset, limit: LIMIT, updatedAt: s.updatedAt, note: `reseta ${hhmm(reset)}` }
}

const args = process.argv.slice(2)
const g = gate()

if (args.includes('--hook')) {
  // hook PostToolUse: só fala quando passa do limite, uma vez por janela
  process.stdin.resume()
  process.stdin.on('data', () => {})
  process.stdin.on('end', () => {
    if (g.status !== 'PAUSAR' || read(WARNED)?.resetsAt === g.resetsAt) return
    try { writeFileSync(WARNED, JSON.stringify({ resetsAt: g.resetsAt })) } catch {}
    process.stdout.write(JSON.stringify({
      hookSpecificOutput: {
        hookEventName: 'PostToolUse',
        additionalContext:
          `[uso do Claude] A janela de 5 h chegou a ${Math.round(g.used)}% (limite ${g.limit}%). ` +
          `Termine só o passo em andamento num ponto seguro, registre o estado (checklist/CONTEXTO-IA) ` +
          `e PAUSE — não comece item novo. Retomar às ${hhmm(g.resetsAt + MARGIN)} ` +
          `(node .claude/scripts/usage-gate.mjs devolve SEGUIR quando liberar).`,
      },
    }))
  })
} else if (args.includes('--json')) {
  process.stdout.write(JSON.stringify(g) + '\n')
} else {
  const pct = g.used !== undefined ? ` ${Math.round(g.used)}%` : ''
  const wait = g.waitSeconds ? ` · aguardar ${g.waitSeconds}s` : ''
  process.stdout.write(`${g.status}${pct} · ${g.note}${wait}\n`)
}
