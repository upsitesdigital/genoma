export default function Footer() {
  return (
    <footer className="border-t border-border bg-background py-8">
      <div className="container text-center text-sm text-muted-foreground">
        &copy; {new Date().getFullYear()} — UpWork Framework
      </div>
    </footer>
  )
}
