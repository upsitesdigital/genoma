<?php
declare(strict_types=1);

namespace App\Contato;

use Core\Framework\Controller;
use Core\Framework\Attributes\Get;
use Core\Framework\Attributes\Cache;

final class ContatoController extends Controller
{
    #[Get('/contato')]
    #[Get('/contato/:id')]
    #[Cache(ttl: 300)]
    public function index(\WP_REST_Request $request): array
    {
        $pageId = (int) ($request->get_param('id') ?: 0);

        return [
            'hero'          => $this->hero($pageId),
            'listaContatos' => $this->listaContatos($pageId),
        ];
    }

    /**
     * Monta o Hero (etiqueta + título + descrição + cards de canais de contato).
     */
    private function hero(int $pageId): array
    {
        $rows = $this->field($pageId, 'hero_canais');

        $canais = is_array($rows) ? array_map(fn (array $row): array => $this->mapCanal($row), $rows) : [];

        return [
            'eyebrow'   => (string) ($this->field($pageId, 'hero_eyebrow') ?: ''),
            'titulo'    => (string) ($this->field($pageId, 'hero_titulo') ?: ''),
            'descricao' => (string) ($this->field($pageId, 'hero_descricao') ?: ''),
            'canais'    => $canais,
        ];
    }

    private function mapCanal(array $row): array
    {
        return [
            'icone'  => $this->image($row['icone'] ?? null),
            'titulo' => (string) ($row['titulo'] ?? ''),
            'valor'  => (string) ($row['valor'] ?? ''),
            'link'   => (string) ($row['link'] ?? ''),
        ];
    }

    /**
     * Monta a "Lista de contatos" (cards com telefones/horários de atendimento
     * e cards de texto institucional) a partir do flexible_content do ACF.
     */
    private function listaContatos(int $pageId): array
    {
        $rows = $this->field($pageId, 'lista_contatos');

        if (empty($rows) || !is_array($rows)) {
            return [];
        }

        return array_map(fn (array $row): array => $this->mapListaContatosRow($row), $rows);
    }

    private function mapListaContatosRow(array $row): array
    {
        $layout = (string) ($row['acf_fc_layout'] ?? '');

        if ($layout === 'card_texto') {
            $mostrarBotao = (bool) ($row['mostrar_botao'] ?? false);

            return [
                'tipo'   => 'texto',
                'titulo' => (string) ($row['titulo'] ?? ''),
                'texto'  => (string) ($row['texto'] ?? ''),
                'botao'  => $mostrarBotao ? [
                    'texto' => (string) ($row['botao_texto'] ?? ''),
                    'link'  => (string) ($row['botao_link'] ?? '#'),
                ] : null,
            ];
        }

        $itens = $row['itens'] ?? [];

        return [
            'tipo'      => 'info',
            'titulo'    => (string) ($row['titulo'] ?? ''),
            'descricao' => (string) ($row['descricao'] ?? ''),
            'itens'     => is_array($itens) ? array_map(
                fn (array $item): array => [
                    'icone' => $this->image($item['icone'] ?? null),
                    'texto' => (string) ($item['texto'] ?? ''),
                ],
                $itens
            ) : [],
        ];
    }
}
