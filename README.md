# UpWork Framework

Tema WordPress que funciona como um **framework MVC full-stack** — WordPress como CMS headless, backend PHP modular e frontend React SPA.

## Stack

| Camada | Tecnologia |
|--------|-----------|
| CMS | WordPress 6+ |
| Campos | ACF (registrados via código) |
| Backend | PHP 8.1+, PSR-4, Atributos PHP |
| Frontend | React 18, Vite 5, TypeScript, Tailwind CSS 3, shadcn/ui |
| API | REST em `/wp-json/framework/v1/` |
| State | TanStack Query |
| Forms | React Hook Form + Zod |

---

## Requisitos

- PHP 8.1+
- Node.js 18+
- Composer
- WordPress 6+ com ACF ativado

---

## Instalação

```bash
# 1. Instalar dependências PHP
composer install

# 2. Instalar dependências Node
npm install

# 3. Build de produção
npm run build

# 4. Ativar o tema no WordPress Admin → Appearance → Themes
```

---

## Estrutura de diretórios

```
UpWork/
├── app/                        ← Módulos (trabalho do dia a dia)
│   └── {slug}/
│       ├── {slug}.module.php   # Registro do módulo + campos ACF
│       ├── {slug}.controller.php # Endpoints REST
│       ├── {slug}.view.tsx     # Componente React
│       ├── {slug}.schema.ts    # Tipos TypeScript
│       └── assets/             # Imagens e ícones do módulo
├── core/                       ← Núcleo do framework (não mexer)
│   ├── Framework/              # Bootstrap, ModuleLoader, Rest, Attributes
│   ├── PostTypes/              # Registro de CPTs e taxonomias
│   ├── Admin/                  # ModuleManager, FormBuilder
│   └── Support/                # Asset (Vite manifest)
├── resources/                  ← Frontend
│   ├── app.tsx                 # Entry point React
│   ├── router.tsx              # React Router
│   ├── module-registry.ts      # Mapa slug → view (gerado automaticamente)
│   ├── components/
│   │   ├── layout/             # Header, Footer, Layout
│   │   └── shared/             # DynamicForm, ErrorBoundary
│   ├── hooks/                  # useModule, useDocumentTitle
│   └── lib/                    # api.ts, cn.ts, env.ts
├── bin/                        ← CLI scripts
│   ├── make-module.php         # Scaffold de módulo
│   └── sync-modules.php        # Regenera module-registry.ts
├── .claude/
│   └── commands/
│       └── bob.md              # Comando /bob (Figma → módulo)
├── index.php                   ← Shell HTML da SPA
├── functions.php               ← Bootstrap do tema
└── CLAUDE.md                   ← Contexto para IA
```

---

## Criando um novo módulo

```bash
composer fw:make:module nome-do-modulo
```

Cria `app/nome-do-modulo/` com os 4 arquivos e atualiza `module-registry.ts` automaticamente.

Depois:
1. No WordPress Admin → **Pages** → **Add New**
2. No painel direito → **Page Attributes** → **Template** → selecionar `Página · NomeDoModulo`
3. Publicar a página
4. Preencher os campos ACF
5. Acessar a URL

---

## Anatomia de um módulo

### `{slug}.module.php` — Registro e campos ACF

```php
<?php
declare(strict_types=1);
namespace App\QuemSomos;

use Core\Framework\Module;
use Core\Framework\Attributes\Module as Mod;

#[Mod(
    slug: 'quem-somos',
    name: 'Quem Somos',
    route: '/quem-somos',
    template: true,
    templateLabel: 'Página · Quem Somos',
)]
final class QuemSomosModule extends Module
{
    public function fields(): void
    {
        acf_add_local_field_group([
            'key'      => 'group_quem_somos',
            'title'    => 'Quem Somos — Campos',
            'location' => [[['param' => 'page_template', 'operator' => '==', 'value' => 'fw:quem-somos']]],
            'fields'   => [
                [
                    'key'           => 'field_quem_somos_titulo',
                    'label'         => 'Título',
                    'name'          => 'titulo',
                    'type'          => 'text',
                    'default_value' => 'Quem Somos',
                ],
                [
                    'key'           => 'field_quem_somos_imagem',
                    'label'         => 'Imagem',
                    'name'          => 'imagem',
                    'type'          => 'image',
                    'return_format' => 'array',
                ],
            ],
        ]);
    }
}
```

**Regras dos campos ACF:**
- Sempre registrar via código — nunca pela UI do ACF
- Key pattern: `field_{slug}_{nome}` (ex: `field_quem_somos_titulo`)
- Group key: `group_{slug}`
- Location: sempre `page_template == fw:{slug}`
- Sempre definir `default_value` para campos de texto/seleção

### `{slug}.controller.php` — Endpoints REST

```php
<?php
declare(strict_types=1);
namespace App\QuemSomos;

use Core\Framework\Controller;
use Core\Framework\Attributes\Get;
use Core\Framework\Attributes\Cache;

final class QuemSomosController extends Controller
{
    #[Get('/quem-somos')]
    #[Get('/quem-somos/:id')]
    #[Cache(ttl: 300)]
    public function index(\WP_REST_Request $request): array
    {
        $pageId = (int)($request->get_param('id') ?: 0);

        return [
            'titulo' => $this->field($pageId, 'titulo'),
            'imagem' => $this->image($this->field($pageId, 'imagem')),
        ];
    }
}
```

**Helpers disponíveis no Controller:**
| Método | Descrição |
|--------|-----------|
| `$this->field($pageId, 'key')` | Lê um campo ACF |
| `$this->fields($pageId)` | Lê todos os campos ACF do post |
| `$this->image($acfImage)` | Normaliza imagem ACF → `{src, alt, width, height, sizes}` |

**Atributos de rota:**
| Atributo | Descrição |
|----------|-----------|
| `#[Get('/rota')]` | Endpoint GET |
| `#[Post('/rota')]` | Endpoint POST |
| `#[Cache(ttl: 300)]` | Cache em transient WordPress |
| `#[Auth(role: 'editor')]` | Exige login + role |

**Repeaters:**
```php
$items = [];
if (have_rows('items', $pageId)) {
    while (the_row()) {
        $items[] = [
            'titulo' => get_sub_field('titulo'),
            'texto'  => get_sub_field('texto'),
        ];
    }
}
```

### `{slug}.schema.ts` — Tipos TypeScript

```typescript
export interface QuemSomosData {
  titulo: string
  imagem: {
    src: string
    alt: string
    width: number | null
    height: number | null
    sizes: Record<string, string>
  } | null
}
```

### `{slug}.view.tsx` — Componente React

```tsx
import { useModule } from '@/hooks/useModule'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'
import type { QuemSomosData } from './quem-somos.schema'

export default function QuemSomosView() {
  const { data, isLoading, error } = useModule<QuemSomosData>('quem-somos')
  useDocumentTitle(data?.titulo ?? 'Quem Somos')

  if (isLoading) return <Skeleton />
  if (error || !data) return (
    <div className="container py-16 text-center text-muted-foreground">Erro ao carregar.</div>
  )

  return (
    <main>
      <section className="container mx-auto px-4 py-16">
        <h1 className="text-4xl font-bold">{data.titulo}</h1>
        {data.imagem && (
          <img src={data.imagem.src} alt={data.imagem.alt} className="w-full h-auto mt-8" />
        )}
      </section>
    </main>
  )
}

function Skeleton() {
  return (
    <div className="animate-pulse container mx-auto px-4 py-16 space-y-4">
      <div className="h-10 w-1/2 bg-muted rounded" />
      <div className="h-64 bg-muted rounded-xl" />
    </div>
  )
}
```

---

## Custom Post Types

Declare CPTs diretamente no `module.php` com atributos:

```php
use Core\Framework\Attributes\PostType;
use Core\Framework\Attributes\Taxonomy;

#[Mod(slug: 'blog', name: 'Blog', route: '/blog', template: true)]
#[PostType(
    slug: 'post_blog',
    singular: 'Post',
    plural: 'Posts',
    icon: 'dashicons-edit',
    supports: ['title', 'editor', 'thumbnail', 'excerpt'],
)]
#[Taxonomy(
    slug: 'categoria_blog',
    singular: 'Categoria',
    plural: 'Categorias',
    postType: 'post_blog',
)]
final class BlogModule extends Module { ... }
```

---

## Formulários (Form Builder)

O framework inclui um Form Builder nativo. Criar formulários pelo WordPress Admin → **UpWork → Formulários**.

### Usando no React

```tsx
import { DynamicForm } from '@/components/shared/DynamicForm'

// Renderiza o formulário com slug 'contato'
<DynamicForm slug="contato" />
```

O componente busca o schema em `GET /wp-json/framework/v1/forms/contato` e submete em `POST /wp-json/framework/v1/forms/contato/submit`.

---

## Comandos

```bash
# Desenvolvimento
npm run dev                      # Vite dev server (porta 5173)
npm run build                    # Build de produção em public/build/

# CLI do framework
composer fw:make:module <slug>   # Scaffold de módulo (4 arquivos)
composer fw:sync-modules         # Regenera module-registry.ts
```

---

## Module Manager

WordPress Admin → **UpWork → Módulos**

Permite ativar/desativar módulos individualmente. Módulos marcados com `#[Required]` não podem ser desativados.

---

## Desenvolvimento com IA (Claude Code)

Este projeto inclui configuração para Claude Code em `.claude/`:

### `/bob` — Implementar bloco do Figma

```
/bob https://figma.com/design/... — hero com fundo escuro e CTA verde
```

Converte um frame Figma em módulo completo:
- Busca o design via Figma MCP (`get_design_context`)
- Cria os 4 arquivos do módulo
- Registra campos ACF com valores padrão do Figma
- Baixa assets para `app/{slug}/assets/`
- Roda `npm run build` para validar

---

## Convenções

- Campos ACF **sempre** via código no `module.php`, nunca pela UI
- Namespace PHP: `Core\` para o núcleo, `App\NomeDoModulo\` para módulos
- Alias TypeScript `@/` aponta para `resources/`
- Prefixo `upwork_` nas options do WordPress
- Keys ACF: `field_{slug}_{nome}`, groups: `group_{slug}`
- WooCommerce: feature futura — não implementar ainda
