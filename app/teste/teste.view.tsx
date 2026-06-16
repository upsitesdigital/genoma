import { useModule } from '@/hooks/useModule'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'
import type { TesteData } from './teste.schema'

const corMap = {
  azul:    'from-blue-600 to-blue-400',
  verde:   'from-green-600 to-green-400',
  roxo:    'from-purple-600 to-purple-400',
  laranja: 'from-orange-600 to-orange-400',
}

export default function TesteView() {
  const { data, isLoading, error } = useModule<TesteData>('teste')

  useDocumentTitle(data?.titulo ?? 'Teste')

  if (isLoading) return <TesteSkeleton />

  if (error || !data) {
    return (
      <div className="container py-16 text-center text-muted-foreground">
        Erro ao carregar dados do módulo Teste.
      </div>
    )
  }

  const gradiente = corMap[data.cor] ?? corMap.azul

  return (
    <div className="min-h-screen">
      {/* Banner / Hero */}
      {data.exibir_banner && (
        <section className={`bg-gradient-to-br ${gradiente} text-white py-20`}>
          <div className="container max-w-4xl mx-auto px-4 text-center">
            {data.imagem && (
              <img
                src={data.imagem.url}
                alt={data.imagem.alt}
                className="w-24 h-24 rounded-full object-cover mx-auto mb-6 ring-4 ring-white/30"
              />
            )}
            <h1 className="text-5xl font-bold mb-4">{data.titulo}</h1>
            {data.descricao && (
              <p className="text-lg text-white/80 max-w-2xl mx-auto">{data.descricao}</p>
            )}
            {data.numero > 0 && (
              <div className="mt-8 inline-block bg-white/20 backdrop-blur-sm rounded-2xl px-8 py-4">
                <span className="text-6xl font-black">{data.numero}</span>
                <p className="text-sm text-white/70 mt-1">número de destaque</p>
              </div>
            )}
            {data.cta && (
              <div className="mt-8">
                <a
                  href={data.cta.url}
                  target={data.cta.target || '_self'}
                  className="inline-block bg-white text-gray-900 font-semibold px-8 py-3 rounded-full hover:scale-105 transition-transform"
                >
                  {data.cta.title}
                </a>
              </div>
            )}
          </div>
        </section>
      )}

      {/* Cards */}
      {data.cards.length > 0 && (
        <section className="container max-w-5xl mx-auto px-4 py-16">
          <h2 className="text-2xl font-bold text-center mb-10 text-foreground">
            Recursos
          </h2>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            {data.cards.map((card, i) => (
              <div
                key={i}
                className="rounded-2xl border bg-card p-6 hover:shadow-md transition-shadow"
              >
                {card.icone && (
                  <div className="text-4xl mb-3">{card.icone}</div>
                )}
                <h3 className="text-lg font-semibold mb-2 text-card-foreground">
                  {card.titulo}
                </h3>
                <p className="text-sm text-muted-foreground">{card.texto}</p>
              </div>
            ))}
          </div>
        </section>
      )}

      {/* Debug: dados brutos */}
      <section className="container max-w-5xl mx-auto px-4 pb-16">
        <details className="rounded-xl border bg-muted/40 p-4">
          <summary className="cursor-pointer text-sm font-medium text-muted-foreground select-none">
            Dados brutos da API (debug)
          </summary>
          <pre className="mt-4 text-xs overflow-auto max-h-80 text-foreground">
            {JSON.stringify(data, null, 2)}
          </pre>
        </details>
      </section>
    </div>
  )
}

function TesteSkeleton() {
  return (
    <div className="min-h-screen animate-pulse">
      <div className="h-64 bg-muted" />
      <div className="container max-w-5xl mx-auto px-4 py-16">
        <div className="h-8 w-40 bg-muted rounded mx-auto mb-10" />
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
          {[1, 2, 3].map(i => (
            <div key={i} className="h-36 rounded-2xl bg-muted" />
          ))}
        </div>
      </div>
    </div>
  )
}
