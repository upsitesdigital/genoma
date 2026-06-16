<?php
declare(strict_types=1);

namespace Core\Support;

class Asset
{
    private static ?array $manifest = null;
    private static string $devServerUrl = 'http://localhost:5173';

    public static function script(string $entry): string
    {
        if (self::isDev()) {
            $viteClient = '<script type="module" src="' . self::$devServerUrl . '/@vite/client"></script>' . PHP_EOL;
            $entryScript = '<script type="module" src="' . self::$devServerUrl . '/' . $entry . '"></script>';
            return $viteClient . $entryScript;
        }

        $file = self::manifest()[$entry]['file'] ?? '';
        if (!$file) return '';

        return '<script type="module" src="' . get_template_directory_uri() . '/public/build/' . $file . '"></script>';
    }

    public static function style(string $entry): string
    {
        // Em dev o Vite injeta o CSS via JS — não precisa de <link>
        if (self::isDev()) return '';

        $css = self::manifest()[$entry]['css'][0] ?? '';
        if (!$css) return '';

        return '<link rel="stylesheet" href="' . get_template_directory_uri() . '/public/build/' . $css . '">';
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
