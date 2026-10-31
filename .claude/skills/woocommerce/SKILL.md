---
name: woocommerce
description: Regras do UpWork Framework para projetos de loja com WooCommerce — o que fica em template PHP do WC (woocommerce/ na raiz do tema) e o que pode ser módulo do framework, rotas que o framework não pode assumir, estilização com Tailwind, contador do carrinho no header via Store API (store da Interactivity API), produtos em seções (vitrines), campos ACF em produtos, cache e desempenho — mais o checklist de preparação do tema para a loja. Use sempre que o projeto tiver WooCommerce (plugin ativo, pasta woocommerce/ no tema ou `woocommerce: true` no prompt.yaml).
---

# WooCommerce no UpWork Framework

Vale para projetos de loja. Se o projeto **não** tem WooCommerce, ignore esta skill.

## 1. Divisão de responsabilidades (regra principal)

| Tela | Onde fica | Por quê |
|---|---|---|
| Produto, loja/categoria, carrinho, checkout, minha conta | **Templates PHP do WooCommerce**, sobrescritos em `woocommerce/` na raiz do tema | Esses fluxos dependem dos hooks do WC — gateways de pagamento, frete, cupons, variações e extensões injetam markup e JS por eles. Num módulo do framework, isso quebra. |
| Home, landing pages, institucionais, vitrines de produtos | **Módulos do framework** (`template-pages/{slug}/` + seções PHP) | Conteúdo editorial; produtos aparecem como vitrine que leva ao produto/carrinho do WC. |
| Header e footer | **Partes** `template-parts/includes/layout/header` e `layout/footer` — aparecem também nas telas do WC | `get_header('shop')`/`get_footer('shop')` caem no `header.php`/`footer.php` do tema, que renderizam as partes. |

Nunca renderize produto, carrinho, checkout ou minha conta com `View::render()`.

## 2. Rotas — o que o framework não pode assumir

- **Não** aplique o modelo "Página · {Nome}" (`fw:{slug}`) às páginas do WC
  (Carrinho, Finalizar compra, Minha conta): o `Bootstrap::catchAll` trocaria a
  página por um módulo do framework e o fluxo do WC sumiria.
- **Não** defina a página da Loja como página inicial: a front page é sempre
  resolvida para o módulo `home`. Home de loja = módulo `home` com vitrines.
- Loja, categorias e produto são arquivos/singles do WP → seguem a hierarquia
  do WooCommerce normalmente.

## 3. Sobrescrevendo templates (`woocommerce/`)

- Copie o template de `wp-content/plugins/woocommerce/templates/{caminho}` para
  `woocommerce/{caminho}` no tema e edite a cópia. Mantenha o cabeçalho com
  `@version` — o WC avisa em *WooCommerce → Status* quando a cópia fica
  desatualizada; atualize-as a cada versão maior do WC.
- **Prefira hooks a sobrescrever** (`remove_action`/`add_action` nos
  `woocommerce_*` em `upsites/functions.php`): menos arquivos para manter.
- **Nunca remova os `do_action( 'woocommerce_…' )`** de um template copiado —
  é por eles que extensões e gateways funcionam.
- Pode usar classes Tailwind no markup das cópias (exige o `@source` do
  checklist). Não escreva HTML de formulário do WC à mão (checkout, login,
  endereço): estilize as classes que o WC gera.

## 4. Estilização

- O reset do Tailwind (preflight) zera botões, listas e formulários; o markup
  do WC não tem classes Tailwind. As partes não sobrescritas são estilizadas no
  bloco **`// WooCommerce`** do `src/sass/main.scss`, com `@apply` e os tokens
  do tema (`bg-primary`, `border-border`…). **Exceção à skill `tailwind`:** aqui
  `@apply` fora do `@layer base` é permitido — só nesse bloco e só em
  seletores do WooCommerce.
- O CSS visual padrão do WC (`woocommerce-general`) é desligado no checklist;
  ficam o de estrutura (`woocommerce-layout`, `woocommerce-smallscreen`) e o dos
  blocos (carrinho/checkout em blocos).
- Teste os estados: avisos (`.woocommerce-message/-info/-error`), produto em
  promoção (`.onsale`, `del`/`ins` do preço), sem estoque, variações, campos
  com erro no checkout, botões desabilitados.

## 5. Carrinho no header (Interactivity API)

O header sai pronto do servidor (e pode vir de cache de página) e não sabe quando
um item é adicionado. O contador do carrinho **sempre** vem da **Store API do WC**
(`/wp-json/wc/store/v1/cart`, pública, por sessão), buscado no navegador pelo store
do header (`header.store.js`, namespace `upwork/layout-header` — checklist), que recarrega
quando o WC avisa que o carrinho mudou. Alternativa: usar o bloco Mini-Cart do próprio
WooCommerce (já é interativo) no lugar do contador do tema.

- **Nunca** renderize contagem/total do carrinho no PHP do header, no
  `wp_interactivity_state()` nem num controller com `#[Cache]`: varia por visitante e
  quebra com cache de página — o estado inicial é `0` e o store busca o real.
- Link do carrinho: `wc_get_cart_url()` (no `href` e, se o store precisar, no
  `wp_interactivity_state('upwork/layout-header', ['cartUrl' => …])`) — não fixe `/carrinho`.

## 6. Produtos em seções (vitrines)

- Controller: `wc_get_products(['limit' => N, 'status' => 'publish', …])` —
  sempre com `limit`; devolva só o necessário:
  `['id', 'nome' => get_name(), 'url' => get_permalink(), 'preco' => get_price_html(), 'imagem' => ImageData, 'emEstoque' => is_in_stock()]`.
  `get_price_html()` é HTML do WC (com `<del>`/`<ins>`): no template da seção,
  `<?= wp_kses_post($produto['preco']) ?>`.
- **Cache:** preço e estoque mudam sem `save_post` (pedidos baixam estoque) —
  o `#[Cache]` do controller mostraria valor velho. Para vitrine com preço/
  estoque use `#[Cache(ttl: 60)]` no máximo, ou nenhum.
- **Botão de compra na vitrine:** link para o produto (`url`). "Adicionar ao
  carrinho" direto da vitrine só para produto simples, num store da seção
  (`{section}.store.js`) via Store API (`POST /wp-json/wc/store/v1/cart/add-item` com o
  header `Nonce` devolvido pelo `GET /cart`), disparando
  `document.body.dispatchEvent(new CustomEvent('upwork:cart'))` para o header
  atualizar. Produto variável → sempre link.

## 7. Campos ACF em produtos

`product` é CPT do WooCommerce, não do tema — a regra de "grupo de CPT" da
skill `acf` (`Fields::postType()`, só para CPTs do `register_cpts.php` do tema) não
se aplica. Use `Fields::section()` no bloco de um módulo de contexto (ex.:
`template-pages/single-product/`, `template: false`) em
`upsites/customizer-register/inc/acf_fields.php`, com location por post type:

```php
/* ── single-product — Produto ── */

Fields::section('single-product', 'product_extra', 'Informações extras', [ /* campos */ ], order: 1,
    location: [[['param' => 'post_type', 'operator' => '==', 'value' => 'product']]]);
```

Leia esses campos no template PHP do produto (`woocommerce/single-product/...`)
com `get_field()` — a tela de produto é PHP (regra 1).

## 8. Desempenho

- Nada de `wc_get_products()`/`WC_Product_Query` sem `limit`; nada de carregar
  `WC()->cart` em controller REST (não há sessão de visitante ali).
- Carrinho, checkout e minha conta **não** podem ir para cache de página.
- Não enfileire scripts do WC fora das telas dele; se algum plugin enfileirar
  `wc-cart-fragments` no site todo, desligue fora do WC — o header usa a Store API.
- Vitrines são HTML do servidor: só ganham JS (store) se tiverem "adicionar ao
  carrinho", filtro ou carrossel.
- Imagens de produto na vitrine: `ImageData` renderizado com
  `fw_img($img, [...], 'woocommerce_thumbnail')` (`wp_get_attachment_image`: lazy, `width`/`height`, `srcset`).

## 9. Checklist do r2d2 (projeto com WooCommerce)

- [ ] nenhuma página do WC (Carrinho, Finalizar compra, Minha conta) com modelo `fw:*`; Loja não é a página inicial
- [ ] produto/carrinho/checkout/conta em template PHP; nenhum `View::render()` neles
- [ ] cópias em `woocommerce/` com `@version` e com todos os `do_action('woocommerce_…')` do original
- [ ] carrinho no header pelo store `upwork/layout-header` (Store API, buscado no navegador) — nada de contagem renderizada no PHP, no `wp_interactivity_state()` ou em `#[Cache]`
- [ ] vitrines: `limit`, payload enxuto, preço com `wp_kses_post`, `#[Cache]` ≤ 60s
- [ ] `@apply` só no bloco `// WooCommerce` do `main.scss`, em seletores do WC
- [ ] estados de produto/checkout estilizados (promoção, sem estoque, avisos, erros)

## 10. Preparação do tema para a loja (uma vez por projeto)

1. **Tailwind lê os templates do WC** — em `src/sass/main.scss`, junto dos outros `@source`:
   ```scss
   @source '../../woocommerce';
   ```
2. **Estilos base** — no fim do `src/sass/main.scss`, no bloco de ajustes manuais:
   ```scss
   // WooCommerce — markup do WC não tem classes Tailwind (@apply permitido só aqui)
   .woocommerce,
   .woocommerce-page {
     a.button,
     button.button,
     input.button,
     .button.alt {
       @apply inline-flex items-center justify-center rounded-md bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground transition hover:opacity-90 disabled:opacity-60;
     }
     .woocommerce-message,
     .woocommerce-info,
     .woocommerce-error {
       @apply mb-6 list-none rounded-md border border-border bg-muted p-4 text-sm;
     }
     .price { @apply font-semibold text-foreground; }
     .price del { @apply mr-2 font-normal text-muted-foreground; }
     .price ins { @apply no-underline; }
     .onsale { @apply rounded-sm bg-primary px-2 py-1 text-xs font-semibold text-primary-foreground; }
     input.input-text,
     select,
     textarea {
       @apply w-full rounded-md border border-border bg-background px-3 py-2 text-sm;
     }
   }
   ```
3. **CSS visual do WC desligado** — em `upsites/functions.php`:
   ```php
   /* WooCommerce: o tema estiliza (main.scss); fica só o CSS de estrutura do WC */
   add_filter('woocommerce_enqueue_styles', function (array $styles): array {
     unset($styles['woocommerce-general']);
     return $styles;
   });
   ```
4. **Contador do carrinho no header** — em
   `template-parts/includes/layout/header/header.php`, antes do markup:
   ```php
   $hasCart = function_exists('wc_get_cart_url');
   if ($hasCart) {
     wp_interactivity_state('upwork/layout-header', [
       'cartApi'   => rest_url('wc/store/v1/cart'),
       'cartCount' => 0, // o real vem do navegador (varia por visitante; página pode estar em cache)
       'cartLabel' => __('Carrinho: %d itens', 'upsites'),
     ]);
   }
   ```
   e o link, dentro do `<header data-wp-interactive="upwork/layout-header">`:
   ```php
   <?php if ($hasCart) : ?>
     <a href="<?= esc_url(wc_get_cart_url()) ?>" class="relative inline-flex items-center"
        aria-label="<?= esc_attr__('Carrinho', 'upsites') ?>"
        data-wp-init="callbacks.initCart" data-wp-bind--aria-label="state.cartAriaLabel">
       <svg aria-hidden="true" class="size-6">…</svg>
       <span hidden data-wp-bind--hidden="!state.cartCount" data-wp-text="state.cartCount"
             class="absolute -top-2 -right-2 rounded-full bg-primary px-1.5 text-xs font-semibold text-primary-foreground"></span>
     </a>
   <?php endif; ?>
   ```
   No `header.store.js`, junto do que já existe:
   ```js
   const { state } = store('upwork/layout-header', {
     state: {
       get cartAriaLabel() {
         return state.cartLabel.replace('%d', state.cartCount)
       },
     },
     // actions: { toggle, close, closeOnEscape } — as que já existem
     callbacks: {
       initCart() {
         const load = () =>
           fetch(state.cartApi, { credentials: 'same-origin' })
             .then((r) => (r.ok ? r.json() : null))
             .then((cart) => { if (cart) state.cartCount = cart.items_count })
             .catch(() => {})
         load()
         // blocos do WC disparam eventos nativos; 'upwork:cart' = vitrines do tema e a ponte abaixo
         for (const ev of ['wc-blocks_added_to_cart', 'wc-blocks_removed_from_cart', 'upwork:cart']) {
           document.body.addEventListener(ev, load)
         }
         window.addEventListener('pageshow', (e) => e.persisted && load()) // voltar pelo histórico
       },
     },
   })
   ```
   Os eventos do WC clássico (`added_to_cart`, `removed_from_cart`, `updated_cart_totals`,
   `wc_fragments_refreshed`) são **jQuery** — e store não usa jQuery (o review dá erro).
   A ponte fica no JS manual (`src/js/mainJS.js`), só onde o WC carregou o jQuery:
   ```js
   window.jQuery?.(document.body).on('added_to_cart removed_from_cart updated_cart_totals wc_fragments_refreshed', () =>
     document.body.dispatchEvent(new CustomEvent('upwork:cart')),
   )
   ```
5. **`prompt.yaml`** — `projeto.woocommerce: true`, para o c3po avisar os agentes.
6. Depois de ativar o WooCommerce: confira em *WooCommerce → Configurações →
   Avançado* que Carrinho/Finalizar compra/Minha conta usam o modelo **padrão**,
   e rode `npm run build` + `npm run review`.
