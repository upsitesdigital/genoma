<?php
declare(strict_types=1);

namespace App\SinglePost;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

use Core\Framework\Module;
use Core\Framework\Attributes\Module as Mod;

/**
 * Post individual (post type nativo `post`) — porte do app/single-post/ do tema React.
 * Não é modelo de página (`template: false`): o single.php chama
 * View::render('single-post', get_the_ID()) em todo post.
 * Sem campos ACF: título, data, categoria, imagem destacada, conteúdo e os posts de
 * "Veja também" vêm do próprio WordPress.
 */
#[Mod(slug: 'single-post', name: 'Single Post', template: false)]
final class SinglePostModule extends Module
{
}
