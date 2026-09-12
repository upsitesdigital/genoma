import { useState, type FormEvent } from 'react'
import { useQuery, keepPreviousData } from '@tanstack/react-query'
import { useModule } from '@/hooks/useModule'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'
import { cn } from '@/lib/cn'
import { api } from '@/lib/api'
import type { BlogData, BlogHero, BlogHeroCategoria, BlogListaPost, BlogPost } from './blog.schema'

export default function BlogView() {
  const { data, isLoading, error } = useModule<BlogData>('blog')
  useDocumentTitle(data?.hero.titulo ?? 'Blog')

  if (isLoading) return <BlogSkeleton />

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
      <ListaPostSection initial={data.listaPost} />
    </main>
  )
}

// ─── Hero ────────────────────────────────────────────────────────────────────

function HeroSection({ hero }: { hero: BlogHero }) {
  const { eyebrow, titulo, buscaPlaceholder, buscaIcone, categorias } = hero
  const [busca, setBusca] = useState('')

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()

    const params = new URLSearchParams(window.location.search)
    if (busca) {
      params.set('s', busca)
    } else {
      params.delete('s')
    }
    const query = params.toString()
    window.location.search = query
  }

  return (
    <section className="relative isolate w-full">
      <div
        className="w-full pb-16 pt-16 md:pb-20 md:pt-20 lg:pb-24 lg:pt-24"
        style={{ backgroundImage: 'linear-gradient(-20deg, #95ABB7 47%, #AEC6D3 100%)' }}
      >
        <div className="container flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between lg:gap-10">
          <div className="flex max-w-xl flex-col gap-3 md:gap-4">
            {eyebrow && <span className="font-sans text-body-lg text-white">{eyebrow}</span>}
            <h1 className="font-heading text-3xl font-medium leading-tight text-white sm:max-w-md md:text-h1 lg:max-w-md">
              {titulo}
            </h1>
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

      {categorias.length > 0 && (
        <div className="container relative z-10 -mt-8 pb-10 md:-mt-10 lg:-mt-9">
          <div className="flex items-center gap-1 overflow-x-auto rounded-full bg-white p-3 shadow-lg [scrollbar-width:none] [&::-webkit-scrollbar]:hidden md:gap-2 lg:justify-between lg:overflow-visible">
            {categorias.map((categoria, i) => (
              <CategoriaPill key={i} categoria={categoria} />
            ))}
          </div>
        </div>
      )}
    </section>
  )
}

function CategoriaPill({ categoria }: { categoria: BlogHeroCategoria }) {
  const { titulo, link, destaque } = categoria

  return (
    <a
      href={link}
      className={cn(
        'shrink-0 whitespace-nowrap rounded-full px-4 py-3 font-sans text-body-sm transition-colors md:px-6',
        destaque
          ? 'border border-brand-purple-accent bg-[rgba(41,19,154,0.1)] text-brand-purple'
          : 'text-brand-gray-text hover:text-brand-purple'
      )}
    >
      {titulo}
    </a>
  )
}

// ─── Lista de post ───────────────────────────────────────────────────────────
// Lista posts REAIS do WordPress (WP_Query no controller) — sem conteúdo ACF.
// Paginação: 12 posts por página (grid 3x4 no Figma), navegação client-side via
// /blog-posts?page=&categoria=. Filtro por categoria decidido por bom senso do
// framework: a taxonomia nativa `category`, filtrável pelo slug em ?categoria=.

function ListaPostSection({ initial }: { initial: BlogListaPost }) {
  const [page, setPage] = useState(initial.paginaAtual)
  const [categoria] = useState(initial.categoriaAtual)

  const isInitialQuery = page === initial.paginaAtual && categoria === initial.categoriaAtual

  const { data, isFetching } = useQuery<BlogListaPost>({
    queryKey: ['blog-lista-post', categoria, page],
    queryFn: () =>
      api<BlogListaPost>(
        `/blog-posts?page=${page}${categoria ? `&categoria=${encodeURIComponent(categoria)}` : ''}`
      ),
    initialData: isInitialQuery ? initial : undefined,
    placeholderData: keepPreviousData,
  })

  const listaPost = data ?? initial

  function goToPage(next: number) {
    setPage(Math.min(Math.max(1, next), listaPost.totalPaginas))
  }

  if (listaPost.posts.length === 0) {
    return (
      <section className="container py-16 text-center font-sans text-body-sm text-brand-gray-text md:py-20">
        Nenhum post encontrado.
      </section>
    )
  }

  return (
    <section className="container py-16 md:py-20 lg:py-24">
      <div
        className={cn(
          'grid grid-cols-1 gap-8 transition-opacity md:grid-cols-2 lg:grid-cols-3',
          isFetching && 'opacity-60'
        )}
      >
        {listaPost.posts.map((post) => (
          <PostCard key={post.id} post={post} />
        ))}
      </div>

      {listaPost.totalPaginas > 1 && (
        <PaginationPills
          paginaAtual={listaPost.paginaAtual}
          totalPaginas={listaPost.totalPaginas}
          onChange={goToPage}
        />
      )}
    </section>
  )
}

function PostCard({ post }: { post: BlogPost }) {
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

function BlogSkeleton() {
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

        <div className="container relative z-10 -mt-8 pb-10 md:-mt-10 lg:-mt-9">
          <div className="flex items-center gap-2 overflow-x-auto rounded-full bg-white p-3 shadow-lg">
            {Array.from({ length: 5 }).map((_, i) => (
              <div key={i} className="h-10 w-24 shrink-0 animate-pulse rounded-full bg-foreground/10" />
            ))}
          </div>
        </div>
      </section>

      <section className="container py-16 md:py-20 lg:py-24">
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
