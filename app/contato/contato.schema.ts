export interface ContatoImage {
  src: string
  alt: string
  width: number | null
  height: number | null
  sizes: Record<string, string>
}

export interface ContatoHeroCanal {
  icone: ContatoImage
  titulo: string
  valor: string
  link: string
}

export interface ContatoHero {
  eyebrow: string
  titulo: string
  descricao: string
  canais: ContatoHeroCanal[]
}

export interface ContatoListaItem {
  icone: ContatoImage
  texto: string
}

export interface ContatoListaBotao {
  texto: string
  link: string
}

export interface ContatoListaCardInfo {
  tipo: 'info'
  titulo: string
  descricao: string
  itens: ContatoListaItem[]
}

export interface ContatoListaCardTexto {
  tipo: 'texto'
  titulo: string
  texto: string
  botao: ContatoListaBotao | null
}

export type ContatoListaCard = ContatoListaCardInfo | ContatoListaCardTexto

export interface ContatoData {
  hero: ContatoHero
  listaContatos: ContatoListaCard[]
}
