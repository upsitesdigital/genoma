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
            'hero'        => $this->heroSlides($pageId),
            'sobre'       => $this->sobre($pageId),
            'compromisso' => $this->compromisso($pageId),
            'diagnostico' => $this->diagnostico($pageId),
            'footerCta'   => $this->footerCta($pageId),
        ];
    }

    /**
     * Monta a seção "Diaginostico" (nome mantido do YAML, posição 4, última seção).
     */
    private function diagnostico(int $pageId): array
    {
        return [
            'titulo'   => (string) ($this->field($pageId, 'diagnostico_titulo') ?: ''),
            'texto'    => (string) ($this->field($pageId, 'diagnostico_texto') ?: ''),
            'destaque' => (string) ($this->field($pageId, 'diagnostico_destaque') ?: ''),
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
     * Monta a seção "Sobre nós".
     */
    private function sobre(int $pageId): array
    {
        return [
            'eyebrow'  => (string) ($this->field($pageId, 'sobre_eyebrow') ?: ''),
            'titulo'   => (string) ($this->field($pageId, 'sobre_titulo') ?: ''),
            'texto'    => (string) ($this->field($pageId, 'sobre_texto') ?: ''),
            'destaque' => (string) ($this->field($pageId, 'sobre_destaque') ?: ''),
        ];
    }

    /**
     * Monta a seção "Compromisso" (posição 3, banner com imagem de fundo).
     */
    private function compromisso(int $pageId): array
    {
        return [
            'eyebrow' => (string) ($this->field($pageId, 'compromisso_eyebrow') ?: ''),
            'titulo'  => (string) ($this->field($pageId, 'compromisso_titulo') ?: ''),
            'texto'   => (string) ($this->field($pageId, 'compromisso_texto') ?: ''),
            'imagem'  => $this->image($this->field($pageId, 'compromisso_imagem')),
        ];
    }

    /**
     * Monta os slides do carrossel do Hero.
     */
    private function heroSlides(int $pageId): array
    {
        $rows = $this->field($pageId, 'hero_slides');

        return [
            'slides'    => is_array($rows) ? array_map(fn (array $row): array => $this->mapSlide($row), $rows) : [],
            'autoplay'  => (bool) ($this->field($pageId, 'hero_autoplay') ?? true),
            'intervalo' => (int) ($this->field($pageId, 'hero_intervalo') ?: 6000),
        ];
    }

    private function mapSlide(array $row): array
    {
        return [
            'eyebrow'  => (string) ($row['eyebrow'] ?? ''),
            'titulo'   => (string) ($row['titulo'] ?? ''),
            'imagem1'  => $this->image($row['imagem_1'] ?? null),
            'imagem2'  => $this->image($row['imagem_2'] ?? null),
            'destaque' => (string) ($row['destaque'] ?? ''),
        ];
    }
}
