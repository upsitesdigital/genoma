<?php
declare(strict_types=1);

namespace Core\Framework;

use Core\Admin\ModuleManager;
use Core\Admin\FormBuilder\FormCpt;
use Core\Admin\FormBuilder\FormApi;

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
        add_filter('template_include', [self::class,         'catchAll']);

        ModuleManager::register();
        FormCpt::register();
        FormApi::register();
    }

    public static function catchAll(string $template): string
    {
        if (is_admin()) return $template;
        if (is_feed()) return $template;
        if (function_exists('is_sitemap') && is_sitemap()) return $template;

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (str_contains($uri, '/wp-json/')) return $template;

        $shell = get_template_directory() . '/index.php';
        return file_exists($shell) ? $shell : $template;
    }
}
