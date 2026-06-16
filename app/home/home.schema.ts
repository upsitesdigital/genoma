export interface HomeData {
  hero: {
    titulo: string
    subtitulo: string
    imagem: {
      src: string
      alt: string
      width: number | null
      height: number | null
    } | null
  }
  cta: {
    texto: string
    link: string
  }
}
