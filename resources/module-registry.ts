import { lazy, type ComponentType } from 'react'

interface ModuleEntry {
  path: string
  component: ReturnType<typeof lazy<ComponentType>>
}

export const modules: Record<string, ModuleEntry> = {
  'home': {
    path: '/',
    component: lazy(() => import('@/../app/home/home.view')),
  },
  'teste': {
    path: '/teste',
    component: lazy(() => import('@/../app/teste/teste.view')),
  },
  'veterinarios': {
    path: '/veterinarios',
    component: lazy(() => import('@/../app/veterinarios/veterinarios.view')),
  },
}
