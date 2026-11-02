/**
 * Tipos de exames do Responsável (template-parts/includes/responsavel/servicos/servicos.php):
 * - `actions.toggle`: abre/fecha a categoria clicada (`context.open` de cada <li>);
 * - `actions.toggleMore`: "Ver mais"/"Ver menos" — `context.expanded` (na <section>) mostra as
 *   categorias além da 3ª (VerMaisButton do React). As categorias guardam o próprio aberto/fechado.
 */
import { getContext, store } from '@wordpress/interactivity'

store('upwork/responsavel-servicos', {
  actions: {
    toggle() {
      const ctx = getContext()
      ctx.open = !ctx.open
    },
    toggleMore() {
      const ctx = getContext()
      ctx.expanded = !ctx.expanded
    },
  },
})
