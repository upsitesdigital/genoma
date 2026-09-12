export interface ResponsavelImage {
  src: string
  alt: string
  width: number | null
  height: number | null
  sizes: Record<string, string>
}

export interface ResponsavelCta {
  texto: string
  link: string
}

export interface ResponsavelHeroSlide {
  imagem: ResponsavelImage
  eyebrow: string
  titulo: string
  subtitulo: string
  ctaPrimario: ResponsavelCta
  ctaSecundario: ResponsavelCta
}

export interface ResponsavelHero {
  slides: ResponsavelHeroSlide[]
  autoplay: boolean
  intervalo: number
}

export interface ResponsavelData {
  hero: ResponsavelHero
}
