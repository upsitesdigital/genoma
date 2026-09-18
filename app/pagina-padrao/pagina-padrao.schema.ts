export interface PaginaPadraoData {
  titulo: string
  /**
   * HTML já processado (`the_content`) da página real — parágrafos, headings,
   * imagens, listas, blockquote, código, tabela, embed, hr, links etc.
   * Renderizado via `dangerouslySetInnerHTML` dentro do wrapper `.post-content`.
   */
  conteudo: string
}
