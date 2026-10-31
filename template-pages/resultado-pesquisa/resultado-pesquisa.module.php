<?php
declare(strict_types=1);

namespace App\ResultadoPesquisa;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

use Core\Framework\Module;
use Core\Framework\Attributes\Module as Mod;

/** Slug, rota e rótulo do modelo iguais aos do tema React: as páginas de produção usam _wp_page_template = fw:resultado-pesquisa. */
#[Mod(slug: 'resultado-pesquisa', name: 'ResultadoPesquisa', route: '/resultado-pesquisa', template: true, templateLabel: 'Página · ResultadoPesquisa')]
final class ResultadoPesquisaModule extends Module
{
    // Campos ACF: upsites/customizer-register/inc/acf_fields.php
}
