# Genoma Diagnósticos — Como o site funciona

Documentação do site da **Genoma Diagnósticos** construído sobre o tema/framework **UpWork**.
Para a referência genérica do framework (anatomia de módulo, CLI, Form Builder), veja o [README](../README.md).
Para quem edita o conteúdo no painel, veja o [Guia de Uso](GUIA-DE-USO.md).

---

## Sumário

- [Visão geral](#visão-geral)
- [Arquitetura](#arquitetura)
- [Fluxo de uma requisição](#fluxo-de-uma-requisição)
- [Roteamento: qual módulo renderiza cada URL](#roteamento-qual-módulo-renderiza-cada-url)
- [Páginas (módulos)](#páginas-módulos)
- [Layout global: Header e Footer](#layout-global-header-e-footer)
- [Opções do Tema](#opções-do-tema)
- [Menus](#menus)
- [Cache, preload e imagens WebP](#cache-preload-e-imagens-webp)
- [Responsividade e design](#responsividade-e-design)
- [Ambiente de desenvolvimento](#ambiente-de-desenvolvimento)
- [Build e deploy](#build-e-deploy)
- [Como fazer tarefas comuns](#como-fazer-tarefas-comuns)
- [Pontos de atenção conhecidos](#pontos-de-atenção-conhecidos)

---

## Visão geral

| Camada | Papel |
|---|---|
| **WordPress** | CMS: páginas, posts do blog, mídia, menus, usuários. O editor preenche o conteúdo pelos campos **ACF** de cada página. |
| **PHP (`app/`, `core/`)** | Cada página é um **módulo**: registra seus campos ACF e expõe um endpoint REST que devolve o conteúdo em JSON. |
| **React (`resources/`, `app/*/*.view.tsx`)** | SPA que lê o JSON e renderiza a página. Header e Footer são globais. |
| **REST** | Tudo em `/wp-json/framework/v1/...` |

O WordPress **não renderiza HTML de conteúdo**: todo template PHP (`front-page.php`, `page.php`, `single.php`, `search.php`, `index.php`, `404.php`) só descobre o módulo da URL e imprime o "shell" da SPA.

---

## Arquitetura

```
upwork/
├── app/                      ← um diretório por página (módulo)
│   └── home/
│       ├── home.module.php       # #[Module] + campos ACF da página
│       ├── home.controller.php   # GET /framework/v1/home/{id} → JSON
│       ├── home.schema.ts        # tipos TypeScript do JSON
│       ├── home.view.tsx         # componente React da página
│       └── assets/               # SVGs/imagens estáticas do módulo
├── core/
│   ├── Framework/   # Bootstrap, ModuleLoader, RouteResolver, Shell, Rest, Controller
│   ├── Admin/       # Opções do Tema, Module Manager, API de menus, Form Builder
│   ├── Support/     # Asset (Vite), Webp
│   ├── PostTypes/   # suporte a CPTs/taxonomias por atributo (não usado no site)
│   └── acf/         # ACF embarcado no tema
├── resources/
│   ├── app.tsx                 # entry point React
│   ├── module-registry.ts      # slug → view (gerado automaticamente)
│   ├── components/layout/      # Header, Footer, Layout
│   ├── hooks/                  # useModule, useScrollReveal, useDocumentTitle...
│   ├── lib/                    # api, env (FW_BOOT), preload, cn
│   └── styles/globals.css
├── public/build/               # saída do Vite (gerada, fora do git)
└── bin/                        # make-module.php, sync-modules.php
```

---

## Fluxo de uma requisição

```
Navegador → /veterinarios/
   │
   ├─ WordPress carrega page.php
   │     └─ RouteResolver::current()  →  { module: 'veterinarios', pageId: 42, ... }
   │     └─ Shell::render()
   │          ├─ <title>, meta description, Open Graph
   │          ├─ executa internamente GET /framework/v1/veterinarios/42  (preload)
   │          ├─ executa internamente GET /menus/primary e /menus/footer
   │          └─ window.FW_BOOT = { apiBase, siteUrl, themeUrl, themeOptions, currentRoute, preload }
   │
   └─ React (resources/app.tsx)
         ├─ semeia o cache do TanStack Query com o preload (sem skeleton, sem fetch extra)
         ├─ Layout = Header + <View do módulo> + Footer
         └─ useModule('veterinarios') lê os dados do cache
```

Navegação entre páginas é **por recarga completa** (links `<a href>` normais): cada URL passa pelo WordPress e recebe o próprio preload. Paginação do blog/busca é a exceção — feita no cliente via REST.

---

## Roteamento: qual módulo renderiza cada URL

Definido em [`core/Framework/RouteResolver.php`](../core/Framework/RouteResolver.php), nesta ordem:

| # | Condição | Módulo |
|---|---|---|
| 1 | Busca nativa (`/?s=termo`) | `resultado-pesquisa` (lê os campos da primeira Page com esse Modelo, se existir) |
| 1b | "Página de posts" de Configurações → Leitura (se definida) | `blog` |
| 2 | Page com Modelo **"Página · X"** (`fw:x`) | o módulo `x` |
| 3 | Page com **Modelo por omissão** (não é a home) | `pagina-padrao` |
| 4 | Página inicial (Configurações → Leitura) | `home` |
| 5 | Post individual | `single-post` |
| 6 | Qualquer outra coisa | 404 |

Os arquivos de categoria nativos (`/category/slug/`) são redirecionados (301) para o blog filtrado, `/blog/?categoria=slug` (`Bootstrap::redirectCategoryArchive`).

> O Modelo é escolhido no editor da página (painel **Página → Modelo**). Páginas com Modelo de módulo não exibem o editor de conteúdo nativo — o conteúdo vem só dos campos ACF.

---

## Páginas (módulos)

Todos os endpoints são `GET /wp-json/framework/v1/{slug}/{pageId}` e públicos. Os campos ACF de cada módulo aparecem só nas páginas com o Modelo correspondente.

### Home — `app/home` · Modelo "Página · Home"

Seções, em ordem:

1. **Hero (carrossel)** — slides com fade, autoplay configurável, setas e indicadores (com mais de 1 slide). No desktop usa imagem de fundo com gradiente; no mobile, fundo sólido com a **Imagem Mobile** na base e onda decorativa. O *eyebrow* do 1º slide é o `<h1>` da página.
2. **Serviços** — grade de cards (imagem, título, descrição, botão). Mostra 3; "Ver mais" expande.
3. **Exames** — acordeão de categorias (uma aberta por vez), cada uma com texto e lista de exames (nome, prazo, amostra). Mostra 3; "Ver mais" expande.
4. **Sobre a Genoma** — 3 fotos, texto, destaque e cards de diferenciais.
5. **Diferenciais** — banner com imagem e cards com ícone.
6. **Estrutura** — texto e galeria de 3 fotos.
7. **Depoimentos** — carrossel no desktop; cards empilhados no mobile.

| Grupo ACF | Campos |
|---|---|
| Hero | `hero_slides` (repeater: `imagem`, `imagem_mobile`, `eyebrow`, `titulo`, `subtitulo`, `cta_primario_texto/link`, `cta_secundario_texto/link`), `hero_autoplay`, `hero_intervalo` |
| Serviços | `servicos_eyebrow`, `servicos_titulo`, `servicos_itens` (`imagem`, `titulo`, `descricao`, `cta_texto`, `cta_link`), `servicos_cta_texto/link` |
| Exames | `exames_eyebrow`, `exames_titulo`, `exames_descricao`, `exames_categorias` (`titulo`, `texto`, `itens` → `nome`, `prazo`, `amostra`), `exames_cta_texto/link` |
| Sobre | `sobre_eyebrow`, `sobre_titulo`, `sobre_foto_1..3`, `sobre_texto`, `sobre_destaque`, `sobre_diferenciais` (`icone`, `titulo`, `descricao`) |
| Diferenciais | `diferenciais_eyebrow`, `diferenciais_titulo`, `diferenciais_imagem`, `diferenciais_itens` (`icone`, `titulo`) |
| Estrutura | `estrutura_eyebrow`, `estrutura_titulo`, `estrutura_descricao`, `estrutura_foto_1..3` |
| Depoimentos | `depoimentos_eyebrow`, `depoimentos_titulo`, `depoimentos_itens` (`icone`, `texto`, `nome`), `depoimentos_autoplay`, `depoimentos_intervalo` |
| CTA do rodapé | ver [CTA do rodapé por página](#cta-do-rodapé-por-página) |

### O Genoma — `app/o-genoma` · Modelo "Página · O Genoma"

1. **Hero (carrossel)** — título, destaque entre linhas e duas imagens lado a lado.
2. **Sobre** — texto e destaque.
3. **Compromisso** — banner com imagem de fundo.
4. **Diagnóstico** — título, texto e destaque.

Campos: `hero_slides` (`eyebrow`, `titulo`, `imagem_1`, `imagem_2`, `destaque`), `hero_autoplay/intervalo`, `sobre_*`, `compromisso_*` (com `compromisso_imagem`), `diagnostico_*`, `footer_cta_*`.

### Veterinários — `app/veterinarios` · Modelo "Página · Veterinários"

1. **Hero (carrossel)** — título, subtítulo, 2 botões, imagem à direita.
2. **Suporte** — texto, citação e duas imagens.
3. **Praticidade** — card roxo com horários de coleta por motoboy (`praticidade_horarios`).
4. **Exames** — imagem, texto e botão "Lista de exames".
5. **Estrutura** — texto, destaque, 2 imagens e card de contatos.
6. **Benefícios** — grade de 4 cards com ícone.

Campos: `hero_slides`, `suporte_*`, `praticidade_*`, `exames_*`, `estrutura_*` (com `estrutura_contatos`), `beneficios_*`, `footer_cta_*`.

### Responsável (tutor) — `app/responsavel` · Modelo "Página · Responsável"

1. **Hero (carrossel)** — título, subtítulo, 2 botões, imagem recortada.
2. **Suporte** — texto, citação e duas imagens.
3. **Planos** — logos dos planos de saúde pet aceitos (`planos_logos`).
4. **Exames / preparo** — itens com ícone sobre jejum e preparo.
5. **Serviços** — acordeão de categorias de exames (várias abertas ao mesmo tempo; `aberto` define o estado inicial) com tabela nome/prazo/amostra.
6. **Resultados** — texto e imagem.
7. **Benefícios** — grade de 4 cards.

Campos: `hero_slides`, `suporte_*`, `planos_*`, `exames_*`, `servicos_*` (com `servicos_categorias`), `resultados_*`, `beneficios_*`, `footer_cta_*`.

### Contato — `app/contato` · Modelo "Página · Contato"

1. **Hero** — título, descrição e cards de canais (ícone, título, valor; vira link se `link` for preenchido — ex. `https://wa.me/...`, `mailto:`).
2. **Lista de contatos** — conteúdo flexível com dois layouts: `card_info` (título, descrição, itens com ícone) e `card_texto` (título, texto, botão opcional).

Campos: `hero_eyebrow`, `hero_titulo`, `hero_descricao`, `hero_canais`, `lista_contatos`. Não há formulário nesta página.

### Blog — `app/blog` · Modelo "Página · Blog"

1. **Hero** — título, busca (envia para `/?s=termo`) e filtro por categoria (`?categoria=slug`). A categoria com slug **`em-destaque`** aparece sempre em 2º lugar, logo após "Todos".
2. **Lista de posts** — grade de cards com paginação numerada no cliente (`GET /blog-posts?page=&categoria=`).

- Posts por página = **Configurações → Leitura → "As páginas do blog mostram no máximo"**.
- Imagem do card = imagem destacada do post (placeholder se não houver).
- Campos ACF: `hero_eyebrow`, `hero_titulo`, `hero_busca_placeholder`.

### Post individual — `app/single-post` · automático

Hero (data, categoria — com link para o blog filtrado por ela —, título, imagem destacada, botões de compartilhar por e-mail/LinkedIn/WhatsApp/Facebook), conteúdo do editor e **"Veja também"** com 3 posts da mesma categoria (completa com os mais recentes). Sem campos ACF.

### Resultado da pesquisa — `app/resultado-pesquisa` · automático em `/?s=`

Hero com o termo e nova busca, e grade de resultados paginada (`GET /resultado-pesquisa-lista?s=&page=`). Busca em **posts e páginas** (exceto a própria página de resultados); páginas aparecem sem categoria e com a imagem placeholder. Para editar os textos (`hero_eyebrow`, `hero_busca_placeholder`, `sem_resultados_texto`), crie uma Page com o Modelo "Página · ResultadoPesquisa".

### Página padrão — `app/pagina-padrao` · automático

Qualquer página com "Modelo por omissão" (ex.: Política de Privacidade, Termos): título + conteúdo do editor de blocos, estilizado por `.post-content`.

### Teste — `app/teste`

Módulo de demonstração que veio com o framework. Não faz parte do site; pode ser desativado em **UpWork → Módulos**.

---

## Layout global: Header e Footer

Ficam em [`resources/components/layout/`](../resources/components/layout/) e aparecem em todas as páginas.

### Header

- **Desktop:** fixo no topo, transparente sobre o hero e com fundo `#ABC3CF` ao rolar. Logo, menu `primary` (com submenus em dropdown) e dois botões (Resultados / Contato).
- **Mobile/tablet (< 1024px):** fundo branco, altura mínima de 93px, logo com 122px e botão de menu redondo. O menu aberto ocupa a tela inteira e trava o scroll da página.

### Footer

1. **CTA "Fale conosco"** — imagem + título + 1 ou 2 botões. No desktop é um card branco sobreposto à seção anterior; no mobile, uma seção lilás com tudo empilhado.
2. **Barra principal** — desktop: logo, menu `footer` e botão Contato. Mobile: logo, frase, e-mail e telefone.
3. **Barra inferior** — copyright, Política de privacidade e créditos (no mobile: copyright, "Todos os direitos reservados." e Política de privacidade).

### CTA do rodapé por página

O título e os botões do CTA vêm, em ordem de prioridade:

1. Dos campos **`footer_cta_*`** da página atual (Home, O Genoma, Veterinários, Responsável):
   `footer_cta_titulo`, `footer_cta_primario_texto/link`, `footer_cta_mostrar_secundario`, `footer_cta_secundario_texto/link`.
2. Das **Opções do Tema** (todas as outras páginas, ou campos deixados em branco — inclusive na Home).

Com apenas 1 botão, ele usa o estilo preenchido.

---

## Opções do Tema

**WP Admin → UpWork → Opções do Tema** (option `upwork_theme_options`). Tudo é exposto ao React em `FW_BOOT.themeOptions`.

| Grupo | Campos |
|---|---|
| Identidade | Nome do site, Logo (header), Logo do rodapé, Cor primária |
| Header | Botão primário (Resultados) e secundário (Contato): texto + URL |
| CTA do rodapé (padrão) | Eyebrow, título, imagem, botão primário e secundário |
| Rodapé | Texto de copyright (aceita HTML), Política de privacidade (texto + URL), créditos |
| Rodapé mobile | Frase abaixo do logo, e-mail, telefone, "direitos reservados" |

> E-mail e telefone só aparecem no rodapé mobile se forem preenchidos.

---

## Menus

**Aparência → Menus**, duas localizações:

| Localização | Onde aparece |
|---|---|
| `primary` — Menu Principal | Header (desktop e menu mobile). Itens filhos viram dropdown. |
| `footer` — Menu Rodapé | Barra do rodapé (somente desktop). |

Servidos por `GET /wp-json/framework/v1/menus/{localização}` e pré-carregados no shell.

---

## Cache, preload e imagens WebP

- **Cache REST:** os endpoints dos módulos usam `#[Cache(ttl: 60–300)]` (transients `fw_rest_*`). O cache inteiro é limpo automaticamente ao **salvar ou excluir qualquer post/página**.
- **Preload:** o shell já embute o JSON da página e dos menus em `FW_BOOT.preload`, então a primeira renderização não faz requisição nem mostra skeleton.
- **WebP:** [`core/Support/Webp.php`](../core/Support/Webp.php) troca URLs `.jpg/.png` de `wp-content` pela versão `.webp` gerada pelo plugin **WebP Express**, quando ela existe no disco. Isso vale para o JSON da API e para o `FW_BOOT`, porque no servidor (nginx) as regras de `.htaccess` do plugin não se aplicam.
- **Animação de entrada:** [`useScrollReveal`](../resources/hooks/useScrollReveal.ts) anima automaticamente títulos, textos, imagens, botões e cards ao entrarem na tela. Para excluir um bloco, use `data-no-reveal`.

---

## Responsividade e design

- **Breakpoint principal: `lg` (1024px).** Abaixo dele vale o layout **mobile do Figma**; a partir dele, o layout desktop. Nos componentes, as classes sem prefixo são as do mobile e as com `lg:` são as do desktop.
- Padrões do mobile aplicados em todo o site:
  - Hero com `padding-top: 186px` (o header fixo tem 93px).
  - Botões com largura total (`w-full lg:w-auto` / `lg:w-fit`).
  - Setas de carrossel escondidas (ficam só os indicadores).
- **Container:** 20px de margem lateral no mobile, 32px a partir de `sm`, máximo 1400px.
- **Tokens** ([`tailwind.config.js`](../tailwind.config.js)):

| Token | Valor | Uso |
|---|---|---|
| `brand-purple` | `#433292` | botões, cor primária |
| `brand-purple-dark` | `#2D2559` | títulos, rodapé mobile |
| `brand-purple-accent` | `#6E47F2` | eyebrows, destaques |
| `brand-light-purple` | `#F5F4FB` | fundos de cards e do CTA |
| `brand-gray-text` | `#6E6E6E` | textos de apoio |
| `brand-gray-border` | `#DFDEE3` | bordas, textos claros no rodapé |
| Fontes | Poppins (títulos), Manrope (texto) | |
| Tipografia | `text-h1`…`text-h5`, `text-h2-mobile`, `text-eyebrow`, `text-body(-lg/-sm)` | |

- O layout de referência está no Figma do projeto (*Genoma Diagnósticos*).

---

## Ambiente de desenvolvimento

Requisitos: PHP 8.1+, Composer, Node 18+, WordPress 6+ (local: XAMPP).
O ACF vem **embarcado** no tema (`core/acf/`); não é preciso instalar o plugin. Os campos são registrados **sempre via código**, nunca pela interface do ACF.

```bash
composer install     # autoload PSR-4
npm install
npm run dev          # Vite em http://localhost:5173 com HMR
```

Com o `npm run dev` ativo, o Vite grava `public/build/hot` e o tema passa a carregar os assets do dev server. Ao parar o servidor, o arquivo é removido e o tema volta a usar o build.

| Comando | O que faz |
|---|---|
| `npm run dev` | Sincroniza módulos e sobe o Vite (HMR) |
| `npm run build` | Sincroniza módulos, checa tipos (`tsc`) e gera `public/build/` |
| `npm run lint` / `npm run typecheck` | ESLint / TypeScript |
| `composer fw:make:module <slug>` | Cria um módulo novo com os 4 arquivos |
| `composer fw:sync-modules` | Regera `resources/module-registry.ts` |

---

## Build e deploy

`public/build/` e `vendor/` estão no `.gitignore`. Portanto, **todo deploy precisa gerar os dois**:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

Se o build for feito localmente e só os arquivos forem enviados ao servidor, inclua `public/build/` (com o `manifest.json`) e `vendor/` no upload. Garanta também que **não** exista `public/build/hot` no servidor, senão o tema tentará carregar os assets de `localhost:5173`.

---

## Como fazer tarefas comuns

**Criar uma nova página com layout próprio**
1. `composer fw:make:module minha-pagina` → cria `app/minha-pagina/`.
2. Registre os campos ACF em `minha-pagina.module.php`, devolva-os no controller, tipe no `.schema.ts` e monte o `.view.tsx`.
3. `npm run build`.
4. No admin: criar Page → Modelo "Página · MinhaPagina" → preencher os campos.

**Página simples de texto** (política, termos): crie a Page com Modelo por omissão; ela usa automaticamente o `pagina-padrao`.

**Adicionar um campo a uma seção existente**
1. Adicione o campo em `{slug}.module.php` (key `field_{slug}_{nome}`).
2. Leia e devolva o valor no `mapX()` do controller.
3. Acrescente o tipo no `.schema.ts` e use no `.view.tsx`.
4. `npm run build`.

**Editar textos do header/rodapé:** use as Opções do Tema. Os itens de menu ficam em Aparência → Menus.

**Destacar uma categoria no blog:** use o slug `em-destaque`.

---

## Pontos de atenção conhecidos

- **Assets dos módulos:** a maioria das imagens em `app/*/assets/` são exportações do Figma usadas como referência para upload no ACF. O código só usa diretamente as ondas SVG dos heros, a imagem mobile padrão da home, a decoração de DNA e os placeholders do blog/post.
- **Módulo `teste`:** é só demonstração; pode ser desativado.
- **Lista de contatos** (página Contato): sem nenhum card cadastrado, a seção não aparece. Não há cards padrão.
