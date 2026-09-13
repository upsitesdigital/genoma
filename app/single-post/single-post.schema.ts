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

export interface SinglePostRelacionado {
  id: number
  titulo: string
  resumo: string
  data: string
  link: string
  categoria: string
  imagem: SinglePostImage
}

export interface SinglePostData {
  hero: SinglePostHero
  /**
   * HTML já processado (`the_content`) do post real — parágrafos, headings,
   * imagens, listas, blockquote, código, tabela, embed, hr, links etc.
   * Renderizado via `dangerouslySetInnerHTML` dentro do wrapper `.post-content`.
   */
  conteudo: string
  /**
   * "Veja também" — posts reais relacionados (mesma categoria do post atual,
   * com fallback para posts recentes de qualquer categoria caso não haja o
   * suficiente). Nunca inclui o próprio post. Vazio quando não há nenhum post
   * disponível para completar a lista.
   */
  vejaTambem: SinglePostRelacionado[]
}
