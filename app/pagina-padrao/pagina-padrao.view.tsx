import { useModule } from '@/hooks/useModule'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'
import type { PaginaPadraoData } from './pagina-padrao.schema'

export default function PaginaPadraoView() {
  const { data, isLoading, error } = useModule<PaginaPadraoData>('pagina-padrao')
  useDocumentTitle(data?.titulo ?? 'Página')

  if (isLoading) return <PaginaPadraoSkeleton />

  if (error || !data) {
    return (
      <p className="container py-16 text-center text-muted-foreground">
        Erro ao carregar conteúdo.
      </p>
    )
  }

  return (
    <main>
      <section className="w-full">
        <div className="container pb-16 pt-32 md:pb-20 md:pt-36 lg:pb-24 lg:pt-40">
          <div className="mx-auto max-w-3xl">
            <h1 className="font-heading text-h3 font-normal text-brand-purple-dark md:text-h2">
              {data.titulo}
            </h1>

            <div
              className="post-content mt-8 md:mt-10"
              dangerouslySetInnerHTML={{ __html: data.conteudo }}
            />
          </div>
        </div>
      </section>
    </main>
  )
}

function PaginaPadraoSkeleton() {
  return (
    <main>
      <section className="w-full">
        <div className="container pb-16 pt-32 md:pb-20 md:pt-36 lg:pb-24 lg:pt-40">
          <div className="mx-auto max-w-3xl space-y-4">
            <div className="h-9 w-2/3 animate-pulse rounded bg-foreground/10 md:h-10" />
            <div className="mt-6 space-y-3">
              <div className="h-4 w-full animate-pulse rounded bg-foreground/10" />
              <div className="h-4 w-full animate-pulse rounded bg-foreground/10" />
              <div className="h-4 w-2/3 animate-pulse rounded bg-foreground/10" />
            </div>
          </div>
        </div>
      </section>
    </main>
  )
}
