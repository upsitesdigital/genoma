/**
 * Depoimentos da home (template-parts/includes/home/depoimentos/depoimentos.php) — carrossel.
 * - O trilho (`context.trilho`, id do <ul>) é uma faixa com rolagem horizontal e scroll-snap no lg;
 *   abaixo disso os cards ficam empilhados e não há o que rolar (setas escondidas, autoplay parado).
 * - Setas: rolam um card; na ponta, voltam ao outro extremo (scrollByCard do React).
 * - Autoplay a cada `intervalo` ms (mínimo 2000, como no React); pausa com o mouse ou o foco
 *   dentro da seção e fica desligado com prefers-reduced-motion.
 */
import { getContext, getElement, store } from '@wordpress/interactivity'

const MIN_INTERVAL = 2000
const EDGE = 4
const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)')

function scrollByCard(track, direction) {
  if (!track || track.scrollWidth <= track.clientWidth + EDGE) return
  const behavior = reduceMotion.matches ? 'instant' : 'smooth'
  const card = track.firstElementChild
  const gap = parseFloat(getComputedStyle(track).columnGap) || 0
  const step = card ? card.getBoundingClientRect().width + gap : track.clientWidth

  const atEnd = track.scrollLeft + track.clientWidth >= track.scrollWidth - EDGE
  const atStart = track.scrollLeft <= EDGE
  if (direction === 1 && atEnd) return track.scrollTo({ left: 0, behavior })
  if (direction === -1 && atStart) return track.scrollTo({ left: track.scrollWidth, behavior })
  track.scrollBy({ left: direction * step, behavior })
}

const trackOf = (ctx) => document.getElementById(ctx.trilho)

store('upwork/home-depoimentos', {
  actions: {
    prev() {
      scrollByCard(trackOf(getContext()), -1)
    },
    next() {
      scrollByCard(trackOf(getContext()), 1)
    },
    pause() {
      getContext().paused = true
    },
    resume() {
      const { ref } = getElement()
      // o mouse saiu, mas o foco continua numa seta: segue pausado
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
    // roda de novo a cada pausa/retomada: o tempo recomeça do zero (como o setInterval do React)
    autoplay() {
      const ctx = getContext()
      const { paused, autoplay, intervalo } = ctx
      if (!autoplay || paused || reduceMotion.matches) return
      const id = window.setInterval(
        () => scrollByCard(trackOf(ctx), 1),
        Math.max(Number(intervalo) || 0, MIN_INTERVAL),
      )
      return () => window.clearInterval(id)
    },
  },
})
