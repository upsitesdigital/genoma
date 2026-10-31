<?php
/**
 * Formulário do Contact Form 7. O controller devolve só a referência (`$this->cf7($formId)` →
 * `fw-cf7:{id}`, que pode ir para o cache) e o shortcode é renderizado AQUI, a cada página:
 * o HTML do CF7 depende da requisição (valores/erros de um envio sem JS, URL do `action`) e
 * não pode ser servido do cache a outro visitante. O próprio CF7 liga validação e envio, sem JS do tema.
 *
 * Estilize pelas classes do CF7 (.wpcf7-form, .wpcf7-form-control, .wpcf7-submit,
 * .wpcf7-not-valid-tip, .wpcf7-response-output) via `class` com variantes arbitrárias do
 * Tailwind, ex: [&_.wpcf7-submit]:bg-primary.
 *
 *   Part::render('shared/cf7-form', ['html' => $data['form'], 'class' => '…']);
 *
 * @var array{html: string|null, class?: string} $data
 *      html = a referência do controller (`fw-cf7:{id}`).
 */

defined('ABSPATH') || exit;

$ref = (string) ($data['html'] ?? '');
if (!preg_match('/^fw-cf7:(\d+)$/', $ref, $m) || !function_exists('wpcf7_contact_form') || !wpcf7_contact_form((int) $m[1])) return;

$form = do_shortcode(sprintf('[contact-form-7 id="%d"]', (int) $m[1]));
?>
<div class="<?= fw_cn('fw-cf7', $data['class'] ?? '') ?>"><?= $form // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML do shortcode do CF7 ?></div>
