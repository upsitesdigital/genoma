/**
 * Header (template-parts/includes/layout/header/header.php).
 * Menu mobile: `context.open` (data-wp-context no <header>).
 * Submenu do desktop: `context.sub` (data-wp-context em cada <li> com filhos) — abre/fecha
 * pelo botão; fecha com Esc, clique fora ou quando o foco sai do item.
 */
import { getContext, getElement, store } from '@wordpress/interactivity'

store('upwork/layout-header', {
  actions: {
    toggle() {
      const ctx = getContext()
      ctx.open = !ctx.open
    },
    close() {
      getContext().open = false
    },
    closeOnEscape(event) {
      const ctx = getContext()
      if (event.key !== 'Escape' || !ctx.open) return
      ctx.open = false
      // devolve o foco ao botão hambúrguer (o menu mobile some com hidden)
      getElement().ref?.querySelector('[aria-controls="fw-mobile-menu"]')?.focus()
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
})
