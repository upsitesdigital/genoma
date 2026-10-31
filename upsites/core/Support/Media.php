<?php
declare(strict_types=1);

namespace Core\Support;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

/**
 * Importa assets do tema (ícones e imagens do Figma em template-parts/includes/…/assets/)
 * para a biblioteca de mídia, uma única vez, para virarem o default_value dos campos
 * de imagem do ACF: o editor vê o arquivo do Figma já preenchido no campo e pode
 * trocar ou remover.
 *
 * A importação só acontece no admin (onde o campo é exibido); no site, enquanto o
 * asset não foi importado, o controller usa o arquivo do tema direto (Controller::asset).
 * O mapa caminho → anexo fica na option `upwork_seeded_media`.
 */
final class Media
{
    public const OPTION = 'upwork_seeded_media';

    private const MIMES = [
        'svg'      => 'image/svg+xml',
        'png'      => 'image/png',
        'jpg|jpeg' => 'image/jpeg',
        'webp'     => 'image/webp',
        'gif'      => 'image/gif',
        'avif'     => 'image/avif',
    ];

    /**
     * ID do anexo do asset (importa se ainda não existir — só para quem pode enviar arquivos,
     * no painel ou no `npm run seed`; ver canImport()). null antes da importação.
     * $alt = texto alternativo do Figma: gravado no anexo se ele ainda não tiver.
     */
    public static function seed(string $path, ?string $alt = null): ?int
    {
        $path = ltrim($path, '/');
        $map  = self::map();

        $id = !empty($map[$path]) && get_post_status($map[$path]) ? $map[$path] : null;
        if (!$id) {
            if (!self::canImport()) return null;
            // trava curta: duas requisições do editor ao mesmo tempo não importam o mesmo arquivo duas vezes
            $lock = 'upwork_media_lock_' . md5($path);
            if (!add_option($lock, time(), '', false)) {
                if (time() - (int) get_option($lock) < 60) return null; // outra requisição importando
                update_option($lock, time(), false);                   // trava velha (falha no meio)
            }
            $id = self::import(get_template_directory() . '/' . $path, $path);
            delete_option($lock);
            if (!$id) return null;
            $map = self::map(true); // relê: outra requisição pode ter importado outro asset
            $map[$path] = $id;
            // lido em toda requisição (acf/init): autoload, sem consulta própria
            update_option(self::OPTION, $map, true);
            self::map(true);
        }
        if ($alt !== null && $alt !== '' && self::canImport() && get_post_meta($id, '_wp_attachment_image_alt', true) === '') {
            update_post_meta($id, '_wp_attachment_image_alt', $alt);
        }
        return $id;
    }

    /** Largura/altura de um SVG (atributos width/height ou viewBox) — o WP não calcula para SVG. */
    public static function svgSize(string $file): ?array
    {
        $svg = is_file($file) && is_readable($file) ? file_get_contents($file) : false;
        if (!$svg || !preg_match('/<svg\b[^>]*>/i', $svg, $tag)) return null;
        $num = static fn(string $attr): ?float => preg_match('/\b' . $attr . '\s*=\s*["\']\s*([\d.]+)(px)?\s*["\']/i', $tag[0], $m) ? (float) $m[1] : null;
        $w = $num('width');
        $h = $num('height');
        if ((!$w || !$h) && preg_match('/viewBox\s*=\s*["\']\s*[-\d.]+[\s,]+[-\d.]+[\s,]+([\d.]+)[\s,]+([\d.]+)/i', $tag[0], $vb)) {
            [$w, $h] = [(float) $vb[1], (float) $vb[2]];
        }
        return $w && $h ? ['width' => (int) round($w), 'height' => (int) round($h)] : null;
    }

    /** ID do anexo do asset se já foi importado — nunca importa (seguro no site público). */
    public static function find(string $path): ?int
    {
        $id = self::map()[ltrim($path, '/')] ?? 0;
        return $id && get_post_status($id) ? $id : null;
    }

    /**
     * Imagem do ACF (array do campo image/file) → ImageData { id, src, alt, width, height, sizes }.
     * Sem imagem → null.
     */
    public static function imageData(mixed $acfImage): ?array
    {
        if (!is_array($acfImage) || empty($acfImage['url'])) return null;

        return [
            'id'     => (int) ($acfImage['ID'] ?? $acfImage['id'] ?? 0),
            'src'    => (string) $acfImage['url'],
            'alt'    => (string) ($acfImage['alt'] ?? ''),
            'width'  => !empty($acfImage['width']) ? (int) $acfImage['width'] : null,
            'height' => !empty($acfImage['height']) ? (int) $acfImage['height'] : null,
            'sizes'  => is_array($acfImage['sizes'] ?? null) ? $acfImage['sizes'] : [],
        ];
    }

    /**
     * Ícone do ACF → IconData: ImageData + `svg` (markup sanitizado, para embutir inline e herdar a
     * cor do texto) quando o arquivo é SVG; senão `svg` = null. Sem imagem → null.
     */
    public static function iconData(mixed $acfImage): ?array
    {
        $img = self::imageData($acfImage);
        if (!$img) return null;

        $isSvg = ($acfImage['mime_type'] ?? '') === 'image/svg+xml'
            || str_ends_with(strtolower((string) wp_parse_url($img['src'], PHP_URL_PATH)), '.svg');
        $file  = $isSvg && $img['id'] ? get_attached_file($img['id']) : '';

        return $img + ['svg' => $file ? self::inlineSvg($file, $img['id']) : null];
    }

    /**
     * SVG do arquivo, sanitizado para ir inline. A sanitização (DOM + CSS) roda uma vez por arquivo
     * e requisição e, nos anexos ($attachmentId), fica guardada no post meta até o arquivo ou o
     * Svg.php mudarem — header/footer não passam pelo cache de página e saem em toda requisição.
     */
    public static function inlineSvg(string $file, int $attachmentId = 0): ?string
    {
        static $memo = [];
        if (!is_file($file) || !is_readable($file)) return null;

        $stamp = filemtime($file) . ':' . filemtime(__DIR__ . '/Svg.php');
        $key   = $file . '|' . $stamp;
        if (array_key_exists($key, $memo)) return $memo[$key];

        $stored = $attachmentId ? get_post_meta($attachmentId, '_upwork_svg_inline', true) : null;
        if (is_array($stored) && ($stored['stamp'] ?? '') === $stamp && is_string($stored['svg'] ?? null)) {
            return $memo[$key] = $stored['svg'];
        }

        $svg = Svg::sanitize((string) file_get_contents($file));
        if ($attachmentId && $svg !== null) update_post_meta($attachmentId, '_upwork_svg_inline', ['stamp' => $stamp, 'svg' => $svg]);
        return $memo[$key] = $svg;
    }

    /**
     * Mapa caminho → anexo, lido uma vez por requisição. O acf/init chama seed() para cada campo
     * de imagem com padrão em TODA requisição: um SELECT só para os anexos do mapa (o
     * get_post_status de cada asset sai do cache) em vez de uma consulta por campo.
     *
     * @return array<string, int>
     */
    private static function map(bool $fresh = false): array
    {
        static $map = null;
        if ($map === null || $fresh) {
            $raw = get_option(self::OPTION, []);
            $map = is_array($raw) ? array_map('intval', $raw) : [];
            if ($map && !$fresh) _prime_post_caches(array_values($map), false, false);
        }
        return $map;
    }

    /**
     * Importar/gravar só para quem pode enviar arquivos, numa tela do painel ou no `npm run seed`.
     * is_admin() sozinho não basta: é true em /wp-admin/admin-ajax.php mesmo sem login, e o
     * acf/init (que chama seed()) roda em toda requisição.
     */
    private static function canImport(): bool
    {
        $context = PHP_SAPI === 'cli' || (is_admin() && !wp_doing_ajax());
        return $context && current_user_can('upload_files');
    }

    private static function import(string $file, string $path): ?int
    {
        if (!is_file($file)) return null;

        $upload = wp_upload_dir();
        if (!empty($upload['error'])) return null;

        $name = wp_unique_filename($upload['path'], basename($file));
        $mime = wp_check_filetype($name, self::MIMES)['type'] ?? '';
        if (!$mime) return null;

        $contents = (string) file_get_contents($file);
        if ($mime === 'image/svg+xml') {
            $contents = Svg::sanitize($contents);
            if ($contents === null) return null;
        }

        $target = $upload['path'] . '/' . $name;
        if (file_put_contents($target, $contents) === false) return null;

        $id = wp_insert_attachment([
            'post_mime_type' => $mime,
            'post_title'     => sanitize_text_field(pathinfo($name, PATHINFO_FILENAME)),
            'post_content'   => '',
            'post_status'    => 'inherit',
        ], $target);
        if (!$id || is_wp_error($id)) return null;

        if ($mime !== 'image/svg+xml') {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            wp_update_attachment_metadata($id, wp_generate_attachment_metadata($id, $target));
        } elseif ($size = self::svgSize($target)) {
            // SVG: o WP não grava dimensões — sem elas a imagem sai sem width/height (CLS)
            wp_update_attachment_metadata($id, $size + ['file' => _wp_relative_upload_path($target)]);
        }
        update_post_meta($id, '_upwork_seed', $path);

        return (int) $id;
    }
}
