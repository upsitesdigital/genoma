<?php
declare(strict_types=1);

namespace Core\Support;

/**
 * Troca URLs de imagens JPG/PNG de wp-content pela versão .webp gerada pelo
 * WebP Express, quando ela existe no disco.
 *
 * O WebP Express normalmente serve o .webp via regras de .htaccess, mas no
 * servidor (nginx na frente) os arquivos estáticos não passam por elas — e o
 * "Alter HTML" do plugin não alcança o JSON da API. Por isso a troca é feita
 * direto nos dados que o front recebe (REST do framework + FW_BOOT).
 */
class Webp
{
    /** @var array<string, string> URL original => URL final (cache por requisição) */
    private static array $cache = [];

    private static ?string $pattern = null;

    public static function register(): void
    {
        add_filter('rest_post_dispatch', [self::class, 'filterRestResponse'], 10, 3);
    }

    public static function filterRestResponse(mixed $response, \WP_REST_Server $server, \WP_REST_Request $request): mixed
    {
        if (!$response instanceof \WP_REST_Response || $response->is_error()) return $response;
        if (!str_starts_with($request->get_route(), '/framework/')) return $response;

        $response->set_data(self::rewrite($response->get_data()));
        return $response;
    }

    /** Percorre arrays/strings recursivamente trocando as URLs de imagem. */
    public static function rewrite(mixed $data): mixed
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) $data[$key] = self::rewrite($value);
            return $data;
        }

        if (!is_string($data) || !str_contains($data, 'wp-content')) return $data;

        return (string) preg_replace_callback(self::pattern(), static fn(array $m) => self::url($m[0]), $data);
    }

    /** Devolve a URL .webp equivalente, ou a própria URL se não houver conversão. */
    public static function url(string $url): string
    {
        if (isset(self::$cache[$url])) return self::$cache[$url];

        $path = (string) parse_url($url, PHP_URL_PATH);
        $base = (string) parse_url(content_url(), PHP_URL_PATH);
        $rel  = ltrim(substr(rawurldecode($path), strlen($base)), '/');

        $candidates = [
            // Pasta separada (padrão do WebP Express): wp-content/webp-express/webp-images/uploads/.../a.png.webp
            'webp-express/webp-images/' . $rel . '.webp',
            // "Mingled": a.png.webp ao lado do original
            $rel . '.webp',
        ];

        foreach ($candidates as $candidate) {
            if (is_file(WP_CONTENT_DIR . '/' . $candidate)) {
                return self::$cache[$url] = content_url(implode('/', array_map('rawurlencode', explode('/', $candidate))));
            }
        }

        return self::$cache[$url] = $url;
    }

    private static function pattern(): string
    {
        if (self::$pattern !== null) return self::$pattern;

        // Aceita http/https/protocol-relative para o mesmo host de wp-content.
        $host = preg_replace('#^https?:#i', '', content_url());
        return self::$pattern = '#(?:https?:)?' . preg_quote($host, '#') . '/(?!webp-express/)[^\s"\'<>(),]+?\.(?:jpe?g|png)(?=[\s"\'<>(),?\#]|$)#i';
    }
}
