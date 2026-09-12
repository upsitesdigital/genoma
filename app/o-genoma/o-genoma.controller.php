<?php
declare(strict_types=1);

namespace App\OGenoma;

use Core\Framework\Controller;
use Core\Framework\Attributes\Get;
use Core\Framework\Attributes\Cache;

final class OGenomaController extends Controller
{
    #[Get('/o-genoma')]
    #[Get('/o-genoma/:id')]
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
     * Se não houver linhas cadastradas no ACF, retorna o conteúdo padrão (Figma) com assets locais.
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
            'eyebrow'  => (string) ($row['eyebrow'] ?? ''),
            'titulo'   => (string) ($row['titulo'] ?? ''),
            'imagem1'  => $this->image($row['imagem_1'] ?? null) ?? $this->defaultImagem1(),
            'imagem2'  => $this->image($row['imagem_2'] ?? null) ?? $this->defaultImagem2(),
            'destaque' => (string) ($row['destaque'] ?? ''),
        ];
    }

    private function defaultSlide(): array
    {
        return [
            'eyebrow'  => 'O Genoma',
            'titulo'   => 'Laboratório especializado em análises laboratoriais veterinárias',
            'imagem1'  => $this->defaultImagem1(),
            'imagem2'  => $this->defaultImagem2(),
            'destaque' => 'Criado para apoiar médicos-veterinários na tomada de decisões clínicas com precisão, agilidade e confiabilidade.',
        ];
    }

    private function defaultImagem1(): array
    {
        return [
            'src'    => get_template_directory_uri() . '/app/o-genoma/assets/hero-veterinario-colo-pet.png',
            'alt'    => 'Médico-veterinário cuidando de um pet no colo',
            'width'  => 314,
            'height' => 390,
            'sizes'  => [],
        ];
    }

    private function defaultImagem2(): array
    {
        return [
            'src'    => get_template_directory_uri() . '/app/o-genoma/assets/hero-mulher-abraco-cachorro-471fe4.png',
            'alt'    => 'Mulher abraçando seu cachorro de perto',
            'width'  => 873,
            'height' => 390,
            'sizes'  => [],
        ];
    }
}
