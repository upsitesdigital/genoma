import { useLayoutEffect, type RefObject } from 'react'

/**
 * Animação de entrada ao rolar: cada bloco sobe 50px com fade-in quando entra
 * na tela (mesma config do ScrollReveal usado nos sites Upsites: 1s, 100ms de
 * delay e 100ms de intervalo entre elementos que entram juntos).
 *
 * Os alvos são detectados automaticamente dentro de `root` — títulos, textos,
 * imagens, CTAs e cada filho de grid (cards) —, então novos módulos ganham a
 * animação sem marcação extra. Para excluir um bloco (e tudo dentro dele),
 * use `data-no-reveal`. Usa Web Animations API para não conflitar com as
 * classes `transition-*`/`transform` dos próprios elementos.
 */

const TARGETS = [
  'h1', 'h2', 'h3', 'h4', 'p', 'img', 'hr', 'ul', 'ol', 'blockquote', 'form',
  'a.rounded-full', 'button.rounded-full',
  '.grid > *',
].join(',')

const DURATION = 1000
const DELAY = 100
const INTERVAL = 100
const EASING = 'cubic-bezier(0.5, 0, 0, 1)'
const PENDING = 'sr-pending'

function isPositioned(el: Element): boolean {
  const pos = getComputedStyle(el).position
  return pos === 'absolute' || pos === 'fixed' || pos === 'sticky'
}

function shouldReveal(el: HTMLElement, root: HTMLElement): boolean {
  // Trilhos com rolagem horizontal (carrosséis) entram de lado, não de baixo
  if (el.closest('[data-no-reveal], .overflow-x-auto')) return false
  if (el.classList.contains('sr-done') || el.classList.contains(PENDING)) return false
  // Skeletons de carregamento não animam
  if (el.matches('.animate-pulse') || el.querySelector('.animate-pulse')) return false
  // Elementos posicionados (setas de carrossel, decorações, fundos) mantêm o próprio transform
  if (isPositioned(el)) return false

  for (let p = el.parentElement; p && p !== root; p = p.parentElement) {
    // Um ancestral já anima como bloco (ex: card) — não anima de novo por dentro
    if (p.matches(TARGETS) && !p.classList.contains('sr-done')) return false
    // Imagens de fundo dentro de camadas absolutas (slides do hero) ficam paradas
    if (el.tagName === 'IMG' && isPositioned(p)) return false
  }
  return true
}

export function useScrollReveal(rootRef: RefObject<HTMLElement>) {
  // Layout effect: esconde os alvos antes do 1º paint (o conteúdo já vem pré-carregado)
  useLayoutEffect(() => {
    const root = rootRef.current
    if (!root) return
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return
    if (typeof IntersectionObserver === 'undefined' || !('animate' in Element.prototype)) return

    const io = new IntersectionObserver(
      (entries) => {
        const visible = entries
          .filter((e) => e.isIntersecting)
          .map((e) => e.target as HTMLElement)
          .sort((a, b) => a.getBoundingClientRect().top - b.getBoundingClientRect().top)

        visible.forEach((el, i) => {
          io.unobserve(el)
          const anim = el.animate(
            [
              { opacity: 0, transform: 'translateY(50px)' },
              { opacity: 1, transform: 'translateY(0)' },
            ],
            { duration: DURATION, delay: DELAY + i * INTERVAL, easing: EASING, fill: 'backwards' }
          )
          // Remove o "escondido" assim que a animação assume (fill: backwards segura o 1º quadro)
          el.classList.remove(PENDING)
          el.classList.add('sr-done')
          anim.onfinish = () => anim.cancel()
        })
      },
      { threshold: 0.15, rootMargin: '0px 0px -40px 0px' }
    )

    const scan = (scope: ParentNode) => {
      scope.querySelectorAll<HTMLElement>(TARGETS).forEach((el) => {
        if (!shouldReveal(el, root)) return
        el.classList.add(PENDING)
        io.observe(el)
      })
    }

    scan(root)

    // Conteúdo chega depois (fetch do módulo substitui o skeleton): escaneia o que for inserido
    const mo = new MutationObserver((mutations) => {
      for (const m of mutations) {
        m.addedNodes.forEach((node) => {
          if (node instanceof HTMLElement) scan(node.parentElement ?? node)
        })
      }
    })
    mo.observe(root, { childList: true, subtree: true })

    return () => {
      mo.disconnect()
      io.disconnect()
      root.querySelectorAll(`.${PENDING}`).forEach((el) => el.classList.remove(PENDING))
    }
  }, [rootRef])
}
