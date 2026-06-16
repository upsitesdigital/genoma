export interface TesteCard {
  icone: string
  titulo: string
  texto: string
}

export interface TesteData {
  titulo: string
  descricao: string
  numero: number
  imagem: { url: string; alt: string; width: number; height: number } | null
  cor: 'azul' | 'verde' | 'roxo' | 'laranja'
  exibir_banner: boolean
  cta: { title: string; url: string; target: string } | null
  cards: TesteCard[]
}
