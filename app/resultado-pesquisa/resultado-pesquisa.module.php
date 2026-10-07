<?php
declare(strict_types=1);

namespace App\ResultadoPesquisa;

use Core\Framework\Module;
use Core\Framework\Attributes\Module as Mod;

#[Mod(
    slug: 'resultado-pesquisa',
    name: 'ResultadoPesquisa',
    route: '/resultado-pesquisa',
    template: true,
    templateLabel: 'Página · ResultadoPesquisa',
)]
final class ResultadoPesquisaModule extends Module
{
    public function fields(): void
    {
        acf_add_local_field_group([
            'key'      => 'group_resultado_pesquisa',
            'title'    => 'Resultado da Pesquisa',
            'location' => [[
                ['param' => 'page_template', 'operator' => '==', 'value' => 'fw:resultado-pesquisa'],
            ]],
            'fields' => [
                // ── Hero ──────────────────────────────────────────────────
                // O título ("Resultados para "termo"") é montado dinamicamente
                // no controller a partir do termo pesquisado — não é um campo.
                [
                    'key'           => 'field_resultado_pesquisa_hero_eyebrow',
                    'name'          => 'hero_eyebrow',
                    'label'         => 'Hero — Etiqueta (H1)',
                    'type'          => 'text',
                    'instructions'  => 'Renderizado como H1 da página (o Título abaixo é H2).',
                    'default_value' => 'Busca',
                ],
                [
                    'key'           => 'field_resultado_pesquisa_busca_placeholder',
                    'instructions'  => 'Texto de exemplo dentro do campo de busca (ex.: Buscar artigos).',
                    'name'          => 'hero_busca_placeholder',
                    'label'         => 'Hero — Busca (placeholder do campo)',
                    'type'          => 'text',
                    'default_value' => 'Busca',
                ],
                [
                    'key'           => 'field_resultado_pesquisa_sem_resultados',
                    'instructions'  => 'Mensagem exibida quando a busca não encontra nada.',
                    'name'          => 'sem_resultados_texto',
                    'label'         => 'Mensagem — Nenhum resultado encontrado',
                    'type'          => 'textarea',
                    'rows'          => 2,
                    'default_value' => 'Não encontramos nenhum conteúdo para essa busca. Tente outro termo.',
                ],

                // ── Lista de resultados ───────────────────────────────────
                // 100% dinâmica: busca nativa do WordPress (WP_Query com `s`)
                // via controller, sem conteúdo ACF — mesmo padrão da Lista de
                // post do Blog.
            ],
        ]);
    }
}