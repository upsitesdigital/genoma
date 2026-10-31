<?php
declare(strict_types=1);

namespace Core\Framework;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

final class RouteResolver
{
    /**
     * Módulo das páginas que o tema assume automaticamente (Bootstrap::catchAll):
     * páginas com modelo fw:{slug} e a página inicial estática com o modelo padrão. Os demais contextos do WP
     * (single, arquivo, busca…) escolhem o módulo no próprio template PHP, via View::render().
     *
     * @return array{module: string, pageId: int|null}|null
     */
    public static function current(): ?array
    {
        if (!is_page()) return null;

        $pageId = (int) get_queried_object_id();
        $module = self::moduleForPage($pageId);
        if ($module === null) return null;

        return ['module' => $module, 'pageId' => $pageId ?: null];
    }

    /**
     * Módulo de uma página (regra única: render, tela de edição, Seeder, Yoast, `npm run seed`):
     *  1. modelo fw:{slug} → {slug};
     *  2. a página inicial estática (Configurações → Leitura, "Uma página estática") com o modelo
     *     padrão → home. "Últimos posts" na página inicial, ou página com outro modelo do
     *     tema/plugin (ex.: page builder): página comum, hierarquia normal do WP.
     * Não é página de módulo → null.
     */
    public static function moduleForPage(int $pageId): ?string
    {
        if ($pageId <= 0 || get_post_type($pageId) !== 'page') return null;

        $template = (string) get_post_meta($pageId, '_wp_page_template', true);
        if (str_starts_with($template, 'fw:')) return substr($template, 3) ?: null;

        $isFront = get_option('show_on_front') === 'page' && (int) get_option('page_on_front') === $pageId;
        return $isFront && in_array($template, ['', 'default'], true) ? 'home' : null;
    }
}
