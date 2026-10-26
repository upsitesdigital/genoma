import { useRef, type TouchEvent } from 'react'

const MIN_DISTANCE = 50

/**
 * Arrastar (swipe) horizontal no touch para trocar de slide.
 * Ignora gestos predominantemente verticais, para não atrapalhar a rolagem da página.
 * Uso: <section {...useSwipe({ onNext: next, onPrev: prev })}>
 */
export function useSwipe({ onNext, onPrev }: { onNext: () => void; onPrev: () => void }) {
  const start = useRef<{ x: number; y: number } | null>(null)

  return {
    onTouchStart: (e: TouchEvent) => {
      const t = e.touches[0]
      start.current = { x: t.clientX, y: t.clientY }
    },
    onTouchEnd: (e: TouchEvent) => {
      if (!start.current) return
      const t = e.changedTouches[0]
      const dx = t.clientX - start.current.x
      const dy = t.clientY - start.current.y
      start.current = null

      if (Math.abs(dx) < MIN_DISTANCE || Math.abs(dx) < Math.abs(dy)) return
      if (dx < 0) onNext()
      else onPrev()
    },
  }
}
