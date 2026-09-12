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
            'titulo'   => (string) ($this->field($pageId, 'diagnostico_titulo') ?: 'Diagnóstico que gera confiança'),
            'texto'    => (string) ($this->field($pageId, 'diagnostico_texto') ?: "Acreditamos que um diagnóstico bem-feito é essencial para a condução adequada dos casos clínicos, o bem-estar animal e a confiança entre profissionais.\n\nPor isso, investimos continuamente em tecnologia, capacitação da equipe e melhoria constante dos processos, garantindo resultados consistentes e dentro dos prazos esperados."),
            'destaque' => (string) ($this->field($pageId, 'diagnostico_destaque') ?: 'O Genoma Diagnóstico Veterinário é movido pela experiência, pela inovação e pela responsabilidade com cada resultado entregue.'),
        ];
    }

    /**
     * Sobrescreve o CTA do banner do rodapé só nesta página (Footer.tsx cai
     * pro padrão de Opções do Tema quando os campos ficam em branco).
     */
    private function footerCta(int $pageId): array
    {
        return [
            'titulo'         => (string) ($this->field($pageId, 'footer_cta_titulo') ?: "Entre em contato com\na nossa equipe."),
            'primaryLabel'   => (string) ($this->field($pageId, 'footer_cta_primario_texto') ?: 'Fale conosco pelo WhatsApp'),
            'primaryUrl'     => (string) ($this->field($pageId, 'footer_cta_primario_link') ?: '#'),
            'showSecondary'  => (bool) ($this->field($pageId, 'footer_cta_mostrar_secundario') ?? false),
            'secondaryLabel' => (string) ($this->field($pageId, 'footer_cta_secundario_texto') ?: 'Fale Conosco'),
            'secondaryUrl'   => (string) ($this->field($pageId, 'footer_cta_secundario_link') ?: '#'),
        ];
    }

    /**
     * Monta a seção "Sobre nós".
     */
    private function sobre(int $pageId): array
    {
        return [
            'eyebrow'  => (string) ($this->field($pageId, 'sobre_eyebrow') ?: 'Sobre nós'),
            'titulo'   => (string) ($this->field($pageId, 'sobre_titulo') ?: 'Uma década de excelência em diagnóstico veterinário.'),
            'texto'    => (string) ($this->field($pageId, 'sobre_texto') ?: "Em 2026, completamos 10 anos de atuação, marcando uma trajetória construída com base na ciência, na ética e no compromisso com a medicina veterinária. Ao longo dessa jornada, já realizamos mais de 150 mil atendimentos, contribuindo diariamente para diagnósticos seguros e para o cuidado com a saúde animal.\n\nAtuamos com foco em diagnóstico laboratorial de alta qualidade, utilizando metodologias modernas, equipamentos de ponta e rigorosos padrões de controle, sempre alinhados às boas práticas laboratoriais e às demandas da rotina clínica veterinária."),
            'destaque' => (string) ($this->field($pageId, 'sobre_destaque') ?: "Ciência, precisão e confiança em mais de\n150 mil atendimentos"),
        ];
    }

    /**
     * Monta a seção "Compromisso" (posição 3, banner com imagem de fundo).
     */
    private function compromisso(int $pageId): array
    {
        return [
            'eyebrow' => (string) ($this->field($pageId, 'compromisso_eyebrow') ?: 'Compromisso'),
            'titulo'  => (string) ($this->field($pageId, 'compromisso_titulo') ?: 'Nosso compromisso vai além da liberação de resultados.'),
            'texto'   => (string) ($this->field($pageId, 'compromisso_texto') ?: 'Trabalhamos para oferecer informação diagnóstica confiável, suporte técnico qualificado e um atendimento próximo, transparente e eficiente, fortalecendo parcerias sólidas com clínicas e hospitais veterinários.'),
            'imagem'  => $this->image($this->field($pageId, 'compromisso_imagem')) ?? $this->defaultImagemCompromisso(),
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

    private function defaultImagemCompromisso(): array
    {
        return [
            'src'    => get_template_directory_uri() . '/app/o-genoma/assets/compromisso-veterinario-pet-7e6208.png',
            'alt'    => 'Médico-veterinário cuidando de um pet',
            'width'  => 1001,
            'height' => 668,
            'sizes'  => [],
        ];
    }
}
