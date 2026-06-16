import { useQuery } from '@tanstack/react-query'
import { api } from '@/lib/api'
import { boot } from '@/lib/env'

/**
 * Busca os dados de um módulo via REST.
 * Chama GET /wp-json/framework/v1/{slug} ou /{slug}/{pageId}.
 */
export function useModule<T>(slug: string, pageId?: number) {
  const route = boot.currentRoute
  const id = pageId ?? (route?.module === slug ? route.pageId : undefined)

  return useQuery<T>({
    queryKey: ['module', slug, id],
    queryFn: () => api<T>(id ? `/${slug}/${id}` : `/${slug}`),
  })
}
