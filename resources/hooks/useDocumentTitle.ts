import { useEffect } from 'react'
import { boot } from '@/lib/env'

const siteName = boot.themeOptions?.site_name as string | undefined

/**
 * Atualiza o <title> da página dinamicamente.
 * Formato: "Título da Página | Nome do Site"
 */
export function useDocumentTitle(title: string) {
  useEffect(() => {
    const suffix = siteName ? ` | ${siteName}` : ''
    document.title = title ? `${title}${suffix}` : document.title
  }, [title])
}
