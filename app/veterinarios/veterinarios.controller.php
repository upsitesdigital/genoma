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
            'titulo'         => (string) ($this->field($pageId, 'footer_cta_titulo') ?: 'Cuidado começa com diagnóstico preciso.'),
            'primaryLabel'   => (string) ($this->field($pageId, 'footer_cta_primario_texto') ?: 'Quero ser parceiro'),
            'primaryUrl'     => (string) ($this->field($pageId, 'footer_cta_primario_link') ?: '#'),
            'showSecondary'  => (bool) ($this->field($pageId, 'footer_cta_mostrar_secundario') ?? true),
            'secondaryLabel' => (string) ($this->field($pageId, 'footer_cta_secundario_texto') ?: 'Fale Conosco'),
            'secondaryUrl'   => (string) ($this->field($pageId, 'footer_cta_secundario_link') ?: '#'),
        ];
    }

    /**
     * Monta os dados da seção Benefícios.
     */
    private function beneficios(int $pageId): array
    {
        $itens = $this->field($pageId, 'beneficios_itens');

        if (empty($itens) || !is_array($itens)) {
            $itens = $this->defaultBeneficiosItens();
        } else {
            $itens = array_map(
                fn (array $row): array => [
                    'icone' => $this->image($row['icone'] ?? null) ?? $this->defaultBeneficiosIconeEstrutura(),
                    'texto' => (string) ($row['texto'] ?? ''),
                ],
                $itens
            );
        }

        return [
            'eyebrow' => (string) ($this->field($pageId, 'beneficios_eyebrow') ?: 'Benefícios'),
            'titulo'  => (string) ($this->field($pageId, 'beneficios_titulo') ?: 'Benefícios para Veterinários Conveniados'),
            'texto'   => (string) ($this->field($pageId, 'beneficios_texto') ?: 'Ao se tornar um veterinário conveniado ao Genoma, você tem acesso a vantagens exclusivas:'),
            'itens'   => $itens,
        ];
    }

    private function defaultBeneficiosItens(): array
    {
        return [
            [
                'icone' => $this->defaultBeneficiosIconeEstrutura(),
                'texto' => 'Estrutura própria e moderna',
            ],
            [
                'icone' => $this->defaultBeneficiosIconeEquipamentos(),
                'texto' => 'Equipamentos atualizados',
            ],
            [
                'icone' => $this->defaultBeneficiosIconeComunicacao(),
                'texto' => 'Comunicação clara com veterinários',
            ],
            [
                'icone' => $this->defaultBeneficiosIconeAtendimento(),
                'texto' => 'Atendimento acolhedor para tutores',
            ],
        ];
    }

    private function defaultBeneficiosIconeEstrutura(): array
    {
        return [
            'src'    => get_template_directory_uri() . '/app/veterinarios/assets/beneficios-icone-estrutura.svg',
            'alt'    => '',
            'width'  => 52,
            'height' => 52,
            'sizes'  => [],
        ];
    }

    private function defaultBeneficiosIconeEquipamentos(): array
    {
        return [
            'src'    => get_template_directory_uri() . '/app/veterinarios/assets/beneficios-icone-equipamentos.svg',
            'alt'    => '',
            'width'  => 52,
            'height' => 52,
            'sizes'  => [],
        ];
    }

    private function defaultBeneficiosIconeComunicacao(): array
    {
        return [
            'src'    => get_template_directory_uri() . '/app/veterinarios/assets/beneficios-icone-comunicacao.svg',
            'alt'    => '',
            'width'  => 52,
            'height' => 52,
            'sizes'  => [],
        ];
    }

    private function defaultBeneficiosIconeAtendimento(): array
    {
        return [
            'src'    => get_template_directory_uri() . '/app/veterinarios/assets/beneficios-icone-atendimento.svg',
            'alt'    => '',
            'width'  => 52,
            'height' => 52,
            'sizes'  => [],
        ];
    }

    /**
     * Monta os dados da seção Estrutura.
     */
    private function estrutura(int $pageId): array
    {
        $contatos = $this->field($pageId, 'estrutura_contatos');

        if (empty($contatos) || !is_array($contatos)) {
            $contatos = $this->defaultEstruturaContatos();
        } else {
            $contatos = array_map(
                fn (array $row): array => [
                    'icone' => $this->image($row['icone'] ?? null) ?? $this->defaultEstruturaIconeTelefone(),
                    'texto' => (string) ($row['texto'] ?? ''),
                ],
                $contatos
            );
        }

        return [
            'eyebrow'  => (string) ($this->field($pageId, 'estrutura_eyebrow') ?: 'Estrutura / Tecnologia'),
            'titulo'   => (string) ($this->field($pageId, 'estrutura_titulo') ?: 'Suporte Técnico ao Veterinário'),
            'texto'    => (string) ($this->field($pageId, 'estrutura_texto') ?: 'O Genoma oferece suporte direto com corpo veterinário, auxiliando na discussão de casos clínicos, interpretação de resultados e esclarecimento de dúvidas.'),
            'destaque' => (string) ($this->field($pageId, 'estrutura_destaque') ?: 'Nosso objetivo é ser um apoio real no seu raciocínio clínico.'),
            'imagem1'  => $this->image($this->field($pageId, 'estrutura_imagem_1')) ?? $this->defaultEstruturaImagem1(),
            'imagem2'  => $this->image($this->field($pageId, 'estrutura_imagem_2')) ?? $this->defaultEstruturaImagem2(),
            'contatos' => $contatos,
        ];
    }

    private function defaultEstruturaContatos(): array
    {
        return [
            [
                'icone' => $this->defaultEstruturaIconeTelefone(),
                'texto' => "2537-4924 ou \n9 7824-0900",
            ],
            [
                'icone' => $this->defaultEstruturaIconeRelogio(),
                'texto' => 'Segunda a sexta-feira: das 9h às 19h',
            ],
            [
                'icone' => $this->defaultEstruturaIconeCalendario(),
                'texto' => "Sábado: \ndas 9h às 16h",
            ],
        ];
    }

    private function defaultEstruturaIconeTelefone(): array
    {
        return [
            'src'    => get_template_directory_uri() . '/app/veterinarios/assets/estrutura-icone-telefone.svg',
            'alt'    => '',
            'width'  => 52,
            'height' => 52,
            'sizes'  => [],
        ];
    }

    private function defaultEstruturaIconeRelogio(): array
    {
        return [
            'src'    => get_template_directory_uri() . '/app/veterinarios/assets/estrutura-icone-relogio.svg',
            'alt'    => '',
            'width'  => 52,
            'height' => 52,
            'sizes'  => [],
        ];
    }

    private function defaultEstruturaIconeCalendario(): array
    {
        return [
            'src'    => get_template_directory_uri() . '/app/veterinarios/assets/estrutura-icone-calendario.svg',
            'alt'    => '',
            'width'  => 52,
            'height' => 52,
            'sizes'  => [],
        ];
    }

    private function defaultEstruturaImagem1(): array
    {
        return [
            'src'    => get_template_directory_uri() . '/app/veterinarios/assets/estrutura-veterinario-1.png',
            'alt'    => 'Médico-veterinário atendendo em clínica',
            'width'  => 786,
            'height' => 524,
            'sizes'  => [],
        ];
    }

    private function defaultEstruturaImagem2(): array
    {
        return [
            'src'    => get_template_directory_uri() . '/app/veterinarios/assets/estrutura-veterinario-2.png',
            'alt'    => 'Médico-veterinário atendendo em clínica',
            'width'  => 785,
            'height' => 524,
            'sizes'  => [],
        ];
    }

    /**
     * Monta os dados da seção Exames.
     */
    private function exames(int $pageId): array
    {
        return [
            'eyebrow' => (string) ($this->field($pageId, 'exames_eyebrow') ?: 'Exames'),
            'titulo'  => (string) ($this->field($pageId, 'exames_titulo') ?: 'Portfólio de Exames'),
            'texto'   => (string) ($this->field($pageId, 'exames_texto') ?: 'Disponibilizamos um portfólio completo de exames laboratoriais para suporte ao diagnóstico veterinário, contemplando exames de rotina e análises especializadas.'),
            'cta'     => [
                'texto' => (string) ($this->field($pageId, 'exames_cta_texto') ?: 'Lista de exames'),
                'link'  => (string) ($this->field($pageId, 'exames_cta_link') ?: '#'),
            ],
            'imagem'  => $this->image($this->field($pageId, 'exames_imagem')) ?? $this->defaultExamesImagem(),
        ];
    }

    private function defaultExamesImagem(): array
    {
        return [
            'src'    => get_template_directory_uri() . '/app/veterinarios/assets/exames-veterinario-gato.webp',
            'alt'    => 'Médica-veterinária examinando um gato em clínica',
            'width'  => 1400,
            'height' => 785,
            'sizes'  => [],
        ];
    }

    /**
     * Monta os dados da seção Praticidade.
     */
    private function praticidade(int $pageId): array
    {
        $horarios = $this->field($pageId, 'praticidade_horarios');

        if (empty($horarios) || !is_array($horarios)) {
            $horarios = ['Seg a sex: das 9h às 17h', 'Sábados: das 9h às 14h'];
        } else {
            $horarios = array_values(array_filter(array_map(
                fn (array $row): string => (string) ($row['texto'] ?? ''),
                $horarios
            )));
        }

        return [
            'eyebrow'  => (string) ($this->field($pageId, 'praticidade_eyebrow') ?: 'Praticidade'),
            'titulo'   => (string) ($this->field($pageId, 'praticidade_titulo') ?: 'Coleta de Amostras'),
            'texto'    => (string) ($this->field($pageId, 'praticidade_texto') ?: "Para garantir praticidade e segurança no envio das amostras, contamos com serviço de coleta por motoboy\nnos seguintes horários:"),
            'icone'    => $this->image($this->field($pageId, 'praticidade_icone')) ?? $this->defaultPraticidadeIcone(),
            'horarios' => $horarios,
            'imagem'   => $this->image($this->field($pageId, 'praticidade_imagem')) ?? $this->defaultPraticidadeImagem(),
        ];
    }

    private function defaultPraticidadeIcone(): array
    {
        return [
            'src'    => get_template_directory_uri() . '/app/veterinarios/assets/praticidade-icone-moto.svg',
            'alt'    => '',
            'width'  => 58,
            'height' => 58,
            'sizes'  => [],
        ];
    }

    private function defaultPraticidadeImagem(): array
    {
        return [
            'src'    => get_template_directory_uri() . '/app/veterinarios/assets/praticidade-motoboy-54c183.png',
            'alt'    => 'Motoboy realizando coleta de amostras',
            'width'  => 921,
            'height' => 945,
            'sizes'  => [],
        ];
    }

    /**
     * Monta os dados da seção Suporte.
     */
    private function suporte(int $pageId): array
    {
        return [
            'eyebrow' => (string) ($this->field($pageId, 'suporte_eyebrow') ?: 'Suporte técnico'),
            'titulo'  => (string) ($this->field($pageId, 'suporte_titulo') ?: "10 anos de atuação e mais de\n150 mil exames realizados."),
            'texto'   => (string) ($this->field($pageId, 'suporte_texto') ?: 'No Genoma Diagnóstico Veterinário, atuamos lado a lado com o médico-veterinário, oferecendo suporte técnico, agilidade operacional e condições especiais para quem busca excelência no cuidado com seus pacientes.'),
            'quote'   => (string) ($this->field($pageId, 'suporte_quote') ?: 'Somos um laboratório preparado para atender clínicas e hospitais veterinários com eficiência, confiança e proximidade.'),
            'imagem1' => $this->image($this->field($pageId, 'suporte_imagem_1')) ?? $this->defaultSuporteImagem1(),
            'imagem2' => $this->image($this->field($pageId, 'suporte_imagem_2')) ?? $this->defaultSuporteImagem2(),
        ];
    }

    private function defaultSuporteImagem1(): array
    {
        return [
            'src'    => get_template_directory_uri() . '/app/veterinarios/assets/suporte-veterinario-1.jpg',
            'alt'    => 'Médico-veterinário sorrindo',
            'width'  => 628,
            'height' => 780,
            'sizes'  => [],
        ];
    }

    private function defaultSuporteImagem2(): array
    {
        return [
            'src'    => get_template_directory_uri() . '/app/veterinarios/assets/suporte-veterinario-2.jpg',
            'alt'    => 'Veterinário cuidando de um pet de perto',
            'width'  => 1746,
            'height' => 778,
            'sizes'  => [],
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
            'eyebrow'   => 'Veterinários',
            'titulo'    => "Parceria que fortalece\no seu diagnóstico",
            'subtitulo' => 'Suporte técnico, agilidade e condições especiais para médicos-veterinários que buscam excelência no cuidado.',
            'ctaPrimario' => [
                'texto' => 'Quero ser parceiro',
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
            'src'    => get_template_directory_uri() . '/app/veterinarios/assets/hero-veterinario-cachorro.webp',
            'alt'    => 'Médico-veterinário sorrindo com cachorro no colo em clínica',
            'width'  => 1800,
            'height' => 1317,
            'sizes'  => [],
        ];
    }
}
