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
            'hero' => $this->heroSlides($pageId),
        ];
    }

    /**
     * Monta os slides do carrossel do Hero.
     * Se não houver linhas cadastradas no ACF, retorna o conteúdo padrão (Figma) com asset local.
     */
    private function heroSlides(int $pageId): array
    {
        $rows = $this->field($pageId, 'hero_slides');

        if (empty($rows) || !is_array($rows)) {
            $rows = [$this->defaultSlide()];
        } else {
            $rows = array_map(fn (array $row): array => $this->mapSlide($row), $rows);
        }

        return [
            'slides'    => $rows,
            'autoplay'  => (bool) ($this->field($pageId, 'hero_autoplay') ?? true),
            'intervalo' => (int) ($this->field($pageId, 'hero_intervalo') ?: 6000),
        ];
    }

    private function mapSlide(array $row): array
    {
        return [
            'imagem'      => $this->image($row['imagem'] ?? null) ?? $this->defaultImage(),
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

    private function defaultSlide(): array
    {
        return [
            'imagem'    => $this->defaultImage(),
            'eyebrow'   => 'Responsável',
            'titulo'    => 'Cuidado, confiança e diagnóstico preciso para o seu pet',
            'subtitulo' => 'Suporte técnico, agilidade e condições especiais para médicos-veterinários que buscam excelência no cuidado.',
            'ctaPrimario' => [
                'texto' => 'Fale conosco',
                'link'  => '#',
            ],
            'ctaSecundario' => [
                'texto' => 'Nossos serviços',
                'link'  => '#',
            ],
        ];
    }

    private function defaultImage(): array
    {
        return [
            'src'    => get_template_directory_uri() . '/app/responsavel/assets/hero-tutor-gato.webp',
            'alt'    => 'Tutor sorrindo segurando um gato no colo',
            'width'  => 1352,
            'height' => 1319,
            'sizes'  => [],
        ];
    }
}
