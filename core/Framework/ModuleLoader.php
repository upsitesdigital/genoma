<?php
declare(strict_types=1);

namespace Core\Framework;

use Core\Framework\Attributes;
use Core\PostTypes\PostType as PostTypeRegistrar;
use Core\PostTypes\Taxonomy as TaxonomyRegistrar;

class ModuleLoader
{
    /** @var array<string, array{attr: Attributes\Module, instance: Module, reflection: \ReflectionClass<Module>, dir: string, slug: string}> */
    private static array $modules = [];

    /** Varre app/[slug]/ procurando arquivos *.module.php e instancia cada módulo. */
    public static function discover(): void
    {
        $appDir = get_template_directory() . '/app';
        if (!is_dir($appDir)) return;

        foreach (glob($appDir . '/*/') ?: [] as $dir) {
            $slug       = basename($dir);
            $moduleFile = $dir . $slug . '.module.php';

            if (!file_exists($moduleFile)) continue;

            require_once $moduleFile;

            $className = self::resolveClass($slug, 'Module');
            if (!class_exists($className)) continue;

            $ref   = new \ReflectionClass($className);
            $attrs = $ref->getAttributes(Attributes\Module::class);
            if (empty($attrs)) continue;

            /** @var Attributes\Module $moduleAttr */
            $moduleAttr = $attrs[0]->newInstance();

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

    /** Registra CPTs e taxonomias declarados via Attributes no módulo. */
    public static function registerPostTypes(): void
    {
        foreach (self::$modules as $data) {
            if (!self::isActiveModule($data)) continue;

            $ref = $data['reflection'];

            foreach ($ref->getAttributes(Attributes\PostType::class) as $attr) {
                PostTypeRegistrar::register($attr->newInstance());
            }

            foreach ($ref->getAttributes(Attributes\Taxonomy::class) as $attr) {
                TaxonomyRegistrar::register($attr->newInstance());
            }
        }
    }

    /** Chama fields() de cada módulo para registrar grupos ACF via código. */
    public static function registerFields(): void
    {
        if (!function_exists('acf_add_local_field_group')) return;

        foreach (self::$modules as $data) {
            if (!self::isActiveModule($data)) continue;
            $data['instance']->fields();
        }
    }

    /** Lê os controllers e registra as rotas REST via Attributes. */
    public static function registerRestRoutes(): void
    {
        foreach (self::$modules as $data) {
            if (!self::isActiveModule($data)) continue;

            $controllerFile = $data['dir'] . $data['slug'] . '.controller.php';
            if (!file_exists($controllerFile)) continue;

            require_once $controllerFile;

            $controllerClass = self::resolveClass($data['slug'], 'Controller');
            Rest::compileController($controllerClass);
        }
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
            if (!self::isActiveModule($data)) continue;
            $data['instance']->boot();
        }
    }

    /** Verifica se um módulo está ativo (Required sempre está). */
    private static function isActiveModule(array $data): bool
    {
        $isRequired = !empty($data['reflection']->getAttributes(Attributes\Required::class));
        return $isRequired || ModuleRegistry::isActive($data['attr']->slug);
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
