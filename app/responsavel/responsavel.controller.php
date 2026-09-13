<?php
declare(strict_types=1);

namespace App\Responsavel;

use Core\Framework\Controller;
use Core\Framework\Attributes\Get;
use Core\Framework\Attributes\Cache;

final class ResponsavelController extends Controller
{
    #[Get('/responsavel')]
    #[Get('/responsavel/:id')]
    #[Cache(ttl: 300)]
    public function index(\WP_REST_Request $request): array
    {
        $pageId = (int) ($request->get_param('id') ?: 0);

        return [
            'hero'       => $this->heroSlides($pageId),
            'suporte'    => $this->suporte($pageId),
            'planos'     => $this->planos($pageId),
            'exames'     => $this->exames($pageId),
            'servicos'   => $this->servicos($pageId),
            'resultados' => $this->resultados($pageId),
            'beneficios' => $this->beneficios($pageId),
            'footerCta'  => $this->footerCta($pageId),
        ];
    }

    /**
     * Sobrescreve o CTA do banner do rodapé só nesta página (Footer.tsx cai
     * pro padrão de Opções do Tema quando os campos ficam em branco).
     */
    private function footerCta(int $pageId): array
    {
        return [
            'titulo'         => (string) ($this->field($pageId, 'footer_cta_titulo') ?: ''),
            'primaryLabel'   => (string) ($this->field($pageId, 'footer_cta_primario_texto') ?: ''),
            'primaryUrl'     => (string) ($this->field($pageId, 'footer_cta_primario_link') ?: ''),
            'showSecondary'  => (bool) ($this->field($pageId, 'footer_cta_mostrar_secundario') ?? false),
            'secondaryLabel' => (string) ($this->field($pageId, 'footer_cta_secundario_texto') ?: ''),
            'secondaryUrl'   => (string) ($this->field($pageId, 'footer_cta_secundario_link') ?: ''),
        ];
    }

    /**
     * Monta os dados da seção Benefícios.
     */
    private function beneficios(int $pageId): array
    {
        $itens = $this->field($pageId, 'beneficios_itens');

        $itens = is_array($itens) ? array_map(
            fn (array $row): array => [
                'icone' => $this->image($row['icone'] ?? null),
                'texto' => (string) ($row['texto'] ?? ''),
            ],
            $itens
        ) : [];

        return [
            'eyebrow' => (string) ($this->field($pageId, 'beneficios_eyebrow') ?: ''),
            'titulo'  => (string) ($this->field($pageId, 'beneficios_titulo') ?: ''),
            'texto'   => (string) ($this->field($pageId, 'beneficios_texto') ?: ''),
            'itens'   => $itens,
        ];
    }

    /**
     * Monta os dados da seção Resultados dos exames.
     */
    private function resultados(int $pageId): array
    {
        return [
            'eyebrow' => (string) ($this->field($pageId, 'resultados_eyebrow') ?: ''),
            'titulo'  => (string) ($this->field($pageId, 'resultados_titulo') ?: ''),
            'texto'   => (string) ($this->field($pageId, 'resultados_texto') ?: ''),
            'imagem'  => $this->image($this->field($pageId, 'resultados_imagem')),
        ];
    }

    /**
     * Monta os dados da seção Serviços (categorias de exames em accordion).
     */
    private function servicos(int $pageId): array
    {
        $rows = $this->field($pageId, 'servicos_categorias');

        $rows = is_array($rows) ? array_map(fn (array $row): array => $this->mapServicosCategoria($row), $rows) : [];

        return [
            'eyebrow'    => (string) ($this->field($pageId, 'servicos_eyebrow') ?: ''),
            'titulo'     => (string) ($this->field($pageId, 'servicos_titulo') ?: ''),
            'texto'      => (string) ($this->field($pageId, 'servicos_texto') ?: ''),
            'categorias' => $rows,
        ];
    }

    private function mapServicosCategoria(array $row): array
    {
        $exames = $row['exames'] ?? [];

        return [
            'nome'      => (string) ($row['nome'] ?? ''),
            'aberto'    => (bool) ($row['aberto'] ?? false),
            'descricao' => (string) ($row['descricao'] ?? ''),
            'exames'    => is_array($exames) ? array_map(fn (array $exame): array => [
                'nome'    => (string) ($exame['nome'] ?? ''),
                'prazo'   => (string) ($exame['prazo'] ?? ''),
                'amostra' => (string) ($exame['amostra'] ?? ''),
            ], $exames) : [],
        ];
    }

    /**
     * Monta os dados da seção Exames (preparação/jejum para exames).
     */
    private function exames(int $pageId): array
    {
        $rows = $this->field($pageId, 'exames_itens');

        $rows = is_array($rows) ? array_map(fn (array $row): array => [
            'icone'    => $this->image($row['icone'] ?? null),
            'texto'    => (string) ($row['texto'] ?? ''),
            'destaque' => (string) ($row['destaque'] ?? ''),
        ], $rows) : [];

        return [
            'eyebrow' => (string) ($this->field($pageId, 'exames_eyebrow') ?: ''),
            'titulo'  => (string) ($this->field($pageId, 'exames_titulo') ?: ''),
            'texto'   => (string) ($this->field($pageId, 'exames_texto') ?: ''),
            'itens'   => $rows,
            'rodape'  => (string) ($this->field($pageId, 'exames_rodape') ?: ''),
            'imagem'  => $this->image($this->field($pageId, 'exames_imagem')),
        ];
    }

    /**
     * Monta os dados da seção Planos (logos dos planos de saúde atendidos).
     */
    private function planos(int $pageId): array
    {
        $rows = $this->field($pageId, 'planos_logos');

        $rows = is_array($rows) ? array_map(fn (array $row): array => [
            'imagem' => $this->image($row['imagem'] ?? null),
            'nome'   => (string) ($row['nome'] ?? ''),
        ], $rows) : [];

        return [
            'eyebrow' => (string) ($this->field($pageId, 'planos_eyebrow') ?: ''),
            'titulo'  => (string) ($this->field($pageId, 'planos_titulo') ?: ''),
            'logos'   => $rows,
        ];
    }

    /**
     * Monta os dados da seção Suporte.
     */
    private function suporte(int $pageId): array
    {
        return [
            'eyebrow' => (string) ($this->field($pageId, 'suporte_eyebrow') ?: ''),
            'titulo'  => (string) ($this->field($pageId, 'suporte_titulo') ?: ''),
            'texto'   => (string) ($this->field($pageId, 'suporte_texto') ?: ''),
            'quote'   => (string) ($this->field($pageId, 'suporte_quote') ?: ''),
            'imagem1' => $this->image($this->field($pageId, 'suporte_imagem_1')),
            'imagem2' => $this->image($this->field($pageId, 'suporte_imagem_2')),
        ];
    }

    /**
     * Monta os slides do carrossel do Hero.
     * Se não houver linhas cadastradas no ACF, a seção fica sem slides ([]).
     */
    private function heroSlides(int $pageId): array
    {
        $rows = $this->field($pageId, 'hero_slides');

        $rows = is_array($rows) ? array_map(fn (array $row): array => $this->mapSlide($row), $rows) : [];

        return [
            'slides'    => $rows,
            'autoplay'  => (bool) ($this->field($pageId, 'hero_autoplay') ?? true),
            'intervalo' => (int) ($this->field($pageId, 'hero_intervalo') ?: 6000),
        ];
    }

    private function mapSlide(array $row): array
    {
        return [
            'imagem'      => $this->image($row['imagem'] ?? null),
            'eyebrow'     => (string) ($row['eyebrow'] ?? ''),
            'titulo'      => (string) ($row['titulo'] ?? ''),
            'subtitulo'   => (string) ($row['subtitulo'] ?? ''),
            'ctaPrimario' => [
                'texto' => (string) ($row['cta_primario_texto'] ?? ''),
                'link'  => (string) ($row['cta_primario_link'] ?? ''),
            ],
            'ctaSecundario' => [
                'texto' => (string) ($row['cta_secundario_texto'] ?? ''),
                'link'  => (string) ($row['cta_secundario_link'] ?? ''),
            ],
        ];
    }
}
