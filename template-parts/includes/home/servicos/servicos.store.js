/**
 * Serviços da home (template-parts/includes/home/servicos/servicos.php) — "Ver mais"/"Ver menos":
 * `context.expanded` mostra os cards além do 3º (VerMaisButton do React).
 */
import { getContext, store } from '@wordpress/interactivity'

store('upwork/home-servicos', {
  actions: {
    toggle() {
      const ctx = getContext()
      ctx.expanded = !ctx.expanded
    },
  },
})
