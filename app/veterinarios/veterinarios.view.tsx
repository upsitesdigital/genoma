import { useCallback, useEffect, useState } from 'react'
import { ChevronLeft, ChevronRight, ChevronDown } from 'lucide-react'
import { useModule } from '@/hooks/useModule'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'
import { useSwipe } from '@/hooks/useSwipe'
import { cn } from '@/lib/cn'
import { boot } from '@/lib/env'
import { VerMaisButton, VISIVEIS_INICIAL } from '@/components/shared/VerMaisButton'
import type {
  VeterinariosData,
  VeterinariosHeroSlide,
  VeterinariosSuporte,
  VeterinariosPraticidade,
  VeterinariosTiposExames,
  VeterinariosExameCategoria,
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
      <TiposExamesSection tiposExames={data.tiposExames} />
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
  const swipe = useSwipe({ onNext: next, onPrev: prev })

  useEffect(() => {
    if (!autoplay || paused || total <= 1) return
    const id = window.setInterval(next, Math.max(intervalo, 2000))
    return () => window.clearInterval(id)
  }, [autoplay, paused, total, intervalo, next])

  if (total === 0) return null

  const waveUrl = `${boot.themeUrl}/app/veterinarios/assets/hero-bottom-wave.svg`

  return (
    <section id="hero"
      className="relative isolate flex min-h-[620px] w-full items-center bg-gradient-to-br from-[#95ABB7] to-[#AEC6D3] py-20 sm:min-h-[680px] md:py-24 lg:min-h-[720px] xl:min-h-[797px] xl:py-0"
      onMouseEnter={() => setPaused(true)}
      onMouseLeave={() => setPaused(false)}
      aria-roledescription="carousel"
      {...swipe}
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

          <div className="container relative z-10 flex h-full items-start pt-[186px] lg:items-center lg:pt-0">
            <SlideContent slide={slide} isFirst={i === 0} />
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
            className="absolute left-10 top-1/2 z-20 hidden h-[58px] w-[58px] -translate-y-1/2 items-center justify-center rounded-full bg-white shadow-md transition hover:bg-white/90 lg:flex"
          >
            <ChevronLeft className="h-5 w-5 text-primary sm:h-6 sm:w-6" />
          </button>
          <button
            type="button"
            onClick={next}
            aria-label="Próximo slide"
            className="absolute right-10 top-1/2 z-20 hidden h-[58px] w-[58px] -translate-y-1/2 items-center justify-center rounded-full bg-white shadow-md transition hover:bg-white/90 lg:flex"
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
      {/* Mask (como no Figma) funde a borda esquerda da imagem ao gradiente da seção,
          sem a emenda de cor que um overlay de cor fixa deixava */}
      {slide.imagem && (
        <img
          src={slide.imagem.src}
          alt={slide.imagem.alt}
          className="h-full w-full object-cover object-[72%_20%] lg:object-[center_18%] lg:[mask-image:linear-gradient(to_right,transparent,black_40%)]"
        />
      )}

      {/* Scrim para legibilidade do texto em telas menores (imagem ocupa toda a largura) */}
      <div className="absolute inset-0 bg-gradient-to-r from-[#2D2559]/80 via-[#2D2559]/45 to-[#2D2559]/10 lg:hidden" />
    </div>
  )
}

function SlideContent({ slide, isFirst }: { slide: VeterinariosHeroSlide; isFirst: boolean }) {
  const Eyebrow = isFirst ? 'h1' : 'p'

  return (
    <div className="max-w-xl text-left lg:max-w-[598px]">
      {slide.eyebrow && (
        <Eyebrow className="font-sans text-body text-white">{slide.eyebrow}</Eyebrow>
      )}

      <h2 className="mt-3 font-heading text-3xl font-medium leading-tight text-white md:text-4xl lg:mt-6 lg:text-h1">
        {slide.titulo.split('\n').map((line, i) => (
          <span key={i} className="block">
            {line}
          </span>
        ))}
      </h2>

      {slide.subtitulo && (
        <p className="mt-4 max-w-md font-heading text-base font-medium leading-snug text-white md:mt-6 lg:max-w-[542px] lg:text-h4">
          {slide.subtitulo}
        </p>
      )}

      {(slide.ctaPrimario.texto || slide.ctaSecundario.texto) && (
        <div className="mt-8 flex flex-col items-stretch gap-3 md:mt-10 lg:flex-row lg:flex-wrap lg:items-center lg:gap-4">
          {slide.ctaPrimario.texto && (
            <a
              href={slide.ctaPrimario.link || '#'}
              className="inline-flex w-full items-center justify-center rounded-full bg-primary px-7 py-5 font-heading text-h5 lg:w-auto leading-none text-white transition-colors hover:bg-brand-purple-dark"
            >
              {slide.ctaPrimario.texto}
            </a>
          )}
          {slide.ctaSecundario.texto && (
            <a
              href={slide.ctaSecundario.link || '#'}
              className="inline-flex w-full items-center justify-center rounded-full bg-white px-7 py-5 font-heading text-h5 lg:w-auto leading-none text-primary transition-colors hover:bg-primary hover:text-white"
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
    <section id="suporte" className="bg-background pb-6 pt-12 md:pb-10 md:pt-20 lg:pb-12 lg:pt-24 xl:pb-14 xl:pt-28">
      <div className="container">
        <div className="grid grid-cols-1 gap-10 lg:grid-cols-[1fr_351px] lg:gap-16 xl:gap-24">
          <div>
            {eyebrow && (
              <p className="font-sans text-eyebrow uppercase text-brand-purple-accent">{eyebrow}</p>
            )}

            {titulo && (
              <h2 className="mt-2 max-w-[620px] font-heading text-h2-mobile text-brand-purple-dark md:text-h2">
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
    <section className="bg-background py-12 md:py-20 lg:py-24 xl:py-28">
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
    <section id="praticidade" className="bg-background py-12 md:py-20 lg:py-24 xl:py-28">
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
    <section className="bg-background py-12 md:py-20 lg:py-24 xl:py-28">
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

// ─── Exames (acordeão) ──────────────────────────────────────────────────────

function TiposExamesSection({ tiposExames }: { tiposExames: VeterinariosTiposExames }) {
  const { eyebrow, titulo, descricao, categorias, cta } = tiposExames
  const [openIndex, setOpenIndex] = useState<number | null>(null)
  const [expanded, setExpanded] = useState(false)

  if (categorias.length === 0) return null

  const hasMore = categorias.length > VISIVEIS_INICIAL
  const visiveis = expanded ? categorias : categorias.slice(0, VISIVEIS_INICIAL)

  return (
    <section id="tipos-de-exames" className="w-full py-12 md:py-20 lg:py-24">
      <div className="container">
        <div className="flex flex-col gap-4">
          <div className="flex flex-col gap-2">
            {eyebrow && (
              <span className="font-sans text-eyebrow uppercase text-brand-purple-accent">{eyebrow}</span>
            )}
            <h2 className="max-w-xl font-heading text-h2-mobile text-brand-purple-dark md:max-w-2xl md:text-h2">
              {titulo}
            </h2>
          </div>
          {descricao && (
            <p className="max-w-xl font-sans text-body-sm text-brand-gray-text md:max-w-2xl md:text-base">
              {descricao}
            </p>
          )}
        </div>

        <div className="mt-8 flex flex-col gap-4 md:mt-10">
          {visiveis.map((categoria, i) => (
            <ExameCategoriaAccordion
              key={i}
              categoria={categoria}
              isOpen={openIndex === i}
              onToggle={() => setOpenIndex((prev) => (prev === i ? null : i))}
            />
          ))}
        </div>

        <VerMaisButton hasMore={hasMore} expanded={expanded} onToggle={() => setExpanded((v) => !v)} cta={cta} />
      </div>
    </section>
  )
}

function ExameCategoriaAccordion({
  categoria,
  isOpen,
  onToggle,
}: {
  categoria: VeterinariosExameCategoria
  isOpen: boolean
  onToggle: () => void
}) {
  const { titulo, texto, itens } = categoria
  const hasItens = itens.length > 0
  const hasContent = hasItens || !!texto

  return (
    <div className="flex flex-col rounded-2xl bg-brand-purple-subtle p-5 md:p-6 lg:p-8">
      <div className="flex w-full items-center justify-between gap-4">
        <h3 className="font-heading text-h5 font-medium text-brand-purple-dark md:text-h4">
          {titulo}
        </h3>

        <button
          type="button"
          onClick={onToggle}
          disabled={!hasContent}
          aria-expanded={isOpen}
          aria-label={isOpen ? `Recolher ${titulo}` : `Expandir ${titulo}`}
          className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-purple text-white transition-opacity disabled:opacity-40 sm:h-[46px] sm:w-[46px]"
        >
          <ChevronDown
            className={cn('h-4 w-4 transition-transform duration-300', isOpen && 'rotate-180')}
          />
        </button>
      </div>

      {hasContent && (
        <div
          className="grid transition-[grid-template-rows] duration-300 ease-in-out"
          style={{ gridTemplateRows: isOpen ? '1fr' : '0fr' }}
        >
          {/* Espaçamento como padding interno: some junto ao recolher (sem gap fixo) */}
          <div className="overflow-hidden">
          <div className="flex flex-col gap-4 pt-6 sm:pt-9">
            {texto && (
              <p className="font-sans text-body-sm text-brand-gray-text">{texto}</p>
            )}
            {hasItens && (
              <div className="flex flex-col gap-2">
                {itens.map((item, i) => (
                  <div key={i} className="flex w-full flex-wrap items-center gap-2">
                    <div className="min-w-[200px] flex-1 self-stretch rounded-2xl bg-white px-4 py-4 font-sans text-sm font-semibold text-brand-purple-dark sm:text-base">
                      {item.nome}
                    </div>
                    {/* Larguras fixas: dias e amostra alinham em coluna em todas as linhas */}
                    {item.prazo && (
                      <div className="flex w-full shrink-0 items-center justify-center self-stretch rounded-2xl bg-white px-4 py-4 text-center font-sans text-body-sm text-brand-purple-dark sm:w-[132px]">
                        {item.prazo}
                      </div>
                    )}
                    {item.amostra && (
                      <div className="flex w-full shrink-0 items-center justify-center self-stretch rounded-2xl bg-white px-4 py-4 text-center font-sans text-body-sm text-brand-purple-dark sm:w-[220px]">
                        {item.amostra}
                      </div>
                    )}
                  </div>
                ))}
              </div>
            )}
          </div>
          </div>
        </div>
      )}
    </div>
  )
}

function TiposExamesSkeleton() {
  return (
    <section className="w-full py-12 md:py-20 lg:py-24">
      <div className="container">
        <div className="flex flex-col gap-2">
          <div className="h-5 w-24 animate-pulse rounded bg-foreground/10" />
          <div className="h-9 w-2/3 animate-pulse rounded bg-foreground/10 md:h-10" />
          <div className="mt-2 h-5 w-full max-w-xl animate-pulse rounded bg-foreground/10" />
        </div>

        <div className="mt-8 flex flex-col gap-4 md:mt-10">
          {Array.from({ length: 4 }).map((_, i) => (
            <div key={i} className="flex items-center justify-between gap-4 rounded-2xl bg-brand-purple-subtle p-5 md:p-6 lg:p-8">
              <div className="h-7 w-40 animate-pulse rounded bg-foreground/10" />
              <div className="h-10 w-10 shrink-0 animate-pulse rounded-full bg-foreground/10" />
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}

// ─── Estrutura ──────────────────────────────────────────────────────────────

function EstruturaSection({ estrutura }: { estrutura: VeterinariosEstrutura }) {
  const { eyebrow, titulo, texto, destaque, imagem1, imagem2, contatos } = estrutura

  return (
    <section id="estrutura" className="bg-background pb-6 pt-12 md:pb-10 md:pt-20 lg:pb-12 lg:pt-24 xl:pb-14 xl:pt-28">
      <div className="container">
        <div className="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between lg:gap-10">
          <div className="flex flex-col gap-6 lg:gap-8">
            <div className="flex flex-col gap-2">
              {eyebrow && (
                <p className="font-sans text-eyebrow uppercase text-brand-purple-accent">{eyebrow}</p>
              )}
              {titulo && (
                <h2 className="font-heading text-h2-mobile text-brand-purple-dark md:text-h2">
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
    <section className="bg-background py-12 md:py-20 lg:py-24 xl:py-28">
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
    <section id="beneficios" className="bg-background py-12 md:py-20 lg:py-24 xl:py-28">
      <div className="container">
        <div className="flex flex-col gap-2">
          {eyebrow && <p className="font-sans text-eyebrow uppercase text-brand-purple-accent">{eyebrow}</p>}
          {titulo && (
            <h2 className="max-w-2xl font-heading text-h2-mobile text-brand-purple-dark md:text-h2">
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
    <section className="bg-background py-12 md:py-20 lg:py-24 xl:py-28">
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
      <TiposExamesSkeleton />
      <EstruturaSkeleton />
      <BeneficiosSkeleton />
    </main>
  )
}
