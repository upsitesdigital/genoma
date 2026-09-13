export interface SinglePostImage {
  src: string
  alt: string
  width: number | null
  height: number | null
  sizes: Record<string, string>
}

export interface SinglePostHero {
  titulo: string
  data: string
  categoria: string
  categoriaLink: string
  imagem: SinglePostImage
  url: string
}

export interface SinglePostData {
  hero: SinglePostHero
}
