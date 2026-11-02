<?php
/**
 * Tipos de exames — categorias em acordeão com a tabela de exames (React: ServicosSection em
 * app/responsavel/responsavel.view.tsx; <section id="tipos-de-exames">, âncora dos menus).
 * Dados: ResponsavelController → 'servicos' (null = "Ocultar seção" → nem chega aqui).
 *
 * - Sem etiqueta, título e categorias → a seção não aparece (como no React).
 * - Mostra 3 categorias; com mais de 3, o botão "Ver mais"/"Ver menos" mostra as outras
 *   (VerMaisButton do React, sem CTA no lugar quando não há mais).
 * - Cada categoria abre e fecha no clique; `aberto` = já aberta ao carregar. Categoria sem descrição
 *   e sem exames não abre (botão desabilitado, sem a seta).
 * - Exame: nome + prazo + amostra; prazo e amostra com largura fixa a partir do sm (alinham em coluna).
 * - Store `upwork/responsavel-servicos` (servicos.store.js): `context.expanded` (na <section>) mostra as
 *   categorias além da 3ª; `context.open` (em cada categoria) abre o conteúdo. Tudo já vem no HTML,
 *   o que está fechado com `hidden`.
 *
 * @var array{
 *   eyebrow: string|null,
 *   titulo: string|null,
 *   texto: string|null,
 *   categorias: list<array{
 *     nome: string|null,
 *     aberto: bool,
 *     descricao: string|null,
 *     exames: list<array{nome: string|null, prazo: string|null, amostra: string|null}>,
 *   }>,
 * } $data
 */

defined('ABSPATH') || exit;

$categorias = $data['categorias'] ?? [];
$linhas     = $data['titulo'] ? preg_split('/\R/', $data['titulo']) : [];
if (!$data['eyebrow'] && !$linhas && !$categorias) return;

$visiveis = 3; // VISIVEIS_INICIAL do VerMaisButton
$hasMore  = count($categorias) > $visiveis;

// VerMaisButton do React
$verMaisClass = 'group inline-flex w-full items-center justify-center gap-2 rounded-full border border-brand-purple px-6 py-4 font-heading text-base font-medium text-brand-purple transition-colors hover:bg-brand-purple hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-purple md:px-7 md:py-5 md:text-h5 lg:w-auto';
// ChevronDown (lucide)
$chevron = '<path d="m6 9 6 6 6-6"/>';
?>
<section
  id="tipos-de-exames"
  class="bg-background py-12 md:py-20 lg:py-24 xl:py-28"
  data-wp-interactive="upwork/responsavel-servicos"
  <?= wp_interactivity_data_wp_context(['expanded' => false]) ?>
>
  <div class="container">
    <div class="max-w-[571px]">
      <?php if ($data['eyebrow']) : ?>
        <p data-reveal class="font-sans text-eyebrow text-brand-purple-accent uppercase"><?= esc_html($data['eyebrow']) ?></p>
      <?php endif; ?>

      <?php if ($linhas) : ?>
        <h2 data-reveal class="mt-2 font-heading text-h2-mobile text-brand-purple-dark md:text-h2">
          <?php foreach ($linhas as $linha) : ?>
            <span class="block"><?= esc_html($linha) ?></span>
          <?php endforeach; ?>
        </h2>
      <?php endif; ?>

      <?php if ($data['texto']) : ?>
        <p data-reveal class="mt-4 max-w-[553px] font-sans text-body-lg leading-relaxed text-brand-gray-text md:mt-6"><?= esc_html($data['texto']) ?></p>
      <?php endif; ?>
    </div>

    <?php if ($categorias) : ?>
      <ul id="tipos-de-exames-lista" class="mt-8 flex flex-col gap-4 md:mt-10">
        <?php foreach ($categorias as $i => $categoria) :
          $extra      = $i >= $visiveis;
          $hasContent = $categoria['descricao'] || $categoria['exames'];
          $painel     = 'tipos-de-exames-' . ($i + 1);
          ?>
          <li
            class="rounded-2xl bg-brand-purple-subtle p-6 md:p-8"
            <?= wp_interactivity_data_wp_context(['open' => $hasContent && $categoria['aberto']]) ?>
            <?php if ($extra) : ?>
              hidden
              data-wp-bind--hidden="!context.expanded"
            <?php endif; ?>
          >
            <button
              type="button"
              class="<?= fw_cn('flex w-full items-center justify-between gap-4 text-left', ['cursor-default' => !$hasContent]) ?>"
              <?php if ($hasContent) : ?>
                aria-expanded="false"
                aria-controls="<?= esc_attr($painel) ?>"
                data-wp-bind--aria-expanded="context.open"
                data-wp-on--click="actions.toggle"
              <?php else : ?>
                disabled
              <?php endif; ?>
            >
              <span class="font-heading text-h5 font-medium text-brand-purple-dark md:text-h4"><?= esc_html((string) $categoria['nome']) ?></span>

              <?php if ($hasContent) : ?>
                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-purple md:size-[46px]">
                  <svg class="size-4 text-white transition-transform duration-300" data-wp-class--rotate-180="context.open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><?= $chevron // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG fixo do template ?></svg>
                </span>
              <?php endif; ?>
            </button>

            <?php if ($hasContent) : ?>
              <div id="<?= esc_attr($painel) ?>" class="mt-6 flex flex-col gap-4 md:mt-9" hidden data-wp-bind--hidden="!context.open">
                <?php if ($categoria['descricao']) : ?>
                  <p class="max-w-3xl font-sans text-body leading-relaxed font-semibold text-brand-gray-text"><?= esc_html($categoria['descricao']) ?></p>
                <?php endif; ?>

                <?php if ($categoria['exames']) : ?>
                  <ul class="flex flex-col gap-2">
                    <?php foreach ($categoria['exames'] as $exame) : ?>
                      <li class="flex flex-col gap-2 sm:flex-row sm:items-stretch">
                        <div class="flex-1 rounded-2xl bg-white p-4 font-sans text-body font-semibold text-brand-purple-dark"><?= esc_html((string) $exame['nome']) ?></div>
                        <?php // larguras fixas: prazo e amostra alinham em coluna em todas as linhas ?>
                        <?php if ($exame['prazo']) : ?>
                          <div class="flex shrink-0 items-center justify-center rounded-2xl bg-white p-4 text-center font-sans text-body-sm text-brand-purple-dark sm:w-[132px]"><?= esc_html($exame['prazo']) ?></div>
                        <?php endif; ?>
                        <?php if ($exame['amostra']) : ?>
                          <div class="flex shrink-0 items-center justify-center rounded-2xl bg-white p-4 text-center font-sans text-body-sm text-brand-purple-dark sm:w-[220px]"><?= esc_html($exame['amostra']) ?></div>
                        <?php endif; ?>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <?php if ($hasMore) : ?>
      <div class="mt-10 flex justify-center md:mt-12">
        <button
          type="button"
          data-reveal
          class="<?= esc_attr($verMaisClass) ?>"
          aria-controls="tipos-de-exames-lista"
          aria-expanded="false"
          data-wp-bind--aria-expanded="context.expanded"
          data-wp-on--click="actions.toggleMore"
        >
          <span data-wp-bind--hidden="context.expanded"><?php esc_html_e('Ver mais', 'upsites'); ?></span>
          <span hidden data-wp-bind--hidden="!context.expanded"><?php esc_html_e('Ver menos', 'upsites'); ?></span>
          <?php // vira para cima com a lista aberta ?>
          <svg class="size-4 transition-transform duration-300 group-aria-expanded:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><?= $chevron // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG fixo do template ?></svg>
        </button>
      </div>
    <?php endif; ?>
  </div>
</section>
