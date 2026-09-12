<?php
declare(strict_types=1);

namespace App\Home;

use Core\Framework\Controller;
use Core\Framework\Attributes\Get;
use Core\Framework\Attributes\Cache;

final class HomeController extends Controller
{
    #[Get('/home')]
    #[Get('/home/:id')]
    #[Cache(ttl: 300)]
    public function index(\WP_REST_Request $request): array
    {
        $pageId = (int) ($request->get_param('id') ?: get_option('page_on_front') ?: 0);

        return [
            'hero'         => $this->heroSlides($pageId),
            'servicos'     => $this->servicos($pageId),
            'exames'       => $this->exames($pageId),
            'sobre'        => $this->sobre($pageId),
            'diferenciais' => $this->diferenciais($pageId),
            'estrutura'    => $this->estrutura($pageId),
            'depoimentos'  => $this->depoimentos($pageId),
            'footerCta'    => $this->footerCta($pageId),
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
            'primaryLabel'   => (string) ($this->field($pageId, 'footer_cta_primario_texto') ?: 'Agendar Exame'),
            'primaryUrl'     => (string) ($this->field($pageId, 'footer_cta_primario_link') ?: '#'),
            'showSecondary'  => (bool) ($this->field($pageId, 'footer_cta_mostrar_secundario') ?? true),
            'secondaryLabel' => (string) ($this->field($pageId, 'footer_cta_secundario_texto') ?: 'Fale Conosco'),
            'secondaryUrl'   => (string) ($this->field($pageId, 'footer_cta_secundario_link') ?: '#'),
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
            'imagem' => $this->image($row['imagem'] ?? null) ?? $this->defaultImage(),
            'titulo' => (string) ($row['titulo'] ?? ''),
            'subtitulo' => (string) ($row['subtitulo'] ?? ''),
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
            'titulo'    => 'Diagnóstico Veterinário com Precisão, Agilidade e Confiança',
            'subtitulo' => 'Há 10 anos apoiando médicos-veterinários com tecnologia e qualidade.',
            'ctaPrimario' => [
                'texto' => 'Quero ser parceiro',
                'link'  => '#',
            ],
            'ctaSecundario' => [
                'texto' => 'Acessar resultados',
                'link'  => '#',
            ],
        ];
    }

    private function defaultImage(): array
    {
        return [
            'src'    => get_template_directory_uri() . '/app/home/assets/hero-bg-slide-1.png',
            'alt'    => 'Diagnóstico Veterinário com Precisão, Agilidade e Confiança',
            'width'  => 3840,
            'height' => 1594,
            'sizes'  => [],
        ];
    }

    /**
     * Monta a grade de cards da seção Serviços.
     * Se não houver linhas cadastradas no ACF, retorna o conteúdo padrão (Figma) com assets locais.
     */
    private function servicos(int $pageId): array
    {
        $rows = $this->field($pageId, 'servicos_itens');

        if (empty($rows) || !is_array($rows)) {
            $itens = $this->defaultServicos();
        } else {
            $itens = array_map(fn (array $row): array => $this->mapServico($row), $rows);
        }

        return [
            'eyebrow' => (string) ($this->field($pageId, 'servicos_eyebrow') ?: 'Nossos Serviços'),
            'titulo'  => (string) ($this->field($pageId, 'servicos_titulo') ?: 'Tecnologia e cuidado a serviço da saúde animal'),
            'itens'   => $itens,
            'cta'     => [
                'texto' => (string) ($this->field($pageId, 'servicos_cta_texto') ?: 'Ver todos exames'),
                'link'  => (string) ($this->field($pageId, 'servicos_cta_link') ?: '#'),
            ],
        ];
    }

    private function mapServico(array $row): array
    {
        return [
            'imagem'    => $this->image($row['imagem'] ?? null) ?? $this->defaultServicoImage('exames-laboratoriais'),
            'titulo'    => (string) ($row['titulo'] ?? ''),
            'descricao' => (string) ($row['descricao'] ?? ''),
            'cta'       => [
                'texto' => (string) ($row['cta_texto'] ?? 'Agendar'),
                'link'  => (string) ($row['cta_link'] ?? '#'),
            ],
        ];
    }

    private function defaultServicos(): array
    {
        $servicos = [
            [
                'slug'      => 'exames-laboratoriais',
                'titulo'    => 'Exames Laboratoriais',
                'descricao' => 'Análises clínicas com precisão, controle de qualidade e liberação rápida.',
            ],
            [
                'slug'      => 'eletrocardiograma',
                'titulo'    => 'Eletrocardiograma',
                'descricao' => 'Monitoramento da atividade elétrica do coração.',
            ],
            [
                'slug'      => 'raio-x-digital',
                'titulo'    => 'Raio-X Digital',
                'descricao' => 'Imagens com alta definição e agilidade na entrega.',
            ],
            [
                'slug'      => 'ecocardiograma',
                'titulo'    => 'Ecocardiograma',
                'descricao' => 'Avaliação cardíaca com especialistas.',
            ],
            [
                'slug'      => 'pressao-arterial',
                'titulo'    => 'Pressão Arterial',
                'descricao' => 'Acompanhamento seguro e preciso.',
            ],
            [
                'slug'      => 'ultrassom-veterinario',
                'titulo'    => 'Ultrassom Veterinário',
                'descricao' => 'Exames detalhados para apoio diagnóstico seguro.',
            ],
        ];

        return array_map(fn (array $s): array => [
            'imagem'    => $this->defaultServicoImage($s['slug'], $s['titulo']),
            'titulo'    => $s['titulo'],
            'descricao' => $s['descricao'],
            'cta'       => [
                'texto' => 'Agendar',
                'link'  => '#',
            ],
        ], $servicos);
    }

    private function defaultServicoImage(string $slug, string $alt = ''): array
    {
        return [
            'src'    => get_template_directory_uri() . '/app/home/assets/servicos-' . $slug . '.png',
            'alt'    => $alt,
            'width'  => 1408,
            'height' => 768,
            'sizes'  => [],
        ];
    }

    /**
     * Monta o acordeão de categorias de exames.
     * Se não houver linhas cadastradas no ACF, retorna o conteúdo padrão (Figma).
     */
    private function exames(int $pageId): array
    {
        $rows = $this->field($pageId, 'exames_categorias');

        if (empty($rows) || !is_array($rows)) {
            $categorias = $this->defaultExames();
        } else {
            $categorias = array_map(fn (array $row): array => $this->mapExameCategoria($row), $rows);
        }

        return [
            'eyebrow'    => (string) ($this->field($pageId, 'exames_eyebrow') ?: 'Exames'),
            'titulo'     => (string) ($this->field($pageId, 'exames_titulo') ?: 'Tipos de exames realizados'),
            'descricao'  => (string) ($this->field($pageId, 'exames_descricao') ?: 'O Genoma oferece um portfólio completo de exames, organizado nas seguintes áreas:'),
            'categorias' => $categorias,
            'cta'        => [
                'texto' => (string) ($this->field($pageId, 'exames_cta_texto') ?: 'Ver todos exames'),
                'link'  => (string) ($this->field($pageId, 'exames_cta_link') ?: '#'),
            ],
        ];
    }

    private function mapExameCategoria(array $row): array
    {
        $itens = $row['itens'] ?? [];

        return [
            'titulo' => (string) ($row['titulo'] ?? ''),
            'itens'  => is_array($itens) ? array_map(fn (array $item): array => $this->mapExameItem($item), $itens) : [],
        ];
    }

    private function mapExameItem(array $item): array
    {
        return [
            'nome'    => (string) ($item['nome'] ?? ''),
            'prazo'   => (string) ($item['prazo'] ?? ''),
            'amostra' => (string) ($item['amostra'] ?? ''),
        ];
    }

    private function defaultExames(): array
    {
        return [
            [
                'titulo' => 'PCR',
                'itens'  => [],
            ],
            [
                'titulo' => 'Citologia',
                'itens'  => [
                    ['nome' => 'Análise Citológica', 'prazo' => '7 Dias', 'amostra' => 'Lâminas'],
                    ['nome' => 'Análise Citológica - Dermatológica', 'prazo' => '7 Dias', 'amostra' => 'Lâminas Ou Fluídos'],
                    ['nome' => 'Análise Citológica Vaginal', 'prazo' => '7 Dias', 'amostra' => 'Lâminas Ou Fluídos'],
                    ['nome' => 'Análise Citológica Vaginal (3 Amostras)', 'prazo' => '7 Dias', 'amostra' => 'Lâminas Ou Fluídos'],
                ],
            ],
            [
                'titulo' => 'Bioquímica',
                'itens'  => [],
            ],
            [
                'titulo' => 'Hematologia',
                'itens'  => [],
            ],
            [
                'titulo' => 'Coagulação',
                'itens'  => [],
            ],
            [
                'titulo' => 'Histopatologia',
                'itens'  => [],
            ],
            [
                'titulo' => 'Hormônios',
                'itens'  => [],
            ],
        ];
    }

    /**
     * Monta a seção "Sobre o Genoma" (fotos, texto institucional e cards de diferenciais).
     * Se não houver conteúdo cadastrado no ACF, retorna o conteúdo padrão (Figma) com assets locais.
     */
    private function sobre(int $pageId): array
    {
        $rows = $this->field($pageId, 'sobre_diferenciais');

        if (empty($rows) || !is_array($rows)) {
            $diferenciais = $this->defaultDiferenciais();
        } else {
            $diferenciais = array_map(fn (array $row): array => $this->mapDiferencial($row), $rows);
        }

        $texto = (string) ($this->field($pageId, 'sobre_texto') ?: $this->defaultSobreTexto());

        return [
            'eyebrow'      => (string) ($this->field($pageId, 'sobre_eyebrow') ?: 'Sobre o Genoma'),
            'titulo'       => (string) ($this->field($pageId, 'sobre_titulo') ?: '10 anos fortalecendo a medicina veterinária'),
            'fotos'        => [
                $this->image($this->field($pageId, 'sobre_foto_1')) ?? $this->defaultSobreFoto(1),
                $this->image($this->field($pageId, 'sobre_foto_2')) ?? $this->defaultSobreFoto(2),
                $this->image($this->field($pageId, 'sobre_foto_3')) ?? $this->defaultSobreFoto(3),
            ],
            'paragrafos'   => array_values(array_filter(array_map('trim', explode("\n\n", $texto)))),
            'destaque'     => (string) ($this->field($pageId, 'sobre_destaque') ?: 'Mais do que exames, entregamos parceria, suporte técnico e confiança para a rotina clínica.'),
            'diferenciais' => $diferenciais,
        ];
    }

    private function mapDiferencial(array $row): array
    {
        return [
            'icone'     => $this->image($row['icone'] ?? null) ?? $this->defaultDiferencialIcone('experiencia'),
            'titulo'    => (string) ($row['titulo'] ?? ''),
            'descricao' => (string) ($row['descricao'] ?? ''),
        ];
    }

    private function defaultSobreTexto(): string
    {
        return "O Genoma é um centro de diagnóstico veterinário estruturado para oferecer exames laboratoriais e diagnósticos por imagem com precisão, segurança e rapidez.\n\n"
            . "Atuamos como parceiros estratégicos de clínicas e hospitais veterinários, contribuindo diretamente para decisões clínicas mais assertivas.\n\n"
            . 'Com tecnologia moderna, controle rigoroso de qualidade e equipe especializada, entregamos resultados confiáveis todos os dias.';
    }

    private function defaultSobreFoto(int $n): array
    {
        $dims = [
            1 => [723, 624],
            2 => [900, 1214],
            3 => [1400, 952],
        ];
        [$w, $h] = $dims[$n] ?? [723, 624];

        return [
            'src'    => get_template_directory_uri() . '/app/home/assets/sobre-genoma-foto-' . $n . '.png',
            'alt'    => '10 anos fortalecendo a medicina veterinária',
            'width'  => $w,
            'height' => $h,
            'sizes'  => [],
        ];
    }

    private function defaultDiferencialIcone(string $slug): array
    {
        return [
            'src'    => get_template_directory_uri() . '/app/home/assets/sobre-genoma-icon-' . $slug . '.svg',
            'alt'    => '',
            'width'  => 52,
            'height' => 52,
            'sizes'  => [],
        ];
    }

    private function defaultDiferenciais(): array
    {
        return [
            [
                'icone'     => $this->defaultDiferencialIcone('experiencia'),
                'titulo'    => '10 anos de experiência',
                'descricao' => 'Uma década apoiando médicos-veterinários com estrutura moderna, controle rigoroso de qualidade e compromisso com a excelência diagnóstica.',
            ],
            [
                'icone'     => $this->defaultDiferencialIcone('exames'),
                'titulo'    => '+150 mil exames realizados',
                'descricao' => 'Alto volume de análises com precisão técnica, garantindo resultados confiáveis para decisões clínicas mais seguras.',
            ],
            [
                'icone'     => $this->defaultDiferencialIcone('logistica'),
                'titulo'    => 'Logística eficiente de coleta',
                'descricao' => 'Sistema organizado de coleta e transporte que preserva a integridade das amostras e assegura agilidade na liberação dos laudos.',
            ],
        ];
    }

    /**
     * Monta a seção "Diferenciais" (banner com imagem de fundo + cards curtos).
     * Se não houver conteúdo cadastrado no ACF, retorna o conteúdo padrão (Figma) com assets locais.
     */
    private function diferenciais(int $pageId): array
    {
        $rows = $this->field($pageId, 'diferenciais_itens');

        if (empty($rows) || !is_array($rows)) {
            $itens = $this->defaultDiferenciaisItens();
        } else {
            $itens = array_map(fn (array $row): array => $this->mapDiferencialItem($row), $rows);
        }

        return [
            'eyebrow' => (string) ($this->field($pageId, 'diferenciais_eyebrow') ?: 'Diferenciais'),
            'titulo'  => (string) ($this->field($pageId, 'diferenciais_titulo') ?: 'Por que escolher o Genoma?'),
            'imagem'  => $this->image($this->field($pageId, 'diferenciais_imagem')) ?? $this->defaultDiferenciaisImagem(),
            'itens'   => $itens,
        ];
    }

    private function mapDiferencialItem(array $row): array
    {
        return [
            'icone'  => $this->image($row['icone'] ?? null) ?? $this->defaultDiferencialItemIcone('estrutura'),
            'titulo' => (string) ($row['titulo'] ?? ''),
        ];
    }

    private function defaultDiferenciaisImagem(): array
    {
        return [
            'src'    => get_template_directory_uri() . '/app/home/assets/diferenciais-bg.png',
            'alt'    => 'Por que escolher o Genoma?',
            'width'  => 1785,
            'height' => 1225,
            'sizes'  => [],
        ];
    }

    private function defaultDiferencialItemIcone(string $slug): array
    {
        return [
            'src'    => get_template_directory_uri() . '/app/home/assets/diferenciais-icon-' . $slug . '.svg',
            'alt'    => '',
            'width'  => 52,
            'height' => 52,
            'sizes'  => [],
        ];
    }

    private function defaultDiferenciaisItens(): array
    {
        return [
            [
                'icone'  => $this->defaultDiferencialItemIcone('estrutura'),
                'titulo' => 'Estrutura própria e moderna',
            ],
            [
                'icone'  => $this->defaultDiferencialItemIcone('equipamentos'),
                'titulo' => 'Equipamentos atualizados',
            ],
            [
                'icone'  => $this->defaultDiferencialItemIcone('comunicacao'),
                'titulo' => 'Comunicação clara com veterinários',
            ],
            [
                'icone'  => $this->defaultDiferencialItemIcone('atendimento'),
                'titulo' => 'Atendimento acolhedor para Responsável',
            ],
        ];
    }

    /**
     * Monta a seção "Estrutura" (etiqueta + título + descrição + galeria de 3 fotos).
     * Se não houver conteúdo cadastrado no ACF, retorna o conteúdo padrão (Figma) com assets locais.
     */
    private function estrutura(int $pageId): array
    {
        return [
            'eyebrow'   => (string) ($this->field($pageId, 'estrutura_eyebrow') ?: 'Estrutura / Tecnologia'),
            'titulo'    => (string) ($this->field($pageId, 'estrutura_titulo') ?: 'Tecnologia que gera confiança'),
            'descricao' => (string) ($this->field($pageId, 'estrutura_descricao') ?: 'O Genoma é um centro de diagnóstico veterinário estruturado para oferecer exames laboratoriais e diagnósticos por imagem'),
            'fotos'     => [
                $this->image($this->field($pageId, 'estrutura_foto_1')) ?? $this->defaultEstruturaFoto(1),
                $this->image($this->field($pageId, 'estrutura_foto_2')) ?? $this->defaultEstruturaFoto(2),
                $this->image($this->field($pageId, 'estrutura_foto_3')) ?? $this->defaultEstruturaFoto(3),
            ],
        ];
    }

    private function defaultEstruturaFoto(int $n): array
    {
        $dims = [
            1 => [786, 524],
            2 => [785, 524],
            3 => [524, 786],
        ];
        [$w, $h] = $dims[$n] ?? [786, 524];

        return [
            'src'    => get_template_directory_uri() . '/app/home/assets/estrutura-foto-' . $n . '.png',
            'alt'    => 'Tecnologia que gera confiança',
            'width'  => $w,
            'height' => $h,
            'sizes'  => [],
        ];
    }

    /**
     * Monta o carrossel de depoimentos de clientes.
     * Se não houver linhas cadastradas no ACF, retorna o conteúdo padrão (Figma) com asset local.
     */
    private function depoimentos(int $pageId): array
    {
        $rows = $this->field($pageId, 'depoimentos_itens');

        if (empty($rows) || !is_array($rows)) {
            $itens = $this->defaultDepoimentos();
        } else {
            $itens = array_map(fn (array $row): array => $this->mapDepoimento($row), $rows);
        }

        return [
            'eyebrow'   => (string) ($this->field($pageId, 'depoimentos_eyebrow') ?: 'Depoimentos'),
            'titulo'    => (string) ($this->field($pageId, 'depoimentos_titulo') ?: 'O que nossos clientes dizem'),
            'itens'     => $itens,
            'autoplay'  => (bool) ($this->field($pageId, 'depoimentos_autoplay') ?? true),
            'intervalo' => (int) ($this->field($pageId, 'depoimentos_intervalo') ?: 6000),
        ];
    }

    private function mapDepoimento(array $row): array
    {
        return [
            'icone' => $this->image($row['icone'] ?? null) ?? $this->defaultDepoimentoIcone(),
            'texto' => (string) ($row['texto'] ?? ''),
            'nome'  => (string) ($row['nome'] ?? ''),
        ];
    }

    private function defaultDepoimentoIcone(): array
    {
        return [
            'src'    => get_template_directory_uri() . '/app/home/assets/depoimentos-quote-icon.svg',
            'alt'    => '',
            'width'  => 60,
            'height' => 60,
            'sizes'  => [],
        ];
    }

    private function defaultDepoimentos(): array
    {
        return [
            [
                'icone' => $this->defaultDepoimentoIcone(),
                'texto' => 'Sou da proteção animal, além de preços justos, profissionais muito preparados e atencisos, só tenho elogios a fazer, tanto que recomendo sempre a amigos',
                'nome'  => 'Iza Neri',
            ],
            [
                'icone' => $this->defaultDepoimentoIcone(),
                'texto' => 'Hoje fui levar minha gata para fazer o exame de ultrassom e fomos muito bem atendidas na recepção e pelo Dr que foi bem paciente e cuidadoso com a gata!',
                'nome'  => 'Leny Gomes',
            ],
        ];
    }
}
