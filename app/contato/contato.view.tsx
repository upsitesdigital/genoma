import { useModule } from '@/hooks/useModule'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'
import type { ContatoData, ContatoHeroCanal, ContatoListaCard } from './contato.schema'

export default function ContatoView() {
  const { data, isLoading, error } = useModule<ContatoData>('contato')
  useDocumentTitle(data?.hero.titulo ?? 'Contato')

  if (isLoading) return <ContatoSkeleton />

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
      <ListaContatosSection cards={data.listaContatos} />
    </main>
  )
}

// ─── Hero ────────────────────────────────────────────────────────────────────

function HeroSection({ hero }: { hero: ContatoData['hero'] }) {
  const { eyebrow, titulo, descricao, canais } = hero

  return (
    <section className="relative isolate w-full">
      <div
        className="w-full pb-40 pt-28 sm:pt-32 md:pb-48 md:pt-36 lg:pb-56 lg:pt-40"
        style={{ backgroundImage: 'linear-gradient(-20deg, #95ABB7 47%, #AEC6D3 100%)' }}
      >
        <div className="container flex flex-col items-center gap-6 text-center md:gap-9">
          {eyebrow && (
            <span className="font-sans text-body-lg text-white">{eyebrow}</span>
          )}
          <h1 className="max-w-[280px] font-heading text-3xl font-medium leading-tight text-white sm:max-w-md md:max-w-xl md:text-h1 lg:max-w-2xl">
            {titulo}
          </h1>
          {descricao && (
            <p className="max-w-2xl font-sans text-body-lg text-white">{descricao}</p>
          )}
        </div>
      </div>

      {canais.length > 0 && (
        <div className="container relative z-10 -mt-28 pb-8 md:pb-10 sm:-mt-24 md:-mt-28 lg:-mt-24">
          <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {canais.map((canal, i) => (
              <CanalCard key={i} canal={canal} />
            ))}
          </div>
        </div>
      )}
    </section>
  )
}

function CanalCard({ canal }: { canal: ContatoHeroCanal }) {
  const { icone, titulo, valor, link } = canal

  const content = (
    <>
      {icone && <img src={icone.src} alt={icone.alt} className="h-[52px] w-[52px] shrink-0" />}
      <div className="flex flex-col gap-2">
        {titulo && (
          <span className="font-sans text-body-sm text-brand-purple-dark/70">{titulo}</span>
        )}
        {valor && (
          <span className="font-sans text-body-lg text-brand-purple-dark">{valor}</span>
        )}
      </div>
    </>
  )

  const className =
    'flex items-center gap-6 rounded-xl border border-brand-gray-border bg-white p-6 shadow-sm transition-shadow hover:shadow-md sm:p-8 md:gap-8 md:p-12'

  if (link) {
    return (
      <a href={link} className={className}>
        {content}
      </a>
    )
  }

  return <div className={className}>{content}</div>
}

// ─── Lista de contatos ───────────────────────────────────────────────────────

function ListaContatosSection({ cards }: { cards: ContatoListaCard[] }) {
  if (cards.length === 0) return null

  return (
    <section className="container pb-12 pt-4 md:pb-16 md:pt-6 lg:pb-20 lg:pt-8">
      <div className="flex flex-col gap-4 md:gap-[22px]">
        {cards.map((card, i) =>
          card.tipo === 'info' ? (
            <ContatoCardInfo key={i} card={card} />
          ) : (
            <ContatoCardTexto key={i} card={card} />
          )
        )}
      </div>
    </section>
  )
}

function ContatoCardInfo({ card }: { card: Extract<ContatoListaCard, { tipo: 'info' }> }) {
  const { titulo, descricao, itens } = card

  return (
    <div className="flex flex-col gap-6 rounded-2xl border border-brand-gray-border bg-brand-light-purple p-6 sm:p-8 lg:flex-row lg:items-center lg:justify-between lg:gap-8 lg:p-12">
      <div className="flex flex-col gap-3 lg:w-[431px] lg:shrink-0">
        <h3 className="font-heading text-h5 font-medium text-brand-purple-dark">{titulo}</h3>
        {descricao && <p className="font-sans text-body text-brand-gray-text">{descricao}</p>}
      </div>

      {itens.length > 0 && (
        <div className="flex flex-col gap-6 sm:flex-row sm:flex-wrap sm:gap-8 lg:w-[587px] lg:flex-nowrap lg:justify-between">
          {itens.map((item, i) => (
            <div key={i} className="flex flex-col items-center gap-4 text-center">
              {item.icone && (
                <img src={item.icone.src} alt={item.icone.alt} className="h-[52px] w-[52px] shrink-0" />
              )}
              {item.texto && (
                <span className="whitespace-pre-line font-sans text-body text-brand-purple-dark">
                  {item.texto}
                </span>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  )
}

function ContatoCardTexto({ card }: { card: Extract<ContatoListaCard, { tipo: 'texto' }> }) {
  const { titulo, texto, botao } = card

  if (botao) {
    return (
      <div className="flex flex-col gap-6 rounded-2xl border border-brand-gray-border bg-brand-light-purple p-6 sm:p-8 lg:flex-row lg:items-end lg:justify-between lg:p-12">
        <div className="flex flex-col gap-4">
          <h3 className="font-heading text-h5 font-medium text-brand-purple-dark">{titulo}</h3>
          {texto && (
            <p className="font-sans text-body text-brand-gray-text lg:max-w-2xl">{texto}</p>
          )}
        </div>
        <a
          href={botao.link}
          className="inline-flex w-fit shrink-0 items-center justify-center rounded-full bg-brand-purple px-6 py-4 font-sans text-body font-semibold text-white transition-opacity hover:opacity-90"
        >
          {botao.texto}
        </a>
      </div>
    )
  }

  return (
    <div className="flex flex-col gap-4 rounded-2xl border border-brand-gray-border bg-brand-light-purple p-6 sm:p-8 lg:p-12">
      <h3 className="font-heading text-h5 font-medium text-brand-purple-dark">{titulo}</h3>
      {texto && <p className="font-sans text-body text-brand-gray-text lg:max-w-5xl">{texto}</p>}
    </div>
  )
}

function ContatoSkeleton() {
  return (
    <main>
      <section className="relative isolate w-full">
        <div className="w-full bg-muted pb-40 pt-28 sm:pt-32 md:pb-48 md:pt-36 lg:pb-56 lg:pt-40">
          <div className="container flex flex-col items-center gap-6 text-center md:gap-9">
            <div className="h-6 w-24 animate-pulse rounded bg-foreground/10" />
            <div className="h-10 w-full max-w-xl animate-pulse rounded bg-foreground/10 md:h-14" />
            <div className="h-6 w-full max-w-md animate-pulse rounded bg-foreground/10" />
          </div>
        </div>

        <div className="container relative z-10 -mt-28 pb-8 md:pb-10 sm:-mt-24 md:-mt-28 lg:-mt-24">
          <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {Array.from({ length: 3 }).map((_, i) => (
              <div
                key={i}
                className="flex items-center gap-6 rounded-xl border border-brand-gray-border bg-white p-6 sm:p-8 md:gap-8 md:p-12"
              >
                <div className="h-[52px] w-[52px] shrink-0 animate-pulse rounded bg-foreground/10" />
                <div className="flex flex-1 flex-col gap-2">
                  <div className="h-4 w-24 animate-pulse rounded bg-foreground/10" />
                  <div className="h-5 w-32 animate-pulse rounded bg-foreground/10" />
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="container pb-12 pt-4 md:pb-16 md:pt-6 lg:pb-20 lg:pt-8">
        <div className="flex flex-col gap-4 md:gap-[22px]">
          {Array.from({ length: 3 }).map((_, i) => (
            <div
              key={i}
              className="flex flex-col gap-6 rounded-2xl border border-brand-gray-border bg-brand-light-purple p-6 sm:p-8 lg:flex-row lg:items-center lg:justify-between lg:gap-8 lg:p-12"
            >
              <div className="flex flex-col gap-3 lg:w-[431px] lg:shrink-0">
                <div className="h-6 w-56 animate-pulse rounded bg-foreground/10" />
                <div className="h-4 w-64 animate-pulse rounded bg-foreground/10" />
              </div>
              <div className="flex flex-col gap-6 sm:flex-row sm:flex-wrap sm:gap-8 lg:w-[587px] lg:flex-nowrap lg:justify-between">
                {Array.from({ length: 3 }).map((_, j) => (
                  <div key={j} className="flex flex-col items-center gap-4">
                    <div className="h-[52px] w-[52px] shrink-0 animate-pulse rounded-full bg-foreground/10" />
                    <div className="h-4 w-28 animate-pulse rounded bg-foreground/10" />
                  </div>
                ))}
              </div>
            </div>
          ))}
          <div className="h-32 animate-pulse rounded-2xl border border-brand-gray-border bg-brand-light-purple p-6 sm:p-8 lg:p-12" />
        </div>
      </section>
    </main>
  )
}
