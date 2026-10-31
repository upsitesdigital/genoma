<?php
declare(strict_types=1);

namespace Core\Framework;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

use Core\Admin\ModelScreen;
use Core\Admin\Seeder;
use Core\Admin\NonceApi;
use Core\Admin\ThemeOptions;
use Core\Support\Asset;
use Core\Support\Seo;
use Core\Support\Svg;
use Core\Support\Kses;

final class Bootstrap
{
    public static function init(): void
    {
        ModuleLoader::discover();
        ModuleLoader::registerPageTemplates();

        add_action('init',             [ModuleLoader::class, 'registerPostTypes']);
        add_action('init',             [ModuleLoader::class, 'bootModules'], 20);
        add_action('acf/init',         [ModuleLoader::class, 'registerFields']);
        add_action('rest_api_init',    [ModuleLoader::class, 'registerRestRoutes']);
        add_filter('rest_pre_dispatch', [Rest::class,        'stripGetBody'], 10, 3);
        add_filter('template_include', [self::class,         'catchAll']);
        add_action('save_post',        [self::class,         'clearRestCache']);
        // antes de apagar: o post ainda existe (o tipo é conferido); o deleted_post já não acha
        add_action('before_delete_post', [self::class,       'clearRestCache']);
        add_action('acf/save_post',    [self::class,         'clearRestCache'], 20); // inclui Opções do Tema
        // anexo não dispara save_post: alt/título de imagem que está no cache
        add_action('edit_attachment',  [self::class,         'clearRestCache']);
        add_action('delete_attachment', [self::class,        'clearRestCache']);
        // termos (nome/slug em listagens — termo novo vira aba/filtro) e Customizer (logo, nome do site)
        add_action('created_term',     [self::class,         'flushAll']);
        add_action('edited_term',      [self::class,         'flushAll']);
        add_action('delete_term',      [self::class,         'flushAll']);
        add_action('customize_save_after', [self::class,     'flushAll']);
        // termo atribuído por código/importação (wp_set_object_terms) não passa pelo save_post
        add_action('set_object_terms', [self::class,         'clearRestCache']);
        add_action('wp_enqueue_scripts', [Asset::class,      'enqueueStyles']);
        add_action('wp_enqueue_scripts', [Asset::class,      'registerCoreModules'], 5);
        add_action('wp_enqueue_scripts', [self::class,       'interactivity']);
        add_action('admin_notices',    [Asset::class,        'buildNotice']);

        ThemeOptions::register();
        NonceApi::register();
        ModelScreen::register();
        Seeder::register();
        Svg::register();
        Kses::register();
        Seo::register();
        Preview::register();
    }

    /**
     * Configuração global da Interactivity API, lida nos stores com `getConfig('upwork')`
     * (de @wordpress/interactivity) — usada pelo módulo `upwork/api` para chamar a REST do
     * framework. Animações de entrada (skill animation): add_filter('upwork/animation', '__return_true').
     */
    public static function interactivity(): void
    {
        wp_interactivity_config('upwork', [
            'apiBase' => untrailingslashit(rest_url(Rest::NAMESPACE)),
            'nonce'   => wp_create_nonce('wp_rest'),
            'ajaxUrl' => admin_url('admin-ajax.php'), // renovação do nonce vencido (NonceApi)
        ]);

        if (apply_filters('upwork/animation', false)) {
            wp_enqueue_script_module(Asset::MODULE_PREFIX . 'reveal');
            // wp_enqueue_scripts roda no wp_head prioridade 1 — este entra logo depois
            add_action('wp_head', [self::class, 'revealHead'], 2);
        }
    }

    /**
     * Animações ligadas: marca o <html> com .fw-reveal antes de pintar — o HTML já vem pronto
     * do servidor, e sem isto os elementos apareceriam e sumiriam até o módulo carregar.
     * Se o módulo não rodar em 3s (bloqueado, erro), desfaz: o conteúdo nunca fica invisível.
     */
    public static function revealHead(): void
    {
        // wp_print_inline_script_tag: respeita o html5 'script' e os filtros de atributos (CSP/nonce)
        wp_print_inline_script_tag("(function(d){if(window.matchMedia('(prefers-reduced-motion: reduce)').matches||!('IntersectionObserver' in window))return;d.classList.add('fw-reveal');setTimeout(function(){if(!window.fwReveal)d.classList.remove('fw-reveal')},3000)})(document.documentElement);");
    }

    /** HTML de campo editável impresso como HTML: só tags permitidas em post. null continua null. */
    public static function kses(?string $html): ?string
    {
        return $html === null ? null : wp_kses_post($html);
    }

    /**
     * Conteúdo mudou → invalida o cache (páginas e REST) trocando a versão da chave, uma vez
     * no fim da requisição (Rest::flushCacheLater). Revisão e autosave não contam.
     */

    public static function clearRestCache(mixed $postId = 0): void
    {
        if (is_numeric($postId) && (int) $postId > 0) {
            $id = (int) $postId;
            if (wp_is_post_revision($id) || wp_is_post_autosave($id)) return;
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
            // "Adicionar novo" cria um auto-draft (e o cron diário os apaga): não está no site
            if (get_post_status($id) === 'auto-draft') return;
            // só tipos que aparecem no site (páginas, posts, CPTs públicos) e anexos: envio de
            // formulário gravado como post (Flamingo), pedido, item de menu, oembed… não zeram o
            // cache — senão um bot enviando o formulário em loop deixa o cache sempre vazio
            $type = get_post_type($id);
            if ($type && $type !== 'attachment' && !is_post_type_viewable($type)) return;
        }
        Rest::flushCacheLater();
    }

    /** Termo criado/editado/apagado, Customizer salvo: invalida tudo (sem post para conferir). */
    public static function flushAll(): void
    {
        Rest::flushCacheLater();
    }

    public static function catchAll(string $template): string
    {
        if (is_admin()) return $template;
        if (is_feed()) return $template;
        // /embed/ (oEmbed: outro site incorporando a página) → o cartão de embed do WP, não a página inteira
        if (is_embed()) return $template;
        if (function_exists('is_sitemap') && is_sitemap()) return $template;

        // Páginas com modelo fw:{slug} e a página inicial → template-parts/app.php.
        // Todo o resto segue a hierarquia do WP; esses templates podem chamar View::render().
        if (RouteResolver::current() === null) return $template;

        $shell = get_template_directory() . '/template-parts/app.php';
        return file_exists($shell) ? $shell : $template;
    }
}
