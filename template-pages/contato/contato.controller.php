<?php
declare(strict_types=1);

namespace App\Contato;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

use Core\Framework\Controller;
use Core\Framework\Attributes\Cache;

/**
 * Contato — porte do app/contato/contato.controller.php do tema React: mesmos campos (acf_fields.php,
 * bloco contato) e mesmo formato do contato.schema.ts, para o markup ser portado 1:1.
 *
 * Padrões (campo nunca salvo) = default_value de cada campo em acf_fields.php — no React o
 * get_field() já devolvia o default_value nesse caso. O repeater e o flexible não têm linhas padrão.
 *
 * Diferenças em relação ao React (intencionais):
 * - a lista de cards sai em 'lista' => ['cards' => …] (React: 'listaContatos' => […]): a chave da
 *   seção é o segmento dos campos (field_contato_lista_*), com o toggle lista_ocultar;
 * - texto salvo vazio → null (o React devolvia ''; o elemento não aparece);
 * - links passam por esc_url_raw ("/caminho" vira URL do site; javascript:/data: somem) e vazio →
 *   null: o card do hero fica sem link (como no React com o campo vazio) e o botão usa "#";
 * - botão do card de texto sem texto → null (o React mostrava um botão vazio);
 * - sem 'footerCta': o banner do rodapé é lido pelo layout/footer (footer_cta_* da página).
 */
final class ContatoController extends Controller
{
    private const HERO_DESCRICAO = 'Estamos prontos para atender você e esclarecer dúvidas sobre exames, resultados, convênios e parcerias.';

    /**
     * Dados da página — chamado direto em PHP pelo View::render (sem REST).
     * Cada seção passa por section(): "Ocultar seção" marcado → null (o Part não renderiza).
     */
    #[Cache(ttl: 300)]
    public function page(int $pageId): array
    {
        return [
            'hero'  => $this->section($pageId, 'hero', fn(): array => $this->hero($pageId)),
            'lista' => $this->section($pageId, 'lista', fn(): array => $this->lista($pageId)),
        ];
    }

    /** Etiqueta + título + descrição + cards de canais (WhatsApp, e-mail…). */
    private function hero(int $pageId): array
    {
        $rows = $this->rows($pageId, 'hero_canais') ?? [];

        return [
            'eyebrow'   => $this->text($pageId, 'hero_eyebrow', 'Contato'),
            'titulo'    => $this->text($pageId, 'hero_titulo', 'Fale com o Genoma Diagnóstico Veterinário'),
            'descricao' => $this->text($pageId, 'hero_descricao', self::HERO_DESCRICAO),
            'canais'    => array_map(fn(array $row): array => [
                'icone'  => $this->image($row['icone'] ?? null),
                'titulo' => $this->textFrom($row, 'titulo'),
                'valor'  => $this->textFrom($row, 'valor'),
                'link'   => $this->url($this->textFrom($row, 'link')),
            ], $rows),
        ];
    }

    /**
     * Cards abaixo do hero (flexible content): card_info (título, descrição e itens ícone + texto)
     * e card_texto (título, texto e botão opcional). Sem cards a seção some (como no React).
     */
    private function lista(int $pageId): array
    {
        $rows = $this->rows($pageId, 'lista_contatos') ?? [];

        return ['cards' => array_map(fn(array $row): array => $this->card($row), $rows)];
    }

    /** Uma linha do flexible: layout card_texto → tipo 'texto'; qualquer outro → 'info' (como no React). */
    private function card(array $row): array
    {
        if (($row['acf_fc_layout'] ?? '') === 'card_texto') {
            $botaoTexto = $this->textFrom($row, 'botao_texto');

            return [
                'tipo'   => 'texto',
                'titulo' => $this->textFrom($row, 'titulo'),
                'texto'  => $this->textFrom($row, 'texto'),
                'botao'  => !empty($row['mostrar_botao']) && $botaoTexto !== null
                    ? ['texto' => $botaoTexto, 'link' => $this->url($this->textFrom($row, 'botao_link'))]
                    : null,
            ];
        }

        $itens = is_array($row['itens'] ?? null) ? array_values($row['itens']) : [];

        return [
            'tipo'      => 'info',
            'titulo'    => $this->textFrom($row, 'titulo'),
            'descricao' => $this->textFrom($row, 'descricao'),
            'itens'     => array_map(fn(array $item): array => [
                'icone' => $this->image($item['icone'] ?? null),
                'texto' => $this->textFrom($item, 'texto'), // textarea: quebras de linha mantidas (whitespace-pre-line)
            ], $itens),
        ];
    }

    /** URL digitada num campo de texto: "/caminho" vira URL do site; javascript:/data: somem (esc_url_raw). */
    private function url(?string $url): ?string
    {
        $url = trim((string) $url);
        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) $url = home_url($url);
        $url = $url === '' ? '' : esc_url_raw($url);

        return $url !== '' ? $url : null;
    }
}
