import type { FooterCtaOverride } from '@/lib/footer-cta'

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

export interface ResponsavelSuporte {
  eyebrow: string
  titulo: string
  texto: string
  quote: string
  imagem1: ResponsavelImage
  imagem2: ResponsavelImage
}

export interface ResponsavelPlanosLogo {
  imagem: ResponsavelImage
  nome: string
}

export interface ResponsavelPlanos {
  eyebrow: string
  titulo: string
  logos: ResponsavelPlanosLogo[]
}

export interface ResponsavelExamesItem {
  icone: ResponsavelImage
  texto: string
  destaque: string
}

export interface ResponsavelExames {
  eyebrow: string
  titulo: string
  texto: string
  itens: ResponsavelExamesItem[]
  rodape: string
  imagem: ResponsavelImage
}

export interface ResponsavelServicosExame {
  nome: string
  prazo: string
  amostra: string
}

export interface ResponsavelServicosCategoria {
  nome: string
  aberto: boolean
  descricao: string
  exames: ResponsavelServicosExame[]
}

export interface ResponsavelServicos {
  eyebrow: string
  titulo: string
  texto: string
  categorias: ResponsavelServicosCategoria[]
}

export interface ResponsavelResultados {
  eyebrow: string
  titulo: string
  texto: string
  imagem: ResponsavelImage
}

export interface ResponsavelBeneficioItem {
  icone: ResponsavelImage
  texto: string
}

export interface ResponsavelBeneficios {
  eyebrow: string
  titulo: string
  texto: string
  itens: ResponsavelBeneficioItem[]
}

export interface ResponsavelData {
  hero: ResponsavelHero
  suporte: ResponsavelSuporte
  planos: ResponsavelPlanos
  exames: ResponsavelExames
  servicos: ResponsavelServicos
  resultados: ResponsavelResultados
  beneficios: ResponsavelBeneficios
  footerCta: FooterCtaOverride
}
