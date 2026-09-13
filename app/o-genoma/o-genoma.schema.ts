import type { FooterCtaOverride } from '@/lib/footer-cta'

export interface OGenomaImage {
  src: string
  alt: string
  width: number | null
  height: number | null
  sizes: Record<string, string>
}

export interface OGenomaHeroSlide {
  eyebrow: string
  titulo: string
  imagem1: OGenomaImage | null
  imagem2: OGenomaImage | null
  destaque: string
}

export interface OGenomaHero {
  slides: OGenomaHeroSlide[]
  autoplay: boolean
  intervalo: number
}

export interface OGenomaSobre {
  eyebrow: string
  titulo: string
  texto: string
  destaque: string
}

export interface OGenomaCompromisso {
  eyebrow: string
  titulo: string
  texto: string
  imagem: OGenomaImage | null
}

export interface OGenomaDiagnostico {
  titulo: string
  texto: string
  destaque: string
}

export interface OGenomaData {
  hero: OGenomaHero
  sobre: OGenomaSobre
  compromisso: OGenomaCompromisso
  diagnostico: OGenomaDiagnostico
  footerCta: FooterCtaOverride
}
