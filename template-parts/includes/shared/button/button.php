<?php
/**
 * Todo botão/link editável do site: texto, URL e "abrir em nova aba" vêm do campo
 * `link` do ACF (Controller::link/linkFrom). Nova aba ganha rel="noopener noreferrer".
 * Sem link → não renderiza.
 *
 *   Part::render('shared/button', ['link' => $data['botao'], 'class' => 'rounded-md bg-primary px-6 py-3 …']);
 *
 * @var array{link: array{title: string, url: string, target: string}|null, class?: string, after?: string} $data
 *      after = HTML extra depois do texto (ex.: SVG de seta) — só SVG simples e <span> passam.
 */

defined('ABSPATH') || exit;

$link = $data['link'] ?? null;
$href = is_array($link) ? fw_url($link['url'] ?? '') : null;
if (!$href) return;

// ícone depois do texto: mesmo vindo de um campo, só sai markup de ícone (sem script/evento/link)
$paint = ['class' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'fill-rule' => true, 'clip-rule' => true, 'transform' => true];
$after = wp_kses((string) ($data['after'] ?? ''), [
  'svg'      => $paint + ['xmlns' => true, 'viewbox' => true, 'width' => true, 'height' => true, 'aria-hidden' => true, 'focusable' => true, 'role' => true],
  'g'        => $paint,
  'path'     => $paint + ['d' => true],
  'circle'   => $paint + ['cx' => true, 'cy' => true, 'r' => true],
  'rect'     => $paint + ['x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'ry' => true],
  'line'     => $paint + ['x1' => true, 'y1' => true, 'x2' => true, 'y2' => true],
  'polyline' => $paint + ['points' => true],
  'polygon'  => $paint + ['points' => true],
  'span'     => ['class' => true, 'aria-hidden' => true],
]);
?>
<a <?= fw_attrs([
  'href'  => $href,
  'class' => fw_cn('inline-flex items-center justify-center gap-2 transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current', $data['class'] ?? ''),
] + fw_target($link['target'] ?? '')) ?>><?= esc_html($link['title'] ?? '') ?><?= $after // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- passou pelo wp_kses acima ?></a>
