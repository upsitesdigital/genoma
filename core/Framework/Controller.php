<?php
declare(strict_types=1);

namespace Core\Framework;

abstract class Controller
{
    /** Lê um campo ACF de um post. */
    protected function field(int $postId, string $key): mixed
    {
        return function_exists('get_field') ? get_field($key, $postId) : null;
    }

    /** Lê todos os campos ACF de um post como array. */
    protected function fields(int $postId): array
    {
        return function_exists('get_fields') ? (get_fields($postId) ?: []) : [];
    }

    /** Retorna dados de uma imagem ACF (campo image com return_format: array) como array padronizado. */
    protected function image(mixed $acfImage): ?array
    {
        if (!is_array($acfImage) || empty($acfImage['url'])) return null;

        return [
            'src'    => $acfImage['url'],
            'alt'    => $acfImage['alt'] ?? '',
            'width'  => $acfImage['width'] ?? null,
            'height' => $acfImage['height'] ?? null,
            'sizes'  => $acfImage['sizes'] ?? [],
        ];
    }
}
