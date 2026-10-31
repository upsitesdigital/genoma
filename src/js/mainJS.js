/**
 * JS manual do tema (ajustes fora dos stores da Interactivity API), compilado pelo gulp → assets/js/mainJS(.min).js.
 * JavaScript puro — o tema não carrega jQuery. Se um script legado precisar de jQuery,
 * enfileire-o com a dependência ['jquery'] só na página que usa (upsites/functions.php).
 */
import headerOffset from './_header-offset';
import home from './pages/_home';

document.addEventListener('DOMContentLoaded', () => {
  headerOffset();
  home();
});
