import { useEffect, useState } from 'react'
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

interface ThemeOptions {
  site_name?: string
  logo_url?: string
  cta_primary_label?: string
  cta_primary_url?: string
  cta_secondary_label?: string
  cta_secondary_url?: string
}

function useMenu(location: string) {
  return useQuery<MenuItem[]>({
    queryKey: ['menu', location],
    queryFn: () => api<MenuItem[]>(`/menus/${location}`),
    staleTime: 1000 * 60 * 5,
  })
}

function isExternal(url: string): boolean {
  return url.startsWith('http') && !url.startsWith(boot.siteUrl)
}

function isActive(url: string): boolean {
  const current = boot.currentRoute?.url
  if (!current) return false
  return url.replace(/\/$/, '') === current.replace(/\/$/, '')
}

// ─── NavLink (desktop, sem filhos) ───────────────────────────────────────────

function NavLink({ item, active }: { item: MenuItem; active: boolean }) {
  const cls = cn(
    'text-base font-semibold transition-colors hover:text-white',
    active ? 'text-white' : 'text-white/80'
  )

  return (
    <a
      href={item.url}
      target={isExternal(item.url) ? item.target : undefined}
      rel={isExternal(item.url) ? 'noopener noreferrer' : undefined}
      className={cls}
    >
      {item.title}
    </a>
  )
}

// ─── DropdownMenu (desktop, com filhos) ──────────────────────────────────────

function DropdownMenu({ item, active }: { item: MenuItem; active: boolean }) {
  const [open, setOpen] = useState(false)

  return (
    <div className="relative" onMouseEnter={() => setOpen(true)} onMouseLeave={() => setOpen(false)}>
      <button
        className={cn(
          'flex items-center gap-1 text-base font-semibold transition-colors hover:text-white',
          active ? 'text-white' : 'text-white/80'
        )}
      >
        {item.title}
        <ChevronDown className={cn('h-3.5 w-3.5 transition-transform', open && 'rotate-180')} />
      </button>

      {open && (
        <div className="absolute top-full left-0 z-50 mt-2 min-w-[180px] rounded-md border border-border bg-background shadow-md py-1">
          {item.children.map((child) => (
            <a
              key={child.id}
              href={child.url}
              target={isExternal(child.url) ? child.target : undefined}
              rel={isExternal(child.url) ? 'noopener noreferrer' : undefined}
              className="block px-4 py-2 text-sm text-muted-foreground hover:text-foreground hover:bg-muted transition-colors"
            >
              {child.title}
            </a>
          ))}
        </div>
      )}
    </div>
  )
}

// ─── CTA (botões "Resultados" / "Contato") ───────────────────────────────────

function CtaLink({ label, url, variant }: { label: string; url: string; variant: 'primary' | 'secondary' }) {
  const cls = cn(
    'inline-flex items-center justify-center rounded-full px-6 py-4 text-base leading-none transition-colors whitespace-nowrap',
    variant === 'primary'
      ? 'bg-white text-primary hover:bg-brand-light-purple'
      : 'bg-primary text-white border border-white/25 hover:bg-brand-purple-dark active:bg-brand-purple-dark'
  )

  return (
    <a
      href={url}
      target={isExternal(url) ? '_blank' : undefined}
      rel={isExternal(url) ? 'noopener noreferrer' : undefined}
      className={cls}
    >
      {label}
    </a>
  )
}

// ─── Header ──────────────────────────────────────────────────────────────────

export default function Header() {
  const [mobileOpen, setMobileOpen] = useState(false)
  const [openSubmenus, setOpenSubmenus] = useState<Record<number, boolean>>({})
  const [scrolled, setScrolled] = useState(false)
  const { data: items = [] } = useMenu('primary')

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 0)
    onScroll()
    window.addEventListener('scroll', onScroll, { passive: true })
    return () => window.removeEventListener('scroll', onScroll)
  }, [])

  const opts = boot.themeOptions as ThemeOptions
  const siteName = opts.site_name || 'Genoma Diagnósticos'
  const logoUrl = opts.logo_url || `${boot.themeUrl}/resources/components/layout/assets/header-logo.svg`

  const ctaPrimaryLabel = opts.cta_primary_label || 'Resultados'
  const ctaPrimaryUrl = opts.cta_primary_url || '#'
  const ctaSecondaryLabel = opts.cta_secondary_label || 'Contato'
  const ctaSecondaryUrl = opts.cta_secondary_url || '#'

  const toggleSubmenu = (id: number) =>
    setOpenSubmenus((prev) => ({ ...prev, [id]: !prev[id] }))

  return (
    <header
      data-no-reveal
      className={cn(
        'fixed inset-x-0 top-0 z-40 transition-colors',
        scrolled ? 'bg-[#ABC3CF]' : 'bg-transparent'
      )}
    >
      <div className="container flex h-16 md:h-20 lg:h-[84px] items-center justify-between gap-4">

        {/* Logo */}
        <a href={boot.siteUrl} className="flex items-center gap-2 shrink-0">
          <img src={logoUrl} alt={siteName} className="h-[84px] w-[166px] object-contain" />
        </a>

        {/* Menu desktop */}
        {items.length > 0 && (
          <nav className="hidden lg:flex items-center gap-6 xl:gap-10 2xl:gap-14">
            {items.map((item) => {
              const active = isActive(item.url)
              return item.children.length > 0
                ? <DropdownMenu key={item.id} item={item} active={active} />
                : <NavLink key={item.id} item={item} active={active} />
            })}
          </nav>
        )}

        {/* CTAs desktop */}
        <div className="hidden lg:flex items-center gap-2 shrink-0">
          <CtaLink label={ctaPrimaryLabel} url={ctaPrimaryUrl} variant="primary" />
          <CtaLink label={ctaSecondaryLabel} url={ctaSecondaryUrl} variant="secondary" />
        </div>

        {/* Hamburguer mobile/tablet */}
        <button
          className="lg:hidden flex flex-col gap-1.5 p-2"
          aria-label="Abrir menu"
          onClick={() => setMobileOpen((v) => !v)}
        >
          <span className={cn('block h-0.5 w-5 bg-white transition-transform', mobileOpen && 'translate-y-2 rotate-45')} />
          <span className={cn('block h-0.5 w-5 bg-white transition-opacity', mobileOpen && 'opacity-0')} />
          <span className={cn('block h-0.5 w-5 bg-white transition-transform', mobileOpen && '-translate-y-2 -rotate-45')} />
        </button>
      </div>

      {/* Menu mobile/tablet */}
      {mobileOpen && (
        <div className="lg:hidden border-t border-white/15 bg-primary">
          <nav className="container flex flex-col py-4 gap-1">
            {items.map((item) => (
              <div key={item.id}>
                {item.children.length > 0 ? (
                  <>
                    <button
                      className="flex items-center justify-between w-full py-2 text-base font-semibold text-white/90"
                      onClick={() => toggleSubmenu(item.id)}
                    >
                      {item.title}
                      <ChevronDown className={cn('h-3.5 w-3.5 transition-transform', openSubmenus[item.id] && 'rotate-180')} />
                    </button>
                    {openSubmenus[item.id] && (
                      <div className="pl-4 flex flex-col gap-1">
                        {item.children.map((child) => (
                          <a
                            key={child.id}
                            href={child.url}
                            target={isExternal(child.url) ? child.target : undefined}
                            rel={isExternal(child.url) ? 'noopener noreferrer' : undefined}
                            className="py-2 text-sm text-white/70 hover:text-white"
                          >
                            {child.title}
                          </a>
                        ))}
                      </div>
                    )}
                  </>
                ) : (
                  <a
                    href={item.url}
                    target={isExternal(item.url) ? item.target : undefined}
                    rel={isExternal(item.url) ? 'noopener noreferrer' : undefined}
                    className="block py-2 text-base font-semibold text-white/90 hover:text-white"
                  >
                    {item.title}
                  </a>
                )}
              </div>
            ))}

            {/* CTAs mobile/tablet */}
            <div className="flex flex-col gap-2 mt-3">
              <CtaLink label={ctaPrimaryLabel} url={ctaPrimaryUrl} variant="primary" />
              <CtaLink label={ctaSecondaryLabel} url={ctaSecondaryUrl} variant="secondary" />
            </div>
          </nav>
        </div>
      )}
    </header>
  )
}
