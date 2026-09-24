import { useRef, type ReactNode } from 'react'
import Header from './Header'
import Footer from './Footer'
import { useScrollReveal } from '@/hooks/useScrollReveal'

export default function Layout({ children }: { children: ReactNode }) {
  const rootRef = useRef<HTMLDivElement>(null)
  useScrollReveal(rootRef)

  return (
    <div ref={rootRef} className="flex min-h-screen flex-col">
      <Header />
      <main className="flex-1">{children}</main>
      <Footer />
    </div>
  )
}
