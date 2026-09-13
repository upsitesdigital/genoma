import { useCallback, useEffect, useState } from 'react'
import { ChevronDown, ChevronLeft, ChevronRight } from 'lucide-react'
import { useModule } from '@/hooks/useModule'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'
import { cn } from '@/lib/cn'
import { boot } from '@/lib/env'
import type {
  ResponsavelData,
  ResponsavelHeroSlide,
  ResponsavelSuporte,
  ResponsavelPlanos,
  ResponsavelExames,
  ResponsavelServicos,
  ResponsavelResultados,
  ResponsavelBeneficios,
} from './responsavel.schema'

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
      <SuporteSection suporte={data.suporte} />
      <PlanosSection planos={data.planos} />
      <ExamesSection exames={data.exames} />
      <ServicosSection servicos={data.servicos} />
      <ResultadosSection resultados={data.resultados} />
      <BeneficiosSection beneficios={data.beneficios} />
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
  if (!slide.imagem) return null

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
      <SuporteSkeleton />
      <PlanosSkeleton />
      <ExamesSkeleton />
      <ServicosSkeleton />
      <ResultadosSkeleton />
      <BeneficiosSkeleton />
    </main>
  )
}

// ─── Suporte ──────────────────────────────────────────────────────────────────

function SuporteSection({ suporte }: { suporte: ResponsavelSuporte }) {
  const { eyebrow, titulo, texto, quote, imagem1, imagem2 } = suporte

  return (
    <section className="bg-background py-16 md:py-20 lg:py-24 xl:py-28">
      <div className="container">
        <div className="grid grid-cols-1 gap-10 lg:grid-cols-[1fr_351px] lg:gap-16 xl:gap-24">
          <div>
            {eyebrow && (
              <p className="font-sans text-body text-brand-purple-accent">{eyebrow}</p>
            )}

            {titulo && (
              <h2 className="mt-2 max-w-[620px] font-heading text-h3 text-brand-purple-dark md:text-h2">
                {titulo.split('\n').map((line, i) => (
                  <span key={i} className="block">
                    {line}
                  </span>
                ))}
              </h2>
            )}

            {texto && (
              <p className="mt-6 max-w-[646px] whitespace-pre-line font-sans text-body-lg leading-relaxed text-brand-gray-text md:mt-8">
                {texto}
              </p>
            )}
          </div>

          {quote && (
            <div className="flex flex-col gap-8 lg:mt-[52px] lg:gap-[49px]">
              <hr className="border-t border-brand-purple" />
              <p className="font-sans text-body-lg text-brand-purple-dark">{quote}</p>
              <hr className="border-t border-brand-purple" />
            </div>
          )}
        </div>

        {(imagem1 || imagem2) && (
          <div className="mt-10 grid grid-cols-1 gap-4 sm:grid-cols-[314fr_873fr] sm:gap-7 md:mt-14">
            {imagem1 && (
              <img
                src={imagem1.src}
                alt={imagem1.alt}
                className="h-64 w-full rounded-2xl object-cover sm:h-auto sm:aspect-[314/390]"
              />
            )}
            {imagem2 && (
              <img
                src={imagem2.src}
                alt={imagem2.alt}
                className="h-64 w-full rounded-2xl object-cover sm:h-auto sm:aspect-[873/389]"
              />
            )}
          </div>
        )}
      </div>
    </section>
  )
}

function SuporteSkeleton() {
  return (
    <section className="bg-background py-16 md:py-20 lg:py-24 xl:py-28">
      <div className="container">
        <div className="grid grid-cols-1 gap-10 lg:grid-cols-[1fr_351px] lg:gap-16 xl:gap-24">
          <div className="max-w-[646px] space-y-4">
            <div className="h-5 w-32 animate-pulse rounded bg-foreground/10" />
            <div className="h-9 w-full animate-pulse rounded bg-foreground/10" />
            <div className="h-9 w-2/3 animate-pulse rounded bg-foreground/10" />
            <div className="mt-4 h-20 w-full animate-pulse rounded bg-foreground/10" />
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
  )
}

// ─── Planos ───────────────────────────────────────────────────────────────────

function PlanosSection({ planos }: { planos: ResponsavelPlanos }) {
  const { eyebrow, titulo, logos } = planos

  if (!eyebrow && !titulo && logos.length === 0) return null

  return (
    <section className="bg-background pb-16 md:pb-20 lg:pb-24 xl:pb-28">
      <div className="container">
        {(eyebrow || titulo) && (
          <div className="max-w-[572px]">
            {eyebrow && (
              <p className="font-sans text-body text-brand-purple-accent">{eyebrow}</p>
            )}
            {titulo && (
              <h2 className="mt-3 font-heading text-h3 text-brand-purple-dark md:mt-4 md:text-h2">
                {titulo.split('\n').map((line, i) => (
                  <span key={i} className="block">
                    {line}
                  </span>
                ))}
              </h2>
            )}
          </div>
        )}

        {logos.length > 0 && (
          <div className="mt-10 flex flex-wrap items-center justify-center gap-x-10 gap-y-8 md:mt-12 lg:flex-nowrap lg:justify-between lg:gap-x-8">
            {logos.map(
              (logo, i) =>
                logo.imagem && (
                  <img
                    key={i}
                    src={logo.imagem.src}
                    alt={logo.imagem.alt || logo.nome}
                    className="h-10 w-auto max-w-[160px] object-contain sm:h-12 md:h-14 lg:h-16"
                  />
                )
            )}
          </div>
        )}
      </div>
    </section>
  )
}

function PlanosSkeleton() {
  return (
    <section className="bg-background pb-16 md:pb-20 lg:pb-24 xl:pb-28">
      <div className="container">
        <div className="max-w-[572px] space-y-3">
          <div className="h-5 w-40 animate-pulse rounded bg-foreground/10" />
          <div className="h-9 w-full animate-pulse rounded bg-foreground/10" />
        </div>
        <div className="mt-10 flex flex-wrap items-center justify-center gap-x-10 gap-y-8 md:mt-12 lg:flex-nowrap lg:justify-between">
          {Array.from({ length: 4 }).map((_, i) => (
            <div key={i} className="h-12 w-32 animate-pulse rounded bg-foreground/10" />
          ))}
        </div>
      </div>
    </section>
  )
}

// ─── Exames ───────────────────────────────────────────────────────────────────

function ExamesSection({ exames }: { exames: ResponsavelExames }) {
  const { eyebrow, titulo, texto, itens, rodape, imagem } = exames

  return (
    <section className="relative isolate overflow-hidden bg-brand-purple-dark py-16 md:py-20 lg:min-h-[560px] lg:py-24 xl:min-h-[667px] xl:py-28">
      {/* Imagem de fundo */}
      {imagem && (
        <img
          src={imagem.src}
          alt={imagem.alt}
          className="absolute inset-0 -z-10 h-full w-full object-cover"
        />
      )}

      {/* Overlay — versão mobile/tablet (gradiente vertical para legibilidade em imagem full-bleed) */}
      <div
        aria-hidden="true"
        className="absolute inset-0 -z-10 bg-gradient-to-t from-black/85 via-black/55 to-black/25 lg:hidden"
      />
      {/* Overlay — versão desktop (gradiente horizontal fiel ao Figma) */}
      <div
        aria-hidden="true"
        className="absolute inset-0 -z-10 hidden bg-[linear-gradient(90deg,rgba(134,156,170,1)_39%,rgba(158,180,193,0)_100%)] lg:block"
      />

      <div className="container relative flex flex-col gap-8 lg:h-full lg:justify-center lg:gap-10 xl:gap-12">
        <div className="max-w-xl space-y-3 md:space-y-4 lg:max-w-[594px]">
          {eyebrow && <p className="font-sans text-body text-white">{eyebrow}</p>}

          {titulo && (
            <h2 className="font-heading text-h3 font-medium leading-tight text-white md:text-h2">
              {titulo.split('\n').map((line, i) => (
                <span key={i} className="block">
                  {line}
                </span>
              ))}
            </h2>
          )}

          {texto && (
            <p className="max-w-[520px] font-sans text-body leading-relaxed text-white/80">{texto}</p>
          )}
        </div>

        {itens.length > 0 && (
          <div className="flex max-w-xl flex-col gap-5 lg:max-w-[525px] lg:gap-8">
            {itens.map((item, i) => (
              <div key={i} className="flex items-center gap-4 lg:gap-6">
                {item.icone && (
                  <img
                    src={item.icone.src}
                    alt=""
                    aria-hidden="true"
                    className="h-9 w-9 shrink-0 lg:h-[52px] lg:w-[52px]"
                  />
                )}
                <p className="font-sans text-body leading-snug text-white">
                  {item.texto}
                  {item.destaque && <span className="font-semibold">{item.destaque}</span>}
                </p>
              </div>
            ))}
          </div>
        )}

        {rodape && (
          <p className="max-w-xl font-sans text-body-sm leading-relaxed text-white/80 lg:max-w-[594px]">
            {rodape}
          </p>
        )}
      </div>
    </section>
  )
}

function ExamesSkeleton() {
  return (
    <section className="relative overflow-hidden bg-muted py-16 md:py-20 lg:min-h-[560px] lg:py-24 xl:min-h-[667px] xl:py-28">
      <div className="container flex flex-col gap-8 lg:gap-10">
        <div className="max-w-xl space-y-3 lg:max-w-[594px]">
          <div className="h-5 w-24 animate-pulse rounded bg-foreground/10" />
          <div className="h-9 w-full animate-pulse rounded bg-foreground/10" />
          <div className="h-6 w-3/4 animate-pulse rounded bg-foreground/10" />
        </div>
        <div className="flex max-w-xl flex-col gap-5 lg:max-w-[525px]">
          <div className="flex items-center gap-4">
            <div className="h-9 w-9 shrink-0 animate-pulse rounded-full bg-foreground/10" />
            <div className="h-5 w-64 animate-pulse rounded bg-foreground/10" />
          </div>
          <div className="flex items-center gap-4">
            <div className="h-9 w-9 shrink-0 animate-pulse rounded-full bg-foreground/10" />
            <div className="h-5 w-80 animate-pulse rounded bg-foreground/10" />
          </div>
        </div>
        <div className="h-5 w-2/3 max-w-xl animate-pulse rounded bg-foreground/10" />
      </div>
    </section>
  )
}

// ─── Serviços ─────────────────────────────────────────────────────────────────

function ServicosSection({ servicos }: { servicos: ResponsavelServicos }) {
  const { eyebrow, titulo, texto, categorias } = servicos

  const [openState, setOpenState] = useState<boolean[]>(() => categorias.map((c) => c.aberto))

  if (!eyebrow && !titulo && categorias.length === 0) return null

  const toggle = (i: number) => setOpenState((prev) => prev.map((v, idx) => (idx === i ? !v : v)))

  return (
    <section className="bg-background py-16 md:py-20 lg:py-24 xl:py-28">
      <div className="container">
        <div className="max-w-[571px]">
          {eyebrow && (
            <p className="font-sans text-body text-brand-purple-accent">{eyebrow}</p>
          )}

          {titulo && (
            <h2 className="mt-2 font-heading text-h3 text-brand-purple-dark md:text-h2">
              {titulo.split('\n').map((line, i) => (
                <span key={i} className="block">
                  {line}
                </span>
              ))}
            </h2>
          )}

          {texto && (
            <p className="mt-4 max-w-[553px] font-sans text-body-lg leading-relaxed text-brand-gray-text md:mt-6">
              {texto}
            </p>
          )}
        </div>

        {categorias.length > 0 && (
          <div className="mt-8 flex flex-col gap-4 md:mt-10">
            {categorias.map((categoria, i) => {
              const isOpen = Boolean(openState[i])
              const hasContent = Boolean(categoria.descricao) || categoria.exames.length > 0

              return (
                <div key={i} className="rounded-2xl bg-brand-purple-subtle p-6 md:p-8">
                  <button
                    type="button"
                    onClick={() => toggle(i)}
                    aria-expanded={isOpen}
                    disabled={!hasContent}
                    className={cn(
                      'flex w-full items-center justify-between gap-4 text-left',
                      !hasContent && 'cursor-default'
                    )}
                  >
                    <span className="font-heading text-h5 font-medium text-brand-purple-dark md:text-h4">
                      {categoria.nome}
                    </span>

                    {hasContent && (
                      <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-purple md:h-[46px] md:w-[46px]">
                        <ChevronDown
                          className={cn(
                            'h-4 w-4 text-white transition-transform duration-300',
                            isOpen && 'rotate-180'
                          )}
                        />
                      </span>
                    )}
                  </button>

                  {isOpen && hasContent && (
                    <div className="mt-6 flex flex-col gap-4 md:mt-9">
                      {categoria.descricao && (
                        <p className="max-w-3xl font-sans text-body font-semibold leading-relaxed text-brand-gray-text">
                          {categoria.descricao}
                        </p>
                      )}

                      {categoria.exames.length > 0 && (
                        <div className="flex flex-col gap-2">
                          {categoria.exames.map((exame, j) => (
                            <div key={j} className="flex flex-col gap-2 sm:flex-row sm:items-stretch">
                              <div className="flex-1 rounded-2xl bg-white px-4 py-4 font-sans text-body font-semibold text-brand-purple-dark">
                                {exame.nome}
                              </div>
                              {exame.prazo && (
                                <div className="shrink-0 rounded-2xl bg-white px-4 py-4 font-sans text-body-sm text-brand-purple-dark sm:w-[132px]">
                                  {exame.prazo}
                                </div>
                              )}
                              {exame.amostra && (
                                <div className="flex shrink-0 items-center justify-center whitespace-nowrap rounded-2xl bg-white px-4 py-4 font-sans text-body-sm text-brand-purple-dark">
                                  {exame.amostra}
                                </div>
                              )}
                            </div>
                          ))}
                        </div>
                      )}
                    </div>
                  )}
                </div>
              )
            })}
          </div>
        )}
      </div>
    </section>
  )
}

function ServicosSkeleton() {
  return (
    <section className="bg-background py-16 md:py-20 lg:py-24 xl:py-28">
      <div className="container">
        <div className="max-w-[571px] space-y-3">
          <div className="h-5 w-32 animate-pulse rounded bg-foreground/10" />
          <div className="h-9 w-full animate-pulse rounded bg-foreground/10" />
          <div className="mt-2 h-6 w-2/3 animate-pulse rounded bg-foreground/10" />
        </div>
        <div className="mt-8 flex flex-col gap-4 md:mt-10">
          {Array.from({ length: 4 }).map((_, i) => (
            <div key={i} className="flex items-center justify-between rounded-2xl bg-muted p-6 md:p-8">
              <div className="h-6 w-40 animate-pulse rounded bg-foreground/10" />
              <div className="h-10 w-10 shrink-0 animate-pulse rounded-full bg-foreground/10 md:h-[46px] md:w-[46px]" />
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}

// ─── Resultados dos exames ──────────────────────────────────────────────────

function ResultadosSection({ resultados }: { resultados: ResponsavelResultados }) {
  const { eyebrow, titulo, texto, imagem } = resultados

  if (!eyebrow && !titulo && !texto) return null

  return (
    <section className="border-b border-[rgba(41,19,154,0.2)] bg-background py-16 md:py-20 lg:py-24 xl:py-28">
      <div className="container">
        <div className="grid grid-cols-1 gap-10 lg:grid-cols-[1fr_508px] lg:gap-16 xl:gap-24">
          <div>
            {eyebrow && (
              <p className="font-sans text-body text-brand-purple-accent">{eyebrow}</p>
            )}

            {titulo && (
              <h2 className="mt-2 max-w-[620px] font-heading text-h3 text-brand-purple-dark md:text-h2">
                {titulo.split('\n').map((line, i) => (
                  <span key={i} className="block">
                    {line}
                  </span>
                ))}
              </h2>
            )}

            {texto && (
              <div className="mt-6 max-w-[577px] space-y-4 font-sans text-body leading-relaxed text-brand-gray-text md:mt-8">
                {texto.split('\n\n').map((paragrafo, i) => (
                  <p key={i}>{paragrafo}</p>
                ))}
              </div>
            )}
          </div>

          {imagem && (
            <img
              src={imagem.src}
              alt={imagem.alt}
              className="h-64 w-full rounded-2xl object-cover sm:h-auto sm:aspect-square lg:h-full"
            />
          )}
        </div>
      </div>
    </section>
  )
}

function ResultadosSkeleton() {
  return (
    <section className="border-b border-[rgba(41,19,154,0.2)] bg-background py-16 md:py-20 lg:py-24 xl:py-28">
      <div className="container">
        <div className="grid grid-cols-1 gap-10 lg:grid-cols-[1fr_508px] lg:gap-16 xl:gap-24">
          <div className="max-w-[577px] space-y-4">
            <div className="h-5 w-24 animate-pulse rounded bg-foreground/10" />
            <div className="h-9 w-full animate-pulse rounded bg-foreground/10" />
            <div className="mt-4 h-24 w-full animate-pulse rounded bg-foreground/10" />
          </div>
          <div className="h-64 w-full animate-pulse rounded-2xl bg-foreground/10 sm:aspect-square sm:h-auto" />
        </div>
      </div>
    </section>
  )
}

// ─── Benefícios ───────────────────────────────────────────────────────────────

function BeneficiosSection({ beneficios }: { beneficios: ResponsavelBeneficios }) {
  const { eyebrow, titulo, texto, itens } = beneficios

  if (!eyebrow && !titulo && itens.length === 0) return null

  return (
    <section className="bg-background py-16 md:py-20 lg:py-24 xl:py-28">
      <div className="container">
        <div className="flex flex-col gap-2">
          {eyebrow && <p className="font-sans text-body text-brand-purple-accent">{eyebrow}</p>}
          {titulo && (
            <h2 className="max-w-2xl font-heading text-h3 text-brand-purple-dark md:text-h2">
              {titulo}
            </h2>
          )}
        </div>

        {texto && (
          <p className="mt-6 max-w-2xl font-sans text-body-lg leading-relaxed text-brand-gray-text md:mt-8">
            {texto}
          </p>
        )}

        {itens.length > 0 && (
          <div className="mt-10 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 md:mt-14">
            {itens.map((item, i) => (
              <div
                key={i}
                className="flex flex-col gap-8 rounded-xl border border-border bg-white p-7 sm:gap-10 sm:p-8 lg:gap-12 lg:p-9"
              >
                {item.icone && (
                  <img
                    src={item.icone.src}
                    alt={item.icone.alt}
                    className="h-10 w-10 shrink-0 sm:h-12 sm:w-12 lg:h-[52px] lg:w-[52px]"
                  />
                )}
                {item.texto && (
                  <p className="font-sans text-body text-brand-purple-dark">{item.texto}</p>
                )}
              </div>
            ))}
          </div>
        )}
      </div>
    </section>
  )
}

function BeneficiosSkeleton() {
  return (
    <section className="bg-background py-16 md:py-20 lg:py-24 xl:py-28">
      <div className="container">
        <div className="max-w-2xl space-y-4">
          <div className="h-5 w-28 animate-pulse rounded bg-foreground/10" />
          <div className="h-9 w-full animate-pulse rounded bg-foreground/10" />
        </div>
        <div className="mt-10 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 md:mt-14">
          {Array.from({ length: 4 }).map((_, i) => (
            <div key={i} className="h-40 w-full animate-pulse rounded-xl bg-foreground/10" />
          ))}
        </div>
      </div>
    </section>
  )
}
