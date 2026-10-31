/**
 * Home — links com a classe .anchor rolam suave até o alvo (#id).
 * (Também dá para usar só CSS: `scroll-behavior: smooth` no html.)
 */
export default function home() {
  document.addEventListener('click', (event) => {
    const link = event.target instanceof Element ? event.target.closest('a.anchor[href^="#"]') : null;
    if (!link) return;

    // getElementById: hash inválido como seletor (#1, #a.b) não lança erro
    let id;
    try {
      id = decodeURIComponent(link.hash.slice(1));
    } catch {
      return;
    }
    const target = id ? document.getElementById(id) : null;
    if (!target) return;

    event.preventDefault();
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    target.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' });
  });
}
