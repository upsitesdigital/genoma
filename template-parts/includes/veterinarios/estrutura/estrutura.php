<?php
/**
 * Estrutura — título + texto, frase em destaque e grade com 2 fotos + card de contato (React:
 * EstruturaSection em app/veterinarios/veterinarios.view.tsx; <section id="estrutura">).
 * Dados: VeterinariosController → 'estrutura' (null = "Ocultar seção" → nem chega aqui).
 *
 * - titulo (<h2>): uma linha por quebra do textarea (React: titulo.split('\n') → <span class="block">;
 *   o texto vem com \r\n — preg_split('/\R/', …)).
 * - destaque: à direita e alinhado à direita do lg em diante; null → não aparece.
 * - Grade: imagem1, imagem2 e o card roxo de contatos (3 colunas no lg; no md as fotos lado a
 *   lado e o card embaixo, com a largura toda). Foto null ou contatos vazio → o item não aparece;
 *   sem nenhum dos três, a grade não aparece.
 * - contatos: ícone (null → só o texto) + texto com as quebras de linha mantidas (whitespace-pre-line).
 *
 * @var array{
 *   eyebrow: string|null,
 *   titulo: string|null,
 *   texto: string|null,
 *   destaque: string|null,
 *   imagem1: array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array}|null,
 *   imagem2: array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array}|null,
 *   contatos: list<array{
 *     icone: array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array}|null,
 *     texto: string|null,
 *   }>,
 * } $data
 */

defined('ABSPATH') || exit;

$fotoClass = 'h-64 w-full rounded-2xl object-cover lg:h-[374px]';
$fotoSizes = '(min-width: 1400px) 440px, (min-width: 1024px) 32vw, (min-width: 768px) 50vw, 100vw';
?>
<section id="estrutura" class="bg-background pt-12 pb-6 md:pt-20 md:pb-10 lg:pt-24 lg:pb-12 xl:pt-28 xl:pb-14">
  <div class="container">
    <div class="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between lg:gap-10">
      <div class="flex flex-col gap-6 lg:gap-8">
        <?php if ($data['eyebrow'] || $data['titulo']) : ?>
          <div class="flex flex-col gap-2">
            <?php if ($data['eyebrow']) : ?>
              <p data-reveal class="font-sans text-eyebrow text-brand-purple-accent uppercase"><?= esc_html($data['eyebrow']) ?></p>
            <?php endif; ?>
            <?php if ($data['titulo']) : ?>
              <h2 data-reveal class="font-heading text-h2-mobile text-brand-purple-dark md:text-h2">
                <?php foreach (preg_split('/\R/', $data['titulo']) ?: [] as $linha) : ?>
                  <span class="block"><?= esc_html($linha) ?></span>
                <?php endforeach; ?>
              </h2>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if ($data['texto']) : ?>
          <p data-reveal class="max-w-2xl font-sans text-body-lg leading-relaxed text-brand-gray-text lg:max-w-[754px]"><?= esc_html($data['texto']) ?></p>
        <?php endif; ?>
      </div>

      <?php if ($data['destaque']) : ?>
        <p data-reveal class="font-heading text-h4 text-brand-purple-dark lg:max-w-[268px] lg:text-right"><?= esc_html($data['destaque']) ?></p>
      <?php endif; ?>
    </div>

    <?php if ($data['imagem1'] || $data['imagem2'] || $data['contatos']) : ?>
      <div class="mt-10 grid grid-cols-1 gap-4 md:mt-14 md:grid-cols-2 md:gap-5 lg:grid-cols-3">
        <?= fw_img($data['imagem1'], ['data-reveal' => true, 'class' => $fotoClass, 'sizes' => $fotoSizes]) ?>
        <?= fw_img($data['imagem2'], ['data-reveal' => true, 'class' => $fotoClass, 'sizes' => $fotoSizes]) ?>

        <?php if ($data['contatos']) : ?>
          <ul data-reveal class="flex flex-col justify-center gap-6 rounded-2xl bg-brand-purple p-8 sm:p-10 md:col-span-2 lg:col-span-1 lg:h-[374px] lg:gap-11">
            <?php foreach ($data['contatos'] as $contato) : ?>
              <li class="flex items-center gap-6">
                <?= fw_img($contato['icone'], ['class' => 'h-10 w-10 shrink-0 sm:h-12 sm:w-12 lg:h-[52px] lg:w-[52px]']) ?>
                <?php if ($contato['texto']) : ?>
                  <p class="font-sans text-body whitespace-pre-line text-white"><?= esc_html($contato['texto']) ?></p>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
