import { useCallback, useEffect, useState } from 'react'
import { ChevronLeft, ChevronRight } from 'lucide-react'
import { useModule } from '@/hooks/useModule'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'
import { cn } from '@/lib/cn'
import { boot } from '@/lib/env'
import type { ResponsavelData, ResponsavelHeroSlide } from './responsavel.schema'

export default function ResponsavelView() {
  const { data, isLoading, error } = useModule<ResponsavelData>('responsavel')
  useDocumentTitle(data?.hero.slides[0]?.titulo.replace(/\n/g, ' ') ?? 'Responsável')

  if (isLoading) return <ResponsavelSkeleton />

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

function HeroCarousel({ hero }: { hero: ResponsavelData['hero'] }) {
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

  const waveUrl = `${boot.themeUrl}/app/responsavel/assets/hero-bottom-wave.svg`

  return (
    <section
      className="relative isolate flex min-h-[620px] w-full items-center overflow-hidden bg-gradient-to-br from-[#95ABB7] to-[#AEC6D3] py-20 sm:min-h-[680px] md:py-24 lg:min-h-[720px] xl:min-h-[797px] xl:py-0"
      onMouseEnter={() => setPaused(true)}
      onMouseLeave={() => setPaused(false)}
      aria-roledescription="carousel"
    >
      {/* Slides */}
      {slides.map((slide, i) => (
        <div
          key={i}
          className={cn(
            'absolute inset-0 transition-opacity duration-700 ease-in-out',
            i === index ? 'opacity-100' : 'pointer-events-none opacity-0'
          )}
          aria-hidden={i !== index}
        >
          <SlideImage slide={slide} />

          <div className="container relative z-10 flex h-full items-center">
            <SlideContent slide={slide} />
          </div>
        </div>
      ))}

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

          <div className="absolute inset-x-0 bottom-4 z-20 flex justify-center gap-2 md:bottom-6">
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

      {/* Onda decorativa inferior (apenas telas maiores) */}
      <img
        src={waveUrl}
        alt=""
        aria-hidden="true"
        className="pointer-events-none absolute inset-x-0 bottom-0 z-10 mx-auto hidden w-full max-w-[1426px] lg:block"
      />
    </section>
  )
}

function SlideImage({ slide }: { slide: ResponsavelHeroSlide }) {
  return (
    <div className="absolute inset-x-0 bottom-0 h-56 opacity-90 sm:h-72 md:h-80 lg:inset-x-auto lg:bottom-0 lg:right-[8%] lg:top-[15%] lg:h-auto lg:w-[34%] lg:opacity-100">
      {/* Halo suave atrás do recorte, aproximando o blur do Figma (apenas telas maiores) */}
      <div
        aria-hidden="true"
        className="pointer-events-none absolute -inset-8 hidden rounded-full bg-white/15 blur-3xl lg:block"
      />
      <img
        src={slide.imagem.src}
        alt={slide.imagem.alt}
        className="relative h-full w-full object-contain object-bottom"
      />
    </div>
  )
}

function SlideContent({ slide }: { slide: ResponsavelHeroSlide }) {
  return (
    <div className="relative max-w-xl text-left lg:max-w-[598px]">
      {slide.eyebrow && (
        <p className="font-sans text-body text-white">{slide.eyebrow}</p>
      )}

      <h1 className="mt-3 font-heading text-3xl font-medium leading-tight text-white md:text-4xl lg:mt-6 lg:text-h1">
        {slide.titulo.split('\n').map((line, i) => (
          <span key={i} className="block">
            {line}
          </span>
        ))}
      </h1>

      {slide.subtitulo && (
        <p className="mt-4 max-w-md font-heading text-base font-medium leading-snug text-white md:mt-6 lg:max-w-[542px] lg:text-h3">
          {slide.subtitulo}
        </p>
      )}

      {(slide.ctaPrimario.texto || slide.ctaSecundario.texto) && (
        <div className="mt-8 flex flex-wrap items-center gap-4 md:mt-10">
          {slide.ctaPrimario.texto && (
            <a
              href={slide.ctaPrimario.link || '#'}
              className="inline-flex items-center justify-center rounded-full bg-primary px-7 py-5 font-heading text-h4 leading-none text-white transition-opacity hover:opacity-90"
            >
              {slide.ctaPrimario.texto}
            </a>
          )}
          {slide.ctaSecundario.texto && (
            <a
              href={slide.ctaSecundario.link || '#'}
              className="inline-flex items-center justify-center rounded-full bg-white px-7 py-5 font-heading text-h4 leading-none text-primary transition-opacity hover:opacity-90"
            >
              {slide.ctaSecundario.texto}
            </a>
          )}
        </div>
      )}
    </div>
  )
}

function ResponsavelSkeleton() {
  return (
    <main>
      <section className="relative flex min-h-[620px] w-full items-center overflow-hidden bg-muted py-20 sm:min-h-[680px] md:py-24 lg:min-h-[720px] xl:min-h-[797px]">
        <div className="container relative z-10">
          <div className="max-w-xl space-y-4 lg:max-w-[598px]">
            <div className="h-5 w-32 animate-pulse rounded bg-foreground/10" />
            <div className="h-10 w-full animate-pulse rounded bg-foreground/10 md:h-12" />
            <div className="h-10 w-3/4 animate-pulse rounded bg-foreground/10 md:h-12" />
            <div className="mt-4 h-6 w-2/3 animate-pulse rounded bg-foreground/10" />
            <div className="mt-8 flex gap-4">
              <div className="h-14 w-40 animate-pulse rounded-full bg-foreground/10" />
              <div className="h-14 w-40 animate-pulse rounded-full bg-foreground/10" />
            </div>
          </div>
        </div>
      </section>
    </main>
  )
}
