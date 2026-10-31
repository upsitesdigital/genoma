<?php
/**
 * Fecha a página: o footer do site, wp_footer (script modules da Interactivity API) e </body></html>.
 *
 * O footer é a parte template-parts/includes/layout/footer/footer.php, renderizada no
 * servidor em TODAS as páginas (módulos e templates PHP). Não monte footer aqui —
 * edite o footer.php da parte.
 *
 * @package UpWork
 */

use Core\Framework\Part;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
	</div>
	<div id="fw-footer"><?php Part::render( 'layout/footer' ); ?></div>
</div>

<?php wp_footer(); ?>

</body>
</html>
