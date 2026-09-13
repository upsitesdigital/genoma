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

        ModuleManager::register();
        ThemeOptions::register();
        MenuApi::register();
        NonceApi::register();
        FormCpt::register();
        FormApi::register();
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
