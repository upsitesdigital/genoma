<?php
declare(strict_types=1);

namespace Core\Support;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

/**
 * CSS e JS do tema.
 *
 * - CSS (Tailwind + Sass) e JS manual (src/js) são compilados pelo gulp em assets/ — versão
 *   aberta no localhost, .min em homologação/produção (Asset::style / Asset::script).
 * - Interatividade é da Interactivity API do WordPress: cada parte com estado tem um
 *   `{nome}.store.js` (script module) ao lado do `{nome}.php`, enfileirado sozinho quando a
 *   parte é renderizada (Core\Framework\Part). Asset::module() registra/enfileira um módulo.
 */
final class Asset
{
    /** Prefixo dos IDs de script module do tema (import map): upwork/{grupo}-{nome}. */
    public const MODULE_PREFIX = 'upwork/';

    /**
     * Ambiente local? Decide entre arquivo aberto (main.css, mainJS.js) e compilado
     * (main.min.css, mainJS.min.js). Local = localhost/127.0.0.1/::1, domínios
     * *.local, *.test, *.localhost ou WP_ENVIRONMENT_TYPE 'local'. Homolog e
     * produção usam sempre o .min.
     * Para testar o .min no localhost: define('FW_MINIFIED_ASSETS', true) no wp-config.
     */
    public static function isLocal(): bool
    {
        if (defined('FW_MINIFIED_ASSETS')) return !FW_MINIFIED_ASSETS;
        return self::isLocalHost();
    }

    /**
     * Máquina de desenvolvimento de verdade (host/ambiente), sem o atalho FW_MINIFIED_ASSETS —
     * para recursos que só podem existir no local (ex.: Preview), mesmo se alguém definir
     * FW_MINIFIED_ASSETS=false num servidor.
     */
    public static function isLocalHost(): bool
    {
        if (function_exists('wp_get_environment_type') && wp_get_environment_type() === 'local') return true;

        $host = strtolower((string) wp_parse_url(home_url(), PHP_URL_HOST));
        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) return true;

        foreach (['.localhost', '.local', '.test'] as $suffix) {
            if (str_ends_with($host, $suffix)) return true;
        }
        return false;
    }

    /**
     * assets/{type}/{name}.{type} no local, assets/{type}/{name}.min.{type} nos demais.
     * Sem fallback: fora do local, falta do .min = build não rodado (registra no error_log).
     *
     * @return array{0: string, 1: string}|null [url, versão (data do arquivo)]
     */
    public static function file(string $type, string $name): ?array
    {
        $file = self::isLocal() ? "{$name}.{$type}" : "{$name}.min.{$type}";
        $path = get_template_directory() . "/assets/{$type}/{$file}";

        if (!file_exists($path)) {
            if (!self::isLocal()) error_log("[UpWork] assets/{$type}/{$file} não encontrado — rode `npm run build` antes do deploy.");
            return null;
        }

        return [get_template_directory_uri() . "/assets/{$type}/{$file}", (string) filemtime($path)];
    }

    /** Enfileira um CSS compilado pelo gulp (assets/css/{name}[.min].css). */
    public static function style(string $handle, string $name, array $deps = [], string $media = 'all'): bool
    {
        $asset = self::file('css', $name);
        if (!$asset) return false;

        wp_enqueue_style($handle, $asset[0], $deps, $asset[1], $media);
        return true;
    }

    /** Enfileira um JS compilado pelo gulp/webpack (assets/js/{name}[.min].js), no footer. */
    public static function script(string $handle, string $name, array $deps = []): bool
    {
        $asset = self::file('js', $name);
        if (!$asset) return false;

        wp_enqueue_script($handle, $asset[0], $deps, $asset[1], true);
        return true;
    }

    /** CSS global do tema (src/sass/main.scss → Tailwind + Sass via gulp), em todas as páginas. */
    public static function enqueueStyles(): void
    {
        self::style('fw-main', 'main');
    }

    /**
     * Módulos JS do próprio framework (upsites/core/js/), disponíveis no import map para
     * qualquer store: `import { api } from 'upwork/api'`.
     */
    public static function registerCoreModules(): void
    {
        foreach (['api', 'reveal'] as $name) {
            $path = get_template_directory() . "/upsites/core/js/{$name}.js";
            if (!is_file($path)) continue;
            wp_register_script_module(
                self::MODULE_PREFIX . $name,
                get_template_directory_uri() . "/upsites/core/js/{$name}.js",
                $name === 'api' ? ['@wordpress/interactivity'] : [],
                (string) filemtime($path)
            );
        }
    }

    /**
     * Registra e enfileira o store (Interactivity API) de uma parte:
     * template-parts/includes/{grupo}/{nome}/{nome}.store.js → módulo `upwork/{grupo}-{nome}`.
     * Chamado pelo Part::render() — só baixa nas páginas que usam a parte.
     * Os stores podem importar `@wordpress/interactivity` e `upwork/api`.
     */
    public static function module(string $part): bool
    {
        $name = basename($part);
        $rel  = "template-parts/includes/{$part}/{$name}.store.js";
        $path = get_template_directory() . '/' . $rel;
        if (!is_file($path)) return false;

        wp_enqueue_script_module(
            self::MODULE_PREFIX . str_replace('/', '-', $part),
            get_template_directory_uri() . '/' . $rel,
            ['@wordpress/interactivity', ['id' => self::MODULE_PREFIX . 'api', 'import' => 'dynamic']],
            (string) filemtime($path)
        );
        return true;
    }

    /**
     * assets/ não é versionado (é gerado pelo `npm run build` no deploy). Sem o build,
     * o site abre sem CSS — avisa os administradores no painel em vez de falhar calado.
     */
    public static function buildNotice(): void
    {
        if (!current_user_can('manage_options') || self::file('css', 'main') !== null) return;

        echo '<div class="notice notice-error"><p><strong>UpWork:</strong> '
            . esc_html__('o build do tema não está no servidor (assets/css/main.min.css). Rode "npm run build" e envie a pasta assets/ — ou use o zip gerado por "npm run package" / pela pipeline.', 'upsites')
            . '</p></div>';
    }
}
