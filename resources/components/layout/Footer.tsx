import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { api } from '@/lib/api'
import { boot } from '@/lib/env'
import { cn } from '@/lib/cn'
import { useCurrentRoute } from '@/hooks/useCurrentRoute'
import type { FooterCtaOverride } from '@/lib/footer-cta'

interface MenuItem {
  id: number
  title: string
  url: string
  target: string
  classes: string
  children: MenuItem[]
}

interface ThemeOptions {
  logo_url?: string
  footer_text?: string
  cta_secondary_label?: string
  cta_secondary_url?: string
  footer_cta_overline?: string
  footer_cta_title?: string
  footer_cta_image_url?: string
  footer_cta_primary_label?: string
  footer_cta_primary_url?: string
  footer_cta_secondary_label?: string
  footer_cta_secondary_url?: string
  footer_privacy_label?: string
  footer_privacy_url?: string
  footer_credits_text?: string
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

// ─── Override do CTA do rodapé pela página atual ─────────────────────────────
// Mesma queryKey que useModule(slug) usaria — reaproveita o cache, sem fetch duplicado.

function useFooterCtaOverride(): FooterCtaOverride | undefined {
  const { data: route } = useCurrentRoute()
  const slug = route?.module
  const pageId = route?.pageId ?? undefined

  const { data } = useQuery<{ footerCta?: FooterCtaOverride }>({
    queryKey: ['module', slug, pageId],
    queryFn: () => api<{ footerCta?: FooterCtaOverride }>(pageId ? `/${slug}/${pageId}` : `/${slug}`),
    enabled: !!slug,
  })

  return data?.footerCta
}

// ─── FooterNavLink (itens do menu WP `footer`) ───────────────────────────────

function FooterNavLink({ item }: { item: MenuItem }) {
  const cls = 'text-sm md:text-base font-semibold text-white/85 hover:text-white transition-colors whitespace-nowrap'

  if (isExternal(item.url)) {
    return <a href={item.url} target={item.target} rel="noopener noreferrer" className={cls}>{item.title}</a>
  }
  return <Link to={resolveHref(item.url)} className={cls}>{item.title}</Link>
}

// ─── Botões do banner de CTA (dentro do card branco) ─────────────────────────

function CtaBannerButton({
  label,
  url,
  variant,
}: {
  label: string
  url: string
  variant: 'outline' | 'filled'
}) {
  const cls = cn(
    'inline-flex items-center justify-center rounded-full px-6 py-4 md:px-7 md:py-5 text-h5 leading-none transition-colors whitespace-nowrap',
    variant === 'outline'
      ? 'border border-primary text-primary hover:bg-primary hover:text-white'
      : 'bg-primary text-white hover:bg-brand-purple-dark'
  )

  if (isExternal(url)) {
    return <a href={url} target="_blank" rel="noopener noreferrer" className={cls}>{label}</a>
  }
  return <Link to={resolveHref(url)} className={cls}>{label}</Link>
}

// ─── Botão "Contato" da barra de navegação (pill contornada branca) ──────────

function FooterContatoLink({ label, url }: { label: string; url: string }) {
  const cls = 'inline-flex shrink-0 items-center justify-center rounded-full border border-white/60 px-6 py-4 text-body text-white transition-colors hover:bg-white/10 whitespace-nowrap'

  if (isExternal(url)) {
    return <a href={url} target="_blank" rel="noopener noreferrer" className={cls}>{label}</a>
  }
  return <Link to={resolveHref(url)} className={cls}>{label}</Link>
}

// ─── Footer ──────────────────────────────────────────────────────────────────

export default function Footer() {
  const opts = boot.themeOptions as ThemeOptions
  const year = new Date().getFullYear()
  const ctaOverride = useFooterCtaOverride()

  const logoUrl = opts.logo_url || `${boot.themeUrl}/resources/components/layout/assets/header-logo.svg`
  const ctaImageUrl = opts.footer_cta_image_url || `${boot.themeUrl}/resources/components/layout/assets/footer-cta-photo.png`
  const watermarkUrl = `${boot.themeUrl}/resources/components/layout/assets/footer-watermark.svg`

  const overline = opts.footer_cta_overline || 'Fale conosco'
  // O módulo da página atual pode sobrescrever título, CTAs e a quantidade de
  // botões do banner (ex: 1 botão "Fale conosco pelo WhatsApp" em Responsável
  // vs 2 botões em Home/Veterinários) — cai pro padrão global de Opções do
  // Tema quando o módulo não define nada.
  const title = ctaOverride?.titulo || opts.footer_cta_title || 'Cuidado começa com diagnóstico preciso.'
  const primaryLabel = ctaOverride?.primaryLabel || opts.footer_cta_primary_label || 'Agendar Exame'
  const primaryUrl = ctaOverride?.primaryUrl || opts.footer_cta_primary_url || '#'
  const showSecondary = ctaOverride?.showSecondary ?? true
  const secondaryLabel = ctaOverride?.secondaryLabel || opts.footer_cta_secondary_label || 'Fale Conosco'
  const secondaryUrl = ctaOverride?.secondaryUrl || opts.footer_cta_secondary_url || '#'

  const contatoLabel = opts.cta_secondary_label || 'Contato'
  const contatoUrl = opts.cta_secondary_url || '#'

  const privacyLabel = opts.footer_privacy_label || 'Política de privacidade'
  const privacyUrl = opts.footer_privacy_url || '#'
  const credits = opts.footer_credits_text || 'Desenvolvido por Upsites'
  const copyright = opts.footer_text || `&copy; Copyright ${year}`

  const { data: items = [] } = useMenu('footer')

  return (
    <footer className="relative overflow-hidden bg-primary">
      {/* ── Banner de CTA: imagem + card branco sobreposto ── */}
      <div className="container pt-10 md:pt-14 lg:pt-16">
        <div className="relative overflow-hidden rounded-2xl">
          <img
            src={ctaImageUrl}
            alt=""
            className="h-[220px] w-full object-cover md:h-[300px] lg:h-[345px]"
          />
          <img
            src={watermarkUrl}
            alt=""
            aria-hidden="true"
            className="pointer-events-none absolute bottom-0 left-1/2 hidden w-[280px] -translate-x-1/2 translate-y-1/4 opacity-70 md:block lg:w-[420px]"
          />
        </div>

        <div className="relative z-10 -mt-10 flex flex-col gap-8 rounded-2xl bg-white p-6 shadow-lg md:-mt-14 md:p-8 lg:-mt-16 lg:flex-row lg:items-end lg:justify-between lg:gap-6 lg:p-10">
          <div className="flex max-w-xl flex-col gap-2">
            <span className="text-body font-normal text-accent">{overline}</span>
            <h2 className="text-h4 text-brand-purple-dark md:text-h3 lg:text-h2">
              {title.split('\n').map((line, i) => (
                <span key={i} className="block">{line}</span>
              ))}
            </h2>
          </div>
          <div className="flex flex-wrap items-center gap-3 md:gap-4">
            {/* Com 1 botão só (showSecondary=false), o Figma usa o estilo preenchido —
                o contornado só existe pra diferenciar do preenchido quando há 2. */}
            <CtaBannerButton label={primaryLabel} url={primaryUrl} variant={showSecondary ? 'outline' : 'filled'} />
            {showSecondary && (
              <CtaBannerButton label={secondaryLabel} url={secondaryUrl} variant="filled" />
            )}
          </div>
        </div>
      </div>

      {/* ── Barra de navegação: logo, menu, botão Contato ── */}
      <div className="container flex flex-col items-center gap-6 py-10 md:py-12 lg:flex-row lg:items-center lg:justify-between lg:gap-8">
        <Link to="/" className="shrink-0">
          <img src={logoUrl} alt="" className="h-9 w-auto object-contain md:h-10" />
        </Link>

        {items.length > 0 && (
          <nav className="flex flex-wrap items-center justify-center gap-4 md:gap-8 lg:gap-10">
            {items.map((item) => (
              <FooterNavLink key={item.id} item={item} />
            ))}
          </nav>
        )}

        <FooterContatoLink label={contatoLabel} url={contatoUrl} />
      </div>

      {/* ── Separador ── */}
      <div className="container">
        <div className="h-px w-full bg-white/20" />
      </div>

      {/* ── Barra inferior: copyright, privacidade, créditos ── */}
      <div className="container flex flex-col items-center gap-3 py-6 text-center text-body-sm text-white/85 md:flex-row md:justify-between md:text-left">
        <span dangerouslySetInnerHTML={{ __html: copyright }} />

        {isExternal(privacyUrl) ? (
          <a href={privacyUrl} target="_blank" rel="noopener noreferrer" className="hover:text-white transition-colors">
            {privacyLabel}
          </a>
        ) : (
          <Link to={resolveHref(privacyUrl)} className="hover:text-white transition-colors">
            {privacyLabel}
          </Link>
        )}

        <span className="text-white/70">{credits}</span>
      </div>
    </footer>
  )
}
