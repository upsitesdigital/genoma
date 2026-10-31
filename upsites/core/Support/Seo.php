<?php
declare(strict_types=1);

namespace Core\Support;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

use Core\Framework\RouteResolver;
use Core\Framework\View;

/**
 * SEO do site: SEMPRE pelo Yoast SEO (title, description, Open Graph, schema, sitemap) — o tema
 * não imprime meta tags próprias. Sem o Yoast ativo, o painel mostra um aviso.
 *
 * O conteúdo das páginas de módulo está nos campos ACF (o editor da página fica vazio e
 * escondido), então o tema entrega ao Yoast o que ele não acharia sozinho:
 * - descrição em branco no Yoast → a gerada do conteúdo da página;
 * - imagem do hero como og:image (página de modelo não tem imagem destacada);
 * - análise de conteúdo no editor (legibilidade, palavra-chave) com o texto dos campos.
 */
final class Seo
{
    /** Chaves que não são texto visível (URLs, SVG, metadados de imagem/link, formulário). */
    private const SKIP_KEYS = ['src', 'url', 'href', 'svg', 'target', 'sizes', 'width', 'height', 'id', 'slug', 'classes', 'mime_type', 'alt', 'icone', 'icon', 'form'];

    /** Chaves preferidas para a descrição, na ordem (subtítulo/texto do hero). */
    private const DESCRIPTION_KEYS = '/^(subtitulo|descricao|texto|resumo|lead|paragrafo|subtitle|description|text)/i';

    public static function register(): void
    {
        add_filter('wpseo_metadesc', [self::class, 'yoastDescription']);
        add_action('wpseo_add_opengraph_additional_images', [self::class, 'yoastImage']);
        add_action('admin_enqueue_scripts', [self::class, 'analysisScript']);
        add_action('admin_notices', [self::class, 'missingYoastNotice']);
    }

    public static function hasYoast(): bool
    {
        return defined('WPSEO_VERSION');
    }

    /** Painel sem o Yoast ativo: aviso para quem gerencia plugins (Painel, Plugins, Páginas). */
    public static function missingYoastNotice(): void
    {
        if (self::hasYoast() || !current_user_can('activate_plugins')) return;
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen && !in_array($screen->base, ['dashboard', 'plugins', 'edit'], true)) return;

        printf(
            '<div class="notice notice-warning"><p>%s</p></div>',
            esc_html__('O SEO deste tema é feito pelo plugin Yoast SEO (title, description, Open Graph e sitemap). Instale e ative o Yoast SEO.', 'upsites')
        );
    }

    /** Yoast: descrição em branco → a do conteúdo da página. */
    public static function yoastDescription(mixed $description): mixed
    {
        if (is_string($description) && trim($description) !== '') return $description;
        if (!View::isActive()) return $description;
        return self::description(View::data()) ?? $description;
    }

    /** Yoast: imagem do hero como og:image (o plugin só a usa se não houver outra definida). */
    public static function yoastImage(mixed $images): void
    {
        if (!View::isActive() || !is_object($images) || !method_exists($images, 'add_image_by_url')) return;
        $image = View::lcpImage(View::data());
        if ($image) $images->add_image_by_url($image);
    }

    /**
     * Descrição (≤ 160 caracteres) a partir dos dados do módulo: o 1º texto "de descrição"
     * (subtítulo, texto…) das duas primeiras seções; senão o 1º texto com 50+ caracteres.
     */
    public static function description(mixed $data): ?string
    {
        if (!is_array($data)) return null;

        $sections  = array_slice(array_values(array_filter($data, 'is_array')), 0, 2);
        $preferred = null;
        $fallback  = null;
        foreach ($sections as $section) {
            self::walk($section, function (string $key, string $text) use (&$preferred, &$fallback): void {
                if ($preferred === null && preg_match(self::DESCRIPTION_KEYS, $key) && mb_strlen($text) >= 30) $preferred = $text;
                if ($fallback === null && mb_strlen($text) >= 50) $fallback = $text;
            });
        }

        $text = $preferred ?? $fallback;
        if ($text === null) return null;
        if (mb_strlen($text) <= 160) return $text;

        $cut = mb_substr($text, 0, 157);
        $cut = preg_replace('/\s+\S*$/u', '', $cut) ?: $cut;
        return rtrim($cut, " ,.;:–-") . '…';
    }

    /** Todo o texto visível dos dados do módulo, em parágrafos (para a análise do Yoast). */
    public static function contentHtml(mixed $data): string
    {
        if (!is_array($data)) return '';
        $html = '';
        self::walk($data, function (string $key, string $text) use (&$html): void {
            $html .= '<p>' . esc_html($text) . "</p>\n";
        });
        return $html;
    }

    /** Percorre os dados chamando $fn(chave, texto limpo) para cada texto visível. */
    private static function walk(array $node, callable $fn, string $parentKey = ''): void
    {
        foreach ($node as $key => $value) {
            $key = is_string($key) ? $key : $parentKey;
            if (in_array(strtolower($key), self::SKIP_KEYS, true)) continue;
            if (is_array($value)) {
                self::walk($value, $fn, $key);
            } elseif (is_string($value)) {
                $text = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(wp_strip_all_tags($value), ENT_QUOTES, 'UTF-8')));
                if ($text === '' || preg_match('#^(https?:|/|\#|mailto:|tel:|fw-cf7:)#i', $text) || str_contains($text, '<svg')) continue;
                $fn($key, $text);
            }
        }
    }

    /**
     * Editor de página de módulo com o Yoast ativo: a análise de conteúdo dele lê o editor
     * (vazio). Entrega o texto dos campos ACF (o mesmo do site: page() do controller, via
     * View::fetch — logado, sem cache). O texto é o salvo: mudanças nos campos entram na
     * análise depois de salvar e recarregar.
     */
    public static function analysisScript(string $hook): void
    {
        if (!in_array($hook, ['post.php', 'post-new.php'], true) || !self::hasYoast()) return;

        $post = get_post();
        if (!$post || $post->post_type !== 'page' || !current_user_can('edit_post', $post->ID)) return;

        $module = RouteResolver::moduleForPage($post->ID);
        if ($module === null) return;

        $html = self::contentHtml(View::fetch($module, $post->ID));
        if ($html === '') return;

        $json = wp_json_encode($html, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        if ($json === false) return;
        wp_register_script('upwork-seo-analysis', '', [], null, true);
        wp_enqueue_script('upwork-seo-analysis');
        wp_add_inline_script('upwork-seo-analysis', <<<JS
(function () {
  var html = {$json};
  // Yoast: modificação do conteúdo analisado
  function yoast() {
    if (!window.YoastSEO || !YoastSEO.app || !YoastSEO.app.registerPlugin) return false;
    YoastSEO.app.registerPlugin('upwork', { status: 'ready' });
    YoastSEO.app.registerModification('content', function (c) { return (c || '') + html; }, 'upwork', 5);
    return true;
  }
  if (!yoast() && window.jQuery) jQuery(window).on('YoastSEO:ready', yoast);
})();
JS);
    }
}
