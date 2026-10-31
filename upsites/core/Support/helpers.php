<?php
declare(strict_types=1);

/**
 * Helpers de markup dos templates PHP (seções, header/footer, componentes) — atalhos
 * globais para Core\Support\Html. Todos devolvem string JÁ ESCAPADA: use com `echo`/`<?=`
 * direto (o PHPCS os conhece como escapados — phpcs.xml.dist, customAutoEscapedFunctions).
 *
 *   <div class="<?= fw_cn('grid gap-6', ['lg:col-span-2' => !$data['imagem']]) ?>">
 *   <a <?= fw_attrs(['href' => fw_url($link['url']), 'class' => 'btn'] + fw_target($link['target'])) ?>>
 *   <?= fw_img($data['imagem'], ['class' => 'w-full', 'fetchpriority' => 'high', 'loading' => false]) ?>
 *   <p><?= fw_lines($data['texto']) ?></p>
 *
 * @package UpWork
 */

use Core\Support\Html;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

/** Classes juntas (strings; ['classe' => bool]) — escapado para atributo. */
function fw_cn(mixed ...$inputs): string
{
    return Html::cn(...$inputs);
}

/** Atributos HTML de um array (null/false omitem, true = atributo sem valor) — escapado. */
function fw_attrs(array $attrs): string
{
    return Html::attrs($attrs);
}

/** URL segura (http/https/mailto/tel/relativa) ou null — NÃO escapada: use dentro de fw_attrs() ou com esc_url(). */
function fw_url(?string $url): ?string
{
    return Html::url($url);
}

/** ['target' => '_blank', 'rel' => 'noopener noreferrer'] para nova aba; [] nos demais. Some no fw_attrs(). */
function fw_target(?string $target): array
{
    return Html::target($target);
}

/**
 * <img> de um ImageData (Controller::image/picture) — anexo pelo wp_get_attachment_image()
 * no tamanho $size (srcset/sizes do WP), lazy por padrão; '' sem imagem.
 */
function fw_img(?array $image, array $attrs = [], string $size = 'large'): string
{
    return Html::img($image, $attrs, $size);
}

/** Texto de textarea com quebras de linha em <br> — escapado. */
function fw_lines(?string $text): string
{
    return Html::lines($text);
}
