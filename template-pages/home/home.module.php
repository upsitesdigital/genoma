<?php
declare(strict_types=1);

namespace App\Home;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

use Core\Framework\Module;
use Core\Framework\Attributes\Module as Mod;

/** Slug, rota e rótulo do modelo iguais aos do tema React: as páginas de produção usam _wp_page_template = fw:home. */
#[Mod(slug: 'home', name: 'Home', template: true, templateLabel: 'Página · Home')]
final class HomeModule extends Module
{
    // Campos ACF: upsites/customizer-register/inc/acf_fields.php
}
