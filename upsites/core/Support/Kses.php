<?php
declare(strict_types=1);

namespace Core\Support;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

/**
 * Diretivas da Interactivity API (`data-wp-*`) em conteúdo de usuário.
 *
 * O kses do WP libera `data-*` em qualquer tag permitida — inclusive em comentário de
 * visitante. Como o runtime da Interactivity API roda em toda página do tema (header.store.js),
 * ele hidrata qualquer `[data-wp-interactive]` do HTML: um link com
 * `data-wp-context='{"u":"javascript:…"}' data-wp-bind--href="context.u"` vira JavaScript no
 * clique (o `bind` do cliente não filtra o protocolo). Aqui as diretivas saem AO SALVAR:
 *  - post/resumo de quem não tem `unfiltered_html` (Autor, Colaborador);
 *  - comentário, sempre.
 * Blocos do core que usam diretivas (lightbox de imagem, navegação) as geram na renderização,
 * não no post_content salvo — não são afetados. Conteúdo salvo antes desta proteção não é
 * reprocessado (salvar o post de novo limpa).
 */
final class Kses
{
    public static function register(): void
    {
        // depois do kses do WP (prioridade 10), que só roda para quem não tem unfiltered_html
        add_filter('content_save_pre', [self::class, 'stripForUser'], 11);
        add_filter('excerpt_save_pre', [self::class, 'stripForUser'], 11);
        add_filter('pre_comment_content', [self::class, 'strip'], 11);
        add_filter('acf/update_value/type=wysiwyg', [self::class, 'stripAcf'], 11);
    }

    /** Conteúdo de quem não pode publicar HTML livre (o filtro recebe o valor com barras). */
    public static function stripForUser(mixed $content): mixed
    {
        if (!is_string($content) || current_user_can('unfiltered_html')) return $content;
        return wp_slash(self::strip(wp_unslash($content)));
    }

    /**
     * Remove todo atributo `data-wp-*` do HTML — e neutraliza o que sobrar.
     *
     * O tokenizer do WP trata <textarea>/<title> como texto, mas dentro de <math>/<svg> o
     * navegador não: `<math><textarea><div data-wp-…>` passaria pelo laço como "texto" e
     * viraria elemento de verdade na página. Por isso, depois do laço, qualquer `data-wp-`
     * restante (atributo escondido assim, ou só texto) vira `data-wp_` — nenhum parser
     * reconstrói uma diretiva a partir disso.
     */
    public static function strip(mixed $html): mixed
    {
        if (!is_string($html) || stripos($html, 'data-wp-') === false) return $html;

        $tags = new \WP_HTML_Tag_Processor($html);
        while ($tags->next_tag()) {
            foreach ($tags->get_attribute_names_with_prefix('data-wp-') ?? [] as $name) {
                $tags->remove_attribute($name);
            }
        }
        $html = $tags->get_updated_html();

        return (string) preg_replace('/data-wp-/i', 'data-wp_', $html);
    }

    /**
     * Campo WYSIWYG do ACF salvo por quem não tem `unfiltered_html` (ex.: editor em multisite):
     * o tema imprime com wp_kses_post, que mantém data-*. O valor do ACF chega sem barras.
     */
    public static function stripAcf(mixed $value): mixed
    {
        return is_string($value) && !current_user_can('unfiltered_html') ? self::strip($value) : $value;
    }
}
