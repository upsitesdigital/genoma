<?php
declare(strict_types=1);

namespace App\SinglePost;

use Core\Framework\Module;
use Core\Framework\Attributes\Module as Mod;
use Core\Framework\Attributes\Required;

/**
 * Módulo do template nativo de post individual (single.php / is_singular('post')).
 * Não é selecionável no dropdown "Modelo" de Página — se aplica automaticamente
 * a todo post do WordPress via RouteResolver::current(). Por isso `template: false`
 * e sem `route`/`templateLabel`.
 */
#[Mod(
    slug: 'single-post',
    name: 'Single Post',
    template: false,
)]
#[Required]
final class SinglePostModule extends Module
{
    public function fields(): void
    {
        // Sem campos ACF neste módulo (Hero, Conteudo do post, Veja também): todos
        // os dados vêm do post real sendo visualizado ou de posts relacionados
        // reais (título, data, categoria, imagem destacada, conteúdo, posts da
        // mesma categoria) — todos nativos do WordPress. Um repeater/flexible_content
        // de página não faz sentido aqui, já que este módulo roda uma vez por post,
        // não por Página.
    }
}
