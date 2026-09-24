import { StrictMode, Suspense } from 'react'
import { createRoot } from 'react-dom/client'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { modules } from './module-registry'
import Layout from '@/components/layout/Layout'
import ErrorBoundary from '@/components/shared/ErrorBoundary'
import { boot } from '@/lib/env'
import { seedQueryCache } from '@/lib/preload'
import './styles/globals.css'

const queryClient = new QueryClient({
  defaultOptions: {
    queries: { staleTime: 60_000, retry: 1 },
  },
})

seedQueryCache(queryClient)

const root = document.getElementById('app-root')

if (!root) throw new Error('#app-root não encontrado')

const ModuleComponent = boot.currentRoute ? modules[boot.currentRoute.module] : undefined

createRoot(root).render(
  <StrictMode>
    <ErrorBoundary>
      <QueryClientProvider client={queryClient}>
        <Layout>
          {ModuleComponent ? (
            <Suspense fallback={<div className="min-h-screen" />}>
              <ModuleComponent />
            </Suspense>
          ) : (
            <div className="container py-32 text-center">
              <h1 className="text-6xl font-bold">404</h1>
              <p className="mt-4 text-muted-foreground">Página não encontrada.</p>
            </div>
          )}
        </Layout>
      </QueryClientProvider>
    </ErrorBoundary>
  </StrictMode>
)
