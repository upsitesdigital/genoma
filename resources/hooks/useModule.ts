import { useQuery } from '@tanstack/react-query'
import { api } from '@/lib/api'
import { useCurrentRoute } from './useCurrentRoute'

/**
 * Busca os dados de um módulo via REST.
 * Resolve o pageId automaticamente via useCurrentRoute, inclusive na navegação client-side.
 */
export function useModule<T>(slug: string, pageId?: number) {
  const { data: route, isLoading: routeLoading } = useCurrentRoute()

  const resolvedId = pageId ?? (route?.module === slug ? route.pageId ?? undefined : undefined)

  return useQuery<T>({
    queryKey: ['module', slug, resolvedId],
    queryFn:  () => api<T>(resolvedId ? `/${slug}/${resolvedId}` : `/${slug}`),
    enabled:  !routeLoading,
  })
}
