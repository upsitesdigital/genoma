<?php
/**
 * Praticidade — card roxo "Coleta de Amostras": texto, quadro de horários e foto (React:
 * PraticidadeSection em app/veterinarios/veterinarios.view.tsx; <section id="praticidade">).
 * Dados: VeterinariosController → 'praticidade' (null = "Ocultar seção" → nem chega aqui).
 *
 * - texto: textarea com as quebras de linha mantidas (whitespace-pre-line, como no React).
 * - horarios vazio → o quadro de horários (com o ícone) não aparece; icone null → só os textos.
 * - imagem: no celular/tablet abaixo do texto, encostada na base do card (sem padding embaixo); do lg
 *   em diante solta à direita (absolute), sobre o painel roxo-escuro decorativo (22% da largura, não
 *   é campo), também encostada na base. Sem imagem, o card tem padding embaixo.
 *
 * @var array{
 *   eyebrow: string|null,
 *   titulo: string|null,
 *   texto: string|null,
 *   icone: array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array}|null,
 *   horarios: list<string>,
 *   imagem: array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array}|null,
 * } $data
 */

defined('ABSPATH') || exit;
?>
<section id="praticidade" class="bg-background py-12 md:py-20 lg:py-24 xl:py-28">
  <div class="container">
    <div class="relative overflow-hidden rounded-2xl bg-brand-purple">
      <?php // Painel decorativo mais escuro atrás da imagem (só do lg em diante) ?>
      <div aria-hidden="true" class="absolute inset-y-0 right-0 hidden w-[22%] rounded-r-2xl bg-brand-purple-dark lg:block"></div>

      <?php // com foto, sem padding embaixo no celular/tablet: a foto encosta na base do card (no lg ela é absolute) ?>
      <div class="<?= fw_cn('relative z-10 flex flex-col gap-8 px-8 pt-8 sm:px-10 sm:pt-10 md:px-12 md:pt-12 lg:flex-row lg:items-center lg:gap-10 lg:p-14 xl:gap-16 xl:p-16', ['pb-8 sm:pb-10 md:pb-12' => !$data['imagem']]) ?>">
        <div class="flex flex-col gap-8 lg:max-w-[552px] lg:shrink-0 lg:gap-[46px]">
          <div class="flex flex-col gap-4">
            <?php if ($data['eyebrow'] || $data['titulo']) : ?>
              <div class="flex flex-col gap-2">
                <?php if ($data['eyebrow']) : ?>
                  <p data-reveal class="font-sans text-body text-white"><?= esc_html($data['eyebrow']) ?></p>
                <?php endif; ?>
                <?php if ($data['titulo']) : ?>
                  <h2 data-reveal class="font-heading text-h3 text-white md:text-h2"><?= esc_html($data['titulo']) ?></h2>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <?php if ($data['texto']) : ?>
              <p data-reveal class="font-sans text-body-lg leading-relaxed whitespace-pre-line text-white/70"><?= esc_html($data['texto']) ?></p>
            <?php endif; ?>
          </div>

          <?php if ($data['horarios']) : ?>
            <div data-reveal class="flex items-center gap-4 rounded-2xl bg-white/10 p-6 sm:gap-6 sm:p-7">
              <?= fw_img($data['icone'], ['class' => 'h-10 w-10 shrink-0 sm:h-12 sm:w-12 lg:h-[58px] lg:w-[58px]']) ?>
              <ul class="flex flex-col gap-1">
                <?php foreach ($data['horarios'] as $linha) : ?>
                  <li class="font-heading text-h5 text-white md:text-h4"><?= esc_html($linha) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>
        </div>

        <?php if ($data['imagem']) : ?>
          <div class="relative h-64 w-full overflow-hidden rounded-2xl sm:h-80 lg:absolute lg:top-[30px] lg:right-10 lg:bottom-0 lg:h-full lg:w-[38%] xl:right-16 xl:w-[478px]">
            <?= fw_img($data['imagem'], [
              'data-reveal' => 'fade',
              'class'       => 'h-full w-full rounded-2xl object-contain object-bottom',
              'sizes'       => '(min-width: 1280px) 478px, (min-width: 1024px) 38vw, 100vw',
            ]) ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
