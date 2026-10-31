<?php
declare(strict_types=1);

namespace App\Home;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

use Core\Framework\Controller;
use Core\Framework\Attributes\Cache;

/**
 * Home — porte do app/home/home.controller.php do tema React: mesmos campos (acf_fields.php, bloco
 * home) e mesmo formato do home.schema.ts (chaves iguais), para o markup ser portado 1:1.
 *
 * Padrões (campo nunca salvo) = default_value de cada campo em acf_fields.php — no React o
 * get_field() já devolvia o default_value nesse caso. Imagens não têm padrão (como no React),
 * exceto a Imagem Mobile do hero (ver hero()).
 *
 * Diferenças de semântica em relação ao React (intencionais):
 * - texto salvo vazio → null (o React devolvia ''; nos dois o elemento não aparece);
 * - CTAs continuam {texto, link} de dois campos de texto (legado, não Fields::button): link
 *   passa por esc_url_raw e vazio → null (o React mandava '' e o view usava '#');
 * - ícones (repeaters de sobre, diferenciais e depoimentos) saem como IconData: ImageData + `svg`;
 * - sem 'footerCta': o banner do rodapé é lido pelo layout/footer (footer_cta_* da página).
 */
final class HomeController extends Controller
{
    /** Imagem do celular dos slides sem "Imagem Mobile" (React: imagemMobilePadrao no home.view.tsx). */
    private const HERO_IMAGEM_MOBILE = 'template-parts/includes/home/hero/assets/diagnostico-veterinario-em-sp-18.png';

    private const PORTFOLIO_TEXTO = 'Disponibilizamos um portfólio completo de exames laboratoriais para suporte ao diagnóstico veterinário, contemplando exames de rotina e análises especializadas.';

    private const SOBRE_TEXTO = "O Genoma é um centro de diagnóstico veterinário estruturado para oferecer exames laboratoriais e diagnósticos por imagem com precisão, segurança e rapidez.\n\nAtuamos como parceiros estratégicos de clínicas e hospitais veterinários, contribuindo diretamente para decisões clínicas mais assertivas.\n\nCom tecnologia moderna, controle rigoroso de qualidade e equipe especializada, entregamos resultados confiáveis todos os dias.";

    private const SOBRE_DESTAQUE = 'Mais do que exames, entregamos parceria, suporte técnico e confiança para a rotina clínica.';

    private const ESTRUTURA_DESCRICAO = 'O Genoma é um centro de diagnóstico veterinário estruturado para oferecer exames laboratoriais e diagnósticos por imagem';

    /**
     * Dados da página — chamado direto em PHP pelo View::render (sem REST).
     * Cada seção passa por section(): "Ocultar seção" marcado → null (o Part não renderiza).
     */
    #[Cache(ttl: 300)]
    public function page(int $pageId): array
    {
        return [
            'hero'         => $this->section($pageId, 'hero', fn(): array => $this->hero($pageId)),
            'servicos'     => $this->section($pageId, 'servicos', fn(): array => $this->servicos($pageId)),
            'portfolio'    => $this->section($pageId, 'portfolio', fn(): array => $this->portfolio($pageId)),
            'sobre'        => $this->section($pageId, 'sobre', fn(): array => $this->sobre($pageId)),
            'diferenciais' => $this->section($pageId, 'diferenciais', fn(): array => $this->diferenciais($pageId)),
            'estrutura'    => $this->section($pageId, 'estrutura', fn(): array => $this->estrutura($pageId)),
            'depoimentos'  => $this->section($pageId, 'depoimentos', fn(): array => $this->depoimentos($pageId)),
        ];
    }

    /**
     * Carrossel do topo. Sem slides cadastrados (ou nunca salvo) a lista fica vazia e o hero some,
     * como no React (o repeater não tem linhas padrão).
     */
    private function hero(int $pageId): array
    {
        // React: slide sem "Imagem Mobile" mostra esta imagem do tema no celular (object-fill em vez de
        // object-cover) — apagar o campo NÃO tira a imagem. Fica aqui para o template não ter padrão.
        $imagemMobilePadrao = $this->asset(self::HERO_IMAGEM_MOBILE);
        unset($imagemMobilePadrao['svg']);
        $rows = $this->rows($pageId, 'hero_slides') ?? [];

        return [
            'slides'             => array_map(fn(array $row): array => [
                'imagem'        => $this->image($row['imagem'] ?? null),
                'imagemMobile'  => $this->image($row['imagem_mobile'] ?? null),
                'eyebrow'       => $this->textFrom($row, 'eyebrow'),
                'titulo'        => $this->textFrom($row, 'titulo'),
                'subtitulo'     => $this->textFrom($row, 'subtitulo'),
                'ctaPrimario'   => $this->cta($this->textFrom($row, 'cta_primario_texto'), $this->textFrom($row, 'cta_primario_link')),
                'ctaSecundario' => $this->cta($this->textFrom($row, 'cta_secundario_texto'), $this->textFrom($row, 'cta_secundario_link')),
            ], $rows),
            'autoplay'           => $this->flag($pageId, 'hero_autoplay', true),
            'intervalo'          => $this->number($pageId, 'hero_intervalo', 6000),
            'imagemMobilePadrao' => $imagemMobilePadrao,
        ];
    }

    /** Grade de cards. Sem cards a seção some (React: o view não renderiza com a lista vazia). */
    private function servicos(int $pageId): array
    {
        $rows = $this->rows($pageId, 'servicos_itens') ?? [];

        return [
            'eyebrow' => $this->text($pageId, 'servicos_eyebrow', 'Nossos Serviços'),
            'titulo'  => $this->text($pageId, 'servicos_titulo', 'Tecnologia e cuidado a serviço da saúde animal'),
            'itens'   => array_map(fn(array $row): array => [
                'imagem'    => $this->image($row['imagem'] ?? null),
                'titulo'    => $this->textFrom($row, 'titulo'),
                'descricao' => $this->textFrom($row, 'descricao'),
                'cta'       => $this->cta($this->textFrom($row, 'cta_texto'), $this->textFrom($row, 'cta_link')),
            ], $rows),
            // botão abaixo dos cards; com mais de 3 cards o view mostra "Ver mais" no lugar (VerMaisButton do React)
            'cta'     => $this->cta(
                $this->text($pageId, 'servicos_cta_texto', 'Ver todos exames'),
                $this->text($pageId, 'servicos_cta_link', '#'),
            ),
        ];
    }

    /** Banner "Portfólio de Exames". */
    private function portfolio(int $pageId): array
    {
        return [
            'eyebrow' => $this->text($pageId, 'portfolio_eyebrow', 'Exames'),
            'titulo'  => $this->text($pageId, 'portfolio_titulo', 'Portfólio de Exames'),
            'texto'   => $this->text($pageId, 'portfolio_texto', self::PORTFOLIO_TEXTO),
            'cta'     => $this->cta(
                $this->text($pageId, 'portfolio_cta_texto', 'Lista de exames'),
                $this->text($pageId, 'portfolio_cta_link', '#'),
            ),
            'imagem'  => $this->picture($pageId, 'portfolio_imagem'),
        ];
    }

    /** "Sobre o Genoma": 3 fotos, texto institucional, frase de destaque e cards. */
    private function sobre(int $pageId): array
    {
        $rows = $this->rows($pageId, 'sobre_diferenciais') ?? [];

        return [
            'eyebrow'      => $this->text($pageId, 'sobre_eyebrow', 'Sobre o Genoma'),
            'titulo'       => $this->text($pageId, 'sobre_titulo', '10 anos fortalecendo a medicina veterinária'),
            // sempre 3 posições, como no React (foto apagada → null; o view deixa a 3ª mais larga)
            'fotos'        => [
                $this->picture($pageId, 'sobre_foto_1'),
                $this->picture($pageId, 'sobre_foto_2'),
                $this->picture($pageId, 'sobre_foto_3'),
            ],
            'paragrafos'   => $this->paragrafos($this->text($pageId, 'sobre_texto', self::SOBRE_TEXTO)),
            'destaque'     => $this->text($pageId, 'sobre_destaque', self::SOBRE_DESTAQUE),
            'diferenciais' => array_map(fn(array $row): array => [
                'icone'     => $this->iconFrom($row['icone'] ?? null),
                'titulo'    => $this->textFrom($row, 'titulo'),
                'descricao' => $this->textFrom($row, 'descricao'),
            ], $rows),
        ];
    }

    /** Banner com imagem de fundo + cards curtos. Sem cards a seção some (como no React). */
    private function diferenciais(int $pageId): array
    {
        $rows = $this->rows($pageId, 'diferenciais_itens') ?? [];

        return [
            'eyebrow' => $this->text($pageId, 'diferenciais_eyebrow', 'Diferenciais'),
            'titulo'  => $this->text($pageId, 'diferenciais_titulo', 'Por que escolher o Genoma?'),
            'imagem'  => $this->picture($pageId, 'diferenciais_imagem'),
            'itens'   => array_map(fn(array $row): array => [
                'icone'  => $this->iconFrom($row['icone'] ?? null),
                'titulo' => $this->textFrom($row, 'titulo'), // textarea: quebras de linha mantidas (whitespace-pre-line)
            ], $rows),
        ];
    }

    /** Etiqueta + título + descrição + galeria de 3 fotos. */
    private function estrutura(int $pageId): array
    {
        return [
            'eyebrow'   => $this->text($pageId, 'estrutura_eyebrow', 'Estrutura / Tecnologia'),
            'titulo'    => $this->text($pageId, 'estrutura_titulo', 'Tecnologia que gera confiança'),
            'descricao' => $this->text($pageId, 'estrutura_descricao', self::ESTRUTURA_DESCRICAO),
            // sempre 3 posições, como no React (foto apagada → null)
            'fotos'     => [
                $this->picture($pageId, 'estrutura_foto_1'),
                $this->picture($pageId, 'estrutura_foto_2'),
                $this->picture($pageId, 'estrutura_foto_3'),
            ],
        ];
    }

    /** Carrossel de depoimentos. Sem itens a seção some (como no React). */
    private function depoimentos(int $pageId): array
    {
        $rows = $this->rows($pageId, 'depoimentos_itens') ?? [];

        return [
            // padrão = default_value do campo, que no React ficou "Estrutura / Tecnologia" (copiado da seção
            // Estrutura); o banco tem "Depoimentos" — corrigir o default_value no acf_fields.php e aqui
            'eyebrow'   => $this->text($pageId, 'depoimentos_eyebrow', 'Estrutura / Tecnologia'),
            'titulo'    => $this->text($pageId, 'depoimentos_titulo', 'O que nossos clientes dizem'),
            'itens'     => array_map(fn(array $row): array => [
                'icone' => $this->iconFrom($row['icone'] ?? null),
                'texto' => $this->textFrom($row, 'texto'),
                'nome'  => $this->textFrom($row, 'nome'),
            ], $rows),
            'autoplay'  => $this->flag($pageId, 'depoimentos_autoplay', true),
            'intervalo' => $this->number($pageId, 'depoimentos_intervalo', 6000),
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
