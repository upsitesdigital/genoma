export interface FwBoot {
  apiBase: string
  wpApiBase: string
  siteUrl: string
  themeUrl: string
  nonce: string  // mutável — renovado por api.ts ao receber 401
  themeOptions: Record<string, unknown>
  currentPath: string
  currentRoute: {
    module: string
    pageId: number | null
    url: string
    title: string
  } | null
  /** Respostas REST já resolvidas no servidor (core/Framework/Shell.php) — null quando falhou */
  preload?: {
    module: unknown
    menus: Record<string, unknown>
  }
}

declare global {
  interface Window {
    FW_BOOT: FwBoot
  }
}

export const boot: FwBoot = window.FW_BOOT
