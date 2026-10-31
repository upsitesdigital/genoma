---
name: performance
description: Pontos de desempenho do UpWork Framework — HTML renderizado no servidor (PHP + Interactivity API, sem hidratação), JS só nas partes interativas (stores), cache dos controllers (atributo Cache) e cache de página, LCP, imagens, fontes e o enqueue legado do upsites. Use ao investigar PageSpeed/Lighthouse, páginas lentas, seção que não aparece, ou antes de otimizar build e enqueue.
---

# Desempenho no UpWork Framework

Para revisão de código PHP/WP em geral (queries, hooks, cache de objeto), use
também a skill `wp-performance-review`. Aqui ficam os pontos **específicos
deste framework**.

## Página sem estilo / seção sumida — confira o build e o log antes de tudo

- CSS/JS do gulp: no localhost o WP carrega o aberto (`main.css`, ~5× maior,
  com sourcemap); em homolog/prod, **só** o `.min`. Site sem estilo fora do
  localhost = `main.min.css` não gerado/enviado (veja o `error_log` e o aviso no
  painel; rode `npm run build`). Classe nova sem efeito no local = CSS não recompilado
  (`npm run dev` ou `npm run css`).
- Seção que não aparece: ou o editor marcou "Ocultar seção" (`$data` null), ou o
  template deu erro — o `Part` engole o `Throwable`, some só com aquela parte e
  registra `[upwork] erro ao renderizar "{grupo}/{nome}"` no `error_log`.
- Não há bundle de app: o HTML sai pronto do servidor e **só as partes interativas
  baixam JS** — o `{nome}.store.js` delas (script module, enfileirado pelo `Part` só
  nas páginas que usam a parte) + o runtime da Interactivity API do próprio WP
  (~10 KB gz, compartilhado e cacheado). Sem vendor chunk, sem view lazy, sem
  `modulepreload` manual.
- **Medir:** `npm run size` — JS gzip por página ("comum" = store do header +
  `mainJS`; cada módulo = comum + stores das suas seções). Store acima de ~10 KB gz
  merece olhar (lógica que poderia ir para o PHP). Não importe biblioteca grande num
  store do header.

## Como o conteúdo chega (custo por requisição)

- `View::render()` chama o `page()` do controller direto em PHP **no
  servidor**, em toda visualização — sem REST (nem o servidor REST do WP, que
  registraria as ~170 rotas do WP e dos plugins a cada página). Use
  `#[Cache(ttl: …)]` no `page()`: o transient `upwork_page_*` evita refazer as queries ACF.
- Como o HTML é completo e não depende de JS, **cache de página** (plugin/CDN)
  funciona normalmente; o nonce do `upwork/api` vencido em página cacheada é renovado
  sozinho.
- `Bootstrap::clearRestCache()` invalida **todo** o cache do framework — `upwork_page_*`
  (page()) e `upwork_rest_*` (rotas dos stores), trocando a versão da chave — ao
  salvar/apagar página, post, CPT público ou anexo e ao atribuir termos; criar/editar/apagar
  termo e salvar o Customizer chamam o `flushAll` — uma vez só, no fim da requisição
  (`Rest::flushCacheLater`), depois de tudo gravado. Tipos internos (envio de formulário,
  pedido, item de menu) não invalidam. Deploy que altera um controller também não serve o
  cache antigo (a data do arquivo entra na chave). Em sites com muitas edições o cache
  esvazia com frequência; se pesar, invalide por módulo.
- Header e footer montam os menus em toda página (`MenuApi::tree()` →
  `wp_get_nav_menu_items`). Com menus grandes, considere cache por localização.
- O render de uma página lê só o controller dela (`ModuleLoader::controller`); todos os
  controllers e as rotas REST só são compilados na REST. Ícone SVG inline
  (`Media::iconData`/`inlineSvg`) é sanitizado uma vez e, em anexo, guardado no post meta
  `_upwork_svg_inline` até o arquivo mudar.
- O retorno do `page()` vai inteiro para o transient: devolva só o que o view usa
  (sem `get_fields()` inteiro, sem objetos `WP_Post` crus).
- `#[Cache]` só vale para GET/HEAD — numa rota de escrita ele é ignorado (e avisa no log).
- Estado global passado ao navegador (`wp_interactivity_state()`) vai no HTML como
  JSON: só o que o store precisa.

## Renderização e LCP

- O conteúdo é renderizado no servidor: o LCP não depende de JS nem de
  hidratação, e o Google lê o HTML completo.
- Imagem do hero: `fw_img($img, ['fetchpriority' => 'high', 'loading' => false])`;
  demais imagens com o padrão do `fw_img()` (`loading="lazy"` + `decoding="async"`).
  Sempre `width`/`height` (CLS) — o `fw_img()` já põe.
- A imagem do hero está no HTML inicial, então o navegador a descobre sem preload.
  `View::lcpImage()` (maior imagem da 1ª seção) é usada como `og:image` pelo `Seo`.
- Anexos saem pelo `wp_get_attachment_image()` (dentro do `fw_img()`): `srcset` só com
  os tamanhos da mesma proporção e `sizes="auto, …"` nas lazy. Escolha o tamanho pelo
  espaço (3º argumento: `medium` em ícone/logo, `large` em card, `full` no hero de tela
  cheia — com `large` o `sizes` limita a 1024px e o fundo fica borrado em tela larga).
- Conteúdo carregado depois por um store (`api()`) não deve ser o LCP nem o
  conteúdo principal para SEO — o que importa vem no HTML do servidor.

## Fontes e CSS

- Fontes **só locais**: Google Fonts por `@import`/`<link>` bloqueia a
  renderização, abre conexão com outro domínio e expõe o IP do visitante (LGPD).
  `.woff2` (subset latin, só os pesos usados) em `src/fonts/` → `gulp fonts`
  copia para `assets/fonts/`; `@font-face` no `main.scss` com `font-display: swap`.
  Preload só da fonte do texto acima da dobra:
  `add_filter('upwork/preload_fonts', fn() => ['{arquivo}.woff2']);` (o
  `upsites/functions.php` imprime o `<link rel="preload" as="font" crossorigin>`).
  Cada peso/estilo é um arquivo a mais — não carregue pesos que o layout não usa.
- **Critical CSS da home**: `src/sass/home-above-the-fold.scss` (importa o `main.scss` com
  `$fw-critical: true`; classes só dos `@source` dele — header, hero e componentes que eles
  usam) vai inline no `<head>` da home e o `main.css` dela carrega sem bloquear
  (`US_critical_css`, `upsites/functions.php`; `url()` relativo vira absoluto). Seção
  visível na primeira tela do celular/desktop precisa de `@source` lá; bloco do `main.scss`
  que nunca aparece na dobra fica em `@if not $fw-critical { … }`. Conferir: print da
  primeira tela com o `main.css` bloqueado (Playwright `page.route`) igual ao normal.
  Outra página: `src/sass/{módulo}-above-the-fold.scss` (o gulp compila todos).
- Tailwind usa `source(none)` + `@source` explícito: só `src/js/`,
  `template-pages/`, `template-parts/`, os `*.php` da raiz e `upsites/core/Framework/`
  são varridos. Classe montada por pedaço não é gerada (ver skill `tailwind`).

## Legado que pesa em toda página

- `upsites/functions.php` enfileira `mainJS` (JS manual, sem jQuery) em **todas**
  as páginas. Se o projeto não tem JS manual, deixe o `src/js/mainJS.js` mínimo;
  interação de seção vai num store da Interactivity API, não no `mainJS`.

## Medir

- Lighthouse / aba Network em janela anônima (sem admin bar, sem cache de
  usuário logado).
- `npm run size` para o JS por página; no Network, filtre por "JS" e confira que
  só aparecem os stores das partes interativas da página.
- Tempo do PHP: Query Monitor (plugin) mostra o custo do controller (`page()`)
  e das queries ACF por página.
