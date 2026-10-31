# framework

Tema WordPress em PHP + [Interactivity API](https://developer.wordpress.org/block-editor/reference-guides/interactivity-api/) + Tailwind v4,
sobre o UpWork Framework (`upsites/core/`). O WordPress resolve as rotas e escolhe o template PHP; o template
renderiza o módulo no servidor com os dados do controller. A interatividade (menu, abas, acordeões…) é feita com
diretivas `data-wp-*` e stores pequenos em JS — **WordPress 6.5+**.

## Estrutura

| Pasta | Conteúdo |
|---|---|
| `upsites/core/` | Framework PHP: módulos, rotas REST via attributes, render de módulos (`View`) e de partes (`Part`), helpers de markup (`fw_*`), Opções do Tema; `core/js/` = módulos JS do framework (`upwork/api`, `upwork/reveal`) |
| `template-pages/{slug}/` | Módulos: `{slug}.module.php` (`#[Module]`, rota, modelo), `.controller.php`, `.view.php` (compõe as seções) |
| `upsites/customizer-register/inc/register_cpts.php` · `register_taxes.php` | Todos os CPTs (`PostType::add()`) e todas as taxonomias (`Taxonomy::add()`) do tema; `public: false` = só no admin |
| `upsites/customizer-register/inc/acf_fields.php` | Todos os campos ACF do tema: um bloco por página, uma `Fields::section('{slug}', …)` por seção (`Fields::postType()`/`Fields::taxonomy()` para CPTs) |
| `template-parts/includes/{slug}/{section-name}/` | Uma pasta por seção (nome **em inglês**): `{section-name}.php` (markup), `{section-name}.store.js` (opcional, Interactivity API), `assets/` |
| `template-parts/includes/layout/` · `shared/` | Header e footer do site · componentes reusados entre páginas (`button`, `icon`, `cf7-form`, `not-found`, `post-list`/`post-item` dos templates de blog) — mesmo formato de pasta das seções |
| `template-pages/*.php` | Page templates PHP avulsos |
| `template-parts/*.php` | `app.php` (template das páginas de módulo) + `archive.php`, `search.php`, `author.php`, `error.php` (incluídos pelo `index.php`) |
| `index.php` · `single.php` · `page.php` · `404.php` · `comments.php` | Hierarquia do WP em PHP puro (ver "Templates do WordPress") |
| `header.php` / `footer.php` | A casca da página (`<head>`, `wp_footer`) — renderizam `layout/header` e `layout/footer` em `#fw-header`/`#fw-footer` em todas as páginas |
| `src/` | CSS do tema em `sass/main.scss` (Tailwind + Sass) e JS manual em `js/` |
| `assets/` | **Não versionado** (`.gitignore`) — saída do build (gulp): `css/` e `js/` (aberto + `.min`), `images/`, `fonts/` (cópia de `src/fonts/`) |
| `upsites/` | PHP de runtime do tema: o framework (`core/`, com o ACF PRO 6.8.10 embarcado em `core/acf/` — usado quando o plugin não está instalado, caso normal em produção) e as funções UpSites |

## Roteamento e render

Páginas com modelo **"Página · {Nome}"** (`fw:{slug}`) e a página inicial renderizam o módulo
automaticamente. Os demais templates do WP (`single.php`, `archive.php`…) chamam:

```php
Core\Framework\View::render('single-post', get_the_ID());
```

No painel, as páginas de modelo (e a página inicial) abrem **sem editor de conteúdo e sem imagem
destacada** — só o título e o grupo de campos da página, com uma aba lateral por seção (`Core\Admin\ModelScreen`). Página nova:
ao salvar com um modelo escolhido, a tela recarrega já nesse formato.

O `View::render()` chama o `page(int $pageId)` do controller direto em PHP (sem REST: o id vem do WordPress, com o
`#[Cache]` dele) e passa o retorno, em `$data`, para `template-pages/{slug}/{slug}.view.php`, que
compõe as seções:

```php
<main>
  <?php Part::render('home/hero', $data['hero']); ?>      <?php // null ("Ocultar seção") → não renderiza ?>
  <?php Part::render('home/about-us', $data['aboutUs']); ?>
</main>
```

`Part::render('{grupo}/{nome}', $data)` inclui `template-parts/includes/{grupo}/{nome}/{nome}.php` com `$data`.
Se a pasta tiver `{nome}.store.js`, o script module é enfileirado sozinho (só nas páginas que usam a parte).
Erro dentro de uma parte não derruba a página: só ela some (e o erro vai para o `error_log`). As diretivas
`data-wp-*` são processadas no servidor (`wp_interactivity_process_directives`), então o HTML já sai com o
estado inicial — o JS só assume a interação. Toda navegação é carregamento normal de página.

### Templates do WordPress (sem módulo)

Tudo que não é página de modelo nem a página inicial segue a hierarquia do WP, com templates em PHP puro
(Tailwind, saída escapada, textos no text domain `upsites`) — funcionam sem módulo nenhum:

| Template | O que mostra |
|---|---|
| `single.php` | post: título, data/autor, imagem destacada, conteúdo (`.entry-content`), `wp_link_pages`, link de editar, navegação entre posts e comentários; chama `US_set_post_views()`. Com módulo de post, troque o corpo por `View::render('single-post', get_the_ID())` (comentário no topo do arquivo) |
| `page.php` | página comum (sem modelo): título, conteúdo, `wp_link_pages`, editar e comentários |
| `index.php` | fallback: página de posts (lista + paginação) e despacho para `template-parts/author.php`, `archive.php` (título/descrição do arquivo) e `search.php` ("Resultados para: …" + formulário de busca) |
| `404.php` | `template-parts/error.php` → parte `shared/not-found` (o WP já envia o status 404) |
| `comments.php` | lista, paginação e formulário de comentários; aviso de comentários fechados |

As listas usam `Part::render('shared/post-list', ['empty' => …])`, que roda o loop principal com um
`shared/post-item` por post. O conteúdo do editor recebe uma tipografia mínima em `.entry-content`
(`src/sass/main.scss`); `.screen-reader-text` (texto oculto que o WP imprime) é `sr-only`.

Supports do tema (`upsites/functions.php`, `after_setup_theme`): `title-tag`, `post-thumbnails`,
`automatic-feed-links`, `html5`, `responsive-embeds`, `editor-styles`, `custom-logo` (197×50, flexível) e,
só com o plugin ativo, os do WooCommerce. A meta `author` da UpSites fica desligada (metadados de
SEO são do Yoast); para ligar: `add_filter('upwork/author_meta', '__return_true');`. O jQuery (só
plugins o usam) vai para o rodapé, mantendo o registro do WordPress (`jquery-core` + `jquery-migrate`).
No texto do rodapé (Opções do Tema), `{ano}` e `{site}` viram o ano atual e o nome do site.

### Markup das seções

O shape de `$data` fica documentado no topo do `.php` da seção (`@var array{…} $data`). Toda saída é escapada:
`esc_html()`/`esc_attr()`/`esc_url()`, `wp_kses_post()` para HTML editável, e os helpers do tema
(`upsites/core/Support/helpers.php`, já escapados — o PHPCS os reconhece):

| Helper | Uso |
|---|---|
| `fw_cn('a b', ['c' => $cond])` | classes condicionais (atributo `class`) |
| `fw_attrs([...])` | atributos a partir de array (`null`/`false` omitem, `true` = atributo booleano) |
| `fw_url($url)` / `fw_target($target)` | URL segura (só http/https/mailto/tel/relativa) · `target`+`rel` de nova aba — para usar dentro do `fw_attrs()` |
| `fw_img($imagem, [...], 'large')` | `<img>` de um ImageData: anexo da biblioteca sai pelo `wp_get_attachment_image($id, $tamanho, false, ['loading' => 'lazy', …])` — `width`/`height`, `srcset` e `sizes` do WP (`auto` nas lazy). Tamanho pelo espaço que ocupa: `thumbnail`, `medium`, `medium_large`, `large` (padrão) ou `full` (hero de tela cheia). Asset do tema/SVG → `<img>` simples. Hero: `['fetchpriority' => 'high', 'loading' => false]` |
| `fw_lines($texto)` | textarea com quebras de linha em `<br>` |

Componentes prontos: `Part::render('shared/button', ['link' => $data['botao'], 'class' => '…'])`,
`shared/icon` (`['icon' => …, 'class' => 'size-6']`), `shared/cf7-form` (`['html' => $data['form']]`).

### Interatividade (Interactivity API)

```php
<div data-wp-interactive="upwork/home-faq" <?= wp_interactivity_data_wp_context(['open' => false]) ?>>
  <button data-wp-on--click="actions.toggle" data-wp-bind--aria-expanded="context.open">…</button>
  <div hidden data-wp-bind--hidden="!context.open">…</div>
</div>
```

```js
// template-parts/includes/home/faq/faq.store.js — ES module, sem build
import { getContext, store } from '@wordpress/interactivity'

store('upwork/home-faq', {
  actions: {
    toggle() {
      const ctx = getContext()
      ctx.open = !ctx.open
    },
  },
})
```

Namespace do store: `upwork/{grupo}-{nome}`. Estado global: `wp_interactivity_state('upwork/…', [...])` no PHP.
Para chamar a REST do framework num store: `import { api } from 'upwork/api'` (nonce e URL vêm de
`wp_interactivity_config('upwork', …)`; nonce vencido é renovado sozinho). Exemplo real: o menu mobile em
`template-parts/includes/layout/header/`.

## CSS e JS

O **Tailwind é a base** do CSS (`src/sass/main.scss`, classes lidas dos `.php` e `.js` do tema); ajustes manuais
vão em SCSS no mesmo arquivo e JS manual em `src/js/`. Os stores da Interactivity API (`{nome}.store.js`) e os
módulos de `upsites/core/js/` são **script modules servidos direto, sem build** (o WordPress monta o import map).
O **gulp compila** CSS e JS manual em duas versões e o WordPress escolhe pelo ambiente:

| Ambiente | CSS | JS manual |
|---|---|---|
| localhost, `*.local`, `*.test`, `WP_ENVIRONMENT_TYPE=local` | `main.css` | `mainJS.js` |
| homologação e produção | `main.min.css` | `mainJS.min.js` |

Fora do localhost não há fallback: o servidor precisa do build (veja "Deploy"). Para testar o `.min` localmente,
`define('FW_MINIFIED_ASSETS', true);` no `wp-config.php`.

**Critical CSS da primeira dobra** (home): `src/sass/home-above-the-fold.scss` → `assets/css/home-above-the-fold[.min].css`,
impresso inline no `<head>` da home (`US_critical_css`, `upsites/functions.php`); com ele, o `main.css` dessa página
carrega sem bloquear a renderização (`media="print"` → `all`, com `<noscript>`). O arquivo importa o `main.scss` com
`$fw-critical: true` — tokens, fontes e base vêm de lá — e o Tailwind gera **só** as classes dos `@source` dele (header,
hero e os componentes que eles usam). Seção que aparece na primeira tela do celular ou do desktop entra com um `@source`
a mais; confira tirando um print da primeira tela com o `main.css` bloqueado (deve ficar igual). Outra página: crie
`src/sass/{módulo}-above-the-fold.scss` igual (o gulp compila todo `*-above-the-fold.scss`; o tema usa o do módulo da página).
Bloco do `main.scss` que nunca aparece na dobra (ex.: conteúdo de post) fica dentro de `@if not $fw-critical { … }`.

**Fontes web são sempre locais** — nada de Google Fonts por `@import`/`<link>` (o navegador do visitante envia o IP
ao Google: LGPD). Baixe os `.woff2` (subset latin, só os pesos usados — ex.: google-webfonts-helper ou Fontsource)
para `src/fonts/` (versionado), **junto com o arquivo de licença da fonte** (ex.: `OFL.txt`, que vem no pacote da
fonte — a OFL exige que a licença acompanhe a fonte distribuída); o build (`gulp fonts`, dentro do
`npm run dev`/`npm run build`) copia os `.woff2`/`.woff` e os `.txt` para `assets/fonts/`, e eles vão no pacote do
tema. Cite a fonte e a licença no `== Copyright ==` do `readme.txt`. No `main.scss`, um `@font-face` por peso com `font-display: swap` e
`src: url('../fonts/arquivo.woff2') format('woff2')` + o token `--font-*` no `@theme` (há um modelo comentado no
topo do arquivo). Preload só da fonte do texto: `add_filter('upwork/preload_fonts', fn() => ['arquivo.woff2']);`.

## Segurança

O que o framework garante sozinho (não desfaça ao customizar):

- **REST** (`framework/v1`): só o que um store do JS busca (`#[Get]` em método próprio) — os dados da página saem do
  `page()` em PHP e não ficam expostos em `/wp-json/`. Leitura é pública e escrita sem `#[Auth]` é negada. Rota com `:id` só responde se o post
  for visível para quem pede (rascunho, privado e com senha → 404 para visitante). O `id` é conferido no caminho, na
  query e no corpo — valores diferentes ou não canônicos (`01`) → 404. Só conteúdo público vai para o cache; rota com
  `#[Auth]` separa o cache por usuário. Rota sem id que precise da home: `$this->frontPageId()` (só a página inicial pública).
- **Saída**: todo template escapa (`esc_html`/`esc_attr`/`esc_url`/`wp_kses_post`; helpers `fw_*` já escapados) —
  `npm run lint:wpcs` e `npm run review` acusam saída crua. `fw_url()` bloqueia `javascript:`, `data:` e `//`.
- **SVG**: upload só para quem tem `upload_files` (ou os papéis do Safe SVG), sanitizado por lista do que é permitido em todos os caminhos de
  upload (XML-RPC/`wp_upload_bits` só aceita SVG já limpo; todo anexo criado é reescrito), e de novo ao ser embutido
  inline. CSS do SVG só por gramática fechada (propriedades de desenho, remontado do zero); ids e classes ganham
  prefixo próprio (não colidem com a página nem com o tema) e o ícone é isolado com `contain: paint`. Só a raiz
  `<svg>` sem prefixo e elementos no mesmo namespace dela (`<s:svg>` embutido no HTML viraria HTML). Detalhes na
  skill `wordpress`.
- **Plugin Safe SVG** (recomendado junto): sanitiza de novo no upload; o tema liga
  `safe_svg_remove_remote_references` e, com "Upload roles" marcado (Configurações → Mídia — marque só
  Administrador), passa a seguir esses papéis. Ele **não substitui** o `Svg::sanitize`: não remove `data-wp-*`,
  classes do tema, `position: fixed` nem CSS global — o que só importa no SVG embutido inline.
- **Conteúdo de usuário**: diretivas `data-wp-*` saem ao salvar post/resumo de quem não tem `unfiltered_html`, todo
  comentário e WYSIWYG do ACF salvo sem `unfiltered_html` (`Core\Support\Kses`); o que sobrar vira `data-wp_` —
  senão o runtime da Interactivity API as executaria.
- **Fontes** só locais (`src/fonts/` → `assets/fonts/`): nada de Google Fonts por `@import`/`<link>` (LGPD).
- **Formulários** são do Contact Form 7 (validação, nonce e anti-spam dele); o tema não grava dados de visitante.
- **Preview** (render isolado do `npm run fidelity`) só existe em ambiente local.

Ao criar endpoint que grava, use `#[Auth('capability')]` e valide/sanitize cada parâmetro. Mantenha o ACF PRO
embarcado atualizado (`npm run acf:update`).

## Credenciais (nunca no repositório)

- **Token do Figma**: variável de ambiente `FIGMA_API_KEY` (o `.mcp.json` versionado usa `"${FIGMA_API_KEY}"`,
  que o Claude Code expande; o `npm run fidelity` lê a mesma variável). Não grave o token no `.mcp.json`.
- **Usuário/senha do wp-admin**: não vão no `.claude/prompt.yaml` (versionado) — faça login no navegador ou use
  `FW_ADMIN_USER`/`FW_ADMIN_PASS` no ambiente.
- `.env`, `.env.*` e `*.local.*` estão no `.gitignore` para configurações locais.

## Deploy

`assets/` **não é versionado** — o build é gerado a partir do código, nunca commitado. Para subir o tema:

- **Pela pipeline (GitHub Actions, `.github/workflows/build.yml`)**: todo push na `main` e todo PR roda `npm run check`
  e `npm run package`; o tema pronto fica em *Actions → execução → Artifacts → `tema`* (zip). É esse zip que vai
  para homologação/produção.
- **Local**: `npm run package` → `dist/{tema}.zip` (build + tema sem `node_modules`, `src/`, `.claude/`, configs).
  Suba em *Aparência → Temas → Enviar tema* ou descompacte em `wp-content/themes/`.

Se o servidor ficar sem o build, o painel mostra um aviso para os administradores (`Asset::buildNotice`).
Depois de clonar o repositório, rode `npm install && npm run build` (ou `npm run dev`) antes de abrir o site local.

## SEO, plugins e tradução

O conteúdo das páginas de modelo fica nos **campos ACF** e é renderizado em **PHP** pelos templates das seções
(o HTML completo sai do servidor — buscadores e redes sociais leem tudo sem JS). O editor da página fica vazio, e
os filtros do WordPress (`the_content`…) não passam por esse conteúdo. Por isso:

| Recurso / plugin | Situação | O que fazer |
|---|---|---|
| Plugin de cache de página (WP Rocket, LiteSpeed, W3TC) | ✅ funciona | o HTML é gerado inteiro no servidor |
| SEO — título, canonical, sitemap, robots | ✅ **Yoast SEO** (obrigatório) | as rotas são do WordPress; sem o Yoast ativo o painel avisa |
| SEO — meta description / Open Graph | ✅ pelo Yoast | o tema não imprime meta tags; descrição vazia no Yoast usa a do conteúdo, e o Yoast ganha a imagem do hero como og:image (`Core\Support\Seo`) |
| SEO — análise de conteúdo (legibilidade, palavra-chave) | ✅ com o texto salvo | o Yoast recebe o texto dos campos ao abrir o editor (mudanças entram depois de salvar e recarregar) |
| Busca nativa do WordPress (`?s=`) | ⚠️ não encontra texto dos campos ACF | Relevanssi ou SearchWP com indexação de campos personalizados |
| Tradução do conteúdo (WPML, Polylang) | ⚠️ configurar | traduzir os **campos ACF** (WPML: "ACF Multilingual"; Polylang Pro: sincronização de campos); as páginas de modelo funcionam por idioma |
| Tradução dos textos fixos (botão de menu, 404…) | ✅ text domain `upsites` | arquivos em `languages/upsites-{locale}.po/.mo` (Loco Translate ou Poedit), a partir do modelo `languages/upsites.pot` — gere/atualize com `npm run i18n:pot` (precisa do [WP-CLI](https://wp-cli.org/) no PATH). Nos templates: `esc_html_e('Texto', 'upsites')` |
| Lazy-load / troca de URL de imagem por CDN feita por plugin via `the_content` | ❌ não alcança | usar CDN por troca de domínio no servidor/DNS (offload de mídia) — as URLs vêm do ACF |
| Plugins que injetam algo no conteúdo (`the_content`: botões de compartilhar, anúncios, TOC) | ❌ não alcança | implementar como seção/componente do tema |
| Construtores de página (Elementor, Divi) | ❌ não use nas páginas de modelo · ⚠️ páginas comuns | o conteúdo das páginas de modelo é dos campos ACF. Em página sem modelo `fw:*`, use os modelos do próprio construtor ("Elementor Canvas"/"Full Width"): o `page.php` do tema põe título e `.container` em volta do conteúdo, e o reset do Tailwind pode alterar widgets |
| Editor dos posts (blocos ou Classic Editor) | ✅ com a fonte e as cores do site (paleta só no de blocos) | `theme.json` e `assets/css/editor.css` são gerados do `main.scss` no build |

**Status HTTP:** página de módulo sem `{slug}.view.php` responde **404**, e controller com erro responde **500**.
A página mostra a mensagem, mas o status é o real (sem "soft 404" indexado).

**Acessibilidade:** "Pular para o conteúdo" é o 1º item do Tab em toda página (`header.php`), e leva o foco ao `#conteudo`.

## Documentação do projeto

Ao final do projeto, o agente `bb8` gera em `docs/`, a partir do código:

| Arquivo | Para quem |
|---|---|
| `GUIA-DE-USO.md` | Cliente/editor: onde fica cada campo no painel e como preencher |
| `SITE.md` | Devs: como o site funciona — rotas, páginas, campos, layout, build e deploy |
| `CONTEXTO-IA.md` | IA de quem continua o projeto: estado, próximos passos, decisões e armadilhas (carregado sozinho via `CLAUDE.md`) |

O c3po atualiza o `CONTEXTO-IA.md` a cada página concluída. Fora do fluxo do c3po: peça "rode o bb8"
(ou `modo: contexto` para atualizar só o arquivo da IA, por exemplo antes de passar o projeto a outro dev).

## Animações de entrada

Com `animation: true` no `.claude/prompt.yaml`, os agentes seguem a skill `animation`: o setup liga
`add_filter('upwork/animation', '__return_true');` e as seções marcam títulos, textos, botões e cards com `data-reveal`
(sobem com fade em cascata ao entrar na tela; presets `fade`, `left`, `right`, `line`, `quick`).
Sem dependência (`upsites/core/js/reveal.js` + `src/sass/core/_reveal.scss`), respeita `prefers-reduced-motion` e não
afeta a medição de fidelidade. Sem o filtro, nada disso é carregado.

## WooCommerce

Projetos de loja seguem a skill `woocommerce` (`.claude/skills/woocommerce/`): produto, carrinho,
checkout e minha conta ficam nos templates PHP do WooCommerce (`woocommerce/` na raiz do tema);
home, landing pages e vitrines são módulos do tema (seções PHP). A seção 10 da skill traz a preparação do tema
(`@source`, estilos base, contador do carrinho com a Interactivity API via Store API).

## Fluxo longo com pausa por uso (c3po em loop)

Para o c3po construir o tema inteiro sem estourar a janela de 5 h do plano do Claude:

```bash
claude --agent c3po          # no terminal, na raiz do tema
/loop siga o .claude/prompt.yaml
```

- A status line do projeto (`.claude/settings.json`) mostra o uso da janela (`5h 72% · reseta 17:57`) e grava em
  `~/.claude/usage-limits.json`. Precisa de assinatura Pro/Max.
- Ao passar de **90%**, um hook avisa o agente; o c3po termina o item em andamento, anota onde parou no checklist e
  agenda o próximo ciclo para depois do reset. Ao acordar, confere `node .claude/scripts/usage-gate.mjs` e continua.
- Limite configurável: variável de ambiente `CLAUDE_USAGE_PAUSE_AT` (padrão 90).
- A sessão precisa ficar aberta, com a máquina ligada e sem prompt de permissão pendente — senão o loop para.

## Comandos

```bash
npm install && composer install
npm run dev                                  # gulp watch do CSS (scss + classes nos .php/.js) e do JS manual
npm run build                                # theme.json + gulp release (CSS e JS manual em versão aberta e .min)
npm run package                              # build + dist/{tema}.zip pronto para o servidor (assets/ não é versionado)
npm run size                                 # JS (gzip) por página: stores da Interactivity API + JS manual
npm run css                                  # só recompila o CSS aberto (src/sass/main.scss → assets/css/main.css)
npm run js                                   # só recompila o JS manual aberto (src/js → assets/js)
npm run make:module -- quem-somos "Quem somos" # novo módulo (+ seção hero e bloco de campos no acf_fields.php)
npm run make:section -- quem-somos about-us  # nova seção em template-parts/includes/quem-somos/about-us/ (--store: + .store.js)
npm run review                               # checagem de consistência dos módulos (base do agente r2d2)
npm run seed -- --apply                      # passa o conteúdo padrão (Figma) para o painel nas páginas já existentes
npm run fidelity -- home/hero --figma "<link do frame>" --reset   # % de fidelidade da seção ao Figma (t800) — render pelo WP local (?fw_preview=home/hero)
npm run fidelity -- --layout header --label menu --figma "<frame do menu aberto>" --click "[aria-controls=site-sidebar]" --reset   # clica antes de capturar (estado aberto; captura a tela)
npm run check                                # tudo antes de commitar: eslint + php -l + PHPCS + testes PHP + theme.json + review
npm run lint                                 # só o ESLint (stores .store.js, upsites/core/js, src/js)
npm run lint:php                             # só php -l em todo PHP do tema (sem o ACF embarcado)
npm run lint:wpcs                            # PHPCS com as regras de segurança e i18n do WordPress (precisa de `composer install` uma vez)
npm run test:php                             # testes das funções puras (SVG, #[Auth], #[Cache]) — upsites/core/tests/run.php
npm run i18n:pot                             # gera languages/upsites.pot (textos do tema para tradução) — precisa do WP-CLI (`wp`)
npm run acf:version                          # versão do ACF PRO embarcado
npm run acf:update -- ~/Downloads/advanced-custom-fields-pro.zip [--apply]   # atualiza o ACF embarcado
```

## Atualizar o ACF PRO embarcado

A cópia em `upsites/core/acf/` **não se atualiza sozinha** (o aviso de update fica desligado, porque não há licença
no tema). A cada release do ACF PRO — e sempre que sair uma correção de segurança — baixe o zip na conta ACF e rode
`npm run acf:update -- <zip>` (mostra a versão atual → nova) e depois com `--apply`. Em seguida `npm run check`,
abra uma página no wp-admin e confira abas, repeaters, clones e a página Opções do Tema antes de commitar.
Se o site tiver o ACF PRO instalado como plugin, é o plugin que roda (e se atualiza pelo painel).

## Módulos legados opcionais

- `upsites/post-views/` — contador de visualizações de post: desligado por padrão. Ligar: `add_filter('upwork/post_views', '__return_true');`
  Incremento atômico no banco; não conta usuários logados, prévias nem robôs comuns. Com cache de página, conta só
  as visitas que chegam ao PHP.
- O tema não carrega jQuery. Um script que precise dele deve ser enfileirado com `['jquery']` só na página que usa.
