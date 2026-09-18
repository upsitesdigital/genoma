export interface ResultadoPesquisaImage {
  src: string
  alt: string
  width: number | null
  height: number | null
  sizes: Record<string, string>
}

export interface ResultadoPesquisaHero {
  eyebrow: string
  buscaPlaceholder: string
  buscaIcone: ResultadoPesquisaImage
  termo: string
  semResultadosTexto: string
}

export interface ResultadoPesquisaPost {
  id: number
  titulo: string
  resumo: string
  data: string
  link: string
  categoria: string
  imagem: ResultadoPesquisaImage
}

export interface ResultadoPesquisaLista {
  posts: ResultadoPesquisaPost[]
  paginaAtual: number
  totalPaginas: number
  totalPosts: number
  termo: string
}

export interface ResultadoPesquisaData {
  hero: ResultadoPesquisaHero
  resultados: ResultadoPesquisaLista
}
