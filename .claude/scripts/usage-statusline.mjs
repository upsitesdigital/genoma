#!/usr/bin/env node
/**
 * Status line do Claude Code: mostra o uso da janela de 5 h e grava em
 * ~/.claude/usage-limits.json para o usage-gate.mjs (pausa em 90%).
 *
 * O Claude Code manda o JSON da sessão no stdin; `rate_limits` só existe em
 * assinatura Pro/Max e depois da primeira resposta da sessão.
 */
import { mkdirSync, writeFileSync } from 'node:fs'
import { homedir } from 'node:os'
import { dirname, join } from 'node:path'

const STATE = join(homedir(), '.claude', 'usage-limits.json')

let input = ''
process.stdin.setEncoding('utf8')
process.stdin.on('data', (d) => (input += d))
process.stdin.on('end', () => {
  let data = {}
  try { data = JSON.parse(input || '{}') } catch {}

  const five = data.rate_limits?.five_hour
  const week = data.rate_limits?.seven_day
  if (five || week) {
    const state = {
      updatedAt: Math.floor(Date.now() / 1000),
      fiveHour: five ? { used: Number(five.used_percentage), resetsAt: Number(five.resets_at) } : null,
      sevenDay: week ? { used: Number(week.used_percentage), resetsAt: Number(week.resets_at) } : null,
    }
    try {
      mkdirSync(dirname(STATE), { recursive: true })
      writeFileSync(STATE, JSON.stringify(state))
    } catch {}
  }

  const hhmm = (s) => new Date(s * 1000).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })
  const parts = [data.model?.display_name, data.workspace?.current_dir?.split(/[\\/]/).pop()].filter(Boolean)
  if (five) parts.push(`5h ${Math.round(five.used_percentage)}% · reseta ${hhmm(five.resets_at)}`)
  if (week && week.used_percentage >= 50) parts.push(`semana ${Math.round(week.used_percentage)}%`)
  process.stdout.write(parts.join(' │ '))
})
