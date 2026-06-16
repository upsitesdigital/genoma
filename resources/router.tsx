import { createBrowserRouter } from 'react-router-dom'
import { Suspense } from 'react'
import { modules } from './module-registry'
import Layout from '@/components/layout/Layout'

const routes = Object.entries(modules).map(([, mod]) => ({
  path: mod.path,
  element: (
    <Suspense fallback={<div className="container py-16 text-center text-muted-foreground">Carregando...</div>}>
      <mod.component />
    </Suspense>
  ),
}))

export const router = createBrowserRouter([
  {
    path: '/',
    element: <Layout />,
    children: [
      ...routes,
      {
        path: '*',
        element: (
          <div className="container py-32 text-center">
            <h1 className="text-6xl font-bold">404</h1>
            <p className="mt-4 text-muted-foreground">Página não encontrada.</p>
          </div>
        ),
      },
    ],
  },
])
