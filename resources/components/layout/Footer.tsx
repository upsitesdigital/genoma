import { boot } from '@/lib/env'

export default function Footer() {
  const opts = boot.themeOptions as { footer_text?: string }
  const year = new Date().getFullYear()
  const text = opts.footer_text || `&copy; ${year} — UpWork Framework`

  return (
    <footer className="border-t border-border bg-background py-8">
      <div
        className="container text-center text-sm text-muted-foreground"
        dangerouslySetInnerHTML={{ __html: text }}
      />
    </footer>
  )
}
