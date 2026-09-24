import { useState, type FormEvent } from 'react'
import { useQuery, keepPreviousData } from '@tanstack/react-query'
import { useModule } from '@/hooks/useModule'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'
import { cn } from '@/lib/cn'
import { api } from '@/lib/api'
import { boot } from '@/lib/env'
import type {
  ResultadoPesquisaData,
  ResultadoPesquisaHero,
  ResultadoPesquisaLista,
  ResultadoPesquisaPost,
} from './resultado-pesquisa.schema'

export default function ResultadoPesquisaView() {
  const { data, isLoading, error } = useModule<ResultadoPesquisaData>('resultado-pesquisa')
  useDocumentTitle(data ? `Busca: ${data.hero.termo}` : 'Resultado da pesquisa')

  if (isLoading) return <ResultadoPesquisaSkeleton />

  if (error || !data) {
    return (
      <p className="container py-16 text-center text-muted-foreground">
        Erro ao carregar conteúdo.
      </p>
    )
  }

  return (
    <main>
      <HeroSection hero={data.hero} />
      <ListaResultadosSection initial={data.resultados} semResultadosTexto={data.hero.semResultadosTexto} />
    </main>
  )
}

// ─── Hero ────────────────────────────────────────────────────────────────────

function HeroSection({ hero }: { hero: ResultadoPesquisaHero }) {
  const { eyebrow, buscaPlaceholder, buscaIcone, termo } = hero
  const [busca, setBusca] = useState(termo)

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    window.location.href = `${boot.siteUrl}/?s=${encodeURIComponent(busca)}`
  }

  return (
    <section className="relative isolate w-full">
      <div
        className="w-full pb-16 pt-16 md:pb-20 md:pt-20 lg:pb-24 lg:pt-24"
        style={{ backgroundImage: 'linear-gradient(-20deg, #95ABB7 47%, #AEC6D3 100%)' }}
      >
        <div className="container flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between lg:gap-10">
          <div className="flex max-w-xl flex-col gap-3 md:gap-4">
            {eyebrow && <h1 className="font-sans text-body-lg text-white">{eyebrow}</h1>}
            <h2 className="font-heading text-3xl font-medium leading-tight text-white sm:max-w-md md:text-h1 lg:max-w-lg">
              {termo ? (
                <>
                  Resultados para <span className="italic">"{termo}"</span>
                </>
              ) : (
                'Buscar no site'
              )}
            </h2>
          </div>

          <form
            onSubmit={handleSubmit}
            className="flex w-full items-center justify-between gap-3 rounded-full border border-white/70 px-6 py-4 lg:w-[423px] lg:shrink-0"
          >
            <input
              type="search"
              value={busca}
              onChange={(event) => setBusca(event.target.value)}
              placeholder={buscaPlaceholder}
              aria-label={buscaPlaceholder}
              className="w-full bg-transparent font-sans text-body-sm text-white placeholder:text-white/90 focus:outline-none"
            />
            <button type="submit" aria-label={buscaPlaceholder} className="shrink-0">
              <img src={buscaIcone.src} alt={buscaIcone.alt} className="h-6 w-6" />
            </button>
          </form>
        </div>
      </div>
    </section>
  )
}

// ─── Lista de resultados ─────────────────────────────────────────────────────
// Resultados REAIS do WordPress (WP_Query com `s` no controller) — sem
// conteúdo ACF. Paginação: 12 itens por página, navegação client-side via
// /resultado-pesquisa-lista?page=&s=.

function ListaResultadosSection({
  initial,
  semResultadosTexto,
}: {
  initial: ResultadoPesquisaLista
  semResultadosTexto: string
}) {
  const [page, setPage] = useState(initial.paginaAtual)
  const termo = initial.termo

  const isInitialQuery = page === initial.paginaAtual

  const { data, isFetching } = useQuery<ResultadoPesquisaLista>({
    queryKey: ['resultado-pesquisa-lista', termo, page],
    queryFn: () =>
      api<ResultadoPesquisaLista>(
        `/resultado-pesquisa-lista?page=${page}&s=${encodeURIComponent(termo)}`
      ),
    initialData: isInitialQuery ? initial : undefined,
    placeholderData: keepPreviousData,
    enabled: termo !== '',
  })

  const resultados = data ?? initial

  function goToPage(next: number) {
    setPage(Math.min(Math.max(1, next), resultados.totalPaginas))
  }

  if (resultados.posts.length === 0) {
    return (
      <section className="container py-16 text-center font-sans text-body-sm text-brand-gray-text md:py-20">
        {semResultadosTexto || 'Nenhum resultado encontrado.'}
      </section>
    )
  }

  return (
    <section className="container py-12 md:py-20 lg:py-24">
      <p className="mb-8 font-sans text-body text-brand-gray-text md:mb-10">
        {resultados.totalPosts} resultado{resultados.totalPosts === 1 ? '' : 's'} encontrado
        {resultados.totalPosts === 1 ? '' : 's'}
      </p>

      <div
        className={cn(
          'grid grid-cols-1 gap-8 transition-opacity md:grid-cols-2 lg:grid-cols-3',
          isFetching && 'opacity-60'
        )}
      >
        {resultados.posts.map((post) => (
          <ResultadoCard key={post.id} post={post} />
        ))}
      </div>

      {resultados.totalPaginas > 1 && (
        <PaginationPills
          paginaAtual={resultados.paginaAtual}
          totalPaginas={resultados.totalPaginas}
          onChange={goToPage}
        />
      )}
    </section>
  )
}

function ResultadoCard({ post }: { post: ResultadoPesquisaPost }) {
  return (
    <a
      href={post.link}
      className="flex flex-col gap-9 rounded-2xl bg-brand-purple-subtle p-6 transition-shadow hover:shadow-lg md:p-8"
    >
      <div className="flex flex-col gap-4">
        <img
          src={post.imagem.src}
          alt={post.imagem.alt || post.titulo}
          className="h-[170px] w-full rounded-lg object-cover"
        />
        <div className="flex flex-col gap-2">
          {post.categoria && (
            <span className="font-sans text-body-sm text-brand-purple-accent">{post.categoria}</span>
          )}
          <h3 className="font-heading text-h4 font-medium leading-snug text-brand-purple-dark">
            {post.titulo}
          </h3>
          <p className="line-clamp-3 font-sans text-body-sm text-brand-gray-text">{post.resumo}</p>
        </div>
      </div>
      <span className="inline-flex w-fit shrink-0 items-center justify-center rounded-full bg-brand-purple px-4 py-3.5 font-sans text-body-sm font-semibold text-white transition-opacity hover:opacity-90">
        Saiba mais
      </span>
    </a>
  )
}

function PaginationPills({
  paginaAtual,
  totalPaginas,
  onChange,
}: {
  paginaAtual: number
  totalPaginas: number
  onChange: (page: number) => void
}) {
  return (
    <div className="mt-10 flex flex-wrap items-center justify-center gap-3 md:mt-12 md:gap-4">
      {Array.from({ length: totalPaginas }, (_, i) => i + 1).map((n) => (
        <button
          key={n}
          type="button"
          onClick={() => onChange(n)}
          aria-current={n === paginaAtual ? 'page' : undefined}
          className={cn(
            'flex h-11 w-11 items-center justify-center rounded-full border font-heading text-base font-medium transition-colors md:h-12 md:w-12 md:text-h4',
            n === paginaAtual
              ? 'border-brand-purple text-brand-purple'
              : 'border-brand-gray-text text-brand-gray-text hover:border-brand-purple hover:text-brand-purple'
          )}
        >
          {String(n).padStart(2, '0')}
        </button>
      ))}
    </div>
  )
}

function ResultadoPesquisaSkeleton() {
  return (
    <main>
      <section className="relative isolate w-full">
        <div className="w-full bg-muted pb-16 pt-16 md:pb-20 md:pt-20 lg:pb-24 lg:pt-24">
          <div className="container flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between lg:gap-10">
            <div className="flex max-w-xl flex-col gap-3 md:gap-4">
              <div className="h-6 w-16 animate-pulse rounded bg-foreground/10" />
              <div className="h-10 w-full max-w-md animate-pulse rounded bg-foreground/10 md:h-14" />
            </div>
            <div className="h-[56px] w-full animate-pulse rounded-full bg-foreground/10 lg:w-[423px]" />
          </div>
        </div>
      </section>

      <section className="container py-12 md:py-20 lg:py-24">
        <div className="mb-8 h-5 w-48 animate-pulse rounded bg-foreground/10 md:mb-10" />
        <div className="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
          {Array.from({ length: 6 }).map((_, i) => (
            <div key={i} className="flex flex-col gap-9 rounded-2xl bg-brand-purple-subtle p-6 md:p-8">
              <div className="flex flex-col gap-4">
                <div className="h-[170px] w-full animate-pulse rounded-lg bg-foreground/10" />
                <div className="flex flex-col gap-2">
                  <div className="h-6 w-3/4 animate-pulse rounded bg-foreground/10" />
                  <div className="h-4 w-full animate-pulse rounded bg-foreground/10" />
                  <div className="h-4 w-2/3 animate-pulse rounded bg-foreground/10" />
                </div>
              </div>
              <div className="h-11 w-28 animate-pulse rounded-full bg-foreground/10" />
            </div>
          ))}
        </div>
      </section>
    </main>
  )
}
