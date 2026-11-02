<?php
/**
 * Lista de contatos — cards empilhados abaixo do hero (React: ListaContatosSection em
 * app/contato/contato.view.tsx; <section id="contatos"> — âncora dos menus de produção, por isso o
 * id difere do nome da pasta).
 * Dados: ContatoController → 'lista' (null = "Ocultar seção" → nem chega aqui).
 *
 * - cards vazio → a seção não aparece (como no React).
 * - tipo 'info' (React: ContatoCardInfo): título + descrição à esquerda e os itens (ícone + texto) à
 *   direita no desktop; empilhados no celular. texto do item vem de textarea: quebras de linha
 *   mantidas (whitespace-pre-line).
 * - tipo 'texto' (React: ContatoCardTexto): título + texto; com botão, o botão vai para a direita
 *   no desktop (alinhado ao fim do texto). Botão: link null → href "#" (como no React).
 * - Ícones: ImageData pelo fw_img — imagem com o alt do arquivo, como no React.
 *
 * @var array{
 *   cards: list<array{
 *     tipo: 'info',
 *     titulo: string|null,
 *     descricao: string|null,
 *     itens: list<array{
 *       icone: array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array}|null,
 *       texto: string|null,
 *     }>,
 *   }|array{
 *     tipo: 'texto',
 *     titulo: string|null,
 *     texto: string|null,
 *     botao: array{texto: string, link: string|null}|null,
 *   }>,
 * } $data
 */

defined('ABSPATH') || exit;

$cards = $data['cards'] ?? [];
if (!$cards) return;

$cardClass   = 'flex flex-col rounded-2xl border border-brand-gray-border bg-brand-light-purple p-6 sm:p-8 lg:p-12';
$tituloClass = 'font-heading text-h5 font-medium text-brand-purple-dark';
$textoClass  = 'font-sans text-body text-brand-gray-text';
?>
<section id="contatos" class="container pt-4 pb-12 md:pt-6 md:pb-16 lg:pt-8 lg:pb-20">
  <ul class="flex flex-col gap-4 md:gap-[22px]">
    <?php foreach ($cards as $card) : ?>
      <?php if ($card['tipo'] === 'info') : ?>
        <li class="<?= fw_cn($cardClass, 'gap-6 lg:flex-row lg:items-center lg:justify-between lg:gap-8') ?>">
          <div class="flex flex-col gap-3 lg:w-[431px] lg:shrink-0">
            <?php if ($card['titulo']) : ?>
              <h3 data-reveal class="<?= esc_attr($tituloClass) ?>"><?= esc_html($card['titulo']) ?></h3>
            <?php endif; ?>
            <?php if ($card['descricao']) : ?>
              <p data-reveal class="<?= esc_attr($textoClass) ?>"><?= esc_html($card['descricao']) ?></p>
            <?php endif; ?>
          </div>

          <?php if ($card['itens']) : ?>
            <ul class="flex flex-col gap-6 sm:flex-row sm:flex-wrap sm:gap-8 lg:w-[587px] lg:flex-nowrap lg:justify-between">
              <?php foreach ($card['itens'] as $item) : ?>
                <li class="flex flex-col items-center gap-4 text-center">
                  <?= fw_img($item['icone'], ['class' => 'size-[52px] shrink-0', 'data-reveal' => true]) ?>
                  <?php if ($item['texto']) : ?>
                    <span class="font-sans text-body whitespace-pre-line text-brand-purple-dark"><?= esc_html($item['texto']) ?></span>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </li>
      <?php elseif ($card['botao']) : ?>
        <li class="<?= fw_cn($cardClass, 'gap-6 lg:flex-row lg:items-end lg:justify-between') ?>">
          <div class="flex flex-col gap-4">
            <?php if ($card['titulo']) : ?>
              <h3 data-reveal class="<?= esc_attr($tituloClass) ?>"><?= esc_html($card['titulo']) ?></h3>
            <?php endif; ?>
            <?php if ($card['texto']) : ?>
              <p data-reveal class="<?= fw_cn($textoClass, 'lg:max-w-2xl') ?>"><?= esc_html($card['texto']) ?></p>
            <?php endif; ?>
          </div>
          <a <?= fw_attrs([
            'href'        => fw_url($card['botao']['link']) ?? '#',
            'data-reveal' => true,
            'class'       => 'inline-flex w-full shrink-0 items-center justify-center rounded-full bg-brand-purple px-6 py-4 font-sans text-body font-semibold text-white transition-opacity hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-purple lg:w-fit',
          ]) ?>><?= esc_html($card['botao']['texto']) ?></a>
        </li>
      <?php else : ?>
        <li class="<?= fw_cn($cardClass, 'gap-4') ?>">
          <?php if ($card['titulo']) : ?>
            <h3 data-reveal class="<?= esc_attr($tituloClass) ?>"><?= esc_html($card['titulo']) ?></h3>
          <?php endif; ?>
          <?php if ($card['texto']) : ?>
            <p data-reveal class="<?= fw_cn($textoClass, 'lg:max-w-5xl') ?>"><?= esc_html($card['texto']) ?></p>
          <?php endif; ?>
        </li>
      <?php endif; ?>
    <?php endforeach; ?>
  </ul>
</section>
