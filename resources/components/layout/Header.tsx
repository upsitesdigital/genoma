export default function Header() {
  return (
    <header className="border-b border-border bg-background">
      <div className="container flex h-16 items-center justify-between">
        <span className="text-lg font-semibold">UpWork</span>
        <nav>{/* menus via wp_nav_menu serão injetados via REST na Fase 1 */}</nav>
      </div>
    </header>
  )
}
