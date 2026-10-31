<?php
declare(strict_types=1);

namespace App\Responsavel;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

use Core\Framework\Module;
use Core\Framework\Attributes\Module as Mod;

/** Slug, rota e rótulo do modelo iguais aos do tema React: as páginas de produção usam _wp_page_template = fw:responsavel. */
#[Mod(slug: 'responsavel', name: 'Responsável', route: '/responsavel', template: true, templateLabel: 'Página · Responsável')]
final class ResponsavelModule extends Module
{
    // Campos ACF: upsites/customizer-register/inc/acf_fields.php
}
