<?php
declare(strict_types=1);

namespace App\OGenoma;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

use Core\Framework\Module;
use Core\Framework\Attributes\Module as Mod;

/** Slug, rota e rótulo do modelo iguais aos do tema React: as páginas de produção usam _wp_page_template = fw:o-genoma. */
#[Mod(slug: 'o-genoma', name: 'O Genoma', route: '/o-genoma', template: true, templateLabel: 'Página · O Genoma')]
final class OGenomaModule extends Module
{
    // Campos ACF: upsites/customizer-register/inc/acf_fields.php
}
