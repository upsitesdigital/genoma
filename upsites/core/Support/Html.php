<?php
declare(strict_types=1);

namespace Core\Support;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

/**
 * Implementação dos helpers de markup dos templates — nos templates use os atalhos globais
 * fw_cn(), fw_attrs(), fw_url(), fw_target(), fw_img(), fw_lines() (upsites/core/Support/helpers.php).
 * Todos devolvem string já escapada (exceto url() e target(), que alimentam o attrs()).
 */
final class Html
{
    /**
     * Junta classes (equivalente ao cn()/clsx): strings vazias/false somem;
     * array associativo ['classe' => bool] entra só com true. Retorna escapado para atributo.
     */
    public static function cn(mixed ...$inputs): string
    {
        $out = [];
        foreach ($inputs as $input) {
            if (is_array($input)) {
                foreach ($input as $key => $value) {
                    if (is_int($key)) { if (is_string($value) && $value !== '') $out[] = $value; }
                    elseif ($value) $out[] = (string) $key;
                }
            } elseif (is_string($input) && trim($input) !== '') {
                $out[] = trim($input);
            }
        }
        return esc_attr(implode(' ', $out));
    }

    /**
     * Atributos HTML a partir de um array: null/false omitem o atributo, true vira atributo
     * sem valor (ex.: `hidden`). Atributos de URL (href, src, action, formaction, poster…)
     * passam por esc_url — `javascript:` vira vazio; os demais por esc_attr.
     */
    private const URL_ATTRS = ['href', 'src', 'action', 'formaction', 'poster', 'cite', 'data', 'xlink:href', 'background', 'ping'];

    public static function attrs(array $attrs): string
    {
        $parts = [];
        foreach ($attrs as $name => $value) {
            if ($value === null || $value === false) continue;
            $name = preg_replace('/[^a-zA-Z0-9_:.\-]/', '', (string) $name);
            if ($name === '') continue;
            if ($value === true) { $parts[] = $name; continue; }
            $value   = in_array(strtolower($name), self::URL_ATTRS, true) ? esc_url((string) $value) : esc_attr((string) $value);
            $parts[] = sprintf('%s="%s"', $name, $value);
        }
        return implode(' ', $parts);
    }

    /**
     * URL segura para href: só http(s), mailto, tel e caminhos relativos (/, #, ?).
     * javascript:, data: e afins → null (o link não é renderizado). O controller já filtra
     * (Controller::link → esc_url_raw); isto é a segunda barreira no template.
     */
    public static function url(?string $url): ?string
    {
        $value = trim((string) $url);
        if ($value === '') return null;
        // o navegador lê "\" como "/": "/\evil.com" viraria //evil.com (outro site)
        if (str_contains($value, '\\') || preg_match('/[\x00-\x1f]/', $value)) return null;
        if (preg_match('#^[/\#?]#', $value) && !str_starts_with($value, '//')) return $value;
        if (preg_match('#^(https?://|mailto:|tel:)#i', $value)) return $value;
        return null;
    }

    /** target + rel de um link: nova aba sempre com rel="noopener noreferrer". Para somar no attrs(). */
    public static function target(?string $target): array
    {
        return $target === '_blank' ? ['target' => '_blank', 'rel' => 'noopener noreferrer'] : [];
    }

    /**
     * <img> de um ImageData (Controller::image/picture). Anexo da biblioteca → sempre pelo
     * wp_get_attachment_image($id, $size, false, $attrs): `src` do tamanho pedido, `width`/
     * `height` (sem CLS), `srcset` só com os tamanhos da mesma proporção e `sizes` do WP
     * (`auto` nas lazy). Escolha o tamanho pelo espaço que a imagem ocupa: 'thumbnail',
     * 'medium', 'medium_large', 'large' (padrão) ou 'full' (hero de tela cheia).
     * Asset do tema / SVG (sem anexo) → <img> simples com o src/width/height do ImageData.
     * Sem imagem → ''. Padrão loading="lazy" + decoding="async"; no hero passe
     * ['fetchpriority' => 'high', 'loading' => false].
     */
    public static function img(?array $image, array $attrs = [], string $size = 'large'): string
    {
        if (!$image || empty($image['src'])) return '';

        $attrs = array_merge(['alt' => (string) ($image['alt'] ?? ''), 'loading' => 'lazy', 'decoding' => 'async'], $attrs);
        $id    = (int) ($image['id'] ?? 0);
        $isSvg = str_ends_with(strtolower((string) strtok((string) $image['src'], '?')), '.svg');

        if ($id > 0 && !$isSvg && function_exists('wp_get_attachment_image') && wp_attachment_is_image($id)) {
            $wp = [];
            foreach ($attrs as $name => $value) {
                $name = preg_replace('/[^a-zA-Z0-9_:.\-]/', '', (string) $name);
                // width/height vêm do tamanho; loading=false o WP omite (hero); null/false somem
                if ($name === '' || in_array($name, ['src', 'width', 'height'], true)) continue;
                if ($value === null || ($value === false && $name !== 'loading')) continue;
                if ($value === true) $value = '';
                // o WP escapa os valores com esc_attr; URL passa antes pelo esc_url
                $wp[$name] = is_string($value) && in_array(strtolower($name), self::URL_ATTRS, true) ? esc_url($value) : $value;
            }
            $html = wp_get_attachment_image($id, $size, false, $wp);
            if ($html !== '') return $html;
        }

        $base = ['src' => $image['src'], 'width' => $image['width'] ?? null, 'height' => $image['height'] ?? null];
        // sem srcset (asset do tema/SVG) o sizes não tem efeito
        if (empty($attrs['srcset'])) unset($attrs['sizes']);
        // alt precisa existir mesmo vazio (imagem decorativa)
        $html = self::attrs(array_merge($base, $attrs));
        if (!str_contains($html, 'alt="')) $html .= ' alt=""';

        return "<img {$html}>";
    }

    /** Texto com quebras de linha do textarea viram <br>, escapado. */
    public static function lines(?string $text): string
    {
        return nl2br(esc_html((string) $text), false);
    }
}
