---
name: wordpress
description: Arquitetura e convenções WordPress deste tema (UpWork Framework — rotas pelo WP/PHP, conteúdo renderizado no servidor em templates PHP com os dados do controller, interatividade pela Interactivity API do WP, dados da página no page() do controller em PHP, REST por attributes só para os stores). Use ao criar ou editar módulos, controllers, endpoints REST, CPTs/taxonomias, templates PHP, functions.php/upsites, ao revisar bugs do tema ou ao testar mudanças no site local do XAMPP.
---

# UpWork Framework — WP roteia, PHP renderiza, Interactivity API dá vida

## Forma do tema

| Pasta / arquivo | Papel |
|---|---|
| `functions.php` | autoload `Core\` → `upsites/core/`, `upsites/functions.php` (carrega o ACF embarcado de `upsites/core/acf/` se o plugin não estiver ativo), `Core\Framework\Bootstrap::init()` |
| `upsites/core/Framework/` | `Bootstrap`, `ModuleLoader`, `Rest` (rotas por attributes), `View` (render de módulo), `Part` (render de seção/header/footer/componente), `Preview` (render isolado para o fidelity), `RouteResolver`, `Controller`, `Module`, `Fields` (registro dos campos ACF), `Attributes/` |
| `upsites/core/Support/helpers.php` | helpers de template `fw_cn()`, `fw_attrs()`, `fw_url()`, `fw_target()`, `fw_img()`, `fw_lines()` (atalhos de `Core\Support\Html`, todos devolvem string escapada) |
| `upsites/core/js/` | script modules do framework: `api.js` (`upwork/api`, chamada à REST a partir de um store) e `reveal.js` (`upwork/reveal`, skill `animation`) |
| `upsites/core/Admin/` | Configuração do Tema (página de opções do ACF; campos em `Fields::option()` no `acf_fields.php`) e APIs de menu e nonce — nome do site e logo são os do WP (Personalizar → Identidade do site) |
| `template-pages/{slug}/` | **módulos** (uma página/rota cada) — ver anatomia abaixo |
| `upsites/customizer-register/inc/acf_fields.php` | **todos os campos ACF** do tema, um bloco por página, `Fields::section('{slug}', …)` por seção (`Core\Framework\Fields`) — ver skill `acf` |
| `upsites/customizer-register/inc/register_cpts.php` · `register_taxes.php` | **todos os CPTs** (`PostType::add()`) e **todas as taxonomias** (`Taxonomy::add()`) do tema — ver "CPTs e taxonomias" |
| `template-parts/app.php` | template das páginas `fw:{slug}` e da home → `View::render()` |
| `template-parts/*.php`, `index.php`, `page.php`, `single.php`, `404.php`, `comments.php` | hierarquia do WP — podem chamar `View::render()` ou ficar em PHP puro (o padrão já tem loop, paginação, comentários e 404; listas por `shared/post-list` + `shared/post-item`) |
| `template-parts/includes/` | **partes** renderizadas por `Part::render()`: seções `{slug}/{section-name}/`, `layout/header`, `layout/footer` e componentes `shared/{nome}` |
| `src/` | CSS do tema `sass/main.scss` (Tailwind + SCSS; `sass/core/_reveal.scss`) · JS manual `js/` · fontes locais `fonts/*.woff2` + a licença (`OFL.txt`) (copiadas para `assets/fonts/`; nunca Google Fonts — LGPD) |
| `assets/` | **não versionado** (`.gitignore`; deploy por `npm run package` ou pelo artefato da pipeline) — saída do build (gulp): `css/` (`main.css` + `.min`) · `js/` (JS manual aberto + `.min`) · `images/`, `fonts/` |
| `upsites/` | PHP de runtime: framework em `upsites/core/` (com o ACF PRO embarcado em `upsites/core/acf/`) + `customizer-register/` (campos ACF, CPTs, taxonomias) e `post-views/` (contador de visualizações, desligado por padrão: `add_filter('upwork/post_views', '__return_true')`). O tema não carrega jQuery. |

`upsites/core/` é o framework: **não altere** ao implementar seção/módulo. Única
exceção prevista: novo campo global em `upsites/core/Admin/ThemeOptions.php`.

## Roteamento — o WordPress decide, o PHP renderiza

Não há roteador no front: **toda navegação é carregamento de página normal** e
a hierarquia de templates do WP escolhe o arquivo PHP. O PHP só diz *qual
módulo* renderizar:

```php
Core\Framework\View::render(string $module, ?int $id = null, array $params = []);
```

`View::render` imprime `get_header()`, faz `require` de
`template-pages/{slug}/{slug}.view.php` com a variável `$data` (o array retornado
pelo controller) e depois `get_footer()`. O HTML sai **pronto do servidor** — não
há montagem em JS nem hidratação.
Os dados vêm do **`page(int $id, array $params)` do controller, chamado direto em PHP**
— sem REST. O `$id` vem do WordPress (o template já passou pela main query: rascunho,
privado e senha não digitada nem chegam aqui para o visitante) e os `$params` do
template; nada vem da URL. O `View::fetch` ainda confere a visibilidade do post (segunda
barreira), aplica o `#[Auth]` (sem acesso → 404) e o `#[Cache]` (transient `upwork_page_*`,
chave = classe + id + `$params`; só conteúdo público, sem usuário logado e fora da
pré-visualização). REST (`/wp-json/framework/v1/…`) **só** para o que um store do JS busca
depois (`api()`, ex.: paginação AJAX): métodos com `#[Get]`. Controller sem `page()` (formato
antigo, `#[Get('/{slug}/:id')]` + `index($request)`) não carrega mais: a página dá 404 e o
review acusa erro. View inexistente → 404 + parte `shared/not-found`; controller com erro
→ 404/500 e a mensagem "Erro ao carregar.". `View::data()`, `View::current()` e
`View::lcpImage()` expõem os dados da requisição (usados pelo `Seo`).

```php
// template-pages/home/home.view.php — só compõe as seções
<main>
  <?php Part::render('home/hero', $data['hero']); ?>
  <?php Part::render('home/about-us', $data['aboutUs']); ?>
</main>
```

`Part::render('{grupo}/{nome}', $data)` inclui
`template-parts/includes/{grupo}/{nome}/{nome}.php` com `$data` (array):

- `$data === null` → não renderiza nada (é assim que funciona o "Ocultar seção":
  `$this->section()` do controller devolve `null`);
- existe `{nome}.store.js` ao lado → enfileirado sozinho como script module
  (`upwork/{grupo}-{nome}`, deps `@wordpress/interactivity` + `upwork/api` dinâmico),
  só nas páginas que usam a parte;
- erro (`Throwable`) dentro da parte → só ela some, o detalhe vai para o
  `error_log`, o resto da página segue;
- no nível de fora roda `wp_interactivity_process_directives()`: as diretivas
  `data-wp-*` já saem do servidor com o estado inicial aplicado.
- `Part::html()` devolve a string em vez de imprimir.

Quem chama `View::render`:

| Contexto | Arquivo | Chamada |
|---|---|---|
| Página com modelo `fw:{slug}` | `template-parts/app.php` (automático, via `Bootstrap::catchAll`) | `View::render($slug, $pageId)` |
| Página inicial | idem | `View::render('home', $pageOnFront)` — o módulo `home` precisa existir |
| Post / CPT single | `single.php` / `single-{cpt}.php` | `View::render('single-post', get_the_ID())` |
| Arquivo, categoria, busca | `archive.php`, `category.php`, `search.php`… | `View::render('blog', null, ['page' => max(1, get_query_var('paged')), 'cat' => get_queried_object_id()])` |
| Sem módulo | qualquer template | PHP puro com `get_header()`/`get_footer()` — o header/footer aparece igual |

Sem módulo, o tema já cobre a hierarquia em PHP puro: `index.php` despacha para
`template-parts/archive.php`, `search.php`, `author.php` e `error.php`; `404.php`
usa `shared/not-found`; `single.php`/`page.php` têm loop com `post_class()`,
`.entry-content` (tipografia mínima do editor no `main.scss`), `wp_link_pages`,
link de editar e `comments_template()` condicional (`comments.php`). Listas de
posts: `Part::render('shared/post-list', ['empty' => …, 'searchForm' => …])` — roda o
loop principal com um `shared/post-item` por post e `the_posts_pagination()`.
Projeto com blog no layout do Figma troca por `View::render()` (tabela acima).

Módulos de contexto (single, arquivo) usam `template: false` no `#[Module]`
— não aparecem como modelo de página. O controller lê o contexto pelo `$id` e
pelos `$params` do `page()`, **nunca** por `get_queried_object()`/`is_single()`/
`get_query_var()` (a chave do `#[Cache]` é id + `$params`: o que vier da main query não
entra nela e a resposta errada seria servida do cache). Precisa de paginação/termo? O
template passa: `View::render('blog', null, ['page' => …, 'cat' => …])`.

Título da aba, meta tags e SEO são do WordPress (`title-tag`, plugins de
SEO) — os templates não alteram o `<title>`.

## Anatomia de um módulo

```
template-pages/{slug}/
├── {slug}.module.php      ← #[Module] (rota, modelo "Página · …") + boot() — sem campos, sem CPT/taxonomia
├── {slug}.controller.php  ← endpoints REST
└── {slug}.view.php        ← só compõe as seções: Part::render('{slug}/{section-name}', $data['{camel}']) (t800)

template-parts/includes/{slug}/{section-name}/   ← uma pasta por seção, section-name SEMPRE em inglês
├── {section-name}.php                  ← markup + Tailwind; recebe $data; PHPDoc @var array{…} $data no topo = contrato (t800/t1000)
├── {section-name}.store.js             ← opcional: estado/ações da Interactivity API (t800)
└── assets/

upsites/customizer-register/inc/acf_fields.php   ← campos ACF de TODAS as páginas:
    bloco /* ── {slug} — {Nome} ── */ com uma Fields::section('{slug}', …) por seção (t1000)
```

- Criar módulo: `npm run make:module -- {slug} "{Nome}"` (gera `module.php`,
  `controller.php`, `view.php` + a seção `hero` (`hero.php`) + o bloco da página no
  `acf_fields.php`, antes da linha `// ↑ novas páginas entram acima desta linha`).
- Criar seção: `npm run make:section -- {slug} {section-name} [--store]` (ex: `home about-us`;
  `--store` cria também o `{section-name}.store.js`); ele mostra a `Fields::section()` a
  acrescentar no bloco da página.
- Contrato de nomes: chave do controller em `camelCase` (`'contactForm'`) ↔
  `$data['contactForm']` no view ↔ pasta `contact-form` ↔ seção ACF `contact_form`.
- O `{slug}.module.php` só leva o comentário
  `// Campos ACF: upsites/customizer-register/inc/acf_fields.php`. `fields()`,
  `addSection()`, `iconField()`/`imageField()` existem no `Module` só como
  `@deprecated` (projetos antigos) — usá-los dá **erro** no `npm run review`.
- Seção é chamada pelo nome `{slug}/{section-name}` em `Part::render()` (caminho
  relativo a `template-parts/includes/`) — nada de `include`/`require` manual.
- `template-parts/` também guarda os templates PHP (`app.php`, `archive.php`…); as
  seções ficam todas em `includes/{slug}/`.
- Classes resolvidas pelo slug: `quem-somos` → `App\QuemSomos\QuemSomosModule`
  e `App\QuemSomos\QuemSomosController`. Namespace e nome **têm** que seguir
  isso ou o loader ignora o arquivo em silêncio.
- Todo arquivo PHP do módulo começa com `declare(strict_types=1);`.

### `#[Module]`

```php
#[Mod(slug: 'contato', name: 'Contato', route: '/contato', template: true, templateLabel: 'Página · Contato')]
```
`template: true` → aparece em Página → Modelo como `fw:{slug}`. Todo módulo
encontrado em `template-pages/` está ativo (não há tela de ativar/desativar);
o que se oculta no admin são **seções**, pelo toggle "Ocultar seção" de cada
grupo ACF (ver skill `acf`).

Páginas com modelo `fw:{slug}` e a página inicial abrem no painel **sem editor
de conteúdo e sem imagem destacada** (`Core\Admin\ModelScreen`): o conteúdo
vem só dos campos ACF. Não crie campo nem leia `the_content()`/
`get_the_post_thumbnail()` nessas páginas — imagem de seção é campo
`Fields::image()`. A imagem de compartilhamento (og:image) é do plugin de SEO.

### CPTs e taxonomias

Cada um no seu **arquivo único**, dentro da função que já roda no `init`:

```php
// upsites/customizer-register/inc/register_cpts.php — dentro de US_register_cpts()
PostType::add('servico', 'Serviço', 'Serviços', icon: 'dashicons-portfolio', supports: ['title', 'thumbnail']);
PostType::add('depoimento', 'Depoimento', 'Depoimentos', icon: 'dashicons-format-quote', public: false, supports: ['title']);

// upsites/customizer-register/inc/register_taxes.php — dentro de US_register_taxes()
Taxonomy::add('servico_categoria', 'Categorias', 'servico', hierarchical: true);
Taxonomy::add('servico_cor', 'Cores', 'servico', singular: 'Cor');
```

- `Core\PostTypes\PostType::add(slug, singular, plural, icon:, public:, showInRest:, supports:, rewrite:, args:)`
  e `Core\PostTypes\Taxonomy::add(slug, plural, postType, hierarchical:, showInRest:, public:, args:, singular:)`
  (`singular:` opcional — padrão: o plural sem o "s" final; passe quando não servir, ex. "Cores").
  Os rótulos do admin ("Adicionar %s", "Buscar %s"…) já passam pelo i18n (text domain `upsites`).
- `public: false` = **só no admin**: editável no painel, sem single/arquivo/URL
  no site (caso comum: itens de uma listagem sem página interna). Não use
  filtro `register_post_type_args`/`register_taxonomy_args` para isso.
- `args:` acrescenta/sobrescreve qualquer argumento do `register_post_type`/
  `register_taxonomy` (ex.: `args: ['menu_position' => 5]`).
- `has_archive` é sempre `false` — listagens são feitas pelo controller.
- Comente acima de cada `add()` o que o CPT é e onde aparece (listagem, single).
- Nunca registre CPT/taxonomia no módulo (`#[PostType]`/`#[Taxonomy]` são
  legado, `register_post_type()`/`register_taxonomy()` direto também) — o
  `npm run review` dá **erro**. Campos ACF do CPT: `Fields::postType()` (skill `acf`).

## Controller

```php
final class ListaDePostController extends Controller
{
    // dados da página: PHP direto (View::render), sem rota REST
    #[Cache(ttl: 300)]
    public function page(int $pageId, array $params = []): array { ... }

    // só o que um store busca depois ("carregar mais") vira rota REST
    #[Get('/lista-de-post/latest-posts/:id'), Cache(ttl: 300, vary: ['pagina'])]
    public function latestPosts(\WP_REST_Request $request): array { ... }
}
```

- **`page()`** recebe o `$pageId` pronto do WordPress (0 = sem página: padrões do
  Figma; na home, a página inicial) — sem `$request`, sem `pageId()`/`frontPageId()`.
  Toda página tem `page()`; a maioria dos módulos **não tem** rota REST.
- **Rota REST** só quando um store (`api('/…')`) precisa buscar dados depois do
  carregamento — o review dá **erro** em `api()` sem `#[Get]` que case a rota. As
  regras abaixo (`:id`, `vary`, `pageId($request)`) valem para esses métodos.
- Namespace REST fixo: `framework/v1`. `:param` = obrigatório, `:param?` =
  opcional no final; aceita `[a-zA-Z0-9-_]`. **`:id` aceita só dígitos e é sempre
  o ID de um post**: o `Rest` confere antes de chamar o controller — post
  inexistente, rascunho, privado, na lixeira ou com senha não digitada → **404**
  para o visitante (quem pode editar vê, para pré-visualizar). Só conteúdo
  público vai para o cache. Não use `:id` para outra coisa (ex.: id de termo →
  `:termo`).
- O `id` é conferido em **todas** as origens (caminho, `?id=` da query, corpo
  form/JSON): valores diferentes entre elas, não numéricos ou não canônicos
  (`01`, `001`) → **404**. Assim `/{slug}/{privado}?id={publicado}` não passa e
  variações do mesmo id não criam entradas de cache. Rota sem `:id` é tratada como
  pública e cacheada — rota REST sem id que precise da página inicial usa
  `$this->frontPageId()`, que só devolve a página inicial se ela for pública.
- Todo `.php` do tema tem a guarda `defined('ABSPATH') || exit;` (depois do
  `namespace`, que o PHP exige como primeira instrução) — o `make:module` já gera.
- URL vinda de campo vai para o template só por `Controller::link`/`linkFrom` (passa
  por `esc_url_raw`: `javascript:` some). URL de repeater/opção montada à mão:
  `esc_url_raw()`. HTML rico de campo: `wp_kses_post()` (`Bootstrap::kses()`). No
  template, link de dado usa `Part::render('shared/button', …)` ou
  `fw_attrs(['href' => fw_url($url)] + fw_target($target))` (nova aba ganha
  `rel="noopener noreferrer"`).
- **Escape no template (obrigatório):** texto `esc_html()`, atributo `esc_attr()`, URL
  `esc_url()`, HTML rico `wp_kses_post()`; os helpers `fw_*` já devolvem escapado.
  HTML confiável cru (SVG sanitizado, shortcode do CF7) leva comentário inline
  `// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- motivo`.
  `<?= $var ?>`/`echo $var` sem escape = **erro** no review.
- **SVG** (`Core\Support\Svg`): upload liberado só para quem tem `upload_files` (com o
  plugin Safe SVG e "Upload roles" marcado em Configurações → Mídia, só esses papéis), e
  sanitizado em todo caminho — formulário, sideload e qualquer anexo SVG criado
  (`add_attachment` reescreve o arquivo ou apaga o anexo inválido); por
  `wp_upload_bits` (XML-RPC, importadores) só passa SVG já sanitizado. Lista do que é
  permitido: só elementos de desenho; saem `<script>`, `foreignObject`, SMIL, HTML,
  comentários, `on*`, `data-*` (o SVG inline passa pela Interactivity API —
  `data-wp-*` viraria diretiva), `tabindex`/`ping`/`popover`…, atributos importantes
  com caixa diferente (`CLASS`, `ID`, `Style`); `<STYLE>`/`<title>`/`<desc>`
  normalizados e só com texto; `data:` só PNG/JPEG/GIF/WebP em `<image>`; `name`,
  `srcset`, `usemap`, `srcdoc`, `is` (só valem em HTML). **Só a raiz `<svg>` sem prefixo**
  e filhos no mesmo namespace dela, sem prefixo: `<s:svg xmlns:s=…>` embutido no HTML não
  abre o contexto SVG e os filhos virariam HTML (`<image>` → `<img>`).
  **CSS (`<style>` e `style=""`) por gramática fechada**, remontado do zero: seletor
  só de tipo/`.classe`/`#id` com espaço ou `>`, propriedades só de desenho (`fill`,
  `stroke*`, `opacity`, `color`, `stop-*`, `transform`, fonte do texto…) e valor só
  com números/cores/`url(#id)`/`rgb()`/`hsl()`/funções de transform — regra fora disso
  some (nada de `position`, `contain`, `var()`, `@regras`, `~`/`+`, aspas, `\`,
  parêntese ou colchete solto). **Todo `id` e toda `class` ganham o prefixo
  `fwi{hash}-`** (referências `#id`, `url(#id)`/`url('#id')`, aria-*by e seletores
  acompanham) — nunca colidem com um id da página (ex.: o
  `wp-script-module-data-@wordpress/interactivity` do runtime) nem com uma classe do
  tema (`fixed inset-0`). O wrapper do `shared/icon` tem `contain: paint`. O SVG é
  sanitizado de novo ao ser embutido (`Controller::icon`/`iconFrom`,
  `ThemeOptions::icon`) — CSS do tema não deve mirar `#id`/`.classe` interno de ícone
  (estilize pelo wrapper: `text-*` com `currentColor`).
- **Plugin Safe SVG** (recomendado junto): sanitiza de novo no upload, e o tema liga
  `safe_svg_remove_remote_references`. **Não substitui** o `Svg::sanitize` — ele pensa no SVG
  exibido como `<img>` e mantém `data-wp-*`, classes do tema, `position: fixed` e CSS
  global, que só importam no SVG embutido inline. Nunca desligue o sanitizador do tema.
- **Diretivas em conteúdo de usuário** (`Core\Support\Kses`): o kses do WP libera
  `data-*`, e o runtime da Interactivity API hidrata qualquer `[data-wp-interactive]`
  da página. Ao salvar post/resumo de quem não tem `unfiltered_html`, todo comentário
  e campo WYSIWYG do ACF salvo sem `unfiltered_html`: os atributos `data-wp-*` saem e
  qualquer `data-wp-` que sobrar no texto vira `data-wp_` (cobre `<math><textarea>`,
  que o tokenizer do WP lê como texto e o navegador como HTML). Conteúdo salvo antes
  da proteção só é limpo ao salvar de novo. Diretiva só em template do tema.
- Attributes de método: `#[Get]`, `#[Post]`, `#[Put]`, `#[Patch]`,
  `#[Delete]`, `#[Route(path, methods)]`, `#[Cache(ttl, vary)]`, `#[Auth(can)]`.
  Sem `#[Auth]` a rota é pública.
- `#[Auth('edit_posts')]` — **capability** do WP (nome de papel como `'editor'`
  também funciona: vira a capability mínima do papel, então papel acima passa).
  `#[Auth]` vazio = qualquer usuário logado.
- Retorne **array** (vira JSON). Exceção lançada vira `500 fw_controller_error`
  com mensagem **genérica** para quem chamou (a real só com `WP_DEBUG`); o detalhe
  (classe, mensagem, arquivo:linha) vai para o `error_log`.
- `#[Cache]` no `page()`: transient `upwork_page_*` (classe + id + `$params`). Numa rota
  REST: transient `upwork_rest_*`. A chave da rota REST usa **só** os parâmetros do
  caminho (`:id`, `:slug`) e os listados em `vary`, com o valor que o controller lê
  (`get_param` — chegam como **string**: converta com `(int)`/`absint` e valide). GET/HEAD
  descartam o corpo da requisição (`Rest::stripGetBody`, antes da validação): JSON enviado
  junto não muda a resposta nem a entrada do cache
  (`#[Cache(ttl: 300, vary: ['pagina', 'categoria'])]`) — o resto é ignorado, então
  `?x=1`, `?x=2` não criam entradas. Ponha em `vary` só parâmetros de poucos
  valores e **valide/limite** no controller (ex.: página ≤ máximo). Parâmetro que
  muda a resposta e não está em `vary` = resposta errada servida do cache.
- Rota com `#[Auth]` + `#[Cache]`: a chave inclui o usuário (nada vaza entre usuários).
- Invalidação: salvar/apagar página, post, CPT público ou anexo (menos revisão/autosave),
  atribuir termos, criar/editar/apagar termo, salvar as Opções do Tema ou o Customizer
  trocam a versão da chave — uma vez só, no fim da requisição (`Rest::flushCacheLater()` →
  `Rest::flushCache()`); funciona com Redis/Memcached; entradas antigas expiram pelo TTL.
  Deploy que muda um controller também não serve o cache antigo (data do arquivo na chave).
- Em método REST, o id do post: sempre `$this->pageId($request)` (lê só o `:id` do caminho — **nunca** `get_param('id')`, que aceita `?id=` da query; o review acusa). Rota REST
  sem id que precise da home: `$this->pageId($request) ?: $this->frontPageId()` (só devolve a página inicial se ela for pública — nunca `get_option('page_on_front')` direto).
- Listagens de CPT: `WP_Query` com `posts_per_page` limitado,
  `no_found_rows => true` quando não pagina, `fields => 'ids'` se só precisa
  do id. Nunca `posts_per_page => -1` sem limite conhecido.
- Escrita (`#[Post]` etc.): valide e sanitize cada campo
  (`sanitize_text_field`, `sanitize_email`, `absint`…); o nonce `wp_rest`
  vem no header `X-WP-Nonce` (o `api()` de `upwork/api` já envia e, se vencer, renova e
  repete sozinho — num store use sempre `import { api } from 'upwork/api'`, nunca
  `fetch` direto na REST).
- Mantenha o retorno **idêntico** ao `@var array{…} $data` do PHPDoc de cada seção
  (`{section-name}.php`) — mudou um, mude o outro.

## SEO, status e tradução (já automáticos — não refaça)

- **Status HTTP:** `View::render` responde 404 se o `{slug}.view.php` não existe e
  404/500 se o controller falhou (a página mostra "Erro ao carregar."; o status é o real).
- **SEO: sempre pelo Yoast SEO** (title, description, Open Graph, schema, sitemap). O tema
  não imprime meta tags próprias nem suporta outro plugin de SEO; sem o Yoast ativo, o painel
  mostra um aviso. `Core\Support\Seo` só entrega ao Yoast o que está nos campos ACF:
  descrição em branco → a do conteúdo (1º texto de subtítulo/texto da 1ª seção), imagem do
  hero no og:image e o texto dos campos na análise de conteúdo do editor (`page()` via
  `View::fetch`). Não adicione `<meta>` de SEO em template nem no `wp_head` do tema.
- **Tradução:** text domain **`upsites`** (arquivos em `languages/`). Todo texto de
  interface fixo nos templates usa o i18n normal do WP: `esc_html_e('Ler mais', 'upsites')`,
  `esc_attr__('Menu', 'upsites')`, `esc_html__()`. Não há mais tradução no JS.
  Conteúdo editável (ACF) é traduzido por plugin (WPML/Polylang), não por aqui.
- **Plugins que filtram `the_content`** (CDN, lazy-load, compartilhar, TOC) não alcançam o
  conteúdo dos módulos; busca nativa não indexa ACF (use Relevanssi/SearchWP). Tabela
  completa no README, "SEO, plugins e tradução".

## Endpoints do próprio framework (não recrie)

O framework não registra rota REST própria: as rotas em `/wp-json/framework/v1/` são só as dos
métodos `#[Get]`/`#[Post]`… dos controllers (o que um store busca). Menus: no PHP,
`Core\Admin\MenuApi::tree('{location}')`.

| Endpoint | Uso |
|---|---|
| `admin-ajax.php?action=upwork_rest_nonce` | renova o nonce vencido (usado pelo `api()`; não é REST: a REST sem nonce não reconhece o usuário) |

Formulários são do **Contact Form 7** (o envio usa a REST do próprio CF7) —
não crie endpoint de formulário. Ver skill `acf`, "Formulários".

## Comandos e verificação

```bash
php -l template-pages/{slug}/{slug}.controller.php   # sintaxe (rápido, rode sempre)
php -l upsites/customizer-register/inc/acf_fields.php  # idem, se mexeu em campos
php -l template-parts/includes/{slug}/{section}/{section}.php   # template da seção
npm run review                                         # consistência acf_fields/module/controller/view/seções/stores
npm run check                                          # eslint + php -l + PHPCS + testes PHP + theme.json + review
npm run build                                          # só o orquestrador/usuário
```

Testar o endpoint com o tema ativo num site local:
```bash
curl -s "{url-do-site}/wp-json/framework/v1/{slug}/{pageId}"
```
- URL do site local: `projeto.link admin` do `.claude/prompt.yaml` (sem o
  `/wp-login.php`). Se não houver, pergunte. **Não troque o tema ativo nem
  altere o banco de um site sem o usuário pedir.**
- 404 em `wp-json/framework/v1/...` = classe com nome errado (o loader
  ignora em silêncio), permalinks precisando ser salvos, ou `fw_not_found`: o
  `:id` não é de um post publicado (rascunho/privado/senha — teste logado).
- PHP 8.2 local (attributes, `readonly`, `match`, `str_contains` ok). WordPress
  **6.5+** (Interactivity API e script modules).

## Interatividade — Interactivity API

Só a parte que precisa de comportamento no navegador (abas, acordeão, slider, menu,
"ver mais", filtros) ganha um `{nome}.store.js` — módulo ES puro, sem build e sem
TypeScript. Exemplo de referência: `template-parts/includes/layout/header/header.php` +
`header.store.js` (menu mobile).

```php
<section id="faq" data-wp-interactive="upwork/home-faq">
  <?php foreach ($data['itens'] as $item) : ?>
    <div <?= wp_interactivity_data_wp_context(['open' => false]) ?>>
      <button type="button" aria-expanded="false" data-wp-bind--aria-expanded="context.open" data-wp-on--click="actions.toggle"><?= esc_html($item['pergunta']) ?></button>
      <div hidden data-wp-bind--hidden="!context.open"><?= wp_kses_post($item['resposta']) ?></div>
    </div>
  <?php endforeach; ?>
</section>
```

```js
// faq.store.js
import { store, getContext } from '@wordpress/interactivity'

store('upwork/home-faq', {
  actions: {
    toggle() {
      const ctx = getContext()
      ctx.open = !ctx.open
    },
  },
})
```

- Namespace do store = `data-wp-interactive` do template (`upwork/{grupo}-{nome}`); o
  review dá erro se não baterem ou se houver `data-wp-*` sem `data-wp-interactive`.
- Estado por instância: `wp_interactivity_data_wp_context([...])`; estado global:
  `wp_interactivity_state('upwork/…', [...])` no PHP. Diretivas: `data-wp-on--click`,
  `data-wp-bind--hidden`, `data-wp-bind--aria-expanded`, `data-wp-class--{classe}`,
  `data-wp-text`, `data-wp-each`, `data-wp-on-document--keydown`…
- REST a partir do store: `import { api } from 'upwork/api'` (config de
  `wp_interactivity_config('upwork', {apiBase, nonce, ajaxUrl})`, em `Bootstrap::interactivity()`).
- Sem jQuery (o review dá erro) e sem `innerHTML` — o DOM muda pelas diretivas.

## CSS e JS do tema — gulp e ambientes

- **Tailwind é a base** do CSS: `src/sass/main.scss` começa com o Tailwind
  (tokens, base, utilitários). Ajustes manuais do DEV entram em SCSS no mesmo
  arquivo (ou parciais importados nele), **depois** do Tailwind.
- **JS manual** (adições pontuais) fica em `src/js/` e é empacotado
  pelo webpack via gulp. O JS das seções são os stores da Interactivity API
  (`{nome}.store.js`, servidos como estão, sem build).
- O **gulp compila tudo no final**: `npm run build` → `gulp release` gera
  cada asset em duas versões: aberta (`main.css`, `mainJS.js`) e compilada
  (`main.min.css`, `mainJS.min.js`). Em dev, `npm run dev` recompila o
  aberto a cada mudança.
- **Qual arquivo o WP carrega** (`CoreSupportAsset::isLocal()`):
  localhost / 127.0.0.1 / `*.local` / `*.test` / `WP_ENVIRONMENT_TYPE=local`
  → **aberto**; homologação e produção → **sempre o `.min`**, sem fallback
  (`.min` ausente não carrega nada e registra no `error_log` — faltou rodar o
  build). Para testar o `.min` no localhost: `define('FW_MINIFIED_ASSETS', true)`.
- Enfileire assets do tema sempre com `Asset::style($handle, 'nome')` /
  `Asset::script($handle, 'nome', $deps)` — nunca com URL fixa nem
  `strpos(get_bloginfo('url'), 'localhost')`.

## Legado que ainda existe (não confundir)

- `upsites/customizer-register/`: `inc/acf_fields.php` (campos ACF),
  `inc/register_cpts.php` (CPTs) e `inc/register_taxes.php` (taxonomias) são
  **os** arquivos do tema para isso (ver acima) — a pasta não tem mais nada do
  Customizer do tema clássico.
- **Header e footer são partes** (`template-parts/includes/layout/header/header.php` +
  `header.store.js` / `template-parts/includes/layout/footer/footer.php`), em **todas** as
  páginas: o `header.php`/`footer.php` da raiz só abrem/fecham a página e chamam
  `Part::render('layout/header')` / `Part::render('layout/footer')` dentro de `#fw-header` e
  `#fw-footer`. Não monte header/footer na raiz — edite as partes. Elas montam os
  próprios dados (`MenuApi::tree('primary')`, logo por `get_theme_mod('custom_logo')`, texto
  do rodapé por `ThemeOptions::text('rodape_texto', …)`).
  Estrutura: `div.flex.min-h-screen.flex-col > #fw-header + div.flex-1 (conteúdo) + #fw-footer`.
  Layout que depende dos vizinhos do header (ex: barra lateral fixa que recua o
  resto) mira `#fw-header ~ *`, nunca os irmãos do `<header>` (ele não tem).
  Âncoras: o `src/js/_header-offset.js` (mainJS) põe a altura do `#fw-header` sticky em
  `--fw-header-h` (0 se ele não gruda, ex.: `lg:static`) e o `main.scss` usa no
  `scroll-padding-top` do `<html>` (+ barra de admin) — âncora não para por baixo do header.
  Mantenha o id `fw-header` no wrapper do header.
- Menus de `MenuApi::tree()` já vêm com `current` (item da página atual) e
  `ancestor` (pai/ancestral) calculados pelo WordPress, como no `wp_nav_menu()`
  — valem também em posts, arquivos e páginas PHP. Use esses campos para o
  item ativo; não compare URLs.
