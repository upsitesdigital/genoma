---
name: tailwind
description: Padrões Tailwind CSS v4 deste tema (seções em templates PHP em template-parts/includes/{slug}/{section-name}/{section-name}.php, renderizadas no servidor por Part::render) — tokens do tema, container, breakpoints, anatomia de seção, escala Figma px → classes, quando usar valor arbitrário, imagens (fw_img), botões, grids e acessibilidade. Use sempre que for criar ou editar markup de seção (PHP) com classes Tailwind, converter um frame do Figma em seção, ou revisar classes de um componente.
---

# Tailwind v4 neste tema

Este tema usa **Tailwind v4** compilado pelo **gulp** (Sass → PostCSS com
`@tailwindcss/postcss`) a partir de `src/sass/main.scss`, gerando
`assets/css/main.css` (aberto, usado no localhost) e `main.min.css` (compilado,
usado em homologação e produção — gerado pelo `npm run build`). Nenhum template
nem store importa CSS. **Não existe `tailwind.config.js`** —
tudo é configurado no próprio `main.scss`. Não crie arquivo de config nem
`@tailwind base` (sintaxe v3).

As diretivas do Tailwind (`@import 'tailwindcss'`, `@source`, `@theme`,
`@utility`, `@variant`, `@apply`) atravessam o Sass intactas; use comentário
`//` à vontade, mas **não** use variáveis/funções Sass dentro de `@theme` (o
Tailwind lê o CSS já compilado). Classe nova em `.php`/`.js` só aparece depois de
recompilar o CSS: `npm run dev` (gulp watch) faz isso sozinho; fora dele,
`npm run css`.

## Onde as classes são lidas

`main.scss` usa `@import 'tailwindcss' source(none)` + `@source` explícito para
`src/js/`, `template-pages/`, `template-parts/`, os `*.php` da raiz e
`upsites/core/Framework/`. Classe escrita fora desses lugares **não é gerada**.
O critical CSS da home (`src/sass/home-above-the-fold.scss`) só gera as classes dos
`@source` dele: hero novo, seção que aparece na primeira tela ou componente compartilhado
usado nelas (`shared/…`) → acrescente o `@source` lá também.
Nunca monte nomes de classe por pedaço (`class="bg-<?= $cor ?>"`, `'text-' . $cor`) —
o scanner só enxerga strings completas (o review dá aviso). Para variações, use mapa
de strings completas:

```php
<?php $variants = ['primary' => 'bg-primary text-primary-foreground', 'ghost' => 'bg-transparent text-foreground']; ?>
<div class="<?= fw_cn('rounded-md px-6 py-3', $variants[$data['estilo']] ?? $variants['primary']) ?>">
```

## Tokens do tema (use antes de qualquer hex)

Definidos em `:root` + `@theme inline` no `src/sass/main.scss`:

| Uso | Classe |
|---|---|
| Fundo da página / texto padrão | `bg-background` / `text-foreground` |
| Superfície de card, footer | `bg-card` / `text-card-foreground` |
| Fundo suave | `bg-muted` |
| Texto secundário | `text-muted-foreground` |
| Bordas | `border-border` |
| Cor de marca (vem de Configuração do Tema) | `bg-primary`, `text-primary`, `ring-primary/40` |
| Texto sobre primary | `text-primary-foreground` |
| Raios | `rounded-sm`, `rounded-md`, `rounded-lg`, `rounded-xl` (derivados de `--radius`) |

**Cor que se repete no design (2+ seções) e não é token** → adicione ao tema
em vez de repetir hex: declare `--nome: #hex;` em `:root` e
`--color-nome: var(--nome);` dentro de `@theme inline`. Aí `bg-nome`,
`text-nome`, `border-nome` passam a existir. Mesma lógica para fontes
(`--font-display: 'Nome', sans-serif;` → `font-display`).

**Cor de uso único** → valor arbitrário: `bg-[#0E3B5C]`, `text-[#5A6B7B]`.

## Container e largura

`container` é customizado (`@utility container` no `main.scss`): centralizado,
`max-width: 80rem` (1280px), `padding-inline` 1rem → 2rem a partir de `md`.

- Conteúdo de seção sempre dentro de `<div class="container">`.
- Fundo full-bleed: o fundo vai na `<section>`, o conteúdo no `container`.
- Largura de leitura de texto: `max-w-2xl` / `max-w-3xl` (não fixe em px).
- Se o Figma usar grid maior que 1280, use `max-w-[1440px]` no wrapper da
  seção específica — não altere o `container` global.

## Breakpoints (mobile-first, obrigatório cobrir os 4)

| Viewport | Prefixo | A partir de |
|---|---|---|
| Mobile | (nenhum) | 0 |
| Tablet | `md:` | 768px |
| Notebook | `lg:` | 1024px |
| Desktop | `xl:` / `2xl:` | 1280px / 1536px |

Escreva a classe base para **mobile** e sobrescreva para cima. Nunca use
`max-md:` para "desfazer" o desktop — sinal de que a base não é mobile.
Para componentes que mudam pelo tamanho do pai (cards em grids variados),
use container queries nativas: `@container` no pai, `@md:` / `@lg:` no filho.

## Anatomia de uma seção

```php
<?php
/**
 * Home · Exemplo. Dados: HomeController → 'exemplo'.
 *
 * @var array{eyebrow: string|null, titulo: string|null, texto: string|null, itens: list<array{titulo: string|null}>} $data
 */

defined('ABSPATH') || exit;
?>
<section id="exemplo" aria-labelledby="exemplo-titulo" class="bg-card py-16 md:py-20 lg:py-24">
  <div class="container">
    <header class="mx-auto max-w-2xl text-center">
      <?php if ($data['eyebrow']) : ?>
        <p class="text-sm font-semibold uppercase tracking-wider text-primary"><?= esc_html($data['eyebrow']) ?></p>
      <?php endif; ?>
      <h2 id="exemplo-titulo" class="mt-3 text-3xl font-bold tracking-tight md:text-4xl"><?= esc_html($data['titulo']) ?></h2>
      <p class="mt-4 text-lg text-muted-foreground"><?= esc_html($data['texto']) ?></p>
    </header>

    <?php if ($data['itens']) : ?>
      <ul class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($data['itens'] as $item) : ?>
          <li class="rounded-xl border border-border bg-background p-6">
            …
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</section>
```

Regras:
- **Um `<h1>` por página** (no hero). Seções usam `<h2>`, cards `<h3>`.
- Espaçamento vertical da seção com `py-*` responsivo (escala abaixo); não use
  `margin` entre seções.
- Espaço entre blocos internos: `mt-*` no bloco de baixo, ou `gap-*` no pai
  flex/grid. Prefira `gap` a margens em filhos.
- Listas de itens repetidos são `<ul>/<li>` com `foreach`.
- Toda saída de dado escapada (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post` ou
  helper `fw_*`) — ver skill `wordpress`.

## Escala Figma (px) → Tailwind

Tailwind v4: 1 unidade de espaçamento = 4px (`p-4` = 16px). Qualquer múltiplo
de 4 tem classe (`p-18` = 72px, `gap-15` = 60px) — **use a classe numérica,
não `p-[72px]`**.

| Figma | Classe |
|---|---|
| 4 / 8 / 12 / 16 / 20 / 24 | `1` / `2` / `3` / `4` / `5` / `6` |
| 32 / 40 / 48 / 56 / 64 | `8` / `10` / `12` / `14` / `16` |
| 80 / 96 / 120 / 128 | `20` / `24` / `30` / `32` |
| valor fora da grade de 4 (ex: 18, 30) | `[18px]` / `7.5` (30px = 7.5) |

Tipografia (font-size / line-height padrão do Tailwind):

| Figma | Classe |
|---|---|
| 12 / 14 / 16 / 18 / 20 | `text-xs` / `text-sm` / `text-base` / `text-lg` / `text-xl` |
| 24 / 30 / 36 / 48 / 60 / 72 | `text-2xl` / `text-3xl` / `text-4xl` / `text-5xl` / `text-6xl` / `text-7xl` |
| tamanho fora da escala (ex: 40, 52) | `text-[40px]` |
| line-height específico | `leading-[1.2]` ou `text-[40px]/[48px]` |
| peso 400/500/600/700/800 | `font-normal` / `font-medium` / `font-semibold` / `font-bold` / `font-extrabold` |
| letter-spacing | `tracking-tight` (-0.025em), `tracking-wide`, ou `tracking-[-0.02em]` |

Títulos escalam por viewport: o tamanho do Figma desktop vai em `lg:`/`xl:`;
mobile costuma ser ~60–70% (ex: 56px desktop → `text-4xl md:text-5xl xl:text-[56px]`).

## Sintaxe v4 que muda em relação ao v3

| v3 | v4 |
|---|---|
| `bg-gradient-to-r` | `bg-linear-to-r` (também `bg-radial`, `bg-conic`) |
| `shadow-sm` / `shadow` | `shadow-xs` / `shadow-sm` (escala deslocada) |
| `rounded-sm` / `rounded` | `rounded-xs` / `rounded-sm` |
| `blur-sm` / `blur` | `blur-xs` / `blur-sm` |
| `outline-none` | `outline-hidden` (`outline-none` agora zera de fato) |
| `!font-bold` | `font-bold!` (important no final) |
| `bg-opacity-50` | `bg-black/50` (modificador de opacidade) |
| `w-6 h-6` | `size-6` |
| `[--var]` em arbitrário | `bg-(--minha-var)` |

Bordas em v4 usam `currentColor` por padrão — este tema já aplica
`border-border` globalmente em `@layer base`, então `border` sozinho funciona.

## Imagens

```php
<?= fw_img($data['imagem'], ['class' => 'aspect-[4/3] w-full rounded-xl object-cover']) ?>
<?php // hero (LCP): sem lazy, com prioridade ?>
<?= fw_img($data['imagem'], ['fetchpriority' => 'high', 'loading' => false, 'class' => 'w-full rounded-xl object-cover']) ?>
<?php // fundo de tela cheia: tamanho 'full' (3º argumento) ?>
<?= fw_img($data['fundo'], ['alt' => '', 'class' => 'absolute inset-0 size-full object-cover'], 'full') ?>
```

- `fw_img($imagem, $attrs, $tamanho = 'large')` recebe o ImageData do controller; anexo
  da biblioteca sai pelo `wp_get_attachment_image()` — `width`/`height` (evita CLS),
  `alt`, `loading="lazy"`, `decoding="async"`, `srcset` e `sizes` do WP (passe
  `'sizes' => '(min-width: 1024px) 50vw, 100vw'` quando souber o espaço). Nunca monte
  `srcset` à mão nem escreva `<img>` para anexo; sem imagem devolve `''`. Use
  `object-cover` + `aspect-*` quando o recorte do Figma for fixo. `<img>` escrito à mão
  sem width/height/loading = aviso no review.
- `loading="lazy"` em tudo **exceto** a imagem do hero (`'fetchpriority' => 'high', 'loading' => false`).
- Imagem decorativa: `alt=""` + `'aria-hidden' => 'true'`.
- Fundo vindo de campo: `style="background-image: url('<?= esc_url($data['fundo']['src']) ?>')"` +
  `bg-cover bg-center`. Overlay: filho `absolute inset-0 bg-black/50`.
- Assets da seção: `template-parts/includes/{slug}/{section-name}/assets/` — o padrão
  do Figma entra pelo controller (`Fields::image`/`$this->picture()`, ver skill `acf`);
  asset fixo de layout: `esc_url(get_template_directory_uri() . '/template-parts/includes/{slug}/{section-name}/assets/x.svg')`.

## Botões e links

```php
<?php Part::render('shared/button', [
  'link'  => $data['botao'],
  'class' => 'rounded-md bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground hover:opacity-90',
]); ?>

<a <?= fw_attrs([
  'href'  => fw_url($item['url']),
  'class' => 'inline-flex items-center gap-2 rounded-md border border-primary px-6 py-3 text-sm font-semibold text-primary hover:bg-primary/10',
] + fw_target($item['target'])) ?>><?= esc_html($item['title']) ?></a>
```

- Botão editável (campo `link`) usa sempre `shared/button` (já tem `focus-visible:`,
  `rel` de nova aba e some sem link). Link montado à mão: `fw_attrs()` + `fw_url()` + `fw_target()`.
- Navegação é `<a href>` comum (carregamento de página normal — não há router no front). Ação é `<button type="button">`.
- Todo elemento interativo tem estado `hover:` e `focus-visible:`.
- Link com `target="_blank"` leva `rel="noopener"` (`fw_target()` já põe).
- Botão só com ícone leva `aria-label` (texto fixo com `esc_attr__('…', 'upsites')`).

## Layouts recorrentes

| Padrão | Classes |
|---|---|
| Texto + imagem lado a lado | `grid items-center gap-10 lg:grid-cols-2` (inverter: `lg:order-last` na imagem) |
| Grid de cards | `grid gap-6 sm:grid-cols-2 lg:grid-cols-3` (4 col: `xl:grid-cols-4`) |
| Logos / chips em linha | `flex flex-wrap items-center justify-center gap-x-10 gap-y-6` |
| Faixa com scroll horizontal no mobile | `flex snap-x snap-mandatory gap-4 overflow-x-auto md:grid md:grid-cols-3 md:overflow-visible` + `snap-start shrink-0 basis-4/5 md:basis-auto` nos filhos |
| Card clicável inteiro | `<a>` envolvendo o card com `group`, efeitos em `group-hover:` |
| Elemento decorativo sobreposto | pai `relative overflow-hidden`, filho `pointer-events-none absolute …` |

## `fw_cn()` para classes condicionais

```php
<div class="<?= fw_cn('rounded-xl p-6', ['bg-primary text-primary-foreground' => $item['destaque']], $data['class'] ?? '') ?>">
```

`fw_cn()` junta strings e `['classe' => bool]` (só entra com `true`) e devolve escapado
para atributo. **Não** faz merge de conflitos como o `tailwind-merge` — não passe duas
classes que disputam a mesma propriedade (`p-4` + `p-6`); escolha uma pela condição.
Estado que muda no navegador (aberto/ativo) usa `data-wp-class--{classe}="context.x"`
no store da Interactivity API (skill `wordpress`, "Interatividade").

## Estado de carregamento

O HTML sai pronto do servidor: seção **não tem skeleton**. Só conteúdo que um
store carrega depois (ex.: "carregar mais" via `api()`) pode mostrar blocos
`rounded-md bg-muted animate-pulse` enquanto espera.

## Proibido

- `style="…"` para o que existe como classe (só para valor dinâmico de campo).
- CSS/SCSS por seção — a seção é estilizada só com classes no `.php`;
  `src/sass/main.scss` guarda apenas tokens, base e utilitários globais.
- `@apply` em componentes; só é aceito no `@layer base` do `main.scss` (e no
  bloco `// WooCommerce`, em projeto de loja — ver skill `woocommerce`).
- `px` fixo em larguras de layout (`w-[620px]`) — use `max-w-*`, frações
  (`w-1/2`) ou grid.
- `!important` para vencer conflito — resolva escolhendo a classe com `fw_cn()`.
- Classe montada por pedaço (`bg-<?= $cor ?>`) — mapa de classes completas.
