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
     * Se não houver linhas cadastradas no ACF, a lista fica vazia (Hero some da tela).
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
            'imagem' => $this->image($row['imagem'] ?? null),
            'eyebrow' => (string) ($row['eyebrow'] ?? ''),
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

    /**
     * Monta a grade de cards da seção Serviços.
     * Se não houver linhas cadastradas no ACF, a lista fica vazia (seção some da tela).
     */
    private function servicos(int $pageId): array
    {
        $rows = $this->field($pageId, 'servicos_itens');

        return [
            'eyebrow' => (string) ($this->field($pageId, 'servicos_eyebrow') ?: ''),
            'titulo'  => (string) ($this->field($pageId, 'servicos_titulo') ?: ''),
            'itens'   => is_array($rows) ? array_map(fn (array $row): array => $this->mapServico($row), $rows) : [],
            'cta'     => [
                'texto' => (string) ($this->field($pageId, 'servicos_cta_texto') ?: ''),
                'link'  => (string) ($this->field($pageId, 'servicos_cta_link') ?: ''),
            ],
        ];
    }

    private function mapServico(array $row): array
    {
        return [
            'imagem'    => $this->image($row['imagem'] ?? null),
            'titulo'    => (string) ($row['titulo'] ?? ''),
            'descricao' => (string) ($row['descricao'] ?? ''),
            'cta'       => [
                'texto' => (string) ($row['cta_texto'] ?? ''),
                'link'  => (string) ($row['cta_link'] ?? ''),
            ],
        ];
    }

    /**
     * Monta o acordeão de categorias de exames.
     * Se não houver linhas cadastradas no ACF, a lista fica vazia (seção some da tela).
     */
    private function exames(int $pageId): array
    {
        $rows = $this->field($pageId, 'exames_categorias');

        return [
            'eyebrow'    => (string) ($this->field($pageId, 'exames_eyebrow') ?: ''),
            'titulo'     => (string) ($this->field($pageId, 'exames_titulo') ?: ''),
            'descricao'  => (string) ($this->field($pageId, 'exames_descricao') ?: ''),
            'categorias' => is_array($rows) ? array_map(fn (array $row): array => $this->mapExameCategoria($row), $rows) : [],
            'cta'        => [
                'texto' => (string) ($this->field($pageId, 'exames_cta_texto') ?: ''),
                'link'  => (string) ($this->field($pageId, 'exames_cta_link') ?: ''),
            ],
        ];
    }

    private function mapExameCategoria(array $row): array
    {
        $itens = $row['itens'] ?? [];

        return [
            'titulo' => (string) ($row['titulo'] ?? ''),
            'texto'  => (string) ($row['texto'] ?? ''),
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

    /**
     * Monta a seção "Sobre o Genoma" (fotos, texto institucional e cards de diferenciais).
     * Se não houver conteúdo cadastrado no ACF, os campos ficam vazios (elementos somem da tela).
     */
    private function sobre(int $pageId): array
    {
        $rows = $this->field($pageId, 'sobre_diferenciais');
        $texto = (string) ($this->field($pageId, 'sobre_texto') ?: '');

        return [
            'eyebrow'      => (string) ($this->field($pageId, 'sobre_eyebrow') ?: ''),
            'titulo'       => (string) ($this->field($pageId, 'sobre_titulo') ?: ''),
            'fotos'        => [
                $this->image($this->field($pageId, 'sobre_foto_1')),
                $this->image($this->field($pageId, 'sobre_foto_2')),
                $this->image($this->field($pageId, 'sobre_foto_3')),
            ],
            'paragrafos'   => array_values(array_filter(array_map('trim', preg_split('/\R\s*\R/', $texto)))),
            'destaque'     => (string) ($this->field($pageId, 'sobre_destaque') ?: ''),
            'diferenciais' => is_array($rows) ? array_map(fn (array $row): array => $this->mapDiferencial($row), $rows) : [],
        ];
    }

    private function mapDiferencial(array $row): array
    {
        return [
            'icone'     => $this->image($row['icone'] ?? null),
            'titulo'    => (string) ($row['titulo'] ?? ''),
            'descricao' => (string) ($row['descricao'] ?? ''),
        ];
    }

    /**
     * Monta a seção "Diferenciais" (banner com imagem de fundo + cards curtos).
     * Se não houver conteúdo cadastrado no ACF, os campos ficam vazios (elementos somem da tela).
     */
    private function diferenciais(int $pageId): array
    {
        $rows = $this->field($pageId, 'diferenciais_itens');

        return [
            'eyebrow' => (string) ($this->field($pageId, 'diferenciais_eyebrow') ?: ''),
            'titulo'  => (string) ($this->field($pageId, 'diferenciais_titulo') ?: ''),
            'imagem'  => $this->image($this->field($pageId, 'diferenciais_imagem')),
            'itens'   => is_array($rows) ? array_map(fn (array $row): array => $this->mapDiferencialItem($row), $rows) : [],
        ];
    }

    private function mapDiferencialItem(array $row): array
    {
        return [
            'icone'  => $this->image($row['icone'] ?? null),
            'titulo' => (string) ($row['titulo'] ?? ''),
        ];
    }

    /**
     * Monta a seção "Estrutura" (etiqueta + título + descrição + galeria de 3 fotos).
     * Se não houver conteúdo cadastrado no ACF, os campos ficam vazios (elementos somem da tela).
     */
    private function estrutura(int $pageId): array
    {
        return [
            'eyebrow'   => (string) ($this->field($pageId, 'estrutura_eyebrow') ?: ''),
            'titulo'    => (string) ($this->field($pageId, 'estrutura_titulo') ?: ''),
            'descricao' => (string) ($this->field($pageId, 'estrutura_descricao') ?: ''),
            'fotos'     => [
                $this->image($this->field($pageId, 'estrutura_foto_1')),
                $this->image($this->field($pageId, 'estrutura_foto_2')),
                $this->image($this->field($pageId, 'estrutura_foto_3')),
            ],
        ];
    }

    /**
     * Monta o carrossel de depoimentos de clientes.
     * Se não houver linhas cadastradas no ACF, a lista fica vazia (seção some da tela).
     */
    private function depoimentos(int $pageId): array
    {
        $rows = $this->field($pageId, 'depoimentos_itens');

        return [
            'eyebrow'   => (string) ($this->field($pageId, 'depoimentos_eyebrow') ?: ''),
            'titulo'    => (string) ($this->field($pageId, 'depoimentos_titulo') ?: ''),
            'itens'     => is_array($rows) ? array_map(fn (array $row): array => $this->mapDepoimento($row), $rows) : [],
            'autoplay'  => (bool) ($this->field($pageId, 'depoimentos_autoplay') ?? true),
            'intervalo' => (int) ($this->field($pageId, 'depoimentos_intervalo') ?: 6000),
        ];
    }

    private function mapDepoimento(array $row): array
    {
        return [
            'icone' => $this->image($row['icone'] ?? null),
            'texto' => (string) ($row['texto'] ?? ''),
            'nome'  => (string) ($row['nome'] ?? ''),
        ];
    }
}
