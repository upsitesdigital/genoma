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
  footer_logo_url?: string
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
  footer_tagline?: string
  footer_email?: string
  footer_phone?: string
  footer_rights_text?: string
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

// ─── Override do CTA do rodapé pela página atual ─────────────────────────────
// Mesma queryKey que useModule(slug) usaria — reaproveita o cache, sem fetch duplicado.

function useFooterCtaOverride(): FooterCtaOverride | undefined {
  const { data: route } = useCurrentRoute()
  const slug = route?.module
  const pageId = route?.pageId ?? undefined
  const search = window.location.search

  const { data } = useQuery<{ footerCta?: FooterCtaOverride }>({
    queryKey: ['module', slug, pageId, search],
    queryFn: () => api<{ footerCta?: FooterCtaOverride }>(`${pageId ? `/${slug}/${pageId}` : `/${slug}`}${search}`),
    enabled: !!slug,
  })

  return data?.footerCta
}

// ─── FooterNavLink (itens do menu WP `footer`) ───────────────────────────────

function FooterNavLink({ item }: { item: MenuItem }) {
  const cls = 'text-sm md:text-base font-semibold text-white/85 hover:text-white transition-colors whitespace-nowrap'

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
    'inline-flex w-full items-center justify-center rounded-full px-6 py-4 font-sans text-[15px] font-semibold leading-[1.5] transition-colors whitespace-nowrap lg:w-auto lg:px-7 lg:py-5 lg:text-h5 lg:font-medium lg:leading-none',
    variant === 'outline'
      ? 'border border-primary text-primary hover:bg-primary hover:text-white'
      : 'border border-white bg-primary text-white hover:bg-brand-purple-dark'
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

// ─── Botão "Contato" da barra de navegação (pill contornada branca) ──────────

function FooterContatoLink({ label, url }: { label: string; url: string }) {
  const cls = 'inline-flex shrink-0 items-center justify-center rounded-full border border-white/60 px-6 py-4 text-body text-white transition-colors hover:bg-white/10 whitespace-nowrap'

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

// ─── Footer ──────────────────────────────────────────────────────────────────

export default function Footer() {
  const opts = boot.themeOptions as ThemeOptions
  const year = new Date().getFullYear()
  const ctaOverride = useFooterCtaOverride()

  const logoUrl = opts.footer_logo_url || opts.logo_url || `${boot.themeUrl}/resources/components/layout/assets/header-logo.svg`
  const ctaImageUrl = opts.footer_cta_image_url || `${boot.themeUrl}/resources/components/layout/assets/footer-cta-photo.png`
  // Mobile/tablet: recorte mais alto da mesma foto (Figma 353×215). Imagem
  // definida nas Opções do Tema vale para todas as larguras.
  const ctaImageMobileUrl = opts.footer_cta_image_url || `${boot.themeUrl}/resources/components/layout/assets/footer-cta-photo-mobile.webp`
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

  // Exclusivos do rodapé mobile (Figma): frase, contatos e linha de direitos
  const tagline = opts.footer_tagline || 'Diagnóstico Veterinário com Precisão, Agilidade e Confiança.'
  const email = opts.footer_email || ''
  const phone = opts.footer_phone || ''
  const rights = opts.footer_rights_text || 'Todos os direitos reservados.'

  const { data: items = [] } = useMenu('footer')

  return (
    <footer className="relative bg-brand-purple-dark lg:mt-[100px] lg:bg-primary">
      {/* ── Banner de CTA ──
          Mobile/tablet: seção própria (fundo lilás) com imagem, texto e botões empilhados.
          Desktop: imagem + card branco sobreposto, avançando 100px sobre a seção anterior. */}
      <div className="bg-brand-light-purple py-12 lg:bg-transparent lg:py-0">
      <div className="container flex flex-col gap-6 lg:-mt-[100px] lg:block">
        <div className="relative overflow-hidden rounded-[5px] lg:mb-1 lg:rounded-2xl">
          <picture>
            <source media="(min-width: 1024px)" srcSet={ctaImageUrl} />
            <img
              src={ctaImageMobileUrl}
              alt=""
              className="h-[215px] w-full object-cover lg:h-[345px]"
            />
          </picture>
          <img
            src={watermarkUrl}
            alt=""
            aria-hidden="true"
            className="pointer-events-none absolute bottom-[5px] left-[14px] w-[207px] lg:bottom-0 lg:left-10 lg:w-[420px] lg:opacity-70"
          />
        </div>

        <div className="flex flex-col gap-6 lg:relative lg:z-10 lg:flex-row lg:items-end lg:justify-between lg:gap-6 lg:rounded-2xl lg:bg-white lg:p-10 lg:shadow-lg">
          <div className="flex max-w-xl flex-col gap-3 lg:gap-2">
            <span className="text-eyebrow uppercase tracking-[1.5px] text-brand-purple-accent lg:text-body lg:normal-case lg:tracking-normal lg:text-accent">{overline}</span>
            <h2 className="font-heading text-[24px] font-medium leading-[1.3] text-brand-purple-dark lg:text-h2">
              {title.split('\n').map((line, i) => (
                <span key={i} className="block">{line}</span>
              ))}
            </h2>
          </div>
          <div className="flex flex-col gap-3 lg:flex-row lg:flex-wrap lg:items-center lg:gap-4">
            {/* Com 1 botão só (showSecondary=false), o Figma usa o estilo preenchido —
                o contornado só existe pra diferenciar do preenchido quando há 2. */}
            <CtaBannerButton label={primaryLabel} url={primaryUrl} variant={showSecondary ? 'outline' : 'filled'} />
            {showSecondary && (
              <CtaBannerButton label={secondaryLabel} url={secondaryUrl} variant="filled" />
            )}
          </div>
        </div>
      </div>
      </div>

      {/* ── Barra de navegação: logo, menu, botão Contato ── */}
      <div className="container flex flex-col items-center gap-6 pb-8 pt-12 lg:flex-row lg:items-center lg:justify-between lg:gap-8 lg:py-12">
        <a href={boot.siteUrl} className="shrink-0">
          <img src={logoUrl} alt="" className="h-[84px] w-[166px] object-contain" />
        </a>

        {/* Mobile/tablet: frase + contatos no lugar do menu e do botão Contato */}
        <p className="text-center font-sans text-[13px] leading-[1.5] text-brand-gray-border lg:hidden">{tagline}</p>

        {(email || phone) && (
          <div className="flex flex-col items-center gap-3 font-sans text-xs leading-[1.5] text-white lg:hidden">
            {email && <a href={`mailto:${email}`} className="hover:underline">{email}</a>}
            {phone && <a href={`tel:${phone.replace(/\D/g, '')}`} className="hover:underline">{phone}</a>}
          </div>
        )}

        {items.length > 0 && (
          <nav className="hidden flex-wrap items-center justify-center gap-10 lg:flex">
            {items.map((item) => (
              <FooterNavLink key={item.id} item={item} />
            ))}
          </nav>
        )}

        <div className="hidden lg:block">
          <FooterContatoLink label={contatoLabel} url={contatoUrl} />
        </div>
      </div>

      {/* ── Separador ── */}
      <div className="container">
        <div className="h-px w-full bg-white/20" />
      </div>

      {/* ── Barra inferior: copyright, privacidade, créditos ── */}
      <div className="container flex flex-col items-center gap-2 pb-6 pt-8 text-center font-sans text-xs leading-[1.5] text-brand-gray-border lg:flex-row lg:justify-between lg:gap-3 lg:py-6 lg:text-left lg:text-body-sm lg:text-white/85">
        <span dangerouslySetInnerHTML={{ __html: copyright }} />
        <span className="lg:hidden">{rights}</span>

        <a
          href={privacyUrl}
          target="_blank"
          rel="noopener noreferrer"
          className="hover:text-white transition-colors"
        >
          {privacyLabel}
        </a>

        <a
          href="https://upsites.digital/"
          target="_blank"
          rel="noopener noreferrer"
          className="hidden text-white/70 hover:text-white transition-colors lg:inline"
        >
          {credits}
        </a>
      </div>
    </footer>
  )
}
