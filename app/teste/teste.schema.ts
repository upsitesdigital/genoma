export interface TesteCard {
  icone: string
  titulo: string
  texto: string
}

export interface TesteData {
  titulo: string
  descricao: string
  numero: number
  imagem: { src: string; alt: string; width: number | null; height: number | null } | null
  cor: 'azul' | 'verde' | 'roxo' | 'laranja'
  exibir_banner: boolean
  cta: { title: string; url: string; target: string } | null
  cards: TesteCard[]
}
