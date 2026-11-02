<?php
declare(strict_types=1);

namespace App\OGenoma;

defined('ABSPATH') || exit; // acesso direto ao arquivo: nada a fazer

use Core\Framework\Controller;
use Core\Framework\Attributes\Cache;

/**
 * O Genoma — porte do app/o-genoma/o-genoma.controller.php do tema React: mesmos campos
 * (acf_fields.php, bloco o-genoma — keys legadas com hífen) e mesmo formato do o-genoma.schema.ts
 * (chaves iguais), para o markup ser portado 1:1 do o-genoma.view.tsx.
 *
 * Padrões (campo nunca salvo) = default_value de cada campo em acf_fields.php — no React o
 * get_field() já devolvia o default_value nesse caso. Imagens não têm padrão (como no React).
 *
 * Diferenças de semântica em relação ao React (intencionais, as mesmas da Home):
 * - texto salvo vazio → null (o React devolvia ''; nos dois o elemento não aparece);
 * - imagens saem como ImageData do v3 (com `id`; o React mandava só src/alt/width/height/sizes);
 * - sem 'footerCta': o banner do rodapé é lido pelo layout/footer (footer_cta_* da página).
 *
 * Os textos saem crus, com as quebras de linha do textarea como foram salvas (\r\n): quebrar em
 * linhas/parágrafos é do template — a regra de cada campo está no PHPDoc da seção.
 */
final class OGenomaController extends Controller
{
    private const SOBRE_TEXTO = "Em 2026, completamos 10 anos de atuação, marcando uma trajetória construída com base na ciência, na ética e no compromisso com a medicina veterinária. Ao longo dessa jornada, já realizamos mais de 150 mil atendimentos, contribuindo diariamente para diagnósticos seguros e para o cuidado com a saúde animal.\n\nAtuamos com foco em diagnóstico laboratorial de alta qualidade, utilizando metodologias modernas, equipamentos de ponta e rigorosos padrões de controle, sempre alinhados às boas práticas laboratoriais e às demandas da rotina clínica veterinária.";

    private const COMPROMISSO_TEXTO = 'Trabalhamos para oferecer informação diagnóstica confiável, suporte técnico qualificado e um atendimento próximo, transparente e eficiente, fortalecendo parcerias sólidas com clínicas e hospitais veterinários.';

    private const DIAGNOSTICO_TEXTO = "Acreditamos que um diagnóstico bem-feito é essencial para a condução adequada dos casos clínicos, o bem-estar animal e a confiança entre profissionais.\n\nPor isso, investimos continuamente em tecnologia, capacitação da equipe e melhoria constante dos processos, garantindo resultados consistentes e dentro dos prazos esperados.";

    private const DIAGNOSTICO_DESTAQUE = 'O Genoma Diagnóstico Veterinário é movido pela experiência, pela inovação e pela responsabilidade com cada resultado entregue.';

    /**
     * Dados da página — chamado direto em PHP pelo View::render (sem REST).
     * Cada seção passa por section(): "Ocultar seção" marcado → null (o Part não renderiza).
     */
    #[Cache(ttl: 300)]
    public function page(int $pageId): array
    {
        return [
            'hero'        => $this->section($pageId, 'hero', fn(): array => $this->hero($pageId)),
            'sobre'       => $this->section($pageId, 'sobre', fn(): array => $this->sobre($pageId)),
            'compromisso' => $this->section($pageId, 'compromisso', fn(): array => $this->compromisso($pageId)),
            'diagnostico' => $this->section($pageId, 'diagnostico', fn(): array => $this->diagnostico($pageId)),
        ];
    }

    /**
     * Carrossel do topo. Sem slides cadastrados (ou nunca salvo) a lista fica vazia e o hero some,
     * como no React (o repeater não tem linhas padrão).
     */
    private function hero(int $pageId): array
    {
        $rows = $this->rows($pageId, 'hero_slides') ?? [];

        return [
            'slides'    => array_map(fn(array $row): array => [
                'eyebrow'  => $this->textFrom($row, 'eyebrow'),
                'titulo'   => $this->textFrom($row, 'titulo'),
                'imagem1'  => $this->image($row['imagem_1'] ?? null),
                'imagem2'  => $this->image($row['imagem_2'] ?? null),
                'destaque' => $this->textFrom($row, 'destaque'),
            ], $rows),
            'autoplay'  => $this->flag($pageId, 'hero_autoplay', true),
            'intervalo' => $this->number($pageId, 'hero_intervalo', 6000),
        ];
    }

    /** "Sobre nós". Com os 4 textos vazios a seção some (React: o view não renderiza). */
    private function sobre(int $pageId): array
    {
        return [
            'eyebrow'  => $this->text($pageId, 'sobre_eyebrow', 'Sobre nós'),
            'titulo'   => $this->text($pageId, 'sobre_titulo', 'Uma década de excelência em diagnóstico veterinário.'),
            'texto'    => $this->text($pageId, 'sobre_texto', self::SOBRE_TEXTO),
            'destaque' => $this->text($pageId, 'sobre_destaque', "Ciência, precisão e confiança em mais de\n150 mil atendimentos"),
        ];
    }

    /** Banner com imagem de fundo. Sem etiqueta, título e texto a seção some, mesmo com imagem (como no React). */
    private function compromisso(int $pageId): array
    {
        return [
            'eyebrow' => $this->text($pageId, 'compromisso_eyebrow', 'Compromisso'),
            'titulo'  => $this->text($pageId, 'compromisso_titulo', 'Nosso compromisso vai além da liberação de resultados.'),
            'texto'   => $this->text($pageId, 'compromisso_texto', self::COMPROMISSO_TEXTO),
            'imagem'  => $this->picture($pageId, 'compromisso_imagem'),
        ];
    }

    /** "Diagnóstico que gera confiança" (última seção). Com os 3 textos vazios a seção some (como no React). */
    private function diagnostico(int $pageId): array
    {
        return [
            'titulo'   => $this->text($pageId, 'diagnostico_titulo', 'Diagnóstico que gera confiança'),
            'texto'    => $this->text($pageId, 'diagnostico_texto', self::DIAGNOSTICO_TEXTO),
            'destaque' => $this->text($pageId, 'diagnostico_destaque', self::DIAGNOSTICO_DESTAQUE),
        ];
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
