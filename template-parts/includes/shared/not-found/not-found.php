<?php
/**
 * Página não encontrada — porte do 404 do tema React (resources/app.tsx: rota sem módulo), sem
 * busca nem link, como lá. Corpo do 404.php (template-parts/error.php) e do View::render quando o
 * módulo não tem view. O status 404 é do WordPress (404.php) ou do View::render.
 *
 * @var array $data (não usado)
 */

defined('ABSPATH') || exit;
?>
<main>
  <div class="container py-32 text-center">
    <h1 data-reveal class="text-6xl font-bold">404</h1>
    <p data-reveal class="mt-4 text-muted-foreground"><?php esc_html_e('Página não encontrada.', 'upsites'); ?></p>
  </div>
</main>
