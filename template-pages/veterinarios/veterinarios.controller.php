<?php
declare(strict_types=1);

namespace App\Veterinarios;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

use Core\Framework\Controller;
use Core\Framework\Attributes\Cache;

/**
 * Veterinários — porte do app/veterinarios/veterinarios.controller.php do tema React: mesmos campos
 * (acf_fields.php, bloco veterinarios) e mesmo formato do veterinarios.schema.ts (chaves iguais),
 * para o markup ser portado 1:1.
 *
 * Padrões (campo nunca salvo) = default_value de cada campo em acf_fields.php — no React o
 * get_field() já devolvia o default_value nesse caso. Imagens e ícones não têm padrão (como no React).
 *
 * Diferenças de semântica em relação ao React (intencionais):
 * - texto salvo vazio → null (o React devolvia ''; nos dois o elemento não aparece);
 * - CTAs continuam {texto, link} de dois campos de texto (legado, não Fields::button): link
 *   passa por esc_url_raw e vazio → null (o React mandava '' e o view usava '#');
 * - sem 'footerCta': o banner do rodapé é lido pelo layout/footer (footer_cta_* da página).
 */
final class VeterinariosController extends Controller
{
    private const SUPORTE_TITULO = "10 anos de atuação e mais de\n150 mil exames realizados.";

    private const SUPORTE_TEXTO = 'No Genoma Diagnóstico Veterinário, atuamos lado a lado com o médico-veterinário, oferecendo suporte técnico, agilidade operacional e condições especiais para quem busca excelência no cuidado com seus pacientes.';

    private const SUPORTE_QUOTE = 'Somos um laboratório preparado para atender clínicas e hospitais veterinários com eficiência, confiança e proximidade.';

    private const PRATICIDADE_TEXTO = "Para garantir praticidade e segurança no envio das amostras, contamos com serviço de coleta por motoboy\nnos seguintes horários:";

    private const TIPOS_EXAMES_DESCRICAO = 'O Genoma oferece um portfólio completo de exames, organizado nas seguintes áreas:';

    private const ESTRUTURA_TEXTO = 'O Genoma oferece suporte direto com corpo veterinário, auxiliando na discussão de casos clínicos, interpretação de resultados e esclarecimento de dúvidas.';

    private const BENEFICIOS_TEXTO = 'Ao se tornar um veterinário conveniado ao Genoma, você tem acesso a vantagens exclusivas:';

    /**
     * Dados da página — chamado direto em PHP pelo View::render (sem REST).
     * Cada seção passa por section(): "Ocultar seção" marcado → null (o Part não renderiza).
     */
    #[Cache(ttl: 300)]
    public function page(int $pageId): array
    {
        return [
            'hero'        => $this->section($pageId, 'hero', fn(): array => $this->hero($pageId)),
            'suporte'     => $this->section($pageId, 'suporte', fn(): array => $this->suporte($pageId)),
            'praticidade' => $this->section($pageId, 'praticidade', fn(): array => $this->praticidade($pageId)),
            'tiposExames' => $this->section($pageId, 'tipos_exames', fn(): array => $this->tiposExames($pageId)),
            'estrutura'   => $this->section($pageId, 'estrutura', fn(): array => $this->estrutura($pageId)),
            'beneficios'  => $this->section($pageId, 'beneficios', fn(): array => $this->beneficios($pageId)),
        ];
    }

    /**
     * Carrossel do topo. Sem slides cadastrados (ou nunca salvo) a lista fica vazia e o hero some,
     * como no React (o repeater não tem linhas padrão).
     */
    private function hero(int $pageId): array
    {
        $rows = $this->rows($pageId, 'hero_slides') ?? [];

        return [
            'slides'    => array_map(fn(array $row): array => [
                'imagem'        => $this->image($row['imagem'] ?? null),
                'eyebrow'       => $this->textFrom($row, 'eyebrow'),
                'titulo'        => $this->textFrom($row, 'titulo'), // textarea: cada linha vira um <span> em bloco
                'subtitulo'     => $this->textFrom($row, 'subtitulo'),
                'ctaPrimario'   => $this->cta($this->textFrom($row, 'cta_primario_texto'), $this->textFrom($row, 'cta_primario_link')),
                'ctaSecundario' => $this->cta($this->textFrom($row, 'cta_secundario_texto'), $this->textFrom($row, 'cta_secundario_link')),
            ], $rows),
            'autoplay'  => $this->flag($pageId, 'hero_autoplay', true),
            'intervalo' => $this->number($pageId, 'hero_intervalo', 6000),
        ];
    }

    /** Título + texto, frase entre duas linhas e duas fotos lado a lado. */
    private function suporte(int $pageId): array
    {
        return [
            'eyebrow' => $this->text($pageId, 'suporte_eyebrow', 'Suporte técnico'),
            'titulo'  => $this->text($pageId, 'suporte_titulo', self::SUPORTE_TITULO), // textarea: cada linha vira um <span> em bloco
            'texto'   => $this->text($pageId, 'suporte_texto', self::SUPORTE_TEXTO),
            'quote'   => $this->text($pageId, 'suporte_quote', self::SUPORTE_QUOTE),
            'imagem1' => $this->picture($pageId, 'suporte_imagem_1'),
            'imagem2' => $this->picture($pageId, 'suporte_imagem_2'),
        ];
    }

    /** Card roxo "Coleta de Amostras": texto, quadro de horários (com ícone) e foto. */
    private function praticidade(int $pageId): array
    {
        $rows = $this->rows($pageId, 'praticidade_horarios') ?? [];

        return [
            'eyebrow'  => $this->text($pageId, 'praticidade_eyebrow', 'Praticidade'),
            'titulo'   => $this->text($pageId, 'praticidade_titulo', 'Coleta de Amostras'),
            'texto'    => $this->text($pageId, 'praticidade_texto', self::PRATICIDADE_TEXTO), // textarea: quebras de linha mantidas (whitespace-pre-line)
            'icone'    => $this->picture($pageId, 'praticidade_icone'),
            // linhas vazias saem da lista (como no React); sem horários o quadro some
            'horarios' => array_values(array_filter(array_map(fn(array $row): ?string => $this->textFrom($row, 'texto'), $rows))),
            'imagem'   => $this->picture($pageId, 'praticidade_imagem'),
        ];
    }

    /**
     * Acordeão de categorias de exames (3 visíveis + "Ver mais"). Sem categorias a seção some,
     * como no React (o repeater não tem linhas padrão).
     */
    private function tiposExames(int $pageId): array
    {
        $rows = $this->rows($pageId, 'tipos_exames_categorias') ?? [];

        return [
            'eyebrow'    => $this->text($pageId, 'tipos_exames_eyebrow', 'Exames'),
            'titulo'     => $this->text($pageId, 'tipos_exames_titulo', 'Tipos de exames realizados'),
            'descricao'  => $this->text($pageId, 'tipos_exames_descricao', self::TIPOS_EXAMES_DESCRICAO),
            'categorias' => array_map(fn(array $row): array => [
                'titulo' => $this->textFrom($row, 'titulo'),
                'texto'  => $this->textFrom($row, 'texto'),
                'itens'  => array_map(fn(array $item): array => [
                    'nome'    => $this->textFrom($item, 'nome'),
                    'prazo'   => $this->textFrom($item, 'prazo'),
                    'amostra' => $this->textFrom($item, 'amostra'),
                ], is_array($row['itens'] ?? null) ? array_values($row['itens']) : []),
            ], $rows),
            // botão abaixo do acordeão; com mais de 3 categorias o view mostra "Ver mais" no lugar (VerMaisButton do React)
            'cta'        => $this->cta(
                $this->text($pageId, 'tipos_exames_cta_texto', 'Ver todos exames'),
                $this->text($pageId, 'tipos_exames_cta_link', '#'),
            ),
        ];
    }

    /** Título + texto, frase em destaque à direita e grade com 2 fotos + card de contato. */
    private function estrutura(int $pageId): array
    {
        $rows = $this->rows($pageId, 'estrutura_contatos') ?? [];

        return [
            'eyebrow'  => $this->text($pageId, 'estrutura_eyebrow', 'Estrutura / Tecnologia'),
            'titulo'   => $this->text($pageId, 'estrutura_titulo', 'Suporte Técnico ao Veterinário'), // textarea: cada linha vira um <span> em bloco
            'texto'    => $this->text($pageId, 'estrutura_texto', self::ESTRUTURA_TEXTO),
            'destaque' => $this->text($pageId, 'estrutura_destaque', 'Nosso objetivo é ser um apoio real no seu raciocínio clínico.'),
            'imagem1'  => $this->picture($pageId, 'estrutura_imagem_1'),
            'imagem2'  => $this->picture($pageId, 'estrutura_imagem_2'),
            'contatos' => array_map(fn(array $row): array => [
                'icone' => $this->image($row['icone'] ?? null),
                'texto' => $this->textFrom($row, 'texto'), // textarea: quebras de linha mantidas (whitespace-pre-line)
            ], $rows),
        ];
    }

    /** Título + texto e cards com ícone. Sem cards só o cabeçalho aparece (como no React). */
    private function beneficios(int $pageId): array
    {
        $rows = $this->rows($pageId, 'beneficios_itens') ?? [];

        return [
            'eyebrow' => $this->text($pageId, 'beneficios_eyebrow', 'Benefícios'),
            'titulo'  => $this->text($pageId, 'beneficios_titulo', 'Benefícios para Veterinários Conveniados'),
            'texto'   => $this->text($pageId, 'beneficios_texto', self::BENEFICIOS_TEXTO),
            'itens'   => array_map(fn(array $row): array => [
                'icone' => $this->image($row['icone'] ?? null),
                'texto' => $this->textFrom($row, 'texto'),
            ], $rows),
        ];
    }

    /**
     * CTA legado do React: dois campos de texto (texto + link), não Fields::button.
     * Sem texto o botão não aparece (como no React); link null → o view usa "#" (como no React).
     *
     * @return array{texto: string|null, link: string|null}
     */
    private function cta(?string $texto, ?string $link): array
    {
        return ['texto' => $texto, 'link' => $this->url($link)];
    }

    /** URL digitada num campo de texto: "/caminho" vira URL do site; javascript:/data: somem (esc_url_raw). */
    private function url(?string $url): ?string
    {
        $url = trim((string) $url);
        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) $url = home_url($url);
        $url = $url === '' ? '' : esc_url_raw($url);

        return $url !== '' ? $url : null;
    }

    /**
     * Opção do carrossel (true_false) — configuração, não conteúdo: nunca salva → o default_value do
     * campo; salva → o valor (React: `field ?? true`).
     */
    private function flag(int $pageId, string $name, bool $default): bool
    {
        return $this->saved($pageId, $name) ? (bool) $this->field($pageId, $name) : $default;
    }

    /**
     * Intervalo do carrossel em ms — configuração: vazio ou 0 → o padrão (React: `field ?: 6000`).
     * O mínimo de 2000 ms fica no store, como no view do React (Math.max(intervalo, 2000)).
     */
    private function number(int $pageId, string $name, int $default): int
    {
        return (int) $this->field($pageId, $name) ?: $default;
    }
}
