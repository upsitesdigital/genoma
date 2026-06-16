export interface FwBoot {
  apiBase: string
  wpApiBase: string
  siteUrl: string
  themeUrl: string
  nonce: string
  themeOptions: Record<string, unknown>
  currentPath: string
  currentRoute: {
    module: string
    pageId: number | null
    url: string
    title: string
  } | null
}

declare global {
  interface Window {
    FW_BOOT: FwBoot
  }
}

export const boot = window.FW_BOOT
