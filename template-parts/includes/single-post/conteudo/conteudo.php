<?php
/**
 * Post · Conteúdo — o texto do editor, processado pelo the_content (blocos, shortcodes, oEmbed,
 * wpautop), com a tipografia de .entry-content (src/sass/main.scss, a .post-content do React)
 * (React: ConteudoSection em app/single-post/single-post.view.tsx).
 * Dados: SinglePostController → 'conteudo' (null = post sem conteúdo → nem chega aqui).
 *
 * - O the_content roda aqui, a cada página (não no controller, cujo retorno vai para o cache): o WP
 *   enfileira o CSS dos blocos usados e os shortcodes saem com os dados da requisição.
 * - A <section> fica sem o id "conteudo" que o React tinha: o #conteudo do tema é o wrapper do
 *   conteúdo da página (header.php, destino do "Pular para o conteúdo") — id repetido quebraria o
 *   atalho.
 * - Animação de entrada: cada bloco de 1º nível (título, parágrafo, lista, citação, imagem,
 *   separador) ganha data-reveal, como os alvos do useScrollReveal do React.
 *
 * @var array{id: int} $data
 */

defined('ABSPATH') || exit;

$alvo = get_post($data['id']);
// post com senha nunca chega aqui (o View::render mostra só o formulário); conferido de novo
if (!$alvo instanceof WP_Post || post_password_required($alvo)) return;

// filtros do the_content (oEmbed, blocos, galeria) leem o $post global: aponta para o post e
// devolve o anterior no fim (como o controller do React)
global $post;
$anterior = $post;
$post     = $alvo; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- contexto do the_content, restaurado abaixo
setup_postdata($post);

$html = (string) apply_filters('the_content', $alvo->post_content); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- filtro do core

if ($anterior instanceof WP_Post) {
  $post = $anterior; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restaura o global
  setup_postdata($post);
} else {
  wp_reset_postdata();
}

if (trim($html) === '') return;

// alvos do useScrollReveal do React; dentro de um alvo (ex.: <p> da citação) não anima de novo
$alvos     = ['H1', 'H2', 'H3', 'H4', 'P', 'IMG', 'HR', 'UL', 'OL', 'BLOCKQUOTE', 'FORM'];
$processor = WP_HTML_Processor::create_fragment($html);
if ($processor) {
  while ($processor->next_tag()) {
    if (!in_array($processor->get_tag(), $alvos, true)) continue;
    // breadcrumbs = HTML, BODY, ancestrais…, a própria tag
    if (array_intersect(array_slice($processor->get_breadcrumbs(), 2, -1), $alvos)) continue;
    $processor->set_attribute('data-reveal', true);
  }
  // markup que o processador não suporta: o conteúdo sai como veio, sem a animação
  if ($processor->get_last_error() === null) $html = $processor->get_updated_html();
}
?>
<section class="w-full">
  <div class="container pb-16 md:pb-20 lg:pb-24">
    <div class="entry-content mx-auto max-w-3xl">
      <?= $html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- conteúdo do editor já filtrado pelo the_content (kses ao salvar), como o the_content() ?>
    </div>
  </div>
</section>
