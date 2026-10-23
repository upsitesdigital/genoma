import { ChevronDown } from 'lucide-react'
import { cn } from '@/lib/cn'

// Listas que mostram só os primeiros itens; o botão expande o restante.
// Sem itens escondidos, o botão volta a ser o link do CTA configurado no admin.
export const VISIVEIS_INICIAL = 3

export function VerMaisButton({
  hasMore,
  expanded,
  onToggle,
  cta,
}: {
  hasMore: boolean
  expanded: boolean
  onToggle: () => void
  cta: { texto: string; link: string }
}) {
  const cls =
    'inline-flex w-full items-center justify-center gap-2 rounded-full border border-brand-purple px-6 py-4 lg:w-auto font-heading text-base font-medium text-brand-purple transition-colors hover:bg-brand-purple hover:text-white md:px-7 md:py-5 md:text-h5'

  if (hasMore) {
    return (
      <div className="mt-10 flex justify-center md:mt-12">
        <button type="button" onClick={onToggle} aria-expanded={expanded} className={cls}>
          {expanded ? 'Ver menos' : 'Ver mais'}
          <ChevronDown className={cn('h-4 w-4 transition-transform duration-300', expanded && 'rotate-180')} />
        </button>
      </div>
    )
  }

  if (!cta.texto) return null

  return (
    <div className="mt-10 flex justify-center md:mt-12">
      <a href={cta.link || '#'} className={cls}>
        {cta.texto}
      </a>
    </div>
  )
}
