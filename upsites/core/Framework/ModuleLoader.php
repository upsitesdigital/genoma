<?php
declare(strict_types=1);

namespace Core\Framework;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

use Core\Framework\Attributes;
use Core\PostTypes\PostType as PostTypeRegistrar;
use Core\PostTypes\Taxonomy as TaxonomyRegistrar;

final class ModuleLoader
{
    /** @var array<string, array{attr: Attributes\Module, instance: Module, reflection: \ReflectionClass<Module>, dir: string, slug: string}> */
    private static array $modules = [];

    /** Varre template-pages/[slug]/ procurando arquivos *.module.php e instancia cada módulo. */
    public static function discover(): void
    {
        $appDir = get_template_directory() . '/template-pages';
        if (!is_dir($appDir)) return;

        foreach (glob($appDir . '/*/') ?: [] as $dir) {
            $slug       = basename($dir);
            $moduleFile = $dir . $slug . '.module.php';

            if (!file_exists($moduleFile)) continue;

            require_once $moduleFile;

            $className = self::resolveClass($slug, 'Module');
            if (!class_exists($className)) continue;
            if (!is_subclass_of($className, Module::class)) {
                error_log("[upwork] {$className} não estende Core\\Framework\\Module — ignorado");
                continue;
            }

            $ref   = new \ReflectionClass($className);
            $attrs = $ref->getAttributes(Attributes\Module::class);
            if (empty($attrs)) continue;

            /** @var Attributes\Module $moduleAttr */
            $moduleAttr = $attrs[0]->newInstance();
            // o view e o controller são achados pela pasta: slug diferente daria 404 sem aviso
            if ($moduleAttr->slug !== $slug) {
                error_log("[upwork] #[Module(slug: '{$moduleAttr->slug}')] em template-pages/{$slug}/ — o slug tem de ser o nome da pasta; módulo ignorado");
                continue;
            }

            /** @var Module $instance */
            $instance = $ref->newInstance();

            self::$modules[$moduleAttr->slug] = [
                'attr'       => $moduleAttr,
                'instance'   => $instance,
                'reflection' => $ref,
                'dir'        => $dir,
                'slug'       => $slug,
            ];
        }
    }

    /**
     * Legado: CPTs/taxonomias declarados por #[PostType]/#[Taxonomy] em módulos antigos. Os do
     * tema ficam em upsites/customizer-register/inc/register_cpts.php e register_taxes.php.
     */
    public static function registerPostTypes(): void
    {
        foreach (self::$modules as $data) {

            $ref = $data['reflection'];

            foreach ($ref->getAttributes(Attributes\PostType::class) as $attr) {
                PostTypeRegistrar::register($attr->newInstance());
            }

            foreach ($ref->getAttributes(Attributes\Taxonomy::class) as $attr) {
                TaxonomyRegistrar::register($attr->newInstance());
            }
        }
    }

    /**
     * Legado: chama fields() dos módulos antigos. Os campos do tema ficam em
     * upsites/customizer-register/inc/acf_fields.php (o npm run review acusa campos no module.php).
     */
    public static function registerFields(): void
    {
        if (!function_exists('acf_add_local_field_group')) return;

        foreach (self::$modules as $data) {
            $data['instance']->fields();
        }
    }

    private static bool $routesCompiled = false;

    /** @var array<string, Controller|null> controller de cada módulo já lido, pelo slug do módulo (null = sem controller) */
    private static array $controllers = [];

    /**
     * Lê todos os controllers e compila as rotas REST dos Attributes (só dos métodos com #[Get],
     * #[Post]…) — uma vez por requisição, só onde as rotas são usadas: o rest_api_init
     * (registerRestRoutes). O render de uma página lê só o controller dela.
     */
    public static function loadControllers(): void
    {
        if (self::$routesCompiled) return;
        self::$routesCompiled = true;

        foreach (array_keys(self::$modules) as $key) {
            $instance = self::controller($key);
            if ($instance) Rest::compileController($instance);
        }
    }

    /** Controller do módulo (slug do #[Module]), ou null se o módulo não tiver controller. */
    public static function controller(string $module): ?Controller
    {
        if (array_key_exists($module, self::$controllers)) return self::$controllers[$module];
        self::$controllers[$module] = null;

        $data = self::$modules[$module] ?? null;
        if (!$data) return null;

        $controllerFile = $data['dir'] . $data['slug'] . '.controller.php';
        if (!file_exists($controllerFile)) return null;

        require_once $controllerFile;

        $controllerClass = self::resolveClass($data['slug'], 'Controller');
        if (!class_exists($controllerClass)) return null;
        if (!is_subclass_of($controllerClass, Controller::class)) {
            error_log("[upwork] {$controllerClass} não estende Core\Framework\Controller — ignorado");
            return null;
        }

        return self::$controllers[$module] = new $controllerClass();
    }

    /** rest_api_init: registra no WP as rotas dos controllers (endpoint dos stores do JS). */
    public static function registerRestRoutes(): void
    {
        self::loadControllers();
        Rest::registerRoutes();
    }

    /** Registra Page Templates para módulos com template: true no Attribute. */
    public static function registerPageTemplates(): void
    {
        add_filter('theme_page_templates', function (array $templates): array {
            foreach (self::$modules as $data) {
                $attr = $data['attr'];
                if ($attr->template) {
                    $templates["fw:{$attr->slug}"] = $attr->templateLabel ?: $attr->name;
                }
            }
            return $templates;
        });
    }

    /** Chama boot() de cada módulo após o carregamento. */
    public static function bootModules(): void
    {
        foreach (self::$modules as $data) {
            $data['instance']->boot();
        }
    }

    /** @return array<string, array{attr: Attributes\Module, instance: Module, reflection: \ReflectionClass<Module>, dir: string, slug: string}> */
    public static function all(): array
    {
        return self::$modules;
    }

    /**
     * Converte um slug kebab-case para o nome completo da classe PSR-4.
     * Exemplo: 'quem-somos' + 'Module' → 'App\QuemSomos\QuemSomosModule'
     */
    private static function resolveClass(string $slug, string $suffix): string
    {
        $pascal = implode('', array_map('ucfirst', explode('-', $slug)));
        return "App\\{$pascal}\\{$pascal}{$suffix}";
    }
}
