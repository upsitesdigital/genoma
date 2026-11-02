/**
 * Tipos de Exames de Veterinários (template-parts/includes/veterinarios/tipos-exames/tipos-exames.php).
 * - Acordeão: `context.aberta` (na <section>) é o índice da categoria aberta ou null; cada categoria
 *   tem o seu `context.i`. Uma aberta por vez; clicar na aberta fecha (openIndex do React).
 * - "Ver mais"/"Ver menos": `context.expanded` mostra as categorias além da 3ª (VerMaisButton do
 *   React). Recolher a lista não fecha a categoria aberta, como no React.
 */
import { getContext, store } from '@wordpress/interactivity'

store('upwork/veterinarios-tipos-exames', {
  state: {
    get isOpen() {
      const ctx = getContext()
      return ctx.aberta === ctx.i
    },
    // aria-label do botão: "Expandir X" fechado, "Recolher X" aberto
    get rotulo() {
      const ctx = getContext()
      return ctx.aberta === ctx.i ? ctx.recolher : ctx.expandir
    },
  },
  actions: {
    toggle() {
      const ctx = getContext()
      ctx.aberta = ctx.aberta === ctx.i ? null : ctx.i
    },
    toggleLista() {
      const ctx = getContext()
      ctx.expanded = !ctx.expanded
    },
  },
})
