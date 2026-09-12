import { useCallback, useEffect, useState } from 'react'
import { ChevronLeft, ChevronRight } from 'lucide-react'
import { useModule } from '@/hooks/useModule'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'
import { cn } from '@/lib/cn'
import type { OGenomaData, OGenomaHeroSlide } from './o-genoma.schema'

export default function OGenomaView() {
  const { data, isLoading, error } = useModule<OGenomaData>('o-genoma')
  useDocumentTitle(data?.hero.slides[0]?.titulo.replace(/\n/g, ' ') ?? 'O Genoma')

  if (isLoading) return <OGenomaSkeleton />

  if (error || !data) {
    return (
      <p className="container py-16 text-center text-muted-foreground">
        Erro ao carregar conteúdo.
      </p>
    )
  }

  return (
    <main>
      <HeroCarousel hero={data.hero} />
    </main>
  )
}

// ─── Hero (carrossel) ─────────────────────────────────────────────────────────

function HeroCarousel({ hero }: { hero: OGenomaData['hero'] }) {
  const { slides, autoplay, intervalo } = hero
  const total = slides.length

  const [index, setIndex] = useState(0)
  const [paused, setPaused] = useState(false)

  const goTo = useCallback((i: number) => setIndex(((i % total) + total) % total), [total])
  const next = useCallback(() => goTo(index + 1), [goTo, index])
  const prev = useCallback(() => goTo(index - 1), [goTo, index])

  useEffect(() => {
    if (!autoplay || paused || total <= 1) return
    const id = window.setInterval(next, Math.max(intervalo, 2000))
    return () => window.clearInterval(id)
  }, [autoplay, paused, total, intervalo, next])

  if (total === 0) return null

  return (
    <section
      className="relative isolate w-full overflow-hidden bg-gradient-to-br from-[#95ABB7] to-[#AEC6D3] py-16 sm:py-20 md:py-24 xl:py-28"
      onMouseEnter={() => setPaused(true)}
      onMouseLeave={() => setPaused(false)}
      aria-roledescription="carousel"
    >
      {/* Slides */}
      <div className="relative">
        {slides.map((slide, i) => (
          <div
            key={i}
            className={cn(
              'transition-opacity duration-700 ease-in-out',
              i === index ? 'relative opacity-100' : 'absolute inset-0 pointer-events-none opacity-0'
            )}
            aria-hidden={i !== index}
          >
            <SlideContent slide={slide} />
          </div>
        ))}
      </div>

      {/* Setas + indicadores (só com mais de 1 slide) */}
      {total > 1 && (
        <>
          <button
            type="button"
            onClick={prev}
            aria-label="Slide anterior"
            className="absolute left-3 top-1/2 z-20 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white shadow-md transition hover:bg-white/90 sm:left-6 sm:h-12 sm:w-12 lg:left-10 lg:h-[58px] lg:w-[58px]"
          >
            <ChevronLeft className="h-5 w-5 text-primary sm:h-6 sm:w-6" />
          </button>
          <button
            type="button"
            onClick={next}
            aria-label="Próximo slide"
            className="absolute right-3 top-1/2 z-20 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white shadow-md transition hover:bg-white/90 sm:right-6 sm:h-12 sm:w-12 lg:right-10 lg:h-[58px] lg:w-[58px]"
          >
            <ChevronRight className="h-5 w-5 text-primary sm:h-6 sm:w-6" />
          </button>

          <div className="mt-8 flex justify-center gap-2 sm:mt-10">
            {slides.map((_, i) => (
              <button
                key={i}
                type="button"
                onClick={() => goTo(i)}
                aria-label={`Ir para o slide ${i + 1}`}
                aria-current={i === index}
                className={cn(
                  'h-2 rounded-full bg-white/50 transition-all',
                  i === index ? 'w-6 bg-white' : 'w-2 hover:bg-white/80'
                )}
              />
            ))}
          </div>
        </>
      )}
    </section>
  )
}

function SlideContent({ slide }: { slide: OGenomaHeroSlide }) {
  const { eyebrow, titulo, imagem1, imagem2, destaque } = slide

  return (
    <div className="container">
      <div className="grid grid-cols-1 gap-10 lg:grid-cols-[1fr_351px] lg:gap-16 xl:gap-24">
        <div className="max-w-xl lg:max-w-[636px]">
          {eyebrow && <p className="font-sans text-body text-white">{eyebrow}</p>}

          {titulo && (
            <h1 className="mt-4 font-heading text-3xl font-medium leading-tight text-white sm:mt-6 md:text-4xl lg:text-h1">
              {titulo.split('\n').map((line, i) => (
                <span key={i} className="block">
                  {line}
                </span>
              ))}
            </h1>
          )}
        </div>

        {destaque && (
          <div className="flex flex-col gap-8 lg:mt-[52px] lg:gap-[49px]">
            <hr className="border-t border-white" />
            <p className="font-sans text-body-lg text-white">{destaque}</p>
            <hr className="border-t border-white" />
          </div>
        )}
      </div>

      <div className="mt-10 grid grid-cols-1 gap-4 sm:grid-cols-[314fr_873fr] sm:gap-7 md:mt-14">
        <img
          src={imagem1.src}
          alt={imagem1.alt}
          className="h-64 w-full rounded-2xl object-cover sm:h-auto sm:aspect-[314/390]"
        />
        <img
          src={imagem2.src}
          alt={imagem2.alt}
          className="h-64 w-full rounded-2xl object-cover sm:h-auto sm:aspect-[873/390]"
        />
      </div>
    </div>
  )
}

function OGenomaSkeleton() {
  return (
    <main>
      <section className="w-full bg-muted py-16 sm:py-20 md:py-24 xl:py-28">
        <div className="container">
          <div className="grid grid-cols-1 gap-10 lg:grid-cols-[1fr_351px] lg:gap-16 xl:gap-24">
            <div className="max-w-xl space-y-4 lg:max-w-[636px]">
              <div className="h-5 w-32 animate-pulse rounded bg-foreground/10" />
              <div className="h-10 w-full animate-pulse rounded bg-foreground/10 md:h-12" />
              <div className="h-10 w-2/3 animate-pulse rounded bg-foreground/10 md:h-12" />
            </div>
            <div className="space-y-4">
              <div className="h-px w-full animate-pulse bg-foreground/10" />
              <div className="h-16 w-full animate-pulse rounded bg-foreground/10" />
              <div className="h-px w-full animate-pulse bg-foreground/10" />
            </div>
          </div>
          <div className="mt-10 grid grid-cols-1 gap-4 sm:grid-cols-[314fr_873fr] sm:gap-7 md:mt-14">
            <div className="h-64 w-full animate-pulse rounded-2xl bg-foreground/10" />
            <div className="h-64 w-full animate-pulse rounded-2xl bg-foreground/10" />
          </div>
        </div>
      </section>
    </main>
  )
}
