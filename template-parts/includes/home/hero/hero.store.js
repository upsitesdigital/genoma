/**
 * Hero da home (template-parts/includes/home/hero/hero.php) — carrossel de slides.
 * - `context.index` (na <section>): slide ativo; cada slide/bolinha tem o seu `context.i`.
 * - Setas, bolinhas e swipe horizontal no touch (gesto vertical não troca: a página rola).
 * - Autoplay a cada `intervalo` ms (mínimo 2000, como no React); pausa com o mouse ou o foco
 *   dentro do carrossel e fica desligado com prefers-reduced-motion.
 */
import { getContext, getElement, store } from '@wordpress/interactivity'

const MIN_INTERVAL = 2000
const MIN_SWIPE = 50
const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)')

const go = (ctx, i) => {
  ctx.index = ((i % ctx.total) + ctx.total) % ctx.total
}

store('upwork/home-hero', {
  state: {
    get isActive() {
      const ctx = getContext()
      return ctx.i === ctx.index
    },
  },
  actions: {
    prev() {
      const ctx = getContext()
      go(ctx, ctx.index - 1)
    },
    next() {
      const ctx = getContext()
      go(ctx, ctx.index + 1)
    },
    goTo() {
      const ctx = getContext()
      go(ctx, ctx.i)
    },
    pause() {
      getContext().paused = true
    },
    resume() {
      const { ref } = getElement()
      // o mouse saiu, mas o foco continua num botão do carrossel: segue pausado
      if (ref?.contains(document.activeElement)) return
      getContext().paused = false
    },
    resumeOnFocusOut(event) {
      const { ref } = getElement()
      if (ref && event.relatedTarget && ref.contains(event.relatedTarget)) return
      if (ref?.matches(':hover')) return
      getContext().paused = false
    },
  },
  callbacks: {
    // swipe: listeners passivos (não atrasam a rolagem no celular)
    swipe() {
      const ctx = getContext()
      const { ref } = getElement()
      if (!ref || ctx.total < 2) return
      let start = null
      ref.addEventListener('touchstart', (e) => {
        const t = e.touches[0]
        start = { x: t.clientX, y: t.clientY }
      }, { passive: true })
      ref.addEventListener('touchend', (e) => {
        if (!start) return
        const t = e.changedTouches[0]
        const dx = t.clientX - start.x
        const dy = t.clientY - start.y
        start = null
        if (Math.abs(dx) < MIN_SWIPE || Math.abs(dx) < Math.abs(dy)) return
        go(ctx, ctx.index + (dx < 0 ? 1 : -1))
      }, { passive: true })
    },
    // roda de novo a cada troca de slide/pausa: o tempo recomeça do zero (como o setInterval do React)
    autoplay() {
      const ctx = getContext()
      const { index, paused, autoplay, total, intervalo } = ctx
      if (!autoplay || paused || total < 2 || reduceMotion.matches) return
      const id = window.setTimeout(() => go(ctx, index + 1), Math.max(Number(intervalo) || 0, MIN_INTERVAL))
      return () => window.clearTimeout(id)
    },
  },
})
