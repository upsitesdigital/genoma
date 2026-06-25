<?php
declare(strict_types=1);

namespace Core\Support;

class Asset
{
    private static ?array $manifest = null;

    private static function devServerUrl(): string
    {
        if (defined('VITE_DEV_URL')) return VITE_DEV_URL;

        $hot = get_template_directory() . '/public/build/hot';
        if (file_exists($hot)) {
            $url = trim((string) file_get_contents($hot));
            if ($url !== '') return rtrim($url, '/');
        }

        return 'http://localhost:5173';
    }

    /** Registra os assets Vite via wp_enqueue_script / wp_enqueue_style. */
    public static function enqueue(string $entry): void
    {
        if (self::isDev()) {
            $base = self::devServerUrl();
            wp_enqueue_script('vite-client', $base . '/@vite/client', [], null, false);
            wp_enqueue_script('vite-app', $base . '/' . $entry, ['vite-client'], null, true);
        } else {
            $manifest = self::manifest();
            $file = $manifest[$entry]['file'] ?? '';
            $css  = $manifest[$entry]['css'][0] ?? '';

            if ($file) {
                wp_enqueue_script('vite-app', get_template_directory_uri() . '/public/build/' . $file, [], null, true);
            }
            if ($css) {
                wp_enqueue_style('vite-app', get_template_directory_uri() . '/public/build/' . $css);
            }
        }

        add_filter('script_loader_tag', [self::class, 'addModuleType'], 10, 2);
    }

    /** @internal */
    public static function addModuleType(string $tag, string $handle): string
    {
        if (in_array($handle, ['vite-client', 'vite-app'], true)) {
            return str_replace('<script ', '<script type="module" ', $tag);
        }
        return $tag;
    }

    public static function isDev(): bool
    {
        return file_exists(get_template_directory() . '/public/build/hot');
    }

    private static function manifest(): array
    {
        if (self::$manifest !== null) return self::$manifest;

        $path = get_template_directory() . '/public/build/.vite/manifest.json';

        // Vite 5 gera o manifest dentro de .vite/
        if (!file_exists($path)) {
            $path = get_template_directory() . '/public/build/manifest.json';
        }

        self::$manifest = file_exists($path)
            ? (json_decode(file_get_contents($path), true) ?? [])
            : [];

        return self::$manifest;
    }
}
