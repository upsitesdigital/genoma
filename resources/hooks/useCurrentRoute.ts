import { boot } from '@/lib/env'

export type CurrentRoute = {
  module: string
  pageId: number | null
  url: string
  title: string
} | null

/**
 * Retorna a rota resolvida no servidor (front-page.php/page.php/single.php via
 * RouteResolver::current()). Sem client-side routing, é fixa durante toda a
 * vida da página — não precisa de fetch nem de re-resolução por path.
 */
export function useCurrentRoute() {
  return { data: boot.currentRoute as CurrentRoute, isLoading: false }
}
