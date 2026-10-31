/**
 * Animações de entrada ao rolar (estilo ScrollReveal), sem dependência.
 * Só é carregado em projeto com `animation: true` no prompt.yaml — skill `animation`:
 * o setup liga add_filter('upwork/animation', '__return_true') e as seções marcam os
 * elementos com `data-reveal="{preset}"`. Sem o filtro, os atributos não fazem nada (tudo visível).
 * Estados visuais: src/sass/core/_reveal.scss.
 *
 *   <h2 data-reveal>…</h2>                   sobe 50px + fade (padrão)
 *   <ul data-reveal="fade">…</ul>            só fade
 *   <img data-reveal="right">                entra pela direita
 *   <span data-reveal="line"></span>         divisor cresce da esquerda
 *   <li data-reveal data-reveal-delay="200"> atraso extra (ms)
 *
 * Elementos que entram na tela juntos aparecem em cascata (intervalo do preset), na
 * ordem do DOM. Cada elemento anima uma vez. `prefers-reduced-motion` → sem animação.
 */

/** Mesmos tempos do site de referência (ScrollReveal: delay/interval/duration por preset). */
const PRESETS = {
  up: { delay: 100, interval: 100, duration: 1000 },
  fade: { delay: 100, interval: 150, duration: 1000 },
  left: { delay: 100, interval: 150, duration: 1000 },
  right: { delay: 100, interval: 150, duration: 1000 },
  line: { delay: 100, interval: 150, duration: 2000 },
  quick: { delay: 10, interval: 10, duration: 600 },
}

const SELECTOR = '[data-reveal]'
const DONE = 'is-revealed'
/** Header e footer do site nunca animam — marcação lá é solta na hora (fica visível). */
const NEVER = '#fw-header, #fw-footer'

let observer = null

const presetOf = (el) => PRESETS[el.dataset.reveal ?? ''] ?? PRESETS.up

function show(batch) {
  // ordem do DOM, cascata por preset (como o `interval` do ScrollReveal)
  batch.sort((a, b) => (a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1))
  const counters = new Map()
  for (const el of batch) {
    const preset = presetOf(el)
    const i = counters.get(preset) ?? 0
    counters.set(preset, i + 1)
    const delay = preset.delay + i * preset.interval + Number(el.dataset.revealDelay || 0)
    el.style.transitionDelay = `${delay}ms`
    el.classList.add(DONE)
    // terminou → solta o elemento: sem data-reveal, voltam o transition/opacity/transform
    // próprios dele (hover de botão, card com transição) — pode marcar direto, sem wrapper
    window.setTimeout(() => {
      el.removeAttribute('data-reveal')
      el.removeAttribute('data-reveal-delay')
      el.style.transitionDelay = ''
      el.classList.remove(DONE)
    }, delay + preset.duration + 50)
  }
}

function watch(root) {
  if (!observer) return
  const els = root instanceof Element && root.matches(SELECTOR) ? [root] : []
  els.push(...root.querySelectorAll(SELECTOR))
  for (const el of els) {
    if (el.closest(NEVER)) el.removeAttribute('data-reveal')
    else if (!el.classList.contains(DONE)) observer.observe(el)
  }
}

function init() {
  window.fwReveal = true // o script do <head> (Bootstrap::revealHead) não precisa desfazer o .fw-reveal
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  if (reduce || !('IntersectionObserver' in window)) {
    document.documentElement.classList.remove('fw-reveal')
    return // fica tudo visível
  }

  // já posta no <head> (evita o conteúdo aparecer e sumir antes deste módulo carregar)
  document.documentElement.classList.add('fw-reveal')

  observer = new IntersectionObserver(
    (entries) => {
      const batch = entries.filter((e) => e.isIntersecting).map((e) => e.target)
      if (!batch.length) return
      batch.forEach((el) => observer.unobserve(el))
      show(batch)
    },
    { rootMargin: '0px 0px -10% 0px', threshold: 0.1 },
  )

  // elementos já no HTML e os que a Interactivity API insere depois (abas, "ver mais", data-wp-each)
  watch(document)
  new MutationObserver((mutations) => {
    for (const m of mutations) m.addedNodes.forEach((n) => n instanceof Element && watch(n))
  }).observe(document.body, { childList: true, subtree: true })
}

init()
