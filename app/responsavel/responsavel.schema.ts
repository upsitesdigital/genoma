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
  imagem: ResponsavelImage | null
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
  imagem1: ResponsavelImage | null
  imagem2: ResponsavelImage | null
}

export interface ResponsavelPlanosLogo {
  imagem: ResponsavelImage | null
  nome: string
}

export interface ResponsavelPlanos {
  eyebrow: string
  titulo: string
  logos: ResponsavelPlanosLogo[]
}

export interface ResponsavelExamesItem {
  icone: ResponsavelImage | null
  texto: string
  destaque: string
}

export interface ResponsavelExames {
  eyebrow: string
  titulo: string
  texto: string
  itens: ResponsavelExamesItem[]
  rodape: string
  imagem: ResponsavelImage | null
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
  imagem: ResponsavelImage | null
}

export interface ResponsavelBeneficioItem {
  icone: ResponsavelImage | null
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
