import { useCallback, useEffect, useState } from 'react'
import { ChevronLeft, ChevronRight } from 'lucide-react'
import { useModule } from '@/hooks/useModule'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'
import { cn } from '@/lib/cn'
import type {
  OGenomaCompromisso,
  OGenomaData,
  OGenomaDiagnostico,
  OGenomaHeroSlide,
  OGenomaSobre,
} from './o-genoma.schema'

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
      <SobreSection sobre={data.sobre} />
      <CompromissoSection compromisso={data.compromisso} />
      <DiagnosticoSection diagnostico={data.diagnostico} />
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

// ─── Sobre nós ────────────────────────────────────────────────────────────────

function SobreSection({ sobre }: { sobre: OGenomaSobre }) {
  const { eyebrow, titulo, texto, destaque } = sobre

  if (!eyebrow && !titulo && !texto && !destaque) return null

  return (
    <section className="w-full bg-background py-16 sm:py-20 md:py-24 xl:py-28">
      <div className="container">
        <div className="grid grid-cols-1 gap-10 lg:grid-cols-[1fr_351px] lg:gap-16 xl:gap-24">
          <div>
            {eyebrow && <p className="font-sans text-body text-brand-purple-accent">{eyebrow}</p>}

            {titulo && (
              <h2 className="mt-2 max-w-[560px] font-heading text-h3 text-brand-purple-dark md:text-h2">
                {titulo}
              </h2>
            )}

            {texto && (
              <div className="mt-6 max-w-[620px] space-y-4 font-sans text-body-lg leading-relaxed text-brand-gray-text md:mt-8">
                {texto.split('\n\n').map((paragrafo, i) => (
                  <p key={i}>{paragrafo}</p>
                ))}
              </div>
            )}
          </div>

          {destaque && (
            <div className="flex flex-col gap-6 lg:mt-[19px] lg:gap-10">
              <hr className="border-t border-brand-purple" />
              <h3 className="font-heading text-h4 text-brand-purple-dark">
                {destaque.split('\n').map((line, i) => (
                  <span key={i} className="block">
                    {line}
                  </span>
                ))}
              </h3>
              <hr className="border-t border-brand-purple" />
            </div>
          )}
        </div>
      </div>
    </section>
  )
}

function SobreSkeleton() {
  return (
    <section className="w-full bg-background py-16 sm:py-20 md:py-24 xl:py-28">
      <div className="container">
        <div className="grid grid-cols-1 gap-10 lg:grid-cols-[1fr_351px] lg:gap-16 xl:gap-24">
          <div className="space-y-4">
            <div className="h-5 w-24 animate-pulse rounded bg-foreground/10" />
            <div className="h-9 w-full max-w-[560px] animate-pulse rounded bg-foreground/10" />
            <div className="mt-4 h-24 w-full max-w-[620px] animate-pulse rounded bg-foreground/10" />
          </div>
          <div className="space-y-4">
            <div className="h-px w-full animate-pulse bg-foreground/10" />
            <div className="h-16 w-full animate-pulse rounded bg-foreground/10" />
            <div className="h-px w-full animate-pulse bg-foreground/10" />
          </div>
        </div>
      </div>
    </section>
  )
}

// ─── Compromisso ────────────────────────────────────────────────────────────

function CompromissoSection({ compromisso }: { compromisso: OGenomaCompromisso }) {
  const { eyebrow, titulo, texto, imagem } = compromisso

  if (!eyebrow && !titulo && !texto) return null

  return (
    <section className="relative isolate w-full overflow-hidden py-16 sm:py-20 md:py-24 xl:py-28">
      <img
        src={imagem.src}
        alt={imagem.alt}
        className="absolute inset-0 -z-20 h-full w-full object-cover"
      />
      {/* Overlay mobile/tablet: escurece de cima para baixo para garantir legibilidade */}
      <div className="absolute inset-0 -z-10 bg-gradient-to-b from-black/70 via-black/40 to-black/10 md:hidden" />
      {/* Overlay desktop: gradiente horizontal fiel ao Figma */}
      <div
        className="absolute inset-0 -z-10 hidden md:block"
        style={{ background: 'linear-gradient(90deg, rgba(133,156,169,1) 50%, rgba(158,180,193,0) 89%)' }}
      />

      <div className="container">
        <div className="flex max-w-[594px] flex-col gap-6 md:gap-10">
          <div className="flex flex-col gap-2">
            {eyebrow && <p className="font-sans text-body text-white">{eyebrow}</p>}

            {titulo && (
              <h2 className="max-w-[581px] font-heading text-h3 text-white md:text-h2">
                {titulo.split('\n').map((line, i) => (
                  <span key={i} className="block">
                    {line}
                  </span>
                ))}
              </h2>
            )}
          </div>

          {texto && <p className="font-sans text-body-lg leading-relaxed text-white/80">{texto}</p>}
        </div>
      </div>
    </section>
  )
}

function CompromissoSkeleton() {
  return (
    <section className="relative isolate w-full overflow-hidden bg-muted py-16 sm:py-20 md:py-24 xl:py-28">
      <div className="container">
        <div className="max-w-[594px] space-y-4">
          <div className="h-5 w-28 animate-pulse rounded bg-foreground/10" />
          <div className="h-9 w-full animate-pulse rounded bg-foreground/10" />
          <div className="mt-4 h-20 w-full animate-pulse rounded bg-foreground/10" />
        </div>
      </div>
    </section>
  )
}

// ─── Diaginostico (posição 4, última seção) ──────────────────────────────────

function DiagnosticoSection({ diagnostico }: { diagnostico: OGenomaDiagnostico }) {
  const { titulo, texto, destaque } = diagnostico

  if (!titulo && !texto && !destaque) return null

  return (
    <section className="w-full bg-background py-16 sm:py-20 md:py-24 xl:py-28">
      <div className="container">
        <div className="grid grid-cols-1 gap-10 lg:grid-cols-[1fr_351px] lg:gap-16 xl:gap-24">
          <div>
            {titulo && (
              <h2 className="max-w-[560px] font-heading text-h3 text-brand-purple-dark md:text-h2">
                {titulo.split('\n').map((line, i) => (
                  <span key={i} className="block">
                    {line}
                  </span>
                ))}
              </h2>
            )}

            {texto && (
              <div className="mt-6 max-w-[620px] space-y-4 font-sans text-body-lg leading-relaxed text-brand-gray-text md:mt-8">
                {texto.split('\n\n').map((paragrafo, i) => (
                  <p key={i}>{paragrafo}</p>
                ))}
              </div>
            )}
          </div>

          {destaque && (
            <div className="flex flex-col gap-6 lg:gap-10">
              <hr className="border-t border-brand-purple" />
              <p className="font-sans text-body-lg leading-relaxed text-brand-purple-dark">{destaque}</p>
              <hr className="border-t border-brand-purple" />
            </div>
          )}
        </div>
      </div>
    </section>
  )
}

function DiagnosticoSkeleton() {
  return (
    <section className="w-full bg-background py-16 sm:py-20 md:py-24 xl:py-28">
      <div className="container">
        <div className="grid grid-cols-1 gap-10 lg:grid-cols-[1fr_351px] lg:gap-16 xl:gap-24">
          <div className="space-y-4">
            <div className="h-9 w-full max-w-[560px] animate-pulse rounded bg-foreground/10" />
            <div className="mt-4 h-24 w-full max-w-[620px] animate-pulse rounded bg-foreground/10" />
          </div>
          <div className="space-y-4">
            <div className="h-px w-full animate-pulse bg-foreground/10" />
            <div className="h-16 w-full animate-pulse rounded bg-foreground/10" />
            <div className="h-px w-full animate-pulse bg-foreground/10" />
          </div>
        </div>
      </div>
    </section>
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
      <SobreSkeleton />
      <CompromissoSkeleton />
      <DiagnosticoSkeleton />
    </main>
  )
}
