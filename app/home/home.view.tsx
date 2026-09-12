import { useCallback, useEffect, useRef, useState } from 'react'
import { ChevronLeft, ChevronRight, ChevronDown } from 'lucide-react'
import { useModule } from '@/hooks/useModule'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'
import { cn } from '@/lib/cn'
import type {
  HomeData,
  HomeHeroSlide,
  HomeServicoItem,
  HomeServicos,
  HomeExameCategoria,
  HomeExames,
  HomeDiferencial,
  HomeSobre,
  HomeDiferenciaisItem,
  HomeDiferenciais,
  HomeEstrutura,
  HomeDepoimentoItem,
  HomeDepoimentos,
} from './home.schema'

export default function HomeView() {
  const { data, isLoading, error } = useModule<HomeData>('home')
  useDocumentTitle(data?.hero.slides[0]?.titulo ?? 'Home')

  if (isLoading) return <HomeSkeleton />

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
      <ServicosSection servicos={data.servicos} />
      <ExamesSection exames={data.exames} />
      <SobreSection sobre={data.sobre} />
      <DiferenciaisSection diferenciais={data.diferenciais} />
      <EstruturaSection estrutura={data.estrutura} />
      <DepoimentosSection depoimentos={data.depoimentos} />
    </main>
  )
}

// ─── Hero (carrossel) ─────────────────────────────────────────────────────────

function HeroCarousel({ hero }: { hero: HomeData['hero'] }) {
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
      className="relative isolate flex min-h-[560px] w-full items-center overflow-hidden py-20 sm:min-h-[620px] md:py-24 lg:min-h-[720px] xl:min-h-[797px]"
      onMouseEnter={() => setPaused(true)}
      onMouseLeave={() => setPaused(false)}
      aria-roledescription="carousel"
    >
      {/* Slides (imagem de fundo + overlay) */}
      {slides.map((slide, i) => (
        <div
          key={i}
          className={cn(
            'absolute inset-0 transition-opacity duration-700 ease-in-out',
            i === index ? 'opacity-100' : 'pointer-events-none opacity-0'
          )}
          aria-hidden={i !== index}
        >
          <img
            src={slide.imagem.src}
            alt={slide.imagem.alt}
            className="h-full w-full object-cover"
          />
          <div className="absolute inset-0 bg-gradient-to-r from-brand-purple-dark/80 via-brand-purple-dark/40 to-transparent" />
        </div>
      ))}

      {/* Conteúdo do slide ativo */}
      <div className="container relative z-10">
        <SlideContent slide={slides[index]} />
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
    </section>
  )
}

function SlideContent({ slide }: { slide: HomeHeroSlide }) {
  return (
    <div className="max-w-xl text-left lg:max-w-2xl">
      <h1 className="font-heading text-3xl font-medium leading-tight text-white md:text-4xl lg:text-5xl xl:text-h1">
        {slide.titulo}
      </h1>

      {slide.subtitulo && (
        <p className="mt-4 max-w-md font-heading text-base font-medium text-white/90 md:mt-6 lg:max-w-lg lg:text-h4">
          {slide.subtitulo}
        </p>
      )}

      {(slide.ctaPrimario.texto || slide.ctaSecundario.texto) && (
        <div className="mt-8 flex flex-wrap items-center gap-4 md:mt-10">
          {slide.ctaPrimario.texto && (
            <a
              href={slide.ctaPrimario.link || '#'}
              className="inline-flex items-center justify-center rounded-full bg-primary px-7 py-5 font-heading text-h5 leading-none text-white transition-opacity hover:opacity-90"
            >
              {slide.ctaPrimario.texto}
            </a>
          )}
          {slide.ctaSecundario.texto && (
            <a
              href={slide.ctaSecundario.link || '#'}
              className="inline-flex items-center justify-center rounded-full bg-white px-7 py-5 font-heading text-h5 leading-none text-primary transition-opacity hover:opacity-90"
            >
              {slide.ctaSecundario.texto}
            </a>
          )}
        </div>
      )}
    </div>
  )
}

function HomeSkeleton() {
  return (
    <main>
      <section className="relative flex min-h-[560px] w-full items-center overflow-hidden bg-muted py-20 sm:min-h-[620px] md:py-24 lg:min-h-[720px] xl:min-h-[797px]">
        <div className="container">
          <div className="max-w-xl space-y-4">
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
      <ServicosSkeleton />
      <ExamesSkeleton />
      <SobreSkeleton />
      <DiferenciaisSkeleton />
      <EstruturaSkeleton />
      <DepoimentosSkeleton />
    </main>
  )
}

// ─── Serviços (grade de cards) ─────────────────────────────────────────────────

function ServicosSection({ servicos }: { servicos: HomeServicos }) {
  const { eyebrow, titulo, itens, cta } = servicos

  if (itens.length === 0) return null

  return (
    <section className="w-full py-16 md:py-20 lg:py-24">
      <div className="container">
        <div className="flex max-w-2xl flex-col gap-2">
          {eyebrow && (
            <span className="font-sans text-base text-brand-purple-accent">{eyebrow}</span>
          )}
          <h2 className="font-heading text-h3 font-normal text-brand-purple-dark md:text-h2">
            {titulo}
          </h2>
        </div>

        <div className="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 md:mt-10 lg:grid-cols-3 lg:gap-8">
          {itens.map((item, i) => (
            <ServicoCard key={i} item={item} />
          ))}
        </div>

        {cta.texto && (
          <div className="mt-10 flex justify-center md:mt-12">
            <a
              href={cta.link || '#'}
              className="inline-flex items-center justify-center rounded-full border border-brand-purple px-6 py-4 font-heading text-base font-medium text-brand-purple transition-colors hover:bg-brand-purple hover:text-white md:px-7 md:py-5 md:text-h5"
            >
              {cta.texto}
            </a>
          </div>
        )}
      </div>
    </section>
  )
}

function ServicoCard({ item }: { item: HomeServicoItem }) {
  return (
    <div className="flex flex-col gap-9 rounded-2xl bg-brand-light-purple p-6 md:p-8">
      <div className="flex flex-col gap-9">
        <img
          src={item.imagem.src}
          alt={item.imagem.alt}
          className="h-[170px] w-full rounded-lg object-cover"
        />
        <div className="flex flex-col gap-4">
          <h3 className="font-heading text-h5 font-medium text-brand-purple-dark">
            {item.titulo}
          </h3>
          {item.descricao && (
            <p className="font-sans text-base font-semibold text-brand-gray-text">
              {item.descricao}
            </p>
          )}
        </div>
      </div>

      {item.cta.texto && (
        <a
          href={item.cta.link || '#'}
          className="inline-flex w-fit items-center justify-center gap-2 rounded-full bg-brand-purple px-4 py-3.5 font-sans text-base font-semibold text-white transition-opacity hover:opacity-90"
        >
          {item.cta.texto}
        </a>
      )}
    </div>
  )
}

function ServicosSkeleton() {
  return (
    <section className="w-full py-16 md:py-20 lg:py-24">
      <div className="container">
        <div className="flex max-w-2xl flex-col gap-2">
          <div className="h-5 w-32 animate-pulse rounded bg-foreground/10" />
          <div className="h-9 w-full animate-pulse rounded bg-foreground/10 md:h-10" />
        </div>

        <div className="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 md:mt-10 lg:grid-cols-3 lg:gap-8">
          {Array.from({ length: 6 }).map((_, i) => (
            <div key={i} className="flex flex-col gap-9 rounded-2xl bg-brand-light-purple p-6 md:p-8">
              <div className="h-[170px] w-full animate-pulse rounded-lg bg-foreground/10" />
              <div className="space-y-3">
                <div className="h-6 w-2/3 animate-pulse rounded bg-foreground/10" />
                <div className="h-4 w-full animate-pulse rounded bg-foreground/10" />
              </div>
              <div className="h-11 w-28 animate-pulse rounded-full bg-foreground/10" />
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}

// ─── Exames (acordeão) ──────────────────────────────────────────────────────

function ExamesSection({ exames }: { exames: HomeExames }) {
  const { eyebrow, titulo, descricao, categorias, cta } = exames
  const [openIndex, setOpenIndex] = useState<number | null>(null)

  if (categorias.length === 0) return null

  return (
    <section className="w-full py-16 md:py-20 lg:py-24">
      <div className="container">
        <div className="flex flex-col gap-4">
          <div className="flex flex-col gap-2">
            {eyebrow && (
              <span className="font-sans text-base text-brand-purple-accent">{eyebrow}</span>
            )}
            <h2 className="max-w-xl font-heading text-h3 font-normal text-brand-purple-dark md:max-w-2xl md:text-h2">
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
          {categorias.map((categoria, i) => (
            <ExameCategoriaAccordion
              key={i}
              categoria={categoria}
              isOpen={openIndex === i}
              onToggle={() => setOpenIndex((prev) => (prev === i ? null : i))}
            />
          ))}
        </div>

        {cta.texto && (
          <div className="mt-10 flex justify-center md:mt-12">
            <a
              href={cta.link || '#'}
              className="inline-flex items-center justify-center rounded-full border border-brand-purple px-6 py-4 font-heading text-base font-medium text-brand-purple transition-colors hover:bg-brand-purple hover:text-white md:px-7 md:py-5 md:text-h5"
            >
              {cta.texto}
            </a>
          </div>
        )}
      </div>
    </section>
  )
}

function ExameCategoriaAccordion({
  categoria,
  isOpen,
  onToggle,
}: {
  categoria: HomeExameCategoria
  isOpen: boolean
  onToggle: () => void
}) {
  const { titulo, itens } = categoria
  const hasItens = itens.length > 0

  return (
    <div className="flex flex-col gap-6 rounded-2xl bg-brand-purple-subtle p-5 sm:gap-9 md:p-6 lg:p-8">
      <div className="flex w-full items-center justify-between gap-4">
        <h3 className="font-heading text-h5 font-medium text-brand-purple-dark md:text-h4">
          {titulo}
        </h3>

        <button
          type="button"
          onClick={onToggle}
          disabled={!hasItens}
          aria-expanded={isOpen}
          aria-label={isOpen ? `Recolher ${titulo}` : `Expandir ${titulo}`}
          className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-purple text-white transition-opacity disabled:opacity-40 sm:h-[46px] sm:w-[46px]"
        >
          <ChevronDown
            className={cn('h-4 w-4 transition-transform duration-300', isOpen && 'rotate-180')}
          />
        </button>
      </div>

      {hasItens && (
        <div
          className="grid transition-[grid-template-rows] duration-300 ease-in-out"
          style={{ gridTemplateRows: isOpen ? '1fr' : '0fr' }}
        >
          <div className="flex flex-col gap-2 overflow-hidden">
            {itens.map((item, i) => (
              <div key={i} className="flex w-full flex-wrap items-center gap-2">
                <div className="min-w-[200px] flex-1 rounded-2xl bg-white px-4 py-4 font-sans text-sm font-semibold text-brand-purple-dark sm:text-base">
                  {item.nome}
                </div>
                {item.prazo && (
                  <div className="w-full shrink-0 rounded-2xl bg-white px-4 py-4 text-center font-sans text-body-sm text-brand-purple-dark sm:w-auto sm:min-w-[132px]">
                    {item.prazo}
                  </div>
                )}
                {item.amostra && (
                  <div className="w-full shrink-0 rounded-2xl bg-white px-4 py-4 text-center font-sans text-body-sm text-brand-purple-dark sm:w-auto">
                    {item.amostra}
                  </div>
                )}
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  )
}

function ExamesSkeleton() {
  return (
    <section className="w-full py-16 md:py-20 lg:py-24">
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

// ─── Sobre o Genoma ─────────────────────────────────────────────────────────

function SobreSection({ sobre }: { sobre: HomeSobre }) {
  const { eyebrow, titulo, fotos, paragrafos, destaque, diferenciais } = sobre
  const [foto1, foto2, foto3] = fotos

  return (
    <section className="w-full py-16 md:py-20 lg:py-24">
      <div className="container flex flex-col gap-12 md:gap-14 lg:gap-[73px]">
        {/* Cabeçalho + fotos */}
        <div className="flex flex-col gap-8 md:gap-10 lg:gap-[49px]">
          <div className="flex max-w-xl flex-col gap-2 lg:max-w-[476px]">
            {eyebrow && (
              <span className="font-sans text-base text-brand-purple-accent">{eyebrow}</span>
            )}
            <h2 className="font-heading text-h3 font-normal text-brand-purple-dark md:text-h2">
              {titulo}
            </h2>
          </div>

          {fotos.length > 0 && (
            <div className="flex flex-col gap-4 sm:flex-row sm:items-stretch sm:gap-5">
              {foto1 && (
                <img
                  src={foto1.src}
                  alt={foto1.alt}
                  className="h-64 w-full rounded-2xl object-cover sm:h-72 sm:flex-1 md:h-80 lg:h-[389px]"
                />
              )}
              {foto2 && (
                <img
                  src={foto2.src}
                  alt={foto2.alt}
                  className="h-64 w-full rounded-2xl object-cover sm:h-72 sm:flex-1 md:h-80 lg:h-[389px]"
                />
              )}
              {foto3 && (
                <img
                  src={foto3.src}
                  alt={foto3.alt}
                  className="h-64 w-full rounded-2xl object-cover sm:h-72 sm:flex-[1.8] md:h-80 lg:h-[389px]"
                />
              )}
            </div>
          )}
        </div>

        {/* Texto institucional + frase de destaque */}
        {(paragrafos.length > 0 || destaque) && (
          <div className="flex flex-col gap-8 lg:flex-row lg:items-start lg:gap-16">
            {paragrafos.length > 0 && (
              <div className="flex flex-col gap-4 font-sans text-base text-brand-gray-text lg:max-w-xl lg:flex-1">
                {paragrafos.map((paragrafo, i) => (
                  <p key={i}>{paragrafo}</p>
                ))}
              </div>
            )}

            {destaque && (
              <div className="flex flex-col gap-6 border-y border-brand-purple py-6 lg:w-[351px] lg:shrink-0">
                <p className="font-sans text-base text-brand-purple-dark">{destaque}</p>
              </div>
            )}
          </div>
        )}

        {/* Cards de diferenciais */}
        {diferenciais.length > 0 && (
          <div className="grid grid-cols-1 gap-4 md:grid-cols-3 md:gap-6">
            {diferenciais.map((item, i) => (
              <DiferencialCard key={i} item={item} />
            ))}
          </div>
        )}
      </div>
    </section>
  )
}

function DiferencialCard({ item }: { item: HomeDiferencial }) {
  return (
    <div className="flex flex-col gap-8 rounded-2xl bg-brand-light-purple p-6 md:gap-9 md:p-9">
      <img src={item.icone.src} alt={item.icone.alt} className="h-[52px] w-[52px]" />
      <div className="flex flex-col gap-4">
        <h3 className="font-heading text-h5 font-medium text-brand-purple-dark md:text-h4">
          {item.titulo}
        </h3>
        {item.descricao && (
          <p className="font-sans text-sm text-brand-gray-text md:text-base">{item.descricao}</p>
        )}
      </div>
    </div>
  )
}

function SobreSkeleton() {
  return (
    <section className="w-full py-16 md:py-20 lg:py-24">
      <div className="container flex flex-col gap-12 md:gap-14 lg:gap-[73px]">
        <div className="flex flex-col gap-8 md:gap-10 lg:gap-[49px]">
          <div className="flex max-w-xl flex-col gap-2">
            <div className="h-5 w-32 animate-pulse rounded bg-foreground/10" />
            <div className="h-9 w-full animate-pulse rounded bg-foreground/10 md:h-10" />
          </div>
          <div className="flex flex-col gap-4 sm:flex-row sm:gap-5">
            <div className="h-64 w-full animate-pulse rounded-2xl bg-foreground/10 sm:h-72 sm:flex-1 md:h-80 lg:h-[389px]" />
            <div className="h-64 w-full animate-pulse rounded-2xl bg-foreground/10 sm:h-72 sm:flex-1 md:h-80 lg:h-[389px]" />
            <div className="h-64 w-full animate-pulse rounded-2xl bg-foreground/10 sm:h-72 sm:flex-[1.8] md:h-80 lg:h-[389px]" />
          </div>
        </div>

        <div className="flex flex-col gap-8 lg:flex-row lg:gap-16">
          <div className="flex flex-col gap-3 lg:max-w-xl lg:flex-1">
            <div className="h-4 w-full animate-pulse rounded bg-foreground/10" />
            <div className="h-4 w-full animate-pulse rounded bg-foreground/10" />
            <div className="h-4 w-2/3 animate-pulse rounded bg-foreground/10" />
          </div>
          <div className="h-16 w-full animate-pulse rounded bg-foreground/10 lg:w-[351px] lg:shrink-0" />
        </div>

        <div className="grid grid-cols-1 gap-4 md:grid-cols-3 md:gap-6">
          {Array.from({ length: 3 }).map((_, i) => (
            <div key={i} className="flex flex-col gap-8 rounded-2xl bg-brand-light-purple p-6 md:gap-9 md:p-9">
              <div className="h-[52px] w-[52px] animate-pulse rounded bg-foreground/10" />
              <div className="space-y-3">
                <div className="h-6 w-2/3 animate-pulse rounded bg-foreground/10" />
                <div className="h-4 w-full animate-pulse rounded bg-foreground/10" />
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}

// ─── Diferenciais (banner com imagem + cards) ─────────────────────────────────

function DiferenciaisSection({ diferenciais }: { diferenciais: HomeDiferenciais }) {
  const { eyebrow, titulo, imagem, itens } = diferenciais

  return (
    <section
      className="relative isolate w-full overflow-hidden bg-brand-purple-dark bg-cover bg-center py-16 md:py-20 lg:py-24"
      style={{ backgroundImage: `url(${imagem.src})` }}
    >
      <div className="absolute inset-0 bg-gradient-to-r from-brand-purple-dark/95 via-brand-purple-dark/70 to-brand-purple-dark/10" />

      <div className="container relative z-10 flex flex-col gap-8 md:gap-10 lg:gap-12">
        <div className="flex max-w-xl flex-col gap-2 lg:max-w-2xl">
          {eyebrow && <span className="font-sans text-base text-white/80">{eyebrow}</span>}
          <h2 className="font-heading text-h3 font-normal text-white md:text-h2">{titulo}</h2>
        </div>

        {itens.length > 0 && (
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 lg:grid-cols-4">
            {itens.map((item, i) => (
              <DiferencialItemCard key={i} item={item} />
            ))}
          </div>
        )}
      </div>
    </section>
  )
}

function DiferencialItemCard({ item }: { item: HomeDiferenciaisItem }) {
  return (
    <div className="flex flex-col gap-8 rounded-xl border border-[#DFDEE3] bg-white p-6 md:gap-12 md:p-9">
      <img src={item.icone.src} alt={item.icone.alt} className="h-[52px] w-[52px]" />
      {item.titulo && (
        <p className="max-w-[198px] font-sans text-base text-brand-purple-dark">{item.titulo}</p>
      )}
    </div>
  )
}

function DiferenciaisSkeleton() {
  return (
    <section className="w-full bg-muted py-16 md:py-20 lg:py-24">
      <div className="container flex flex-col gap-8 md:gap-10 lg:gap-12">
        <div className="flex max-w-xl flex-col gap-2">
          <div className="h-5 w-28 animate-pulse rounded bg-foreground/10" />
          <div className="h-9 w-full animate-pulse rounded bg-foreground/10 md:h-10" />
        </div>
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 lg:grid-cols-4">
          {Array.from({ length: 4 }).map((_, i) => (
            <div key={i} className="flex flex-col gap-8 rounded-xl border border-[#DFDEE3] bg-white p-6 md:gap-12 md:p-9">
              <div className="h-[52px] w-[52px] animate-pulse rounded bg-foreground/10" />
              <div className="h-5 w-3/4 animate-pulse rounded bg-foreground/10" />
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}

// ─── Estrutura (galeria de fotos) ──────────────────────────────────────────────

function EstruturaSection({ estrutura }: { estrutura: HomeEstrutura }) {
  const { eyebrow, titulo, descricao, fotos } = estrutura

  return (
    <section className="w-full py-16 md:py-20 lg:py-24">
      <div className="container flex flex-col gap-6 md:gap-8 lg:gap-16">
        <div className="flex flex-col gap-2">
          <div className="flex flex-col gap-2">
            {eyebrow && (
              <span className="font-sans text-base text-brand-purple-accent">{eyebrow}</span>
            )}
            <h2 className="font-heading text-h3 font-normal text-brand-purple-dark md:text-h2">
              {titulo}
            </h2>
          </div>
          {descricao && (
            <p className="max-w-xl font-sans text-base text-brand-gray-text lg:max-w-2xl">
              {descricao}
            </p>
          )}
        </div>

        {fotos.length > 0 && (
          <div className="flex flex-col gap-4 sm:flex-row sm:items-stretch sm:gap-5">
            {fotos.map((foto, i) => (
              <img
                key={i}
                src={foto.src}
                alt={foto.alt}
                className="h-64 w-full flex-1 rounded-2xl object-cover sm:h-72 md:h-80 lg:h-[374px]"
              />
            ))}
          </div>
        )}
      </div>
    </section>
  )
}

function EstruturaSkeleton() {
  return (
    <section className="w-full py-16 md:py-20 lg:py-24">
      <div className="container flex flex-col gap-6 md:gap-8 lg:gap-16">
        <div className="flex flex-col gap-2">
          <div className="h-5 w-32 animate-pulse rounded bg-foreground/10" />
          <div className="h-9 w-full max-w-xl animate-pulse rounded bg-foreground/10 md:h-10" />
          <div className="mt-2 h-5 w-full max-w-xl animate-pulse rounded bg-foreground/10" />
        </div>
        <div className="flex flex-col gap-4 sm:flex-row sm:gap-5">
          {Array.from({ length: 3 }).map((_, i) => (
            <div
              key={i}
              className="h-64 w-full flex-1 animate-pulse rounded-2xl bg-foreground/10 sm:h-72 md:h-80 lg:h-[374px]"
            />
          ))}
        </div>
      </div>
    </section>
  )
}

// ─── Depoimentos (carrossel) ────────────────────────────────────────────────

function DepoimentosSection({ depoimentos }: { depoimentos: HomeDepoimentos }) {
  const { eyebrow, titulo, itens, autoplay, intervalo } = depoimentos
  const trackRef = useRef<HTMLDivElement>(null)
  const [paused, setPaused] = useState(false)

  const scrollByCard = useCallback((direction: 1 | -1) => {
    const track = trackRef.current
    if (!track) return

    const card = track.firstElementChild as HTMLElement | null
    const step = card ? card.getBoundingClientRect().width + 20 : track.clientWidth

    const atEnd = track.scrollLeft + track.clientWidth >= track.scrollWidth - 4
    const atStart = track.scrollLeft <= 4

    if (direction === 1 && atEnd) {
      track.scrollTo({ left: 0, behavior: 'smooth' })
      return
    }
    if (direction === -1 && atStart) {
      track.scrollTo({ left: track.scrollWidth, behavior: 'smooth' })
      return
    }

    track.scrollBy({ left: direction * step, behavior: 'smooth' })
  }, [])

  useEffect(() => {
    if (!autoplay || paused || itens.length <= 1) return
    const id = window.setInterval(() => scrollByCard(1), Math.max(intervalo, 2000))
    return () => window.clearInterval(id)
  }, [autoplay, paused, itens.length, intervalo, scrollByCard])

  if (itens.length === 0) return null

  return (
    <section
      className="w-full py-16 md:py-20 lg:py-24"
      onMouseEnter={() => setPaused(true)}
      onMouseLeave={() => setPaused(false)}
    >
      <div className="container flex flex-col items-center gap-8 md:gap-10 lg:gap-12">
        <div className="flex max-w-xl flex-col items-center gap-2 text-center">
          {eyebrow && (
            <span className="font-sans text-base text-brand-purple-accent">{eyebrow}</span>
          )}
          <h2 className="font-heading text-h3 font-normal text-brand-purple-dark md:text-h2">
            {titulo}
          </h2>
        </div>

        <div className="relative flex w-full items-center gap-3 sm:gap-5 lg:gap-8">
          {itens.length > 1 && (
            <button
              type="button"
              onClick={() => scrollByCard(-1)}
              aria-label="Depoimento anterior"
              className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-purple-dark text-white transition-opacity hover:opacity-90 sm:h-[54px] sm:w-[54px]"
            >
              <ChevronLeft className="h-5 w-5 sm:h-6 sm:w-6" />
            </button>
          )}

          <div
            ref={trackRef}
            className="flex w-full snap-x snap-mandatory gap-5 overflow-x-auto scroll-smooth [scrollbar-width:none] [&::-webkit-scrollbar]:hidden lg:gap-8"
          >
            {itens.map((item, i) => (
              <DepoimentoCard key={i} item={item} />
            ))}
          </div>

          {itens.length > 1 && (
            <button
              type="button"
              onClick={() => scrollByCard(1)}
              aria-label="Próximo depoimento"
              className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-purple-dark text-white transition-opacity hover:opacity-90 sm:h-[54px] sm:w-[54px]"
            >
              <ChevronRight className="h-5 w-5 sm:h-6 sm:w-6" />
            </button>
          )}
        </div>
      </div>
    </section>
  )
}

function DepoimentoCard({ item }: { item: HomeDepoimentoItem }) {
  return (
    <div className="flex w-full shrink-0 snap-start flex-col gap-6 rounded-2xl bg-brand-light-purple p-6 sm:w-[calc(50%-10px)] md:gap-8 md:p-10 lg:w-[calc(50%-16px)] lg:p-16">
      <img src={item.icone.src} alt={item.icone.alt} className="h-[60px] w-[60px] rounded-full" />

      {item.texto && (
        <p className="font-heading text-lg font-medium leading-snug text-brand-purple-dark md:text-h4">
          {item.texto}
        </p>
      )}

      {item.nome && (
        <span className="font-heading text-base font-medium text-brand-purple-accent md:text-h5">
          {item.nome}
        </span>
      )}
    </div>
  )
}

function DepoimentosSkeleton() {
  return (
    <section className="w-full py-16 md:py-20 lg:py-24">
      <div className="container flex flex-col items-center gap-8 md:gap-10 lg:gap-12">
        <div className="flex max-w-xl flex-col items-center gap-2">
          <div className="h-5 w-40 animate-pulse rounded bg-foreground/10" />
          <div className="h-9 w-full animate-pulse rounded bg-foreground/10 md:h-10" />
        </div>

        <div className="flex w-full gap-5 overflow-hidden lg:gap-8">
          {Array.from({ length: 2 }).map((_, i) => (
            <div
              key={i}
              className="hidden w-[calc(50%-10px)] shrink-0 flex-col gap-6 rounded-2xl bg-brand-light-purple p-6 first:flex md:gap-8 md:p-10 sm:flex lg:w-[calc(50%-16px)] lg:p-16"
            >
              <div className="h-[60px] w-[60px] animate-pulse rounded-full bg-foreground/10" />
              <div className="space-y-3">
                <div className="h-5 w-full animate-pulse rounded bg-foreground/10" />
                <div className="h-5 w-full animate-pulse rounded bg-foreground/10" />
                <div className="h-5 w-2/3 animate-pulse rounded bg-foreground/10" />
              </div>
              <div className="h-5 w-24 animate-pulse rounded bg-foreground/10" />
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}
