/**
 * Altura do header sticky (#fw-header, header.php) em `--fw-header-h` no <html> — o main.scss
 * usa no `scroll-padding-top`, para âncoras (link "pular para o conteúdo", #comments, seções)
 * não pararem por baixo dele. Header que não gruda (ex.: `lg:static`) → 0.
 * Com `[data-fw-header-bar]` dentro do header, mede só essa barra: o espaço que o header tem
 * apenas no topo da página (some ao rolar — e a âncora sempre rola) e o menu mobile aberto
 * não entram no recuo.
 */
export default function headerOffset() {
  const header = document.getElementById('fw-header');
  if (!header) return;
  const bar = header.querySelector('[data-fw-header-bar]') || header;

  const update = () => {
    const { position } = getComputedStyle(header);
    const height = position === 'sticky' || position === 'fixed' ? bar.offsetHeight : 0;
    document.documentElement.style.setProperty('--fw-header-h', `${height}px`);
  };

  update();
  // altura muda com o menu/fonte; sticky ↔ static muda no breakpoint (resize)
  if ('ResizeObserver' in window) new ResizeObserver(update).observe(header);
  window.addEventListener('resize', update, { passive: true });
}
