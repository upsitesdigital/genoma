<?php
declare(strict_types=1);

namespace App\Responsavel;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

use Core\Framework\Controller;
use Core\Framework\Attributes\Cache;

/**
 * Responsável — porte do app/responsavel/responsavel.controller.php do tema React: mesmos campos
 * (acf_fields.php, bloco responsavel) e mesmo formato do responsavel.schema.ts (chaves iguais),
 * para o markup ser portado 1:1.
 *
 * Padrões (campo nunca salvo) = default_value de cada campo em acf_fields.php — no React o
 * get_field() já devolvia o default_value nesse caso. Imagens e repeaters não têm padrão (como no React).
 *
 * Diferenças de semântica em relação ao React (intencionais):
 * - texto salvo vazio → null (o React devolvia ''; nos dois o elemento não aparece);
 * - CTAs do hero continuam {texto, link} de dois campos de texto (legado, não Fields::button): link
 *   passa por esc_url_raw e vazio → null (o React mandava '' e o view usava '#');
 * - o texto de Resultados já sai separado em parágrafos ('paragrafos'), como na Home;
 * - sem 'footerCta': o banner do rodapé é lido pelo layout/footer (footer_cta_* da página).
 */
final class ResponsavelController extends Controller
{
    private const SUPORTE_TEXTO = "Os exames são sempre solicitados por um médico-veterinário.\nO Genoma realiza as análises laboratoriais e fornece os resultados ao profissional responsável, que é quem fará a avaliação clínica e as orientações necessárias para o tratamento do animal.";

    private const EXAMES_RODAPE = 'Sempre siga também as orientações do médico-veterinário, pois em alguns casos podem existir recomendações específicas para o seu pet.';

    private const RESULTADOS_TEXTO = "Os resultados são liberados dentro dos prazos estabelecidos e enviados ao médico-veterinário solicitante, que é o profissional indicado para interpretar os exames e orientar o responsável sobre os próximos passos no cuidado com o pet.\n\nAlém disso, o responsável recebe um protocolo com usuário e senha para consultar os resultados diretamente em nosso site, de forma prática e segura.\n\nPara exames de rotina, a liberação costuma ser feita com agilidade, ajudando a acelerar o início do tratamento quando necessário.";

    /**
     * Dados da página — chamado direto em PHP pelo View::render (sem REST).
     * Cada seção passa por section(): "Ocultar seção" marcado → null (o Part não renderiza).
     */
    #[Cache(ttl: 300)]
    public function page(int $pageId): array
    {
        return [
            'hero'       => $this->section($pageId, 'hero', fn(): array => $this->hero($pageId)),
            'suporte'    => $this->section($pageId, 'suporte', fn(): array => $this->suporte($pageId)),
            'planos'     => $this->section($pageId, 'planos', fn(): array => $this->planos($pageId)),
            'exames'     => $this->section($pageId, 'exames', fn(): array => $this->exames($pageId)),
            'servicos'   => $this->section($pageId, 'servicos', fn(): array => $this->servicos($pageId)),
            'resultados' => $this->section($pageId, 'resultados', fn(): array => $this->resultados($pageId)),
            'beneficios' => $this->section($pageId, 'beneficios', fn(): array => $this->beneficios($pageId)),
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
                'titulo'        => $this->textFrom($row, 'titulo'),
                'subtitulo'     => $this->textFrom($row, 'subtitulo'),
                'ctaPrimario'   => $this->cta($this->textFrom($row, 'cta_primario_texto'), $this->textFrom($row, 'cta_primario_link')),
                'ctaSecundario' => $this->cta($this->textFrom($row, 'cta_secundario_texto'), $this->textFrom($row, 'cta_secundario_link')),
            ], $rows),
            'autoplay'  => $this->flag($pageId, 'hero_autoplay', true),
            'intervalo' => $this->number($pageId, 'hero_intervalo', 6000),
        ];
    }

    /** "Como funciona o atendimento": texto + frase entre linhas + 2 fotos. Sempre aparece (como no React). */
    private function suporte(int $pageId): array
    {
        return [
            'eyebrow' => $this->text($pageId, 'suporte_eyebrow', 'Suporte técnico'),
            'titulo'  => $this->text($pageId, 'suporte_titulo', 'Como funciona o atendimento ao Responsável?'),
            'texto'   => $this->text($pageId, 'suporte_texto', self::SUPORTE_TEXTO),
            'quote'   => $this->text($pageId, 'suporte_quote', 'Garantimos segurança, ética e um acompanhamento adequado em cada caso.'),
            'imagem1' => $this->picture($pageId, 'suporte_imagem_1'),
            'imagem2' => $this->picture($pageId, 'suporte_imagem_2'),
        ];
    }

    /** Logos dos planos de saúde pet atendidos. */
    private function planos(int $pageId): array
    {
        $rows = $this->rows($pageId, 'planos_logos') ?? [];

        return [
            'eyebrow' => $this->text($pageId, 'planos_eyebrow', 'Planos de saúde atendidos'),
            'titulo'  => $this->text($pageId, 'planos_titulo', 'O Genoma atende os seguintes planos de saúde pet:'),
            'logos'   => array_map(fn(array $row): array => [
                'imagem' => $this->image($row['imagem'] ?? null),
                'nome'   => $this->textFrom($row, 'nome'),
            ], $rows),
        ];
    }

    /** Preparação para exames (jejum): banner com foto de fundo e lista com ícones. Sempre aparece (como no React). */
    private function exames(int $pageId): array
    {
        $rows = $this->rows($pageId, 'exames_itens') ?? [];

        return [
            'eyebrow' => $this->text($pageId, 'exames_eyebrow', 'Exames'),
            'titulo'  => $this->text($pageId, 'exames_titulo', 'Preparação para exames: jejum'),
            'texto'   => $this->text($pageId, 'exames_texto', 'Para garantir resultados mais precisos, alguns exames exigem jejum alimentar:'),
            'itens'   => array_map(fn(array $row): array => [
                'icone'    => $this->image($row['icone'] ?? null),
                'texto'    => $this->textFrom($row, 'texto'), // sem trim: o espaço antes do destaque faz parte do texto
                'destaque' => $this->textFrom($row, 'destaque'),
            ], $rows),
            'rodape'  => $this->text($pageId, 'exames_rodape', self::EXAMES_RODAPE),
            'imagem'  => $this->picture($pageId, 'exames_imagem'),
        ];
    }

    /** Tipos de exames: categorias em acordeão com a tabela de exames (3 visíveis + "Ver mais"). */
    private function servicos(int $pageId): array
    {
        $rows = $this->rows($pageId, 'servicos_categorias') ?? [];

        return [
            'eyebrow'    => $this->text($pageId, 'servicos_eyebrow', 'Nossos Serviços'),
            'titulo'     => $this->text($pageId, 'servicos_titulo', 'Tipos de exames realizados'),
            'texto'      => $this->text($pageId, 'servicos_texto', 'O Genoma oferece um portfólio completo de exames, organizado nas seguintes áreas:'),
            'categorias' => array_map(fn(array $row): array => [
                'nome'      => $this->textFrom($row, 'nome'),
                'aberto'    => !empty($row['aberto']),
                'descricao' => $this->textFrom($row, 'descricao'),
                'exames'    => array_map(fn(array $exame): array => [
                    'nome'    => $this->textFrom($exame, 'nome'),
                    'prazo'   => $this->textFrom($exame, 'prazo'),
                    'amostra' => $this->textFrom($exame, 'amostra'),
                ], is_array($row['exames'] ?? null) ? array_values($row['exames']) : []),
            ], $rows),
        ];
    }

    /** Resultados dos exames: texto em parágrafos + foto. */
    private function resultados(int $pageId): array
    {
        return [
            'eyebrow'    => $this->text($pageId, 'resultados_eyebrow', 'Exames'),
            'titulo'     => $this->text($pageId, 'resultados_titulo', 'Resultados dos exames'),
            'paragrafos' => $this->paragrafos($this->text($pageId, 'resultados_texto', self::RESULTADOS_TEXTO)),
            'imagem'     => $this->picture($pageId, 'resultados_imagem'),
        ];
    }

    /** Cards de benefícios (ícone + texto) e frase de destaque abaixo deles. */
    private function beneficios(int $pageId): array
    {
        $rows = $this->rows($pageId, 'beneficios_itens') ?? [];

        return [
            'eyebrow' => $this->text($pageId, 'beneficios_eyebrow', 'Benefícios'),
            'titulo'  => $this->text($pageId, 'beneficios_titulo', 'Nosso compromisso com o seu pet'),
            'texto'   => $this->text($pageId, 'beneficios_texto', 'Tudo para garantir mais segurança e cuidado com a saúde do seu melhor amigo 💙'),
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

    /** Textarea com parágrafos separados por linha em branco → lista (mesma regra do React). */
    private function paragrafos(?string $texto): array
    {
        if ($texto === null) return [];

        return array_values(array_filter(array_map('trim', preg_split('/\R\s*\R/', $texto) ?: [])));
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
