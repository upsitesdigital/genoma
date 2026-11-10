<?php
/**
 * Tipos de Exames — acordeão de categorias (React: TiposExamesSection + ExameCategoriaAccordion em
 * app/veterinarios/veterinarios.view.tsx; <section id="tipos-de-exames">, âncora dos menus).
 * Dados: VeterinariosController → 'tiposExames' (null = "Ocultar seção" → nem chega aqui).
 *
 * - categorias vazio → a seção não aparece (como no React).
 * - Acordeão: uma categoria aberta por vez (clicar na aberta fecha). O conteúdo (texto + exames)
 *   abre animando grid-template-rows 0fr → 1fr, como no React; fechado, fica `inert`.
 *   Categoria sem texto e sem exames: botão desabilitado (opacity-40) e sem conteúdo.
 *   `aberto` = já aberta ao carregar (vale a primeira marcada com conteúdo).
 * - Exames: nome (flex-1) + prazo (132px) + amostra (220px) — larguras fixas alinham as colunas;
 *   prazo/amostra null → a caixa não aparece.
 * - Mostra 3 categorias; com mais de 3, o botão "Ver mais"/"Ver menos" expande (VerMaisButton) e o
 *   cta da seção não aparece; com 3 ou menos, aparece o cta (se tiver texto).
 * - CTA: texto null → o botão não aparece; link null → href "#" (como no React).
 * - Store `upwork/veterinarios-tipos-exames` (tipos-exames.store.js): `context.aberta` (índice da
 *   categoria aberta ou null) e `context.expanded` (categorias além da 3ª, que já vêm no HTML com
 *   `hidden`); cada categoria tem o seu `context.i` (state.isOpen).
 *
 * @var array{
 *   eyebrow: string|null,
 *   titulo: string|null,
 *   descricao: string|null,
 *   categorias: list<array{
 *     titulo: string|null,
 *     aberto: bool,
 *     texto: string|null,
 *     itens: list<array{nome: string|null, prazo: string|null, amostra: string|null}>,
 *   }>,
 *   cta: array{texto: string|null, link: string|null},
 * } $data
 */

defined('ABSPATH') || exit;

$categorias = $data['categorias'] ?? [];
if (!$categorias) return;

$visiveis = 3; // VISIVEIS_INICIAL do VerMaisButton
$hasMore  = count($categorias) > $visiveis;

// "Aberta por padrão?": uma aberta por vez → vale a primeira marcada que tem conteúdo
$abertaInicial = null;
foreach ($categorias as $i => $categoria) {
  if (!empty($categoria['aberto']) && ($categoria['texto'] || $categoria['itens'])) {
    $abertaInicial = $i;
    break;
  }
}

// categoria aberta e rótulo do botão no HTML do servidor — os mesmos getters do tipos-exames.store.js
wp_interactivity_state('upwork/veterinarios-tipos-exames', [
  'isOpen' => static function (): bool {
    $ctx = wp_interactivity_get_context();
    return isset($ctx['i'], $ctx['aberta']) && $ctx['aberta'] === $ctx['i'];
  },
  'rotulo' => static function (): string {
    $ctx  = wp_interactivity_get_context();
    $open = isset($ctx['i'], $ctx['aberta']) && $ctx['aberta'] === $ctx['i'];
    return (string) ($open ? ($ctx['recolher'] ?? '') : ($ctx['expandir'] ?? ''));
  },
]);

// VerMaisButton do React: mesmo visual para o "Ver mais" e para o link do CTA
$verMaisClass = 'inline-flex w-full items-center justify-center gap-2 rounded-full border border-brand-purple px-6 py-4 font-heading text-base font-medium text-brand-purple transition-colors hover:bg-brand-purple hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-purple md:px-7 md:py-5 md:text-h5 lg:w-auto';
// ChevronDown (lucide) do React
$chevron = '<svg class="size-4 transition-transform duration-300 group-aria-expanded:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m6 9 6 6 6-6"/></svg>';
?>
<section
  id="tipos-de-exames"
  class="w-full py-12 md:py-20 lg:py-24"
  data-wp-interactive="upwork/veterinarios-tipos-exames"
  <?= wp_interactivity_data_wp_context(['aberta' => $abertaInicial, 'expanded' => false]) ?>
>
  <div class="container">
    <?php if ($data['eyebrow'] || $data['titulo'] || $data['descricao']) : ?>
      <div class="flex flex-col gap-4">
        <?php if ($data['eyebrow'] || $data['titulo']) : ?>
          <div class="flex flex-col gap-2">
            <?php if ($data['eyebrow']) : ?>
              <span data-reveal class="font-sans text-eyebrow text-brand-purple-accent uppercase"><?= esc_html($data['eyebrow']) ?></span>
            <?php endif; ?>
            <?php if ($data['titulo']) : ?>
              <h2 data-reveal class="max-w-xl font-heading text-h2-mobile text-brand-purple-dark md:max-w-2xl md:text-h2"><?= esc_html($data['titulo']) ?></h2>
            <?php endif; ?>
          </div>
        <?php endif; ?>
        <?php if ($data['descricao']) : ?>
          <p data-reveal class="max-w-xl font-sans text-body-sm text-brand-gray-text md:max-w-2xl md:text-base"><?= esc_html($data['descricao']) ?></p>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <ul id="tipos-de-exames-lista" class="mt-8 flex flex-col gap-4 md:mt-10">
      <?php foreach ($categorias as $i => $categoria) :
        $extra      = $i >= $visiveis;
        $titulo     = (string) $categoria['titulo'];
        $hasContent = $categoria['texto'] || $categoria['itens'];
        $painel     = 'tipos-de-exames-' . ($i + 1);
        /* translators: %s: nome da categoria de exames */
        $expandir = sprintf(__('Expandir %s', 'upsites'), $titulo);
        /* translators: %s: nome da categoria de exames */
        $recolher = sprintf(__('Recolher %s', 'upsites'), $titulo);
        ?>
        <?php // acordeão não ganha data-reveal (skill animation: conteúdo que abre por clique) ?>
        <li
          <?= wp_interactivity_data_wp_context(['i' => $i, 'expandir' => $expandir, 'recolher' => $recolher]) ?>
          class="flex flex-col rounded-2xl bg-brand-purple-subtle p-5 md:p-6 lg:p-8"
          <?php if ($extra) : ?>
            hidden
            data-wp-bind--hidden="!context.expanded"
          <?php endif; ?>
        >
          <div class="flex w-full items-center justify-between gap-4">
            <h3 class="font-heading text-h5 font-medium text-brand-purple-dark md:text-h4"><?= esc_html($titulo) ?></h3>

            <button
              type="button"
              class="group flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-purple text-white transition-opacity focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-purple disabled:opacity-40 sm:size-[46px]"
              aria-label="<?= esc_attr($expandir) ?>"
              aria-expanded="false"
              <?php if ($hasContent) : ?>
                aria-controls="<?= esc_attr($painel) ?>"
                data-wp-bind--aria-expanded="state.isOpen"
                data-wp-bind--aria-label="state.rotulo"
                data-wp-on--click="actions.toggle"
              <?php else : ?>
                disabled
              <?php endif; ?>
            ><?= $chevron // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG fixo do template ?></button>
          </div>

          <?php if ($hasContent) : ?>
            <?php // grid-template-rows 0fr → 1fr anima a altura (o React fazia o mesmo por style) ?>
            <div
              id="<?= esc_attr($painel) ?>"
              class="grid grid-rows-[0fr] transition-[grid-template-rows] duration-300 ease-in-out motion-reduce:transition-none data-[open=true]:grid-rows-[1fr]"
              data-open="false"
              inert
              data-wp-bind--data-open="state.isOpen"
              data-wp-bind--inert="!state.isOpen"
            >
              <?php // espaçamento como padding interno: some junto ao recolher (sem gap fixo) ?>
              <div class="overflow-hidden">
                <div class="flex flex-col gap-6 pt-6 sm:pt-9 md:gap-8">
                  <?php if ($categoria['texto']) : ?>
                    <p class="font-sans text-body-sm text-brand-gray-text"><?= esc_html($categoria['texto']) ?></p>
                  <?php endif; ?>

                  <?php if ($categoria['itens']) : ?>
                    <ul class="flex flex-col gap-2">
                      <?php foreach ($categoria['itens'] as $item) : ?>
                        <?php // larguras fixas: prazo e amostra alinham em coluna em todas as linhas ?>
                        <li class="flex w-full flex-wrap items-center gap-2">
                          <div class="min-w-[200px] flex-1 self-stretch rounded-2xl bg-white p-4 font-sans text-sm font-semibold text-brand-purple-dark sm:text-base"><?= esc_html((string) $item['nome']) ?></div>
                          <?php if ($item['prazo']) : ?>
                            <div class="flex w-full shrink-0 items-center justify-center self-stretch rounded-2xl bg-white p-4 text-center font-sans text-body-sm text-brand-purple-dark sm:w-[132px]"><?= esc_html($item['prazo']) ?></div>
                          <?php endif; ?>
                          <?php if ($item['amostra']) : ?>
                            <div class="flex w-full shrink-0 items-center justify-center self-stretch rounded-2xl bg-white p-4 text-center font-sans text-body-sm text-brand-purple-dark sm:w-[220px]"><?= esc_html($item['amostra']) ?></div>
                          <?php endif; ?>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>

    <?php if ($hasMore) : ?>
      <div class="mt-10 flex justify-center md:mt-12">
        <button
          type="button"
          data-reveal
          class="<?= fw_cn('group', $verMaisClass) ?>"
          aria-controls="tipos-de-exames-lista"
          aria-expanded="false"
          data-wp-bind--aria-expanded="context.expanded"
          data-wp-on--click="actions.toggleLista"
        >
          <span data-wp-bind--hidden="context.expanded"><?php esc_html_e('Ver mais', 'upsites'); ?></span>
          <span hidden data-wp-bind--hidden="!context.expanded"><?php esc_html_e('Ver menos', 'upsites'); ?></span>
          <?= $chevron // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG fixo do template ?>
        </button>
      </div>
    <?php elseif ($data['cta']['texto']) : ?>
      <div class="mt-10 flex justify-center md:mt-12">
        <a <?= fw_attrs(['href' => fw_url($data['cta']['link']) ?? '#', 'data-reveal' => true, 'class' => $verMaisClass]) ?>><?= esc_html($data['cta']['texto']) ?></a>
      </div>
    <?php endif; ?>
  </div>
</section>
