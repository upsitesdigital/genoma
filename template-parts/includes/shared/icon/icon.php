<?php
/**
 * Ícone enviado pelo editor (ou o do Figma, por padrão) — Controller::icon/iconFrom.
 * SVG vem sanitizado do PHP e é embutido inline (pode ser recolorido por `text-*` se usar
 * currentColor); PNG/WebP viram <img>. Sem ícone → não renderiza nada: o layout em volta
 * deve funcionar sem ele.
 *
 *   Part::render('shared/icon', ['icon' => $item['icone'], 'class' => 'size-6 text-primary']);
 *
 * @var array{icon: array|null, class?: string, label?: string, sizes?: string} $data
 *      label = texto alternativo quando o ícone carrega significado sozinho; omitido = decorativo.
 *      sizes = largura exibida do ícone PNG/WebP (padrão 4rem) — passe se for maior.
 */

defined('ABSPATH') || exit;

$icon  = $data['icon'] ?? null;
$label = $data['label'] ?? null;
$class = $data['class'] ?? '';
if (!is_array($icon)) return;

if (!empty($icon['svg'])) : ?>
<span <?= fw_attrs([
  'role'        => $label ? 'img' : null,
  'aria-label'  => $label,
  'aria-hidden' => $label ? null : 'true',
  // contain:paint — nada do SVG desenha fora da caixa do ícone (nem fixed/absolute/transform)
  'class'       => fw_cn('inline-block shrink-0 [contain:paint] [&>svg]:block [&>svg]:size-full', $class),
]) ?>><?= $icon['svg'] // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG sanitizado por Core\Support\Svg::sanitize no controller ?></span>
<?php
  return;
endif;

echo fw_img($icon, [
  'alt'         => $label ?? ($icon['alt'] ?? ''),
  'aria-hidden' => ($label || !empty($icon['alt'])) ? null : 'true',
  'class'       => fw_cn('inline-block shrink-0 object-contain', $class),
  // ícone é pequeno: sem isto o sizes do WP seria a largura do arquivo (300px) e Safari/Firefox
  // baixariam a imagem grande; 'medium' (não o thumbnail, que é recortado em quadrado)
  'sizes'       => $data['sizes'] ?? '4rem',
], 'medium');
