import type { FooterCtaOverride } from '@/lib/footer-cta'

export interface HomeImage {
  src: string
  alt: string
  width: number | null
  height: number | null
  sizes: Record<string, string>
}

export interface HomeCta {
  texto: string
  link: string
}

export interface HomeHeroSlide {
  imagem: HomeImage | null
  eyebrow: string
  titulo: string
  subtitulo: string
  ctaPrimario: HomeCta
  ctaSecundario: HomeCta
}

export interface HomeHero {
  slides: HomeHeroSlide[]
  autoplay: boolean
  intervalo: number
}

export interface HomeServicoItem {
  imagem: HomeImage | null
  titulo: string
  descricao: string
  cta: HomeCta
}

export interface HomeServicos {
  eyebrow: string
  titulo: string
  itens: HomeServicoItem[]
  cta: HomeCta
}

export interface HomeExameItem {
  nome: string
  prazo: string
  amostra: string
}

export interface HomeExameCategoria {
  titulo: string
  texto: string
  itens: HomeExameItem[]
}

export interface HomeExames {
  eyebrow: string
  titulo: string
  descricao: string
  categorias: HomeExameCategoria[]
  cta: HomeCta
}

export interface HomeDiferencial {
  icone: HomeImage | null
  titulo: string
  descricao: string
}

export interface HomeSobre {
  eyebrow: string
  titulo: string
  fotos: (HomeImage | null)[]
  paragrafos: string[]
  destaque: string
  diferenciais: HomeDiferencial[]
}

export interface HomeDiferenciaisItem {
  icone: HomeImage | null
  titulo: string
}

export interface HomeDiferenciais {
  eyebrow: string
  titulo: string
  imagem: HomeImage | null
  itens: HomeDiferenciaisItem[]
}

export interface HomeEstrutura {
  eyebrow: string
  titulo: string
  descricao: string
  fotos: (HomeImage | null)[]
}

export interface HomeDepoimentoItem {
  icone: HomeImage | null
  texto: string
  nome: string
}

export interface HomeDepoimentos {
  eyebrow: string
  titulo: string
  itens: HomeDepoimentoItem[]
  autoplay: boolean
  intervalo: number
}

export interface HomeData {
  hero: HomeHero
  servicos: HomeServicos
  exames: HomeExames
  sobre: HomeSobre
  diferenciais: HomeDiferenciais
  estrutura: HomeEstrutura
  depoimentos: HomeDepoimentos
  footerCta: FooterCtaOverride
}
