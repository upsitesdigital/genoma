# UpWork Framework — Guia para Claude

## O que é este projeto

Tema WordPress que funciona como um **framework MVC full-stack**:
- **WordPress** = CMS headless (admin, banco, autenticação, ACF)
- **Backend PHP** = módulos em `app/`, núcleo em `core/`
- **Frontend** = SPA React (Vite + TypeScript + Tailwind + shadcn/ui)
- **Comunicação** via REST API em `/wp-json/framework/v1/`

## Estrutura de diretórios

```
UpWork/
├── app/               ← MÓDULOS (onde o trabalho do dia a dia acontece)
│   └── home/
│       ├── home.module.php       # Registro: #[Module], ACF fields
│       ├── home.controller.php   # Endpoints REST com #[Get], #[Post]
│       ├── home.view.tsx         # Componente React da página
│       └── home.schema.ts        # Tipos TypeScript
├── core/              ← NÚCLEO (não mexer no dia a dia)
│   ├── Framework/     # Bootstrap, Module, Controller, ModuleLoader, Rest, RouteResolver
│   ├── PostTypes/     # Registro de CPTs e taxonomias
│   └── Admin/         # ModuleManager, FormBuilder
├── resources/         ← FRONTEND
│   ├── app.tsx        # Entry point React
│   ├── router.tsx     # React Router
│   ├── module-registry.ts  # Mapa slug → view (gerado por fw:sync-modules)
│   ├── components/
│   │   ├── layout/    # Header, Footer, Layout
│   │   └── shared/    # DynamicForm, ErrorBoundary
│   ├── hooks/         # useModule, useDocumentTitle
│   └── lib/           # api.ts, cn.ts, env.ts
├── bin/               ← CLI scripts
│   ├── make-module.php   # scaffold de módulo
│   └── sync-modules.php  # regenera module-registry.ts
├── index.php          ← Shell HTML da SPA (injeta FW_BOOT + assets Vite)
└── functions.php      ← Carrega Composer + Bootstrap::init()
```

## Como criar um novo módulo

```bash
composer fw:make:module nome-do-modulo
```

Cria `app/nome-do-modulo/` com os 4 arquivos e atualiza `module-registry.ts`.

Depois:
1. No WordPress admin → criar uma Page → Template → "Página · NomeDoModulo"
2. Preencher os campos ACF
3. Acessar a URL → React renderiza via `useModule('nome-do-modulo')`

## Convenções importantes

### PHP (módulo)
```php
#[Mod(slug: 'home', name: 'Home', route: '/', template: true)]
#[Required]  // não pode ser desativado no Module Manager
final class HomeModule extends Module {
    public function fields(): void {
        acf_add_local_field_group([...]); // SEMPRE via código, nunca pela UI do ACF
    }
}
```

### PHP (controller)
```php
#[Get('/home')]        // monta em /wp-json/framework/v1/home
#[Get('/home/:id')]    // :param vira regex WP REST
#[Cache(ttl: 300)]     // cacheia resposta em transient
#[Auth(role: 'editor')] // exige autenticação + role
public function index(\WP_REST_Request $request): array { ... }
```

### React (view)
```tsx
const { data, isLoading, error } = useModule<HomeData>('home')
// chama GET /wp-json/framework/v1/home ou /home/{pageId}
```

### Formulários
```tsx
<DynamicForm slug="contato" />
// busca schema em /wp-json/framework/v1/forms/contato
// submete em POST /wp-json/framework/v1/forms/contato/submit
```

## Stack

**Backend:** PHP 8.1+, WordPress 6+, ACF (campos via código), Composer PSR-4  
**Frontend:** React 18, Vite 5, TypeScript strict, Tailwind CSS 3, shadcn/ui, TanStack Query, React Router 6, Zod, React Hook Form  

## Comandos

```bash
npm run dev          # Vite dev server (porta 5173)
npm run build        # Build de produção em public/build/
composer fw:make:module <slug>   # Scaffold de módulo
composer fw:sync-modules         # Regenera module-registry.ts
```

## Regras

- Campos ACF **sempre** via código em `module.php`, nunca pela UI
- WooCommerce é feature futura — não implementar ainda
- Prefixo `upwork_` nas options do WordPress (`upwork_theme_options`, `upwork_active_modules`)
- Namespace PHP: `Core\` para o núcleo, `App\NomeDoModulo\` para módulos
- Alias TypeScript `@/` aponta para `resources/`
