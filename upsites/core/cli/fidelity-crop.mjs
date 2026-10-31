/**
 * Recorte do export do Figma para a bounding box do frame (usado pelo fidelity.mjs).
 *
 * Quando o frame tem conteúdo que vaza além dos limites (sombra, blur, imagem de fundo
 * maior que o frame com clip desligado), o PNG exportado cobre `absoluteRenderBounds`,
 * não `absoluteBoundingBox`. O frame começa em (box.x - render.x, box.y - render.y)
 * dentro do PNG — não em (0, 0).
 */

/**
 * Deslocamento (x, y) do frame dentro do PNG exportado.
 * - `render-bounds`: absoluteRenderBounds confere com o tamanho do PNG → offset exato.
 * - `centro`: sem render bounds confiáveis (ausentes, iguais à box ou de tamanho diferente
 *   do export) → assume vazamento simétrico e centraliza o recorte.
 *
 * @param {{ width: number, height: number }} png
 * @param {{ x: number, y: number, width: number, height: number }} box
 * @param {{ x: number, y: number, width: number, height: number } | null | undefined} render
 */
export function frameOffset(png, box, render) {
  const w = Math.min(Math.round(box.width), png.width)
  const h = Math.min(Math.round(box.height), png.height)
  const clamp = (v, size, part) => Math.min(Math.max(0, Math.round(v)), size - part)
  const fits = render && Math.abs(render.width - png.width) <= 1 && Math.abs(render.height - png.height) <= 1
  if (fits) {
    return { x: clamp(box.x - render.x, png.width, w), y: clamp(box.y - render.y, png.height, h), width: w, height: h, source: 'render-bounds' }
  }
  return { x: clamp((png.width - w) / 2, png.width, w), y: clamp((png.height - h) / 2, png.height, h), width: w, height: h, source: 'centro' }
}
