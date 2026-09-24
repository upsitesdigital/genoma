import type { QueryClient } from '@tanstack/react-query'
import { boot } from '@/lib/env'

/**
 * Semeia o cache do React Query com os dados que o servidor já embutiu no
 * FW_BOOT (core/Framework/Shell.php). As chaves precisam ser idênticas às de
 * useModule e useMenu — assim a 1ª renderização já sai com conteúdo, sem
 * skeleton e sem refazer a requisição.
 */
export function seedQueryCache(queryClient: QueryClient): void {
  const preload = boot.preload
  if (!preload) return

  const route = boot.currentRoute
  if (route && preload.module != null) {
    queryClient.setQueryData(
      ['module', route.module, route.pageId ?? undefined, window.location.search],
      preload.module
    )
  }

  for (const [location, items] of Object.entries(preload.menus ?? {})) {
    if (items != null) queryClient.setQueryData(['menu', location], items)
  }
}
