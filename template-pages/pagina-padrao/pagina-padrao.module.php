<?php
declare(strict_types=1);

namespace App\PaginaPadrao;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

use Core\Framework\Module;
use Core\Framework\Attributes\Module as Mod;

/**
 * Página comum, sem modelo "Página · {Nome}" (ex.: Política de privacidade) — porte do
 * app/pagina-padrao/ do tema React. Não é modelo de página (`template: false`): o page.php
 * chama View::render('pagina-padrao', get_the_ID()) nas páginas que não são de módulo.
 * Sem campos ACF: título e conteúdo vêm do editor do WordPress.
 */
#[Mod(slug: 'pagina-padrao', name: 'Página Padrão', template: false)]
final class PaginaPadraoModule extends Module
{
}
