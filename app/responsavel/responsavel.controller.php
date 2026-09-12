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
            'titulo'         => (string) ($this->field($pageId, 'footer_cta_titulo') ?: "Entre em contato com\na nossa equipe."),
            'primaryLabel'   => (string) ($this->field($pageId, 'footer_cta_primario_texto') ?: 'Fale conosco pelo WhatsApp'),
            'primaryUrl'     => (string) ($this->field($pageId, 'footer_cta_primario_link') ?: '#'),
            'showSecondary'  => (bool) ($this->field($pageId, 'footer_cta_mostrar_secundario') ?? false),
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
                    'icone' => $this->image($row['icone'] ?? null) ?? $this->defaultBeneficiosIconeQualidade(),
                    'texto' => (string) ($row['texto'] ?? ''),
                ],
                $itens
            );
        }

        return [
            'eyebrow' => (string) ($this->field($pageId, 'beneficios_eyebrow') ?: 'Benefícios'),
            'titulo'  => (string) ($this->field($pageId, 'beneficios_titulo') ?: 'Nosso compromisso com o seu pet'),
            'texto'   => (string) ($this->field($pageId, 'beneficios_texto') ?: ''),
            'itens'   => $itens,
        ];
    }

    private function defaultBeneficiosItens(): array
    {
        return [
            [
                'icone' => $this->defaultBeneficiosIconeQualidade(),
                'texto' => 'Qualidade e confiabilidade nos exames',
            ],
            [
                'icone' => $this->defaultBeneficiosIconeTecnologia(),
                'texto' => 'Tecnologia e controle rigoroso de processos',
            ],
            [
                'icone' => $this->defaultBeneficiosIconeEtica(),
                'texto' => 'Respeito às normas éticas e profissionais',
            ],
            [
                'icone' => $this->defaultBeneficiosIconeParceria(),
                'texto' => 'Parceria com médicos-veterinários',
            ],
        ];
    }

    private function defaultBeneficiosIconeQualidade(): array
    {
        return [
            'src'    => get_template_directory_uri() . '/app/responsavel/assets/beneficios-icone-qualidade.svg',
            'alt'    => '',
            'width'  => 52,
            'height' => 52,
            'sizes'  => [],
        ];
    }

    private function defaultBeneficiosIconeTecnologia(): array
    {
        return [
            'src'    => get_template_directory_uri() . '/app/responsavel/assets/beneficios-icone-tecnologia.svg',
            'alt'    => '',
            'width'  => 52,
            'height' => 52,
            'sizes'  => [],
        ];
    }

    private function defaultBeneficiosIconeEtica(): array
    {
        return [
            'src'    => get_template_directory_uri() . '/app/responsavel/assets/beneficios-icone-etica.svg',
            'alt'    => '',
            'width'  => 52,
            'height' => 52,
            'sizes'  => [],
        ];
    }

    private function defaultBeneficiosIconeParceria(): array
    {
        return [
            'src'    => get_template_directory_uri() . '/app/responsavel/assets/beneficios-icone-parceria.svg',
            'alt'    => '',
            'width'  => 52,
            'height' => 52,
            'sizes'  => [],
        ];
    }

    /**
     * Monta os dados da seção Resultados dos exames.
     */
    private function resultados(int $pageId): array
    {
        return [
            'eyebrow' => (string) ($this->field($pageId, 'resultados_eyebrow') ?: 'Exames'),
            'titulo'  => (string) ($this->field($pageId, 'resultados_titulo') ?: 'Resultados dos exames'),
            'texto'   => (string) ($this->field($pageId, 'resultados_texto') ?: "Os resultados são liberados dentro dos prazos estabelecidos e enviados ao médico-veterinário solicitante, que é o profissional indicado para interpretar os exames e orientar o responsável sobre os próximos passos no cuidado com o pet.\n\nAlém disso, o responsável recebe um protocolo com usuário e senha para consultar os resultados diretamente em nosso site, de forma prática e segura.\n\nPara exames de rotina, a liberação costuma ser feita com agilidade, ajudando a acelerar o início do tratamento quando necessário."),
            'imagem'  => $this->image($this->field($pageId, 'resultados_imagem')) ?? $this->defaultResultadosImagem(),
        ];
    }

    private function defaultResultadosImagem(): array
    {
        return [
            'src'    => get_template_directory_uri() . '/app/responsavel/assets/resultados-veterinario-exame.png',
            'alt'    => 'Médico-veterinário analisando resultados de exame',
            'width'  => 762,
            'height' => 770,
            'sizes'  => [],
        ];
    }

    /**
     * Monta os dados da seção Serviços (categorias de exames em accordion).
     */
    private function servicos(int $pageId): array
    {
        $rows = $this->field($pageId, 'servicos_categorias');

        if (empty($rows) || !is_array($rows)) {
            $rows = $this->defaultServicosCategorias();
        } else {
            $rows = array_map(fn (array $row): array => $this->mapServicosCategoria($row), $rows);
        }

        return [
            'eyebrow'    => (string) ($this->field($pageId, 'servicos_eyebrow') ?: 'Nossos Serviços'),
            'titulo'     => (string) ($this->field($pageId, 'servicos_titulo') ?: 'Tipos de exames realizados'),
            'texto'      => (string) ($this->field($pageId, 'servicos_texto') ?: 'O Genoma oferece um portfólio completo de exames, organizado nas seguintes áreas:'),
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

    private function defaultServicosCategorias(): array
    {
        return [
            ['nome' => 'PCR', 'aberto' => false, 'descricao' => '', 'exames' => []],
            [
                'nome'      => 'Citologia',
                'aberto'    => true,
                'descricao' => 'O exame citológico tem como objetivo classificar as lesões, contribuindo para o diagnóstico, a definição do prognóstico e a escolha do tratamento mais adequado. Essa análise permite, principalmente, diferenciar processos inflamatórios, hiperplasias e neoplasias. Entre suas principais vantagens destacam-se a rapidez, a simplicidade da técnica e a necessidade reduzida de equipamentos para sua realização.',
                'exames'    => [
                    ['nome' => 'Análise Citológica', 'prazo' => '7 Dias', 'amostra' => 'Lâminas'],
                    ['nome' => 'Análise Citológica - Dermatológica', 'prazo' => '7 Dias', 'amostra' => 'Lâminas Ou Fluídos'],
                    ['nome' => 'Análise Citológica Vaginal', 'prazo' => '7 Dias', 'amostra' => 'Lâminas Ou Fluídos'],
                    ['nome' => 'Análise Citológica Vaginal (3 Amostras)', 'prazo' => '7 Dias', 'amostra' => 'Lâminas Ou Fluídos'],
                ],
            ],
            ['nome' => 'Bioquímica', 'aberto' => false, 'descricao' => '', 'exames' => []],
            ['nome' => 'Hematologia', 'aberto' => false, 'descricao' => '', 'exames' => []],
            ['nome' => 'Coagulação', 'aberto' => false, 'descricao' => '', 'exames' => []],
            ['nome' => 'Histopatologia', 'aberto' => false, 'descricao' => '', 'exames' => []],
            ['nome' => 'Hormonios', 'aberto' => false, 'descricao' => '', 'exames' => []],
            ['nome' => 'Imunologia', 'aberto' => false, 'descricao' => '', 'exames' => []],
            ['nome' => 'Parasitologia', 'aberto' => false, 'descricao' => '', 'exames' => []],
            ['nome' => 'Urinalise', 'aberto' => false, 'descricao' => '', 'exames' => []],
            ['nome' => 'Hormônios', 'aberto' => false, 'descricao' => '', 'exames' => []],
        ];
    }

    /**
     * Monta os dados da seção Exames (preparação/jejum para exames).
     */
    private function exames(int $pageId): array
    {
        $rows = $this->field($pageId, 'exames_itens');

        if (empty($rows) || !is_array($rows)) {
            $rows = $this->defaultExamesItens();
        } else {
            $rows = array_map(fn (array $row): array => [
                'icone'    => $this->image($row['icone'] ?? null) ?? $this->defaultExamesIcone(),
                'texto'    => (string) ($row['texto'] ?? ''),
                'destaque' => (string) ($row['destaque'] ?? ''),
            ], $rows);
        }

        return [
            'eyebrow' => (string) ($this->field($pageId, 'exames_eyebrow') ?: 'Exames'),
            'titulo'  => (string) ($this->field($pageId, 'exames_titulo') ?: 'Preparação para exames: jejum'),
            'texto'   => (string) ($this->field($pageId, 'exames_texto') ?: 'Para garantir resultados mais precisos, alguns exames exigem jejum alimentar:'),
            'itens'   => $rows,
            'rodape'  => (string) ($this->field($pageId, 'exames_rodape') ?: 'Sempre siga também as orientações do médico-veterinário, pois em alguns casos podem existir recomendações específicas para o seu pet.'),
            'imagem'  => $this->image($this->field($pageId, 'exames_imagem')) ?? $this->defaultExamesImagem(),
        ];
    }

    private function defaultExamesItens(): array
    {
        return [
            [
                'icone'    => $this->defaultExamesIcone(),
                'texto'    => 'Exames de sangue em geral: ',
                'destaque' => 'jejum de 8 horas',
            ],
            [
                'icone'    => $this->defaultExamesIcone(),
                'texto'    => 'Quando houver dosagem de Colesterol, Triglicerídeos ou Glicemia: ',
                'destaque' => 'jejum de 12 horas',
            ],
        ];
    }

    private function defaultExamesIcone(): array
    {
        return [
            'src'    => get_template_directory_uri() . '/app/responsavel/assets/exames-icone-jejum.svg',
            'alt'    => '',
            'width'  => 52,
            'height' => 52,
            'sizes'  => [],
        ];
    }

    private function defaultExamesImagem(): array
    {
        return [
            'src'    => get_template_directory_uri() . '/app/responsavel/assets/exames-veterinario-exame.webp',
            'alt'    => 'Médica-veterinária examinando um pet com cuidado',
            'width'  => 1660,
            'height' => 857,
            'sizes'  => [],
        ];
    }

    /**
     * Monta os dados da seção Planos (logos dos planos de saúde atendidos).
     */
    private function planos(int $pageId): array
    {
        $rows = $this->field($pageId, 'planos_logos');

        if (empty($rows) || !is_array($rows)) {
            $rows = $this->defaultPlanosLogos();
        } else {
            $rows = array_map(fn (array $row): array => [
                'imagem' => $this->image($row['imagem'] ?? null) ?? $this->defaultPlanosLogo('petlove', (string) ($row['nome'] ?? '')),
                'nome'   => (string) ($row['nome'] ?? ''),
            ], $rows);
        }

        return [
            'eyebrow' => (string) ($this->field($pageId, 'planos_eyebrow') ?: 'Planos de saúde atendidos'),
            'titulo'  => (string) ($this->field($pageId, 'planos_titulo') ?: 'O Genoma atende os seguintes planos de saúde pet:'),
            'logos'   => $rows,
        ];
    }

    private function defaultPlanosLogos(): array
    {
        return [
            ['imagem' => $this->defaultPlanosLogo('petlove', 'Petlove'), 'nome' => 'Petlove'],
            ['imagem' => $this->defaultPlanosLogo('doglife', 'Dog Life'), 'nome' => 'Dog Life'],
            ['imagem' => $this->defaultPlanosLogo('pethealth', 'Pethealth'), 'nome' => 'Pethealth'],
            ['imagem' => $this->defaultPlanosLogo('misterdog', 'Mister Dog and Cats — Saúde Animal'), 'nome' => 'Mister Dog and Cats — Saúde Animal'],
        ];
    }

    private function defaultPlanosLogo(string $slug, string $nome): array
    {
        $dimensoes = [
            'petlove'   => [605, 166],
            'doglife'   => [3732, 648],
            'pethealth' => [338, 129],
            'misterdog' => [232, 97],
        ];

        [$width, $height] = $dimensoes[$slug] ?? [null, null];

        return [
            'src'    => get_template_directory_uri() . "/app/responsavel/assets/planos-logo-{$slug}.webp",
            'alt'    => $nome,
            'width'  => $width,
            'height' => $height,
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
            'titulo'  => (string) ($this->field($pageId, 'suporte_titulo') ?: 'Como funciona o atendimento ao Responsável?'),
            'texto'   => (string) ($this->field($pageId, 'suporte_texto') ?: "Os exames são sempre solicitados por um médico-veterinário.\nO Genoma realiza as análises laboratoriais e fornece os resultados ao profissional responsável, que é quem fará a avaliação clínica e as orientações necessárias para o tratamento do animal."),
            'quote'   => (string) ($this->field($pageId, 'suporte_quote') ?: 'Garantimos segurança, ética e um acompanhamento adequado em cada caso.'),
            'imagem1' => $this->image($this->field($pageId, 'suporte_imagem_1')) ?? $this->defaultSuporteImagem1(),
            'imagem2' => $this->image($this->field($pageId, 'suporte_imagem_2')) ?? $this->defaultSuporteImagem2(),
        ];
    }

    private function defaultSuporteImagem1(): array
    {
        return [
            'src'    => get_template_directory_uri() . '/app/responsavel/assets/suporte-casal-cachorro.webp',
            'alt'    => 'Casal jovem sorrindo com um cachorro fofo',
            'width'  => 628,
            'height' => 780,
            'sizes'  => [],
        ];
    }

    private function defaultSuporteImagem2(): array
    {
        return [
            'src'    => get_template_directory_uri() . '/app/responsavel/assets/suporte-dachshund-familia.webp',
            'alt'    => 'Close-up de um dachshund passando tempo com a família',
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
