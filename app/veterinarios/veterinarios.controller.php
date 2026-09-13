<?php
declare(strict_types=1);

namespace App\Veterinarios;

use Core\Framework\Controller;
use Core\Framework\Attributes\Get;
use Core\Framework\Attributes\Cache;

final class VeterinariosController extends Controller
{
    #[Get('/veterinarios')]
    #[Get('/veterinarios/:id')]
    #[Cache(ttl: 300)]
    public function index(\WP_REST_Request $request): array
    {
        $pageId = (int) ($request->get_param('id') ?: 0);

        return [
            'hero'        => $this->heroSlides($pageId),
            'suporte'     => $this->suporte($pageId),
            'praticidade' => $this->praticidade($pageId),
            'exames'      => $this->exames($pageId),
            'estrutura'   => $this->estrutura($pageId),
            'beneficios'  => $this->beneficios($pageId),
            'footerCta'   => $this->footerCta($pageId),
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
            'showSecondary'  => (bool) ($this->field($pageId, 'footer_cta_mostrar_secundario') ?? true),
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

        $itens = is_array($itens)
            ? array_map(
                fn (array $row): array => [
                    'icone' => $this->image($row['icone'] ?? null),
                    'texto' => (string) ($row['texto'] ?? ''),
                ],
                $itens
            )
            : [];

        return [
            'eyebrow' => (string) ($this->field($pageId, 'beneficios_eyebrow') ?: ''),
            'titulo'  => (string) ($this->field($pageId, 'beneficios_titulo') ?: ''),
            'texto'   => (string) ($this->field($pageId, 'beneficios_texto') ?: ''),
            'itens'   => $itens,
        ];
    }

    /**
     * Monta os dados da seção Estrutura.
     */
    private function estrutura(int $pageId): array
    {
        $contatos = $this->field($pageId, 'estrutura_contatos');

        $contatos = is_array($contatos)
            ? array_map(
                fn (array $row): array => [
                    'icone' => $this->image($row['icone'] ?? null),
                    'texto' => (string) ($row['texto'] ?? ''),
                ],
                $contatos
            )
            : [];

        return [
            'eyebrow'  => (string) ($this->field($pageId, 'estrutura_eyebrow') ?: ''),
            'titulo'   => (string) ($this->field($pageId, 'estrutura_titulo') ?: ''),
            'texto'    => (string) ($this->field($pageId, 'estrutura_texto') ?: ''),
            'destaque' => (string) ($this->field($pageId, 'estrutura_destaque') ?: ''),
            'imagem1'  => $this->image($this->field($pageId, 'estrutura_imagem_1')),
            'imagem2'  => $this->image($this->field($pageId, 'estrutura_imagem_2')),
            'contatos' => $contatos,
        ];
    }

    /**
     * Monta os dados da seção Exames.
     */
    private function exames(int $pageId): array
    {
        return [
            'eyebrow' => (string) ($this->field($pageId, 'exames_eyebrow') ?: ''),
            'titulo'  => (string) ($this->field($pageId, 'exames_titulo') ?: ''),
            'texto'   => (string) ($this->field($pageId, 'exames_texto') ?: ''),
            'cta'     => [
                'texto' => (string) ($this->field($pageId, 'exames_cta_texto') ?: ''),
                'link'  => (string) ($this->field($pageId, 'exames_cta_link') ?: ''),
            ],
            'imagem'  => $this->image($this->field($pageId, 'exames_imagem')),
        ];
    }

    /**
     * Monta os dados da seção Praticidade.
     */
    private function praticidade(int $pageId): array
    {
        $horarios = $this->field($pageId, 'praticidade_horarios');

        $horarios = is_array($horarios)
            ? array_values(array_filter(array_map(
                fn (array $row): string => (string) ($row['texto'] ?? ''),
                $horarios
            )))
            : [];

        return [
            'eyebrow'  => (string) ($this->field($pageId, 'praticidade_eyebrow') ?: ''),
            'titulo'   => (string) ($this->field($pageId, 'praticidade_titulo') ?: ''),
            'texto'    => (string) ($this->field($pageId, 'praticidade_texto') ?: ''),
            'icone'    => $this->image($this->field($pageId, 'praticidade_icone')),
            'horarios' => $horarios,
            'imagem'   => $this->image($this->field($pageId, 'praticidade_imagem')),
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
     */
    private function heroSlides(int $pageId): array
    {
        $rows = $this->field($pageId, 'hero_slides');

        $rows = is_array($rows)
            ? array_map(fn (array $row): array => $this->mapSlide($row), $rows)
            : [];

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
