<?php
declare(strict_types=1);

namespace Core\Framework;

use Core\Admin\ModuleManager;
use Core\Admin\MenuApi;
use Core\Admin\NonceApi;
use Core\Admin\ThemeOptions;
use Core\Admin\FormBuilder\FormCpt;
use Core\Admin\FormBuilder\FormApi;
use Core\Support\Asset;
use Core\Support\Webp;

class Bootstrap
{
    public static function init(): void
    {
        ModuleLoader::discover();
        ModuleLoader::registerPageTemplates();

        add_action('init',             [ModuleLoader::class, 'registerPostTypes']);
        add_action('init',             [ModuleLoader::class, 'bootModules'], 20);
        add_action('acf/init',         [ModuleLoader::class, 'registerFields']);
        add_action('rest_api_init',    [ModuleLoader::class, 'registerRestRoutes']);
        add_action('save_post',        [self::class,         'clearRestCache']);
        add_action('deleted_post',     [self::class,         'clearRestCache']);
        add_action('wp_enqueue_scripts', static fn() => Asset::enqueue('resources/app.tsx'));
        add_action('wp_head',          [self::class,         'injectThemeCssVars'], 1);
        add_action('add_meta_boxes',   [self::class,         'maybeRemoveEditorSupport'], 10, 2);
        add_action('template_redirect', [self::class,        'redirectCategoryArchive']);

        ModuleManager::register();
        ThemeOptions::register();
        MenuApi::register();
        NonceApi::register();
        FormCpt::register();
        FormApi::register();
        Webp::register();
    }

    public static function injectThemeCssVars(): void
    {
        $opts  = get_option('upwork_theme_options', []);
        $color = sanitize_hex_color($opts['primary_color'] ?? '');
        if (!$color) return;

        $parts = sscanf($color, '#%02x%02x%02x');
        if (!is_array($parts) || count($parts) < 3) return;

        [$r, $g, $b] = $parts;
        echo "<style>:root{--theme-primary:{$color};--theme-primary-rgb:{$r} {$g} {$b};}</style>\n";
    }

    /** Remove o editor de conteúdo nativo do WP em páginas com Modelo diferente do padrão. */
    public static function maybeRemoveEditorSupport(string $postType, \WP_Post $post): void
    {
        if ($postType !== 'page') return;

        $template = get_post_meta($post->ID, '_wp_page_template', true);
        if ($template && $template !== 'default') {
            remove_post_type_support('page', 'editor');
        }
    }

    /** Arquivo nativo /category/slug/ não tem módulo — redireciona pro blog filtrado. */
    public static function redirectCategoryArchive(): void
    {
        if (!is_category()) return;

        $term = get_queried_object();
        if (!$term instanceof \WP_Term) return;

        $url = RouteResolver::blogCategoryUrl($term->slug);
        if ($url) {
            wp_safe_redirect($url, 301);
            exit;
        }
    }

    public static function clearRestCache(): void
    {
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_fw_rest_%'
                OR option_name LIKE '_transient_timeout_fw_rest_%'"
        );
    }
}
