<?php
/**
 * Página comum · Conteúdo — título e o texto do editor, processado pelo the_content (blocos,
 * shortcodes, oEmbed, wpautop), com a tipografia de .entry-content (src/sass/main.scss, a
 * .post-content do React) (React: app/pagina-padrao/pagina-padrao.view.tsx).
 * Dados: PaginaPadraoController → 'conteudo'.
 *
 * - O the_content roda aqui, a cada página (não no controller, cujo retorno vai para o cache): o WP
 *   enfileira o CSS dos blocos usados e os shortcodes saem com os dados da requisição.
 * - Senha ainda não digitada: no lugar do conteúdo, o formulário de senha do WP (como o
 *   get_the_content() faz). O page.php e o single.php chamam esta seção direto nesse caso (o
 *   View::render nem chega ao controller) — o post com senha também fica só com o título e o
 *   formulário.
 * - A <section> fica sem o id "conteudo" que o React tinha: o #conteudo do tema é o wrapper do
 *   conteúdo da página (header.php, destino do "Pular para o conteúdo"), que começa no mesmo
 *   ponto — id repetido quebraria o atalho.
 * - Animação de entrada: título e cada bloco de 1º nível do conteúdo (título, parágrafo, lista,
 *   citação, imagem, separador) ganham data-reveal, como os alvos do useScrollReveal do React.
 *
 * @var array{titulo: string, id: int} $data
 */

defined('ABSPATH') || exit;

$alvo = get_post($data['id']);
if (!$alvo instanceof WP_Post) return;

// conferido aqui, na saída: sem a senha digitada, o conteúdo nunca sai (nem do cache do controller)
$senha = post_password_required($alvo);
$html  = '';

if (!$senha) {
  // filtros do the_content (oEmbed, blocos, galeria) leem o $post global: aponta para a página e
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

  // alvos do useScrollReveal do React; dentro de um alvo (ex.: <p> da citação) não anima de novo
  $alvos     = ['H1', 'H2', 'H3', 'H4', 'P', 'IMG', 'HR', 'UL', 'OL', 'BLOCKQUOTE', 'FORM'];
  $processor = trim($html) !== '' ? WP_HTML_Processor::create_fragment($html) : null;
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
}
?>
<section class="w-full">
  <div class="container pt-32 pb-16 md:pt-36 md:pb-20 lg:pt-40 lg:pb-24">
    <div class="mx-auto max-w-3xl">
      <?php if ($data['titulo'] !== '') : ?>
        <h1 data-reveal class="font-heading text-h2-mobile text-brand-purple-dark md:text-h2"><?= esc_html($data['titulo']) ?></h1>
      <?php endif; ?>

      <?php if ($senha) : ?>
        <?php // formulário do WP (texto, label + campo, botão; senha errada: aviso em role=alert) estilizado de fora ?>
        <div class="mt-8 font-sans text-body-lg text-brand-gray-text md:mt-10 [&_[role=alert]]:mb-6 [&_[role=alert]]:text-destructive [&_form>p+p]:mt-6 [&_input[type=password]]:w-full [&_input[type=password]]:rounded-full [&_input[type=password]]:border [&_input[type=password]]:border-brand-gray-border [&_input[type=password]]:px-5 [&_input[type=password]]:py-3 [&_input[type=password]]:text-body [&_input[type=password]]:text-brand-purple-dark [&_input[type=password]]:focus-visible:outline-2 [&_input[type=password]]:focus-visible:outline-brand-purple [&_input[type=submit]]:mt-4 [&_input[type=submit]]:cursor-pointer [&_input[type=submit]]:rounded-full [&_input[type=submit]]:bg-brand-purple [&_input[type=submit]]:px-6 [&_input[type=submit]]:py-3.5 [&_input[type=submit]]:text-body-sm [&_input[type=submit]]:font-semibold [&_input[type=submit]]:text-white [&_input[type=submit]]:hover:opacity-90 [&_label]:flex [&_label]:max-w-md [&_label]:flex-col [&_label]:gap-2 [&_label]:text-body [&_label]:text-brand-purple-dark">
          <?= get_the_password_form($alvo) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- formulário do próprio WP (filtro the_password_form) ?>
        </div>
      <?php else : ?>
        <div class="entry-content mt-8 md:mt-10">
          <?= $html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- conteúdo do editor já filtrado pelo the_content (kses ao salvar), como o the_content() ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
