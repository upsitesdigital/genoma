export interface BlogImage {
  src: string
  alt: string
  width: number | null
  height: number | null
  sizes: Record<string, string>
}

export interface BlogHeroCategoria {
  titulo: string
  link: string
  destaque: boolean
}

export interface BlogHero {
  eyebrow: string
  titulo: string
  buscaPlaceholder: string
  buscaIcone: BlogImage
  categorias: BlogHeroCategoria[]
}

export interface BlogPost {
  id: number
  titulo: string
  resumo: string
  data: string
  link: string
  categoria: string
  imagem: BlogImage
}

export interface BlogListaPost {
  posts: BlogPost[]
  paginaAtual: number
  totalPaginas: number
  totalPosts: number
  categoriaAtual: string
}

export interface BlogData {
  hero: BlogHero
  listaPost: BlogListaPost
}
