<?php
declare(strict_types=1);

namespace App\Responsavel;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

use Core\Framework\Controller;
use Core\Framework\Attributes\Cache;

final class ResponsavelController extends Controller
{
    /**
     * Dados da página — chamado direto em PHP pelo View::render (sem REST).
     * Migração do tema React: os campos já estão em acf_fields.php (mesmas keys e names);
     * as seções entram aqui uma a uma, na etapa de reconstrução visual.
     */
    #[Cache(ttl: 300)]
    public function page(int $pageId): array
    {
        return [];
    }
}
