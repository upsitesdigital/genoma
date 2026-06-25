import { useLocation } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { api } from '@/lib/api'
import { boot } from '@/lib/env'

export type CurrentRoute = {
  module: string
  pageId: number | null
  url: string
  title: string
} | null

function pathsMatch(routeUrl: string, pathname: string): boolean {
  try {
    const p1 = new URL(routeUrl).pathname.replace(/\/$/, '') || '/'
    const p2 = pathname.replace(/\/$/, '') || '/'
    return p1 === p2
  } catch {
    return false
  }
}

/**
 * Retorna o módulo e pageId correspondentes à rota atual do React Router.
 * Usa boot.currentRoute quando o path já foi resolvido no carregamento inicial,
 * e busca do REST /route?path= na navegação client-side.
 */
export function useCurrentRoute() {
  const { pathname } = useLocation()
  const bootRoute = boot.currentRoute
  const isCached  = bootRoute !== null && pathsMatch(bootRoute.url, pathname)

  return useQuery<CurrentRoute>({
    queryKey:    ['route', pathname],
    queryFn:     () => api<CurrentRoute>(`/route?path=${encodeURIComponent(pathname)}`),
    enabled:     !isCached,
    staleTime:   Infinity,
    initialData: isCached ? bootRoute : undefined,
  })
}
