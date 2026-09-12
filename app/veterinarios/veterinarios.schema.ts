import type { FooterCtaOverride } from '@/lib/footer-cta'

export interface VeterinariosImage {
  src: string
  alt: string
  width: number | null
  height: number | null
  sizes: Record<string, string>
}

export interface VeterinariosCta {
  texto: string
  link: string
}

export interface VeterinariosHeroSlide {
  imagem: VeterinariosImage
  eyebrow: string
  titulo: string
  subtitulo: string
  ctaPrimario: VeterinariosCta
  ctaSecundario: VeterinariosCta
}

export interface VeterinariosHero {
  slides: VeterinariosHeroSlide[]
  autoplay: boolean
  intervalo: number
}

export interface VeterinariosSuporte {
  eyebrow: string
  titulo: string
  texto: string
  quote: string
  imagem1: VeterinariosImage
  imagem2: VeterinariosImage
}

export interface VeterinariosPraticidade {
  eyebrow: string
  titulo: string
  texto: string
  icone: VeterinariosImage
  horarios: string[]
  imagem: VeterinariosImage
}

export interface VeterinariosExames {
  eyebrow: string
  titulo: string
  texto: string
  cta: VeterinariosCta
  imagem: VeterinariosImage
}

export interface VeterinariosEstruturaContato {
  icone: VeterinariosImage
  texto: string
}

export interface VeterinariosEstrutura {
  eyebrow: string
  titulo: string
  texto: string
  destaque: string
  imagem1: VeterinariosImage
  imagem2: VeterinariosImage
  contatos: VeterinariosEstruturaContato[]
}

export interface VeterinariosBeneficioItem {
  icone: VeterinariosImage
  texto: string
}

export interface VeterinariosBeneficios {
  eyebrow: string
  titulo: string
  texto: string
  itens: VeterinariosBeneficioItem[]
}

export interface VeterinariosData {
  hero: VeterinariosHero
  suporte: VeterinariosSuporte
  praticidade: VeterinariosPraticidade
  exames: VeterinariosExames
  estrutura: VeterinariosEstrutura
  beneficios: VeterinariosBeneficios
  footerCta: FooterCtaOverride
}
