---
name: animation
description: Animações de entrada ao rolar do UpWork Framework (estilo ScrollReveal — sobe com fade, cascata entre elementos, linhas que crescem), via data-reveal no markup PHP e o script module upwork/reveal (upsites/core/js/reveal.js), ligado por add_filter('upwork/animation', '__return_true'), sem jQuery nem biblioteca. Use SOMENTE quando o prompt.yaml tiver `animation: true` (o c3po avisa na chamada) — em projeto sem animação, não carregue nem aplique. Cobre o setup, os presets, onde marcar em cada tipo de seção, o que nunca animar, desempenho e acessibilidade.
---

# Animações de entrada (`animation: true`)

**Só se aplica quando `projeto.animation: true` no `.claude/prompt.yaml`.** Em
projeto sem animação: nada de `data-reveal`, nada do filtro `upwork/animation`.

Referência de comportamento: elementos sobem 50px com fade ao entrar na tela,
em cascata (100–150ms entre irmãos), uma vez só, curva
`cubic-bezier(0.5, 0, 0, 1)`, 1s. Divisores crescem da esquerda.

## Setup (uma vez, no `setup` do c3po — t800)

1. Em `upsites/functions.php`:
   ```php
   add_filter('upwork/animation', '__return_true');
   ```
2. Pronto: com o filtro, `Bootstrap::interactivity()` enfileira o script module
   `upwork/reveal` (`upsites/core/js/reveal.js`) e um script no `<head>` põe a classe
   `.fw-reveal` no `<html>` antes de pintar (evita o conteúdo aparecer e sumir). Os
   estados visuais estão em `src/sass/core/_reveal.scss` (já importado pelo `main.scss`).
   Sem o filtro, `data-reveal` é inerte (tudo visível).

## Presets — `data-reveal="{preset}"`

| Preset | Efeito | Cascata | Use em |
|---|---|---|---|
| (vazio) / `up` | sobe 50px + fade, 1s | 100ms | títulos, eyebrow, parágrafos, botões, cards, itens de lista |
| `fade` | só fade | 150ms | imagens grandes, fundos decorativos, mapas, grades inteiras |
| `left` / `right` | entra 50px pela esquerda / direita | 150ms | coluna de imagem ao lado do texto (imagem à direita → `right`) |
| `line` | divisor cresce da esquerda, 2s | 150ms | `<hr>`/traços decorativos |
| `quick` | sobe, 600ms, quase sem cascata | 10ms | listas longas dentro de seção (logos, links) |

`data-reveal-delay="200"` soma atraso (ms) quando a ordem do DOM não dá o
efeito certo (ex.: imagem que deve vir depois do texto ao lado).

## Onde marcar (t800, no `{section-name}.php`)

Marque **os filhos**, não a `<section>`: a cascata vem dos irmãos que entram
juntos. No PHP é atributo booleano (`data-reveal`) ou com valor (`data-reveal="fade"`);
em `fw_attrs()`/`fw_img()`: `'data-reveal' => true` ou `'data-reveal' => 'right'`.

```php
<section id="about-us" class="py-20">
  <div class="container grid gap-10 lg:grid-cols-2">
    <div>
      <?php if ($data['eyebrow']) : ?><span data-reveal class="…"><?= esc_html($data['eyebrow']) ?></span><?php endif; ?>
      <?php if ($data['titulo']) : ?><h2 data-reveal class="…"><?= esc_html($data['titulo']) ?></h2><?php endif; ?>
      <?php if ($data['texto']) : ?><p data-reveal class="…"><?= esc_html($data['texto']) ?></p><?php endif; ?>
      <?php if ($data['botao']) : ?>
        <div data-reveal><?php Part::render('shared/button', ['link' => $data['botao'], 'class' => '…']); ?></div>
      <?php endif; ?>
    </div>
    <?= fw_img($data['imagem'], ['data-reveal' => 'right', 'class' => '…']) ?>
  </div>
  <ul class="grid gap-6 md:grid-cols-3">
    <?php foreach ($data['itens'] as $item) : ?><li data-reveal>…</li><?php endforeach; ?>
  </ul>
</section>
```

- **Hero** (primeira dobra): título, texto e botão com `data-hero-reveal="{posição}"`
  (0, 1, 2… na ordem do DOM — no PHP, um contador: `fw_attrs(['data-hero-reveal' => $entrada++])`;
  em carrossel, só no 1º slide). É o mesmo movimento do preset padrão, mas só em CSS
  (`_reveal.scss`): começa ao pintar a página, sem esperar o `reveal.js` — com `data-reveal`
  o texto do hero ficava invisível até o JS carregar e atrasava o LCP. Imagem de fundo **sem** animação.
- **Listas/cards/grades**: cada `li`/card com `data-reveal` → cascata por linha.
  Listas longas (logos, links) com `quick`.
- **Duas colunas texto + imagem**: textos `up`, imagem `left`/`right` do lado em que está.
- **Divisores**: `line`. **CTA**: título, texto e botão `up`; fundo nunca.
- **Header e footer: nunca** (nem o menu, nem as colunas do rodapé). O `reveal.js`
  ignora qualquer `data-reveal` dentro de `#fw-header`/`#fw-footer`.
- Pode marcar direto elementos com `transition` próprio (botão com hover,
  card): quando a entrada termina, o `reveal.js` remove o `data-reveal` e o
  `transition`/`opacity`/`transform` do elemento voltam. Componente
  compartilhado que não recebe atributos (`shared/button`, `shared/icon`…): envolva
  (`<div data-reveal><?php Part::render('shared/button', …); ?></div>`) — não marque
  dentro do componente e no uso ao mesmo tempo.
- Conteúdo que a Interactivity API insere depois (abas, "ver mais", `data-wp-each`) é
  observado sozinho pelo `reveal.js` (MutationObserver).

## Nunca anime

- o header e o footer do site (`template-parts/includes/layout/`), overlays, modais, dropdowns, tooltips, abas e acordeões
  (conteúdo que abre por clique já tem a própria transição);
- campos de formulário (CF7) e mensagens de erro/sucesso;
- a imagem de fundo do hero/CTA e qualquer elemento que seja o **LCP** com
  atraso — o LCP só conta quando fica visível: no hero use `data-hero-reveal` (nunca
  `data-reveal`, que espera o JS) e sem `data-reveal-delay`;
- um wrapper e os filhos ao mesmo tempo (anima duas vezes);
- elementos `position: fixed/sticky`.

## Desempenho e acessibilidade

- Só `opacity` e `transform` (GPU) — nada de animar `height`, `top`, `margin`.
  O espaço do elemento existe desde o início: **sem layout shift**.
- `prefers-reduced-motion: reduce` → tudo aparece sem movimento (o `.fw-reveal`
  nem é aplicado). Sem JS → tudo visível (o estado escondido depende da classe `.fw-reveal`;
  se o módulo não carregar em 3s, o script do `<head>` a remove).
- `left`/`right` deslocam 50px na horizontal antes de entrar: com a animação ligada o
  `body` recebe `overflow-x: clip` (`_reveal.scss`), então não há rolagem horizontal no
  celular. `clip` (não `hidden`) não cria contêiner de rolagem nem quebra `position: sticky`
  — não troque por `overflow-x: hidden` nem repita a regra na seção.
- A medição de fidelidade (`npm run fidelity`) roda com reduced-motion: mede o
  estado final, a animação não afeta a nota.
- Não instale GSAP/AOS/ScrollReveal/framer-motion para entrada — o
  `reveal.js` já cobre. Animação específica pedida no prompt (contador, parallax,
  carrossel) é outra coisa: faça na seção (`{section-name}.store.js`), com o mesmo
  cuidado (reduced-motion).

## Verificação

- `php -l` no template; no navegador (skill `browser-harness`, se houver): rolar a
  página e conferir que todo `[data-reveal]` ganha `.is-revealed`, sem elemento
  preso invisível (ex.: dentro de `overflow:hidden` com altura zero) e sem
  `data-reveal` em header/modal/formulário.
- r2d2 (com `animation: true`): `add_filter('upwork/animation', '__return_true')` em
  `upsites/functions.php`, presets válidos, nada da lista "Nunca anime", hero sem atraso extra.
