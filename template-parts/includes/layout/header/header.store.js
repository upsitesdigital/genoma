/**
 * Header (template-parts/includes/layout/header/header.php).
 * - `context.open` (no <header>): menu mobile em tela cheia — trava a rolagem da página, Esc fecha
 *   e devolve o foco ao botão, Tab fica preso no header, clicar num link fecha (âncora na mesma página).
 * - `context.scrolled`: a página saiu do topo → data-scrolled="true" (fundo e altura no desktop).
 * - `context.sub` (em cada <li> com filhos): submenu — dropdown no desktop (fecha com Esc, clique
 *   fora ou quando o foco sai do item), acordeão no menu mobile.
 */
import { getContext, getElement, store } from '@wordpress/interactivity'

const FOCUSABLE = 'a[href], button:not([disabled])'
let locked = false

store('upwork/layout-header', {
  actions: {
    toggle() {
      const ctx = getContext()
      ctx.open = !ctx.open
    },
    close() {
      getContext().open = false
    },
    closeOnLink(event) {
      if (event.target instanceof Element && event.target.closest('a')) getContext().open = false
    },
    onKeydown(event) {
      const ctx = getContext()
      const { ref } = getElement()
      if (!ctx.open || !ref) return
      if (event.key === 'Escape') {
        ctx.open = false
        ref.querySelector('[aria-controls="fw-mobile-menu"]')?.focus()
        return
      }
      if (event.key !== 'Tab') return
      // foco preso: só o que está visível no header (barra + menu aberto)
      const items = [...ref.querySelectorAll(FOCUSABLE)].filter((el) => el.getClientRects().length)
      const first = items[0]
      const last = items[items.length - 1]
      const active = document.activeElement
      if (!first) return
      if (!ref.contains(active) || (event.shiftKey ? active === first : active === last)) {
        event.preventDefault()
        ;(event.shiftKey ? last : first).focus()
      }
    },
    toggleSub() {
      const ctx = getContext()
      ctx.sub = !ctx.sub
    },
    closeSubOnEscape(event) {
      const ctx = getContext()
      if (event.key !== 'Escape' || !ctx.sub) return
      ctx.sub = false
      // devolve o foco ao botão se ele estava dentro do submenu
      const { ref } = getElement()
      if (ref?.contains(document.activeElement)) ref.querySelector('button')?.focus()
    },
    closeSubOutside(event) {
      const ctx = getContext()
      if (!ctx.sub) return
      const { ref } = getElement()
      if (ref && !ref.contains(event.target)) ctx.sub = false
    },
    closeSubOnFocusOut(event) {
      const ctx = getContext()
      if (!ctx.sub) return
      const { ref } = getElement()
      if (ref && event.relatedTarget && !ref.contains(event.relatedTarget)) ctx.sub = false
    },
  },
  callbacks: {
    syncScroll() {
      getContext().scrolled = window.scrollY > 0
    },
    // menu mobile aberto: a página atrás não rola
    lockScroll() {
      const { open } = getContext()
      if (open === locked) return
      locked = open
      document.body.style.overflow = open ? 'hidden' : ''
    },
    // menu aberto e a janela passou para o desktop (onde ele não existe): fecha e solta a rolagem
    closeOnDesktop() {
      const ctx = getContext()
      if (ctx.open && window.matchMedia('(min-width: 1024px)').matches) ctx.open = false
    },
  },
})
