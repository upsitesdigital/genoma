import { lazy, type ComponentType } from 'react'

export const modules: Record<string, ReturnType<typeof lazy<ComponentType>>> = {
  'blog': lazy(() => import('@/../app/blog/blog.view')),
  'contato': lazy(() => import('@/../app/contato/contato.view')),
  'home': lazy(() => import('@/../app/home/home.view')),
  'o-genoma': lazy(() => import('@/../app/o-genoma/o-genoma.view')),
  'pagina-padrao': lazy(() => import('@/../app/pagina-padrao/pagina-padrao.view')),
  'responsavel': lazy(() => import('@/../app/responsavel/responsavel.view')),
  'resultado-pesquisa': lazy(() => import('@/../app/resultado-pesquisa/resultado-pesquisa.view')),
  'single-post': lazy(() => import('@/../app/single-post/single-post.view')),
  'teste': lazy(() => import('@/../app/teste/teste.view')),
  'veterinarios': lazy(() => import('@/../app/veterinarios/veterinarios.view')),
}
