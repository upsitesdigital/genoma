import { useState } from 'react'
import { Link, useLocation } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { api } from '@/lib/api'
import { boot } from '@/lib/env'
import { cn } from '@/lib/cn'

interface MenuItem {
  id: number
  title: string
  url: string
  target: string
  classes: string
  children: MenuItem[]
}

function useMenu(location: string) {
  return useQuery<MenuItem[]>({
    queryKey: ['menu', location],
    queryFn: () => api<MenuItem[]>(`/menus/${location}`),
    staleTime: 1000 * 60 * 5,
  })
}

function resolveHref(url: string): string {
  const siteUrl = boot.siteUrl.replace(/\/$/, '')
  return url.startsWith(siteUrl) ? url.slice(siteUrl.length) || '/' : url
}

export default function Header() {
  const [open, setOpen] = useState(false)
  const { pathname } = useLocation()
  const { data: items = [] } = useMenu('primary')

  const opts = boot.themeOptions as { site_name?: string; logo_url?: string }
  const siteName = opts.site_name || 'UpWork'
  const logoUrl  = opts.logo_url  || ''

  return (
    <header className="border-b border-border bg-background sticky top-0 z-40">
      <div className="container flex h-16 items-center justify-between">
        {/* Logo / nome */}
        <Link to="/" className="flex items-center gap-2 shrink-0">
          {logoUrl
            ? <img src={logoUrl} alt={siteName} className="h-8 w-auto object-contain" />
            : <span className="text-lg font-semibold">{siteName}</span>
          }
        </Link>

        {/* Menu desktop */}
        {items.length > 0 && (
          <nav className="hidden md:flex items-center gap-6">
            {items.map((item) => {
              const href = resolveHref(item.url)
              const isExternal = item.url.startsWith('http') && !item.url.startsWith(boot.siteUrl)
              const isActive = pathname === href

              return isExternal ? (
                <a
                  key={item.id}
                  href={item.url}
                  target={item.target}
                  rel="noopener noreferrer"
                  className="text-sm text-muted-foreground hover:text-foreground transition-colors"
                >
                  {item.title}
                </a>
              ) : (
                <Link
                  key={item.id}
                  to={href}
                  className={cn(
                    'text-sm transition-colors hover:text-foreground',
                    isActive ? 'text-foreground font-medium' : 'text-muted-foreground'
                  )}
                >
                  {item.title}
                </Link>
              )
            })}
          </nav>
        )}

        {/* Hamburguer mobile */}
        {items.length > 0 && (
          <button
            className="md:hidden flex flex-col gap-1.5 p-2"
            aria-label="Abrir menu"
            onClick={() => setOpen((v) => !v)}
          >
            <span className={cn('block h-0.5 w-5 bg-foreground transition-transform', open && 'translate-y-2 rotate-45')} />
            <span className={cn('block h-0.5 w-5 bg-foreground transition-opacity', open && 'opacity-0')} />
            <span className={cn('block h-0.5 w-5 bg-foreground transition-transform', open && '-translate-y-2 -rotate-45')} />
          </button>
        )}
      </div>

      {/* Menu mobile */}
      {open && items.length > 0 && (
        <div className="md:hidden border-t border-border bg-background">
          <nav className="container flex flex-col py-4 gap-4">
            {items.map((item) => {
              const href = resolveHref(item.url)
              const isExternal = item.url.startsWith('http') && !item.url.startsWith(boot.siteUrl)

              return isExternal ? (
                <a
                  key={item.id}
                  href={item.url}
                  target={item.target}
                  rel="noopener noreferrer"
                  className="text-sm text-muted-foreground"
                  onClick={() => setOpen(false)}
                >
                  {item.title}
                </a>
              ) : (
                <Link
                  key={item.id}
                  to={href}
                  className="text-sm text-muted-foreground hover:text-foreground"
                  onClick={() => setOpen(false)}
                >
                  {item.title}
                </Link>
              )
            })}
          </nav>
        </div>
      )}
    </header>
  )
}
