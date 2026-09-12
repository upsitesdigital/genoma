import { lazy, type ComponentType } from 'react'

interface ModuleEntry {
  path: string
  component: ReturnType<typeof lazy<ComponentType>>
}

export const modules: Record<string, ModuleEntry> = {
  'contato': {
    path: '/contato',
    component: lazy(() => import('@/../app/contato/contato.view')),
  },
  'home': {
    path: '/',
    component: lazy(() => import('@/../app/home/home.view')),
  },
  'o-genoma': {
    path: '/o-genoma',
    component: lazy(() => import('@/../app/o-genoma/o-genoma.view')),
  },
  'responsavel': {
    path: '/responsavel',
    component: lazy(() => import('@/../app/responsavel/responsavel.view')),
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
