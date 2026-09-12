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
     * Se não houver linhas cadastradas no ACF, retorna o conteúdo padrão (Figma) com assets locais.
     */
    private function hero(int $pageId): array
    {
        $rows = $this->field($pageId, 'hero_canais');

        if (empty($rows) || !is_array($rows)) {
            $canais = $this->defaultCanais();
        } else {
            $canais = array_map(fn (array $row): array => $this->mapCanal($row), $rows);
        }

        return [
            'eyebrow'   => (string) ($this->field($pageId, 'hero_eyebrow') ?: 'Contato'),
            'titulo'    => (string) ($this->field($pageId, 'hero_titulo') ?: 'Fale com o Genoma Diagnóstico Veterinário'),
            'descricao' => (string) ($this->field($pageId, 'hero_descricao') ?: 'Estamos prontos para atender você e esclarecer dúvidas sobre exames, resultados, convênios e parcerias.'),
            'canais'    => $canais,
        ];
    }

    private function mapCanal(array $row): array
    {
        return [
            'icone'  => $this->image($row['icone'] ?? null) ?? $this->defaultCanalIcone('whatsapp-clientes'),
            'titulo' => (string) ($row['titulo'] ?? ''),
            'valor'  => (string) ($row['valor'] ?? ''),
            'link'   => (string) ($row['link'] ?? ''),
        ];
    }

    private function defaultCanalIcone(string $slug): array
    {
        return [
            'src'    => get_template_directory_uri() . '/app/contato/assets/hero-icon-' . $slug . '.svg',
            'alt'    => '',
            'width'  => 52,
            'height' => 52,
            'sizes'  => [],
        ];
    }

    private function defaultCanais(): array
    {
        return [
            [
                'icone'  => $this->defaultCanalIcone('whatsapp-clientes'),
                'titulo' => 'Whatsapp clientes',
                'valor'  => '11 9 8986-9421',
                'link'   => 'https://wa.me/5511989869421',
            ],
            [
                'icone'  => $this->defaultCanalIcone('whatsapp-veterinarios'),
                'titulo' => 'Whatsapp Veterinários',
                'valor'  => '11 9 7924-0900',
                'link'   => 'https://wa.me/5511979240900',
            ],
            [
                'icone'  => $this->defaultCanalIcone('email'),
                'titulo' => 'Email',
                'valor'  => 'contato@genomavet.com.br',
                'link'   => 'mailto:contato@genomavet.com.br',
            ],
        ];
    }

    /**
     * Monta a "Lista de contatos" (cards com telefones/horários de atendimento
     * e cards de texto institucional). Se não houver linhas cadastradas no ACF
     * (flexible_content), retorna o conteúdo padrão (Figma).
     */
    private function listaContatos(int $pageId): array
    {
        $rows = $this->field($pageId, 'lista_contatos');

        if (empty($rows) || !is_array($rows)) {
            return $this->defaultListaContatos();
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
                    'icone' => $this->image($item['icone'] ?? null) ?? $this->defaultListaIcone('telefone'),
                    'texto' => (string) ($item['texto'] ?? ''),
                ],
                $itens
            ) : [],
        ];
    }

    private function defaultListaIcone(string $slug): array
    {
        return [
            'src'    => get_template_directory_uri() . '/app/contato/assets/lista-icon-' . $slug . '.svg',
            'alt'    => '',
            'width'  => 52,
            'height' => 52,
            'sizes'  => [],
        ];
    }

    private function defaultListaContatos(): array
    {
        return [
            [
                'tipo'      => 'info',
                'titulo'    => 'Atendimento ao Veterinário',
                'descricao' => 'Suporte técnico para discussão de casos, interpretação de exames e esclarecimento de dúvidas.',
                'itens'     => [
                    ['icone' => $this->defaultListaIcone('telefone'), 'texto' => "2537-4924 ou \n9 7824-0900"],
                    ['icone' => $this->defaultListaIcone('calendario'), 'texto' => 'Segunda a sexta-feira: das 9h às 19h'],
                    ['icone' => $this->defaultListaIcone('calendario'), 'texto' => "Sábado:\ndas 9h às 16h"],
                ],
            ],
            [
                'tipo'      => 'info',
                'titulo'    => 'Atendimento ao Responsável',
                'descricao' => 'Atendimento para informações gerais, exames, resultados e orientações.',
                'itens'     => [
                    ['icone' => $this->defaultListaIcone('casa'), 'texto' => 'Atendimento presencial'],
                    ['icone' => $this->defaultListaIcone('calendario'), 'texto' => "Segunda a sexta-feira:\ndas 9h às 19h"],
                    ['icone' => $this->defaultListaIcone('calendario'), 'texto' => "Sábado:\ndas 9h às 16h"],
                ],
            ],
            [
                'tipo'      => 'info',
                'titulo'    => 'Solicitação de retirada de amostras (Motoboy)',
                'descricao' => 'Atendimento para informações gerais, exames, resultados e orientações.',
                'itens'     => [
                    ['icone' => $this->defaultListaIcone('telefone'), 'texto' => "2537-4924 ou\n 9 7824-0900"],
                    ['icone' => $this->defaultListaIcone('calendario'), 'texto' => "Segunda a sexta-feira:\ndas 9h às 17h"],
                    ['icone' => $this->defaultListaIcone('calendario'), 'texto' => "Sábado:\n9h às 14h"],
                ],
            ],
            [
                'tipo'   => 'texto',
                'titulo' => 'Resultados de exames',
                'texto'  => 'Se você realizou exames no Genoma, o responsável  recebe um protocolo com usuário e senha para consultar os resultados diretamente em nosso site, de forma prática e segura. Caso tenha qualquer dificuldade de acesso, nossa equipe está à disposição para ajudar.',
                'botao'  => null,
            ],
            [
                'tipo'   => 'texto',
                'titulo' => 'Quer ser nosso parceiro?',
                'texto'  => 'Se você é médico-veterinário e deseja se tornar conveniado ao Genoma, fale com a nossa equipe e conheça as vantagens e condições especiais.',
                'botao'  => [
                    'texto' => 'Cadastrar como conveniado',
                    'link'  => '#',
                ],
            ],
        ];
    }
}
