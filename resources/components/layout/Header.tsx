import { useState } from 'react'
import { Link, useLocation } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { ChevronDown } from 'lucide-react'
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

function isExternal(url: string): boolean {
  return url.startsWith('http') && !url.startsWith(boot.siteUrl)
}

// ─── NavLink (desktop, sem filhos) ───────────────────────────────────────────

function NavLink({ item, active }: { item: MenuItem; active: boolean }) {
  const cls = cn(
    'text-sm transition-colors hover:text-foreground',
    active ? 'text-foreground font-medium' : 'text-muted-foreground'
  )

  if (isExternal(item.url)) {
    return <a href={item.url} target={item.target} rel="noopener noreferrer" className={cls}>{item.title}</a>
  }
  return <Link to={resolveHref(item.url)} className={cls}>{item.title}</Link>
}

// ─── DropdownMenu (desktop, com filhos) ──────────────────────────────────────

function DropdownMenu({ item, active }: { item: MenuItem; active: boolean }) {
  const [open, setOpen] = useState(false)

  return (
    <div className="relative" onMouseEnter={() => setOpen(true)} onMouseLeave={() => setOpen(false)}>
      <button
        className={cn(
          'flex items-center gap-1 text-sm transition-colors hover:text-foreground',
          active ? 'text-foreground font-medium' : 'text-muted-foreground'
        )}
      >
        {item.title}
        <ChevronDown className={cn('h-3 w-3 transition-transform', open && 'rotate-180')} />
      </button>

      {open && (
        <div className="absolute top-full left-0 z-50 mt-1 min-w-[160px] rounded-md border border-border bg-background shadow-md py-1">
          {item.children.map((child) => (
            <div key={child.id}>
              {isExternal(child.url) ? (
                <a
                  href={child.url}
                  target={child.target}
                  rel="noopener noreferrer"
                  className="block px-4 py-2 text-sm text-muted-foreground hover:text-foreground hover:bg-muted transition-colors"
                >
                  {child.title}
                </a>
              ) : (
                <Link
                  to={resolveHref(child.url)}
                  className="block px-4 py-2 text-sm text-muted-foreground hover:text-foreground hover:bg-muted transition-colors"
                  onClick={() => setOpen(false)}
                >
                  {child.title}
                </Link>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  )
}

// ─── Header ──────────────────────────────────────────────────────────────────

export default function Header() {
  const [mobileOpen, setMobileOpen] = useState(false)
  const [openSubmenus, setOpenSubmenus] = useState<Record<number, boolean>>({})
  const { pathname } = useLocation()
  const { data: items = [] } = useMenu('primary')

  const opts = boot.themeOptions as { site_name?: string; logo_url?: string }
  const siteName = opts.site_name || 'UpWork'
  const logoUrl  = opts.logo_url  || ''

  const toggleSubmenu = (id: number) =>
    setOpenSubmenus((prev) => ({ ...prev, [id]: !prev[id] }))

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
              const active = pathname === resolveHref(item.url)
              return item.children.length > 0
                ? <DropdownMenu key={item.id} item={item} active={active} />
                : <NavLink key={item.id} item={item} active={active} />
            })}
          </nav>
        )}

        {/* Hamburguer mobile */}
        {items.length > 0 && (
          <button
            className="md:hidden flex flex-col gap-1.5 p-2"
            aria-label="Abrir menu"
            onClick={() => setMobileOpen((v) => !v)}
          >
            <span className={cn('block h-0.5 w-5 bg-foreground transition-transform', mobileOpen && 'translate-y-2 rotate-45')} />
            <span className={cn('block h-0.5 w-5 bg-foreground transition-opacity', mobileOpen && 'opacity-0')} />
            <span className={cn('block h-0.5 w-5 bg-foreground transition-transform', mobileOpen && '-translate-y-2 -rotate-45')} />
          </button>
        )}
      </div>

      {/* Menu mobile */}
      {mobileOpen && items.length > 0 && (
        <div className="md:hidden border-t border-border bg-background">
          <nav className="container flex flex-col py-4 gap-1">
            {items.map((item) => (
              <div key={item.id}>
                {item.children.length > 0 ? (
                  <>
                    <button
                      className="flex items-center justify-between w-full py-2 text-sm text-muted-foreground hover:text-foreground"
                      onClick={() => toggleSubmenu(item.id)}
                    >
                      {item.title}
                      <ChevronDown className={cn('h-3 w-3 transition-transform', openSubmenus[item.id] && 'rotate-180')} />
                    </button>
                    {openSubmenus[item.id] && (
                      <div className="pl-4 flex flex-col gap-1">
                        {item.children.map((child) => (
                          isExternal(child.url) ? (
                            <a key={child.id} href={child.url} target={child.target} rel="noopener noreferrer"
                              className="py-2 text-sm text-muted-foreground" onClick={() => setMobileOpen(false)}>
                              {child.title}
                            </a>
                          ) : (
                            <Link key={child.id} to={resolveHref(child.url)}
                              className="py-2 text-sm text-muted-foreground hover:text-foreground"
                              onClick={() => setMobileOpen(false)}>
                              {child.title}
                            </Link>
                          )
                        ))}
                      </div>
                    )}
                  </>
                ) : isExternal(item.url) ? (
                  <a href={item.url} target={item.target} rel="noopener noreferrer"
                    className="block py-2 text-sm text-muted-foreground" onClick={() => setMobileOpen(false)}>
                    {item.title}
                  </a>
                ) : (
                  <Link to={resolveHref(item.url)}
                    className="block py-2 text-sm text-muted-foreground hover:text-foreground"
                    onClick={() => setMobileOpen(false)}>
                    {item.title}
                  </Link>
                )}
              </div>
            ))}
          </nav>
        </div>
      )}
    </header>
  )
}
