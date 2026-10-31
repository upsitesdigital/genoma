<?php
declare(strict_types=1);

namespace App\Veterinarios;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

use Core\Framework\Module;
use Core\Framework\Attributes\Module as Mod;

/** Slug, rota e rótulo do modelo iguais aos do tema React: as páginas de produção usam _wp_page_template = fw:veterinarios. */
#[Mod(slug: 'veterinarios', name: 'Veterinários', route: '/veterinarios', template: true, templateLabel: 'Página · Veterinários')]
final class VeterinariosModule extends Module
{
    // Campos ACF: upsites/customizer-register/inc/acf_fields.php
}
