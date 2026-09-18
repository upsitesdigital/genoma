<?php
declare(strict_types=1);

namespace App\PaginaPadrao;

use Core\Framework\Module;
use Core\Framework\Attributes\Module as Mod;
use Core\Framework\Attributes\Required;

/**
 * Módulo genérico para Páginas com "Modelo por omissão" (sem template fw:*
 * atribuído) — ex: Política de Privacidade, Termos de Uso. Não é selecionável
 * no dropdown "Modelo" de Página — se aplica automaticamente via
 * RouteResolver::current(). Por isso `template: false` e sem `route`/`templateLabel`.
 * Conteúdo vem do editor nativo do WP (post_title/post_content), sem campos ACF.
 */
#[Mod(
    slug: 'pagina-padrao',
    name: 'Página Padrão',
    template: false,
)]
#[Required]
final class PaginaPadraoModule extends Module
{
    public function fields(): void
    {
        // Sem campos ACF: título e conteúdo vêm do editor nativo do WP.
    }
}
