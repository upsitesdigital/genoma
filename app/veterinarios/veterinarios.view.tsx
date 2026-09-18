import { useCallback, useEffect, useState } from 'react'
import { ChevronLeft, ChevronRight } from 'lucide-react'
import { useModule } from '@/hooks/useModule'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'
import { cn } from '@/lib/cn'
import { boot } from '@/lib/env'
import type {
  VeterinariosData,
  VeterinariosHeroSlide,
  VeterinariosSuporte,
  VeterinariosPraticidade,
  VeterinariosExames,
  VeterinariosEstrutura,
  VeterinariosBeneficios,
} from './veterinarios.schema'

export default function VeterinariosView() {
  const { data, isLoading, error } = useModule<VeterinariosData>('veterinarios')
  useDocumentTitle(data?.hero.slides[0]?.titulo.replace(/\n/g, ' ') ?? 'Veterinários')

  if (isLoading) return <VeterinariosSkeleton />

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
      <PraticidadeSection praticidade={data.praticidade} />
      <ExamesSection exames={data.exames} />
      <EstruturaSection estrutura={data.estrutura} />
      <BeneficiosSection beneficios={data.beneficios} />
    </main>
  )
}

// ─── Hero (carrossel) ─────────────────────────────────────────────────────────

function HeroCarousel({ hero }: { hero: VeterinariosData['hero'] }) {
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

  const waveUrl = `${boot.themeUrl}/app/veterinarios/assets/hero-bottom-wave.svg`

  return (
    <section
      className="relative isolate flex min-h-[620px] w-full items-center bg-gradient-to-br from-[#95ABB7] to-[#AEC6D3] py-20 sm:min-h-[680px] md:py-24 lg:min-h-[720px] xl:min-h-[797px] xl:py-0"
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
        className="pointer-events-none absolute inset-x-0 bottom-[-20px] z-10 mx-auto hidden w-full max-w-[1426px] lg:block"
      />
    </section>
  )
}

function SlideImage({ slide }: { slide: VeterinariosHeroSlide }) {
  return (
    <div className="absolute inset-0 lg:inset-y-0 lg:left-[40%] lg:right-0">
      {slide.imagem && (
        <img
          src={slide.imagem.src}
          alt={slide.imagem.alt}
          className="h-full w-full object-cover object-[72%_20%] lg:object-[center_18%]"
        />
      )}

      {/* Scrim para legibilidade do texto em telas menores (imagem ocupa toda a largura) */}
      <div className="absolute inset-0 bg-gradient-to-r from-[#2D2559]/80 via-[#2D2559]/45 to-[#2D2559]/10 lg:hidden" />

      {/* Fade que funde a borda esquerda da imagem ao fundo (mask do Figma) */}
      <div className="pointer-events-none absolute inset-y-0 left-0 hidden w-1/3 bg-gradient-to-r from-[#AEC6D3] via-[#AEC6D3]/70 to-transparent backdrop-blur-[2px] lg:block" />
    </div>
  )
}

function SlideContent({ slide }: { slide: VeterinariosHeroSlide }) {
  return (
    <div className="max-w-xl text-left lg:max-w-[598px]">
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

// ─── Suporte ──────────────────────────────────────────────────────────────────

function SuporteSection({ suporte }: { suporte: VeterinariosSuporte }) {
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
              <p className="mt-6 max-w-[646px] font-sans text-body-lg leading-relaxed text-brand-gray-text md:mt-8">
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

// ─── Praticidade ────────────────────────────────────────────────────────────

function PraticidadeSection({ praticidade }: { praticidade: VeterinariosPraticidade }) {
  const { eyebrow, titulo, texto, icone, horarios, imagem } = praticidade

  return (
    <section className="bg-background py-16 md:py-20 lg:py-24 xl:py-28">
      <div className="container">
        <div className="relative overflow-hidden rounded-2xl bg-brand-purple">
          {/* Painel decorativo mais escuro atrás da imagem (apenas telas maiores) */}
          <div
            aria-hidden="true"
            className="absolute inset-y-0 right-0 hidden w-[22%] rounded-r-2xl bg-brand-purple-dark lg:block"
          />

          <div className="relative z-10 flex flex-col gap-8 p-8 sm:p-10 md:p-12 lg:flex-row lg:items-center lg:gap-10 lg:p-14 xl:gap-16 xl:p-16">
            <div className="flex flex-col gap-8 lg:max-w-[552px] lg:shrink-0 lg:gap-[46px]">
              <div className="flex flex-col gap-4">
                <div className="flex flex-col gap-2">
                  {eyebrow && <p className="font-sans text-body text-white">{eyebrow}</p>}
                  {titulo && (
                    <h2 className="font-heading text-h3 text-white md:text-h2">{titulo}</h2>
                  )}
                </div>

                {texto && (
                  <p className="whitespace-pre-line font-sans text-body-lg leading-relaxed text-white/70">
                    {texto}
                  </p>
                )}
              </div>

              {horarios.length > 0 && (
                <div className="flex items-center gap-4 rounded-2xl bg-white/10 p-6 sm:gap-6 sm:p-7">
                  {icone && (
                    <img
                      src={icone.src}
                      alt={icone.alt}
                      className="h-10 w-10 shrink-0 sm:h-12 sm:w-12 lg:h-[58px] lg:w-[58px]"
                    />
                  )}
                  <div className="flex flex-col gap-1">
                    {horarios.map((linha, i) => (
                      <p key={i} className="font-heading text-h5 text-white md:text-h4">
                        {linha}
                      </p>
                    ))}
                  </div>
                </div>
              )}
            </div>

            {imagem && (
              <div className="relative h-64 w-full overflow-hidden rounded-2xl sm:h-80 lg:absolute lg:top-[30px] lg:bottom-0 lg:right-10 lg:h-full lg:w-[38%] xl:right-16 xl:w-[478px]">
                <img
                  src={imagem.src}
                  alt={imagem.alt}
                  className="h-full w-full rounded-2xl object-contain"
                />
              </div>
            )}
          </div>
        </div>
      </div>
    </section>
  )
}

function PraticidadeSkeleton() {
  return (
    <section className="bg-background py-16 md:py-20 lg:py-24 xl:py-28">
      <div className="container">
        <div className="flex flex-col gap-8 rounded-3xl bg-muted p-8 sm:p-10 md:p-12 lg:flex-row lg:items-center lg:gap-10 lg:p-14">
          <div className="flex flex-col gap-6 lg:max-w-[552px] lg:shrink-0">
            <div className="h-5 w-28 animate-pulse rounded bg-foreground/10" />
            <div className="h-9 w-64 animate-pulse rounded bg-foreground/10" />
            <div className="h-16 w-full animate-pulse rounded bg-foreground/10" />
            <div className="h-20 w-full animate-pulse rounded-2xl bg-foreground/10" />
          </div>
          <div className="h-64 w-full animate-pulse rounded-2xl bg-foreground/10 sm:h-80 lg:h-72 lg:w-[38%]" />
        </div>
      </div>
    </section>
  )
}

// ─── Exames ─────────────────────────────────────────────────────────────────

function ExamesSection({ exames }: { exames: VeterinariosExames }) {
  const { eyebrow, titulo, texto, cta, imagem } = exames

  return (
    <section className="relative isolate flex min-h-[420px] w-full items-center overflow-hidden bg-[#859CA9] py-16 sm:min-h-[480px] md:py-20 lg:min-h-[560px] xl:min-h-[625px] xl:py-0">
      {/* Imagem de fundo + gradientes de legibilidade */}
      <div className="absolute inset-0 lg:inset-y-0 lg:left-[43%] lg:right-0">
        {imagem && (
          <img
            src={imagem.src}
            alt={imagem.alt}
            className="h-full w-full object-cover object-[70%_30%] lg:object-[center_25%]"
          />
        )}

        {/* Scrim para legibilidade do texto em telas menores (imagem ocupa toda a largura) */}
        <div className="absolute inset-0 bg-gradient-to-r from-[#433292]/70 via-[#433292]/35 to-[#433292]/0 lg:hidden" />

        {/* Fade que funde a borda esquerda da imagem ao fundo (mask do Figma) */}
        <div className="pointer-events-none absolute inset-y-0 left-0 hidden w-1/3 bg-gradient-to-r from-[#859CA9] via-[#859CA9]/70 to-transparent lg:block" />
      </div>

      <div className="container relative z-10">
        <div className="flex max-w-xl flex-col gap-6 lg:max-w-[594px] lg:gap-8">
          <div className="flex flex-col gap-2">
            {eyebrow && <p className="font-sans text-body text-white">{eyebrow}</p>}
            {titulo && <h2 className="font-heading text-h3 text-white md:text-h2">{titulo}</h2>}
          </div>

          {texto && (
            <p className="max-w-[560px] font-sans text-body leading-relaxed text-white/80">
              {texto}
            </p>
          )}

          {cta.texto && (
            <a
              href={cta.link || '#'}
              className="inline-flex w-fit items-center justify-center rounded-full bg-white px-7 py-5 font-heading text-h4 leading-none text-primary transition-opacity hover:opacity-90"
            >
              {cta.texto}
            </a>
          )}
        </div>
      </div>
    </section>
  )
}

function ExamesSkeleton() {
  return (
    <section className="relative flex min-h-[420px] w-full items-center overflow-hidden bg-muted py-16 sm:min-h-[480px] md:py-20 lg:min-h-[560px] xl:min-h-[625px]">
      <div className="container relative z-10">
        <div className="max-w-xl space-y-4 lg:max-w-[594px]">
          <div className="h-5 w-24 animate-pulse rounded bg-foreground/10" />
          <div className="h-9 w-full animate-pulse rounded bg-foreground/10" />
          <div className="mt-4 h-16 w-full animate-pulse rounded bg-foreground/10" />
          <div className="mt-4 h-14 w-40 animate-pulse rounded-full bg-foreground/10" />
        </div>
      </div>
    </section>
  )
}

// ─── Estrutura ──────────────────────────────────────────────────────────────

function EstruturaSection({ estrutura }: { estrutura: VeterinariosEstrutura }) {
  const { eyebrow, titulo, texto, destaque, imagem1, imagem2, contatos } = estrutura

  return (
    <section className="bg-background py-16 md:py-20 lg:py-24 xl:py-28">
      <div className="container">
        <div className="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between lg:gap-10">
          <div className="flex flex-col gap-6 lg:gap-8">
            <div className="flex flex-col gap-2">
              {eyebrow && (
                <p className="font-sans text-body text-brand-purple-accent">{eyebrow}</p>
              )}
              {titulo && (
                <h2 className="font-heading text-h3 text-brand-purple-dark md:text-h2">
                  {titulo.split('\n').map((line, i) => (
                    <span key={i} className="block">
                      {line}
                    </span>
                  ))}
                </h2>
              )}
            </div>

            {texto && (
              <p className="max-w-2xl font-sans text-body-lg leading-relaxed text-brand-gray-text lg:max-w-[754px]">
                {texto}
              </p>
            )}
          </div>

          {destaque && (
            <p className="font-heading text-h4 text-brand-purple-dark lg:max-w-[268px] lg:text-right">
              {destaque}
            </p>
          )}
        </div>

        <div className="mt-10 grid grid-cols-1 gap-4 md:grid-cols-2 md:gap-5 lg:grid-cols-3 md:mt-14">
          {imagem1 && (
            <img
              src={imagem1.src}
              alt={imagem1.alt}
              className="h-64 w-full rounded-2xl object-cover lg:h-[374px]"
            />
          )}
          {imagem2 && (
            <img
              src={imagem2.src}
              alt={imagem2.alt}
              className="h-64 w-full rounded-2xl object-cover lg:h-[374px]"
            />
          )}

          {contatos.length > 0 && (
            <div className="flex flex-col justify-center gap-6 rounded-2xl bg-brand-purple p-8 sm:p-10 md:col-span-2 lg:col-span-1 lg:h-[374px] lg:gap-11">
              {contatos.map((contato, i) => (
                <div key={i} className="flex items-center gap-6">
                  {contato.icone && (
                    <img
                      src={contato.icone.src}
                      alt={contato.icone.alt}
                      className="h-10 w-10 shrink-0 sm:h-12 sm:w-12 lg:h-[52px] lg:w-[52px]"
                    />
                  )}
                  {contato.texto && (
                    <p className="whitespace-pre-line font-sans text-body text-white">
                      {contato.texto}
                    </p>
                  )}
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </section>
  )
}

function EstruturaSkeleton() {
  return (
    <section className="bg-background py-16 md:py-20 lg:py-24 xl:py-28">
      <div className="container">
        <div className="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between lg:gap-10">
          <div className="max-w-[754px] flex-1 space-y-4">
            <div className="h-5 w-40 animate-pulse rounded bg-foreground/10" />
            <div className="h-9 w-full animate-pulse rounded bg-foreground/10" />
            <div className="mt-4 h-16 w-full animate-pulse rounded bg-foreground/10" />
          </div>
          <div className="h-16 w-64 animate-pulse rounded bg-foreground/10 lg:w-[268px]" />
        </div>
        <div className="mt-10 grid grid-cols-1 gap-4 md:grid-cols-2 md:gap-5 lg:grid-cols-3 md:mt-14">
          <div className="h-64 w-full animate-pulse rounded-2xl bg-foreground/10 lg:h-[374px]" />
          <div className="h-64 w-full animate-pulse rounded-2xl bg-foreground/10 lg:h-[374px]" />
          <div className="h-64 w-full animate-pulse rounded-2xl bg-foreground/10 md:col-span-2 lg:col-span-1 lg:h-[374px]" />
        </div>
      </div>
    </section>
  )
}

// ─── Benefícios ─────────────────────────────────────────────────────────────

function BeneficiosSection({ beneficios }: { beneficios: VeterinariosBeneficios }) {
  const { eyebrow, titulo, texto, itens } = beneficios

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
          <div className="mt-4 h-14 w-2/3 animate-pulse rounded bg-foreground/10" />
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

function VeterinariosSkeleton() {
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
      <PraticidadeSkeleton />
      <ExamesSkeleton />
      <EstruturaSkeleton />
      <BeneficiosSkeleton />
    </main>
  )
}
