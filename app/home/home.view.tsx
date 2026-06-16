import { useModule } from '@/hooks/useModule'
import type { HomeData } from './home.schema'

export default function HomeView() {
  const { data, isLoading, error } = useModule<HomeData>('home')

  if (isLoading) return <HomeSkeleton />

  if (error || !data) {
    return (
      <p className="container py-16 text-center text-muted-foreground">
        Erro ao carregar conteúdo.
      </p>
    )
  }

  return (
    <div>
      <section className="container py-24 text-center md:py-32">
        {data.hero.imagem && (
          <img
            src={data.hero.imagem.src}
            alt={data.hero.imagem.alt}
            width={data.hero.imagem.width ?? undefined}
            height={data.hero.imagem.height ?? undefined}
            className="mx-auto mb-8 max-h-64 w-auto object-cover"
          />
        )}

        <h1 className="text-4xl font-bold tracking-tight md:text-5xl">
          {data.hero.titulo}
        </h1>

        {data.hero.subtitulo && (
          <p className="mx-auto mt-6 max-w-2xl whitespace-pre-line text-xl text-muted-foreground">
            {data.hero.subtitulo}
          </p>
        )}

        {data.cta.texto && data.cta.link && (
          <div className="mt-10">
            <a
              href={data.cta.link}
              className="inline-flex items-center justify-center rounded-md bg-primary px-8 py-3 text-sm font-medium text-primary-foreground shadow transition-opacity hover:opacity-90"
            >
              {data.cta.texto}
            </a>
          </div>
        )}
      </section>
    </div>
  )
}

function HomeSkeleton() {
  return (
    <section className="container py-24 text-center md:py-32">
      <div className="mx-auto h-12 w-3/4 animate-pulse rounded bg-muted" />
      <div className="mx-auto mt-6 h-6 w-1/2 animate-pulse rounded bg-muted" />
      <div className="mx-auto mt-10 h-10 w-32 animate-pulse rounded bg-muted" />
    </section>
  )
}
