import { Mail, Linkedin, Facebook, MessageCircle } from 'lucide-react'
import { useModule } from '@/hooks/useModule'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'
import type { SinglePostData, SinglePostHero } from './single-post.schema'

export default function SinglePostView() {
  const { data, isLoading, error } = useModule<SinglePostData>('single-post')
  useDocumentTitle(data?.hero.titulo ?? 'Single Post')

  if (isLoading) return <SinglePostSkeleton />

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
    </main>
  )
}

// ─── Hero ────────────────────────────────────────────────────────────────────

function HeroSection({ hero }: { hero: SinglePostHero }) {
  const { titulo, data, categoria, categoriaLink, imagem, url } = hero

  const shareLinks = buildShareLinks(url, titulo)

  return (
    <section className="relative isolate w-full">
      <div
        className="w-full pb-40 pt-16 md:pb-52 md:pt-20 lg:pb-64 lg:pt-24"
        style={{ backgroundImage: 'linear-gradient(-20deg, #95ABB7 47%, #AEC6D3 100%)' }}
      >
        <div className="container">
          <div className="flex max-w-3xl flex-col gap-4 md:gap-6">
            <p className="font-sans text-body text-white">
              {data}
              {categoria && (
                <>
                  {'    •    '}
                  {categoriaLink ? (
                    <a href={categoriaLink} className="hover:underline">
                      {categoria}
                    </a>
                  ) : (
                    categoria
                  )}
                </>
              )}
            </p>

            <h1 className="font-heading text-3xl font-medium leading-tight text-white md:text-h1">
              {titulo}
            </h1>

            <div className="flex items-center gap-6">
              <span className="font-sans text-body text-white">Compartilhe</span>
              <div className="flex items-center gap-3">
                <a
                  href={shareLinks.email}
                  aria-label="Compartilhar por e-mail"
                  className="text-white transition-opacity hover:opacity-75"
                >
                  <Mail className="h-6 w-6" strokeWidth={1.75} />
                </a>
                <a
                  href={shareLinks.linkedin}
                  target="_blank"
                  rel="noopener noreferrer"
                  aria-label="Compartilhar no LinkedIn"
                  className="text-white transition-opacity hover:opacity-75"
                >
                  <Linkedin className="h-6 w-6" strokeWidth={1.75} />
                </a>
                <a
                  href={shareLinks.whatsapp}
                  target="_blank"
                  rel="noopener noreferrer"
                  aria-label="Compartilhar no WhatsApp"
                  className="text-white transition-opacity hover:opacity-75"
                >
                  <MessageCircle className="h-6 w-6" strokeWidth={1.75} />
                </a>
                <a
                  href={shareLinks.facebook}
                  target="_blank"
                  rel="noopener noreferrer"
                  aria-label="Compartilhar no Facebook"
                  className="text-white transition-opacity hover:opacity-75"
                >
                  <Facebook className="h-6 w-6" strokeWidth={1.75} />
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div className="container relative z-10 -mt-32 pb-10 md:-mt-40 md:pb-14 lg:-mt-56 lg:pb-16">
        <div className="max-w-3xl">
          <img
            src={imagem.src}
            alt={imagem.alt || titulo}
            className="aspect-[800/432] w-full rounded-2xl object-cover shadow-xl"
          />
        </div>
      </div>
    </section>
  )
}

function buildShareLinks(url: string, titulo: string) {
  const encodedUrl   = encodeURIComponent(url)
  const encodedTitle = encodeURIComponent(titulo)

  return {
    email:    `mailto:?subject=${encodedTitle}&body=${encodedUrl}`,
    linkedin: `https://www.linkedin.com/sharing/share-offsite/?url=${encodedUrl}`,
    whatsapp: `https://wa.me/?text=${encodedTitle}%20${encodedUrl}`,
    facebook: `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`,
  }
}

function SinglePostSkeleton() {
  return (
    <main>
      <section className="relative isolate w-full">
        <div className="w-full bg-muted pb-40 pt-16 md:pb-52 md:pt-20 lg:pb-64 lg:pt-24">
          <div className="container">
            <div className="flex max-w-3xl flex-col gap-4 md:gap-6">
              <div className="h-5 w-64 animate-pulse rounded bg-foreground/10" />
              <div className="h-10 w-full animate-pulse rounded bg-foreground/10 md:h-14" />
              <div className="flex items-center gap-6">
                <div className="h-5 w-24 animate-pulse rounded bg-foreground/10" />
                <div className="flex items-center gap-3">
                  {Array.from({ length: 4 }).map((_, i) => (
                    <div key={i} className="h-6 w-6 animate-pulse rounded-full bg-foreground/10" />
                  ))}
                </div>
              </div>
            </div>
          </div>
        </div>

        <div className="container relative z-10 -mt-32 pb-10 md:-mt-40 md:pb-14 lg:-mt-56 lg:pb-16">
          <div className="aspect-[800/432] w-full max-w-3xl animate-pulse rounded-2xl bg-foreground/10" />
        </div>
      </section>
    </main>
  )
}
