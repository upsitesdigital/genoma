<?php
/**
 * Redes sociais — ícones redondos com link para os perfis (Configuração do Tema → Rodapé → Redes
 * sociais, `rodape_redes`; novo no v3, o React não tinha). Usado no rodapé (abaixo do logo) e no
 * menu mobile do header (abaixo dos CTAs). Visual para fundo roxo: ícone branco, contorno claro.
 *
 *   Part::render('shared/social-links', ['class' => 'justify-center lg:justify-start']);
 *   $html = Part::html('shared/social-links', […]); // '' sem nenhuma rede: o container em volta pode sumir
 *
 * - Ordem da lista do painel; linha sem link ou sem ícone não aparece. Nunca salvo → as linhas
 *   padrão, que vêm sem link → nada aparece até o editor preencher.
 * - Cada ícone abre o perfil em nova aba; o nome da rede (ou o domínio, sem nome) vira o aria-label.
 *
 * @var array{class?: string} $data  class = classes extras da <ul> (alinhamento, margem).
 */

use Core\Admin\ThemeOptions;
use Core\Framework\Part;

defined('ABSPATH') || exit;

$redes = [];
foreach (ThemeOptions::rows('rodape_redes') ?? [] as $row) {
  $url  = fw_url(is_string($row['url'] ?? null) ? $row['url'] : null);
  $icon = ThemeOptions::icon($row['icone'] ?? null);
  if (!$url || !$icon) continue;
  $nome    = trim((string) ($row['nome'] ?? ''));
  $redes[] = ['url' => $url, 'icon' => $icon, 'nome' => $nome !== '' ? $nome : (string) wp_parse_url($url, PHP_URL_HOST)];
}
if (!$redes) return;
?>
<ul class="<?= fw_cn('flex flex-wrap items-center gap-3', $data['class'] ?? '') ?>">
  <?php foreach ($redes as $rede) : ?>
    <li>
      <a <?= fw_attrs([
        'href'       => $rede['url'],
        /* translators: %s: nome da rede social */
        'aria-label' => sprintf(__('%s (abre em nova aba)', 'upsites'), $rede['nome']),
        'class'      => 'flex size-10 items-center justify-center rounded-full border border-white/30 text-white/85 transition-colors hover:bg-white/10 hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white',
      ] + fw_target('_blank')) ?>>
        <?php Part::render('shared/icon', ['icon' => $rede['icon'], 'class' => 'size-5']); ?>
      </a>
    </li>
  <?php endforeach; ?>
</ul>
