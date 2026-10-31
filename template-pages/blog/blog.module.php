<?php
declare(strict_types=1);

namespace App\Blog;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

use Core\Framework\Module;
use Core\Framework\Attributes\Module as Mod;

/** Slug, rota e rótulo do modelo iguais aos do tema React: as páginas de produção usam _wp_page_template = fw:blog. */
#[Mod(slug: 'blog', name: 'Blog', route: '/blog', template: true, templateLabel: 'Página · Blog')]
final class BlogModule extends Module
{
    // Campos ACF: upsites/customizer-register/inc/acf_fields.php
}
