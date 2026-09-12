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
  imagem1: OGenomaImage
  imagem2: OGenomaImage
  destaque: string
}

export interface OGenomaHero {
  slides: OGenomaHeroSlide[]
  autoplay: boolean
  intervalo: number
}

export interface OGenomaData {
  hero: OGenomaHero
}
