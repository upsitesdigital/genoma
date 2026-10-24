import { useEffect } from 'react'

/**
 * Âncoras vindas de outra página (ex.: /veterinarios/#tipos-de-exames).
 *
 * No carregamento o navegador tenta rolar até o id antes de o React montar a
 * view, então a seção ainda não existe e nada acontece. Aqui esperamos o
 * elemento aparecer no DOM e rolamos até ele; enquanto imagens/dados acima
 * ainda alteram a altura da página, reposicionamos — até o usuário rolar por
 * conta própria ou o tempo limite acabar. O desconto do header fixo vem do
 * `scroll-padding-top` do <html> (globals.css).
 */

const TIMEOUT = 5000
const USER_EVENTS = ['wheel', 'touchstart', 'keydown', 'mousedown'] as const

export function useHashScroll() {
  useEffect(() => {
    const id = decodeURIComponent(window.location.hash.slice(1))
    if (!id) return

    let target: HTMLElement | null = null
    let done = false

    const scroll = () => target?.scrollIntoView({ behavior: 'auto', block: 'start' })

    const stop = () => {
      if (done) return
      done = true
      mutationObserver.disconnect()
      resizeObserver.disconnect()
      clearTimeout(timer)
      for (const ev of USER_EVENTS) window.removeEventListener(ev, stop)
    }

    // Ao achar o alvo, rola; mudanças de altura depois disso reposicionam
    const find = () => {
      if (target) return
      target = document.getElementById(id)
      if (target) scroll()
    }

    const mutationObserver = new MutationObserver(find)
    const resizeObserver = new ResizeObserver(() => target && scroll())
    const timer = setTimeout(stop, TIMEOUT)

    mutationObserver.observe(document.body, { childList: true, subtree: true })
    resizeObserver.observe(document.body)
    for (const ev of USER_EVENTS) window.addEventListener(ev, stop, { passive: true })
    find()

    return stop
  }, [])
}
