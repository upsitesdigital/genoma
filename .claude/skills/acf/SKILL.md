---
name: acf
description: Convenções de ACF Pro do UpWork Framework — todos os campos num único arquivo (upsites/customizer-register/inc/acf_fields.php), um grupo por página com uma aba lateral por seção via Fields::section('{slug}', …) (com toggle "Ocultar seção"), padrão de keys/names, location, defaults do Figma, repeater/flexible_content/componente compartilhado, formulários com Contact Form 7, e como o controller lê cada tipo com $this->section(). Use ao criar, alterar ou revisar campos ACF de um módulo, ou antes de criar um campo para checar se ele já existe.
---

# ACF Pro no UpWork Framework

## Disponibilidade

- Se o plugin **ACF Pro** estiver ativo no site, ele é usado.
- Senão, o `upsites/functions.php` carrega a cópia embarcada em
  `upsites/core/acf/acf.php` (`if (!class_exists('ACF'))`) — o tema funciona
  sem o plugin instalado. Nesse caso o menu do ACF fica oculto no admin
  (`acf/settings/show_admin` = false): os campos existem só por código. Não
  mexa nisso por módulo.
- **Em produção normalmente não há plugin** — a cópia embarcada é o que roda.
  Versão embarcada: **ACF PRO 6.8.10**. Para atualizar: copie a pasta do plugin
  ACF PRO (licenciado) para `upsites/core/acf/` substituindo tudo, **sem** os
  `.po`/`.pot` de `lang/` (só fontes de tradução; o WP usa `.mo`/`.l10n.php`).
- Desenvolva com o plugin ACF **desativado** (ou na mesma versão da cópia):
  com o plugin ativo, a cópia nunca é exercitada e diferenças de versão só
  aparecem em produção.
- Se o site tiver ACF **gratuito** ativo, `repeater`, `flexible_content` e
  `gallery` não existem — avise em vez de registrar esses tipos.

## Onde os campos são registrados

**Só via código**, num **único arquivo** para o tema inteiro:
`upsites/customizer-register/inc/acf_fields.php`. Tudo dentro de um
`add_action('acf/init', …)`, **um bloco por página** (slug de
`template-pages/`) e, dentro dele, **uma `Fields::section()` por seção**
(classe `Core\Framework\Fields`, em `upsites/core/Framework/Fields.php`):

```php
<?php
use Core\Framework\Fields;

add_action('acf/init', function (): void {

    /* ── home — Home ─────────────────────────────────────────────────────────── */

    Fields::section('home', 'hero', 'Hero', [ /* campos */ ], order: 1);
    Fields::section('home', 'about_us', 'Sobre nós', [ /* campos */ ], order: 2);

    /* ── contact — Contato ───────────────────────────────────────────────────── */

    Fields::section('contact', 'contact_form', 'Formulário de contato', [ /* campos */ ], order: 1);

    // ↑ novas páginas entram acima desta linha (npm run make:module acrescenta aqui)
});
```

- **Página nova**: o `npm run make:module` já cria o bloco
  (`/* ── {slug} — {Nome} ── */` + a `Fields::section()` do hero) antes da
  linha marcadora. Criando à mão, ponha o bloco **acima** do marcador.
- **Seção nova**: acrescente a `Fields::section('{slug}', …)` **dentro do
  bloco da página**, na posição da seção na tela (mesma ordem do `order:`).
  O `npm run make:section` mostra a chamada a acrescentar.
- O arquivo é **compartilhado por todas as páginas**: edite só o bloco/seção
  do seu `{slug}` — nunca reordene, reformate ou altere blocos de outras páginas.
- O `{slug}.module.php` **não tem campos**: só o `#[Module]` e `boot()`
  (CPTs/taxonomias ficam em `register_cpts.php`/`register_taxes.php` — skill `wordpress`), com o comentário `// Campos ACF: upsites/customizer-register/inc/acf_fields.php`.

Nunca:
- campos no `{slug}.module.php` (`fields()`, `$this->addSection()`,
  `$this->iconField()`/`imageField()` — legado `@deprecated`): o
  `npm run review` dá **erro** "campos ACF no module.php — mova para …acf_fields.php";
- chamar `acf_add_local_field_group()` direto (seção, CPT ou taxonomia) — use
  `Fields::section()`, `Fields::postType()` ou `Fields::taxonomy()`; o review dá **erro**;
- registrar campos fora do `add_action('acf/init', …)` (o ACF ainda não carregou);
- criar grupo pela UI do ACF no wp-admin (fica só no banco, fora do git).

O `npm run review` lê os campos desse arquivo e aponta `acf_fields.php:{linha}`.

Configurações globais (rodapé, idiomas, redes sociais, contato) ficam na
página **Configuração do Tema** — uma página de opções do ACF, **no mesmo
padrão das páginas** (grupo `group_opcoes` com abas à esquerda, conteúdo do
Figma já no painel, "apagou, some"). Declare no bloco `opcoes` do
`acf_fields.php`, uma aba por assunto, **sem** toggle "Ocultar seção":

```php
Fields::option('rodape', 'Rodapé', [
    ['key' => 'field_opcoes_rodape_texto', 'label' => 'Texto do rodapé', 'name' => 'rodape_texto', 'type' => 'textarea',
     'instructions' => 'Linha de copyright no fim de todas as páginas. Vazio, a linha não aparece.', 'default_value' => '© …'],
], order: 2);
```

**Redes sociais são sempre um repeater** (nunca um campo por rede): subcampos
`nome` (text — não aparece, vira o `aria-label`), `url` (url) e `icone`
(`+ Fields::icon()`), com `default_rows` = as redes do Figma e seus ícones
(SVG em `template-parts/includes/layout/footer/assets/icon-{rede}.svg`, `fill="currentColor"`).
Rede sem link ou sem ícone não aparece. O editor inclui Facebook, YouTube,
WhatsApp… sem mexer no código. O review avisa campo solto `instagram`, `linkedin`, etc.

Keys `field_opcoes_{aba}_{nome}`, names `{aba}_{nome}`. Leitura (direto na parte
que usa — ex.: `layout/footer/footer.php` —, ou num controller):
`ThemeOptions::text('rodape_texto', '{padrão}')` (texto: nunca salvo → padrão;
apagado → `null`), `ThemeOptions::rows('{name}')` (repeater: `null` = nunca salvo,
`[]` = apagou tudo), `ThemeOptions::icon($row['icone'])` (IconData com SVG inline) e
`ThemeOptions::value('{name}')` (valor cru: imagem, link…). Nada
de Settings API/`register_setting` nem HTML de formulário à mão.
**Nome do site e logo** são os padrões do WordPress
(Aparência → Personalizar → Identidade do site: título do site e `custom_logo`)
e o header lê direto (`get_bloginfo('name')`, `get_theme_mod('custom_logo')`) — não crie campo para eles.

## Uma aba por seção — `Fields::section()`

No editor, cada página tem **um grupo de campos** (título = nome da página) com
**abas laterais à esquerda — uma aba por seção**, na ordem da tela. O grupo é
montado sozinho a partir das `Fields::section()` do bloco da página; não crie
campo `tab` à mão.

```php
/* ── home — Home ─────────────────────────────────────────────────────────── */

Fields::section('home', 'hero', 'Hero', [
    ['key' => 'field_home_hero_titulo', 'label' => 'Título', 'name' => 'hero_titulo', 'type' => 'text',
     'instructions' => 'Título principal da página.', 'default_value' => 'Texto do Figma'],
    // ...
], order: 1);

Fields::section('home', 'about_us', 'Sobre nós', [
    // ...
], order: 2);
```

`Fields::section(string $page, string $section, string $label, array $fields, int|array $order = 0, ?array $location = null)`
(`$page` = slug do módulo, pasta em `template-pages/`):
- cria a aba `{label}` (`field_{slug}_{section}_tab`, `placement: left`) no grupo
  da página `group_{slug}` (título = nome do módulo). Seções do mesmo slug com a
  **mesma location** ficam no mesmo grupo. Componente compartilhado (`$page =
  'shared'`) também vira **aba no grupo de cada página que o usa** (ver
  "Componente compartilhado");
- põe no topo da aba o toggle **"Ocultar seção"** (`true_false`, **padrão "Não"**, instrução
  "Marque para ocultar esta seção nesta página.") — toda seção nasce visível e
  some do site quando o editor marca o toggle e salva a página;
  key `field_{slug}_{section}_ocultar`, name `{section}_ocultar`;
- com o toggle marcado, **os demais campos da aba somem do editor** (sobra só
  o "Ocultar seção"): o `Fields::section()` acrescenta em cada campo a condição
  `{toggle} != 1`, em AND com a `conditional_logic` que o campo já tiver.
  Subcampos somem junto com o pai. Os valores salvos são mantidos — desmarcar
  traz tudo de volta. Não declare essa condição à mão;
- `order` = `posicao` da seção → as abas aparecem na ordem da página;
- `location` padrão: `page_template == fw:{slug}`; para `home` inclui
  automaticamente a página inicial (`page_type == front_page`) — não passe
  location para a home. Passe outra só para módulos de contexto
  (`post_type == post`) ou componente compartilhado.

Ao implementar uma seção nova, **acrescente** uma chamada `Fields::section()`
no bloco da página — nunca altere ou remova as de outras seções (nem de outras
páginas). Não declare o campo `_ocultar` à mão.

## Campos de CPT (do próprio post, não de seção)

Campos que pertencem ao post de um CPT (ex: ícone do `servico`) não são seção de
página — não usam `Fields::section()` nem toggle. Declare-os com
`Fields::postType()` no bloco de **um** `{slug}` — o módulo que lista o CPT
ou o módulo de contexto do single (ex.: `servico` listado em `lista-de-servicos`,
campos no bloco dele ou de `single-servico`) —, **depois** das
`Fields::section()` desse bloco:

```php
Fields::postType('lista-de-servicos', 'servico', 'Serviço', [
    ['key' => 'field_lista_de_servicos_servico_icone', 'label' => 'Ícone', 'name' => 'servico_icone',
     'instructions' => 'Ícone exibido no card do serviço. Envie SVG (recomendado) ou PNG.']
    + Fields::icon('template-parts/includes/lista-de-servicos/services/assets/icon-globe.svg'),
]);
```

- `Fields::postType(string $page, string $postType, string $label, array $fields, int $order = 0, array $group = [])`
  cria o grupo `group_{slug}_{cpt}` com location `post_type == {cpt}` sozinho;
  `$group` = opções extras do grupo ACF (ex.: `group: ['position' => 'side']`
  para uma caixa na lateral do editor);
  `{cpt}` tem que estar registrado com `PostType::add('{cpt}', …)` em
  `upsites/customizer-register/inc/register_cpts.php` (`page`, `post` e CPT de
  plugin não valem → use `Fields::section()`)
- campos: `field_{slug}_{cpt}_{nome}` / name `{cpt}_{nome}` (subcampos: name `{nome}`)
  (ex: `group_lista_de_servicos_servico`, `field_lista_de_servicos_servico_icone` → `servico_icone`)
- mesmas regras por campo das seções: `instructions`, imagem com
  `return_format 'array'`, vídeo em `url` (não `oembed`)
- o mesmo `{slug}` não pode ter `Fields::section('{slug}', '{cpt}', …)` (colide com o grupo do CPT)

Um grupo por CPT, reaproveitado pelo single e por outras listagens — nunca
declare o mesmo CPT em dois blocos.

## Campos de taxonomia (do próprio termo)

`Fields::taxonomy('{slug}', '{tax}', '{Label}', [campos])` — mesmo formato do
`Fields::postType()`, com location `taxonomy == {tax}` criada sozinha (`{tax}`
registrada com `Taxonomy::add('{tax}', …)` em `register_taxes.php`): grupo
`group_{slug}_{tax}`, campos `field_{slug}_{tax}_{nome}` / name `{tax}_{nome}` (ex.: `group_lista_de_servicos_servico_categoria`,
`servico_categoria_texto`). O ACF grava em termmeta: no controller, leia com
`get_term_meta()` depois de `get_terms([... 'update_term_meta_cache' => true])`
(sem N+1). `default_value` só se o conteúdo for igual para todos os termos —
senão, fallback por slug no controller enquanto `metadata_exists('term', …)` for falso.

## Nomenclatura

| Item | Padrão | Exemplo (`home`, seção `about-us`) |
|---|---|---|
| Grupo | `group_{slug}` (automático, um por página) | `group_home` |
| Aba da seção | `field_{slug}_{section}_tab` (automático) | `field_home_about_us_tab` |
| Toggle | `field_{slug}_{section}_ocultar` / name `{section}_ocultar` (automático) | `about_us_ocultar` |
| Campo | `field_{slug}_{section}_{nome}` | `field_home_about_us_titulo` |
| `name` do campo | `{section}_{nome}` | `about_us_titulo` |
| Subcampo | `field_{slug}_{section}_{lista}_{nome}`, `name`: `{nome}` | `field_home_services_itens_titulo` → `titulo` |
| Layout de flexible | `layout_{slug}_{section}_{layout}` | `layout_single_post_post_content_quote` |

- `{section}` = section-name da pasta `template-parts/includes/{slug}/{section-name}/`
  (**sempre em inglês**) em snake_case: `about-us` → `about_us`. `slug`
  também em snake_case (`quem-somos` → `quem_somos`), sem acento.
- O `name` vira meta key do post: dois campos no mesmo post não podem repetir
  `name` — por isso o prefixo da seção é obrigatório.
- Keys são globais e imutáveis: renomear uma key órfã o conteúdo já salvo.
  Mude `label`, nunca `key` de campo em uso.

Antes de criar, confira se já existe:
```bash
grep -n "field_{slug}_{section}_" upsites/customizer-register/inc/acf_fields.php
```

## Tipos de campo e o que o controller devolve

| Conteúdo no Figma | Campo ACF | Retorno no controller (JSON) |
|---|---|---|
| Título / texto curto | `text` | `string` |
| Parágrafo sem formatação | `textarea` + `'new_lines' => ''` | `string` |
| Texto com negrito/link/lista | `wysiwyg` + `'media_upload' => 0, 'toolbar' => 'basic'` | `string` HTML (`wp_kses_post`) |
| Imagem (foto, ilustração) | `+ Fields::image('{asset do Figma}', alt: '{alt}')` | `ImageData` via `$this->picture($id, '{name}', '{asset}')` |
| Várias imagens | `gallery` + `'return_format' => 'array'` | `ImageData[]` |
| Botão / link | `...Fields::button([...])` = "Ocultar botão" + `link` (ver "Botões") | `LinkData \| null` via `$this->link($id, '{name}', [padrão do Figma])` |
| Lista de itens (boxes, cards, logos, etapas, botões) | `repeater` **sem `max`** + `'layout' => 'block'` + `'button_label'` | `array` de objetos |
| Blocos variáveis | `flexible_content` | `array` de `{ tipo, ...campos }` |
| Formulário | `post_object` + `'post_type' => ['wpcf7_contact_form'], 'return_format' => 'id', 'allow_null' => 1` | `string \| null` referência `fw-cf7:{id}` via `$this->cf7()` |
| Opções fixas | `select` / `button_group` + `'return_format' => 'value'` | `string` |
| Cor | `color_picker` | `string` hex |
| Vídeo (YouTube/Vimeo) | `url` (não `oembed`) | `string` |
| Referência a posts | `post_object` / `relationship` + `'return_format' => 'id'` | dados montados no controller |
| Ícone | `+ Fields::icon('{asset do Figma}')` — **sempre**, nunca `select` | `IconData` via `$this->icon($id, '{name}', '{asset}')` / `$this->iconFrom($row['…'])` |

Ocultar a seção inteira **não** é campo seu — é o toggle do `Fields::section()`.

## Instruções dos campos (`instructions`) — obrigatórias

**Todo campo de conteúdo leva `instructions`**: o texto cinza que o editor do
site (o cliente, não um dev) vê abaixo do rótulo no admin. Vale para campos de
seção, **subcampos** de repeater/group, campos dos **layouts** de flexible
content e grupos de CPT/taxonomia. Só `tab`, `message` e `accordion` ficam sem.
Faltou → o `npm run review` dá **erro**.

Escreva 1–2 frases curtas, em português, respondendo:
1. **o que é** e **onde aparece** na página;
2. quando fizer diferença: **o que acontece se ficar vazio**, formato esperado
   ou como o campo se combina com outro.

| Campo | Bom ✓ | Ruim ✗ |
|---|---|---|
| Imagem de logo | "Exibido como emblema circular sobre o nome do projeto. Se remover, o emblema não aparece." | "Logo." |
| Título | "Título da seção, acima do texto. Vazio, o título não aparece." | "Título." |
| Textarea | "Texto de introdução exibido no topo do popup, ao lado do título." | "Insira a descrição." |
| Flexible content | "Monte o corpo do popup do projeto empilhando blocos de texto, vídeo e imagens, na ordem que quiser." | "Blocos." |
| Título em duas partes | "Trecho do título exibido em destaque (cor/peso diferente). É mostrado junto com a parte normal do título para formar a frase completa." | "Título destaque" |
| Textarea com parágrafos | "Texto exibido abaixo do título. Deixe uma linha em branco entre os parágrafos." | "Texto" |
| Botão (link) | "Botão de ação abaixo do texto. Vazio, exibe o padrão \"Falar com um expert\"." | "Link do botão." |
| Item de repeater | "Nome da solução, exibido em negrito no card. Mantenha em até 3 palavras para caber em uma linha." | "Nome da solução." |

Não faça: repetir o rótulo, verbo genérico sem contexto ("Insira o título",
"Selecione a imagem"), jargão técnico (`name`, "array", "slug", "HTML") ou
instrução de desenvolvimento ("usado no controller"). Medida de imagem só
quando importar ("Use 1200 × 800 px ou maior, na horizontal.").

Formatos que o controller devolve (e que o `@var array{…} $data` das seções documenta):
```php
// ImageData (Controller::image/picture) → renderize com fw_img()
array{id: int, src: string, alt: string, width: int|null, height: int|null, sizes: array}  // id 0 = asset do tema
// LinkData (Controller::link/linkFrom) → renderize com Part::render('shared/button', …)
array{title: string, url: string, target: string}
```

## Botões — `Fields::button()`: "Ocultar botão" + campo `link`

Todo botão (CTA, "Saiba mais", "Ver todos", botão de card, link de texto com
cara de botão) é declarado com **`...Fields::button([...])`**, que gera dois campos:

1. **"Ocultar botão"** (`{name}_ocultar`, `true_false` no mesmo estilo do
   "Ocultar seção", desmarcado) — para quando o botão não é necessário;
2. o **`link`** ("Seleccionar ligação": URL, texto e "abrir em nova aba" no
   mesmo lugar) — some do editor quando o "Ocultar botão" está marcado.

```php
...Fields::button(['key' => 'field_home_hero_botao', 'label' => 'Botão', 'name' => 'hero_botao',
    'instructions' => 'Botão de ação abaixo do texto. Vazio, exibe o padrão "Falar com um expert".']),
```

(o `...` espalha os dois campos no array; funciona em `sub_fields` de repeater —
lá o name é `botao` e o toggle `botao_ocultar`). Não declare `'type' => 'link'`
à mão nem o `_ocultar` do botão — o review avisa.

- **Nunca** campos separados para texto e URL do botão (`*_botao_texto` +
  `*_botao_link`/`*_url`), nem `text`/`url`/`page_link` para botão — o review avisa.
- `label` = "Botão" (ou "Botão secundário", "Botão do card"); name `{section}_botao`
  (`_botao_secundario`…); em repeater, subcampo `botao`.
- `instructions` no modelo: **onde aparece** + **"Vazio, exibe o padrão
  \"{texto do Figma}\"."** (texto exato do botão no Figma). Se o layout não
  tiver padrão (botão opcional), "Vazio, o botão não aparece."
- Vários botões no mesmo lugar (lista de CTAs) → repeater sem limite com o
  subcampo `...Fields::button(['key' => …, 'name' => 'botao', …])`.
- **Exceção:** rótulo do botão dos cards de uma listagem cuja URL vem de cada
  item (post, case, e-book) — um `text` `{section}_botao_texto` com o texto do
  Figma ("Ler case"), sem campo de URL.
- Controller: **sempre** `$this->link($id, '{section}_botao', ['title' => '{texto do Figma}', 'url' => '{destino}'])`
  (em repeater: `$this->linkFrom($row, 'botao', [...])`). "Ocultar botão"
  marcado → `null` (nem o padrão aparece); link vazio → o padrão; só URL →
  texto do padrão; sem padrão → `null`. URL relativa
  (`/contato`) vira URL do site. O destino do padrão vem da `descricao` da
  seção no prompt.yaml ("Links: …"); sem destino definido, use `#` e avise no retorno.
- Template: `Part::render('shared/button', ['link' => $data['botao'], 'class' => '…'])` —
  trata `target`/`rel="noopener"` e não renderiza quando é `null`.

## Listas — o editor inclui quantos itens quiser

Tudo que se repete no layout (boxes, cards, benefícios, etapas, logos,
depoimentos, botões, FAQ) é **um repeater sem limite** — o Figma mostra uma
quantidade de exemplo, não um teto.

```php
['key' => 'field_home_benefits_itens', 'label' => 'Benefícios', 'name' => 'benefits_itens',
 'type' => 'repeater', 'layout' => 'block', 'button_label' => 'Adicionar benefício',
 'collapsed' => 'field_home_benefits_itens_titulo',
 'instructions' => 'Cards de benefício exibidos em grade abaixo do título. Inclua quantos quiser; a grade se reorganiza sozinha.',
 'sub_fields' => [ /* titulo, texto, icone… */ ]],
```

- **Sem `'max'`** em repeater e flexible content (o `npm run review` dá **erro**).
  `'min'` só se a seção não fizer sentido vazia — e ainda assim prefira o
  "Ocultar seção".
- **Nunca** campos numerados para itens repetidos (`box_1_titulo`,
  `box_2_titulo`, `card3_…`) — o review avisa.
- `'button_label'` com o nome do item ("Adicionar benefício") e `'collapsed'`
  apontando para o subcampo de título — com muitos itens o editor navega pelos
  títulos.
- Relationship/gallery/post_object também sem `'max'`, **a menos que** a
  quantidade seja exigência real do layout (ex.: "4 posts em destaque" num
  mosaico fixo) — e então a `instructions` diz o limite e o porquê.
- Limite de **valor** não é limite de lista: `min`/`max` em campo `number` ou
  `range` (nota de 0 a 5, ano) continuam normais.
- O controller devolve **todos** os itens que o editor cadastrou (nada de
  `array_slice`); só usa os itens do Figma quando o repeater **nunca foi
  salvo** (`$this->rows()` → `null`). Se o editor apagou todas as linhas, a
  lista some. Para o painel já abrir com os itens do Figma, use `'default_rows'`
  no repeater (seção "Defaults").

## Ícones e imagens — o editor sobe, troca ou remove

**Todo ícone e toda imagem que é conteúdo vira campo de imagem** — o cliente tem
liberdade de subir o próprio arquivo ou deixar sem. O arquivo do Figma (baixado
pelo t800 em `template-parts/includes/{slug}/{section-name}/assets/`) é o valor
padrão do campo:

```php
['key' => 'field_home_services_itens_icone', 'label' => 'Ícone', 'name' => 'icone',
 'instructions' => 'Ícone exibido acima do nome do serviço. Envie SVG (recomendado) ou PNG. Se remover, o card fica sem ícone.']
+ Fields::icon('template-parts/includes/home/services/assets/icon-globe.svg'),

['key' => 'field_home_hero_imagem', 'label' => 'Imagem', 'name' => 'hero_imagem',
 'instructions' => 'Foto à direita do título. Use 1200 × 900 px ou maior. Se remover, o texto ocupa a largura toda.']
+ Fields::image('template-parts/includes/home/hero/assets/hero-photo.webp'),
```

- `Fields::icon()` aceita SVG/PNG/WebP; `Fields::image()` JPG/PNG/WebP/AVIF/GIF. Ambos
  com `return_format: array`. O asset do Figma é **importado uma vez para a
  biblioteca de mídia** (no admin) e vira o `default_value`: o editor vê o
  arquivo já no campo.
- Os três estados (o controller resolve sozinho):
  | Estado | Resultado |
  |---|---|
  | campo nunca salvo | arquivo do Figma (da biblioteca, ou do tema antes da importação) |
  | editor **removeu** o arquivo e salvou | **nada** (`null`) — a seção precisa funcionar sem |
  | editor enviou um arquivo | o arquivo dele |
- **SVG**: o upload de SVG está liberado para quem pode enviar arquivos e todo
  SVG é **sanitizado** (`Core\Support\Svg`) no upload — inclusive pela REST/sideload —
  e antes de ir para a página. A sanitização é por **lista do que é permitido**: só
  elementos de desenho SVG ficam (sem `<script>`, `<foreignObject>`, HTML dentro do
  SVG, animações, comentários; `href` só interno `#id`). Ícone que "sumiu" depois do
  upload usava algo fora da lista — reexporte do Figma. `IconData.svg` traz o markup para embutir inline — se o SVG usa
  `currentColor`, o ícone herda a cor do texto (`text-primary`).
- **Repeater**: o `default_value` do subcampo preenche cada linha nova com o ícone
  do Figma; as linhas padrão do controller (repeater nunca salvo) usam
  `$this->asset('{caminho}')` em cada item. Leia com `$this->iconFrom($row['icone'] ?? null)`.
- **Não são campo** (ficam no código, no `{section-name}.php`): ícones de interface — seta de
  carrossel, abrir/fechar menu, chevron de acordeão, check de lista de
  formulário. Regra: se o cliente poderia querer trocar, é campo.
- Instruções de ícone/imagem dizem **onde aparece**, o **formato** (SVG/PNG, medida
  quando importar) e **o que acontece se remover**.

## Formulários — Contact Form 7

O framework **não** tem construtor de formulários: todo formulário é do
**Contact Form 7** (plugin), criado em Contato → Formulários no wp-admin.

- Campo: `post_object` do tipo `wpcf7_contact_form` (tabela acima).
- Controller: `'form' => $this->cf7($this->field($id, '{section}_form'))` —
  devolve a **referência** `fw-cf7:{id}` (não o HTML), ou `null` se o CF7 não estiver
  ativo ou nenhum formulário foi escolhido. O retorno do controller vai para o cache, e o
  HTML do CF7 depende da requisição (valores e erros de um envio sem JS, `action` com a
  URL): nunca chame `do_shortcode('[contact-form-7 …]')` no controller.
- Template: `Part::render('shared/cf7-form', ['html' => $data['form'], 'class' => '…'])` —
  renderiza o shortcode a cada página; o formulário já está no HTML inicial, então o
  próprio CF7 liga validação/envio (sem JS do tema).
- Campos, validação, e-mails e mensagens são configurados no próprio CF7 —
  não crie campos ACF para isso nem endpoint de envio.

## Defaults — vem preenchido; apagou, some

Regra: **todo campo de texto e imagem chega ao painel já preenchido com o
conteúdo do Figma; se o editor apagar, o elemento some do site** — nada de
voltar o texto/imagem do Figma como fallback.

**O conteúdo passa para o painel por padrão** (`Core\Admin\Seeder`): ao abrir
uma página de modelo no editor (e depois de salvá-la), todo campo **nunca
salvo** recebe o seu padrão — `default_value`, imagem do `Fields::image/icon`,
`default_rows` — como valor de verdade no banco. Campo já salvo (com conteúdo
ou apagado) nunca é sobrescrito. Para páginas que já existem:
`npm run seed` (prévia) → `npm run seed -- --apply`; `--vazios` também
preenche os salvos vazios (só para migrar projeto antigo).

Por isso os padrões precisam ser **completos**: texto com `default_value`,
imagem com `Fields::image('{asset}', alt: '{alt do Figma}')` (o alt vai para o
anexo importado — sem ele a imagem fica sem texto alternativo), repeater com
`default_rows`. O que não tiver padrão no campo não vai para o painel.

| Estado do campo no post | Painel mostra | Site mostra |
|---|---|---|
| nunca salvo (página nova ou campo novo) | o conteúdo do Figma | o conteúdo do Figma |
| salvo com conteúdo | o do editor | o do editor |
| **salvo vazio** (o editor apagou) | vazio | **nada — o elemento HTML não é renderizado** |

Como cada tipo chega preenchido e como o controller lê:

| Campo | No `acf_fields.php` | No controller |
|---|---|---|
| Texto (`text`, `textarea`, `wysiwyg`) | `'default_value' => '{texto do Figma}'` | `$this->text($id, '{name}', '{texto do Figma}')` |
| Imagem / ícone | `+ Fields::image('{asset}', alt: '{alt}')` / `+ Fields::icon('{asset}')` (ícone decorativo: sem alt) | `$this->picture($id, '{name}', '{asset}')` / `$this->icon(…)` |
| Repeater (cards, logos, itens) | `'default_rows' => [[ '{sub}' => '…', 'imagem' => '{asset}' ], …]` | `$this->rows($id, '{name}')` → `null` (nunca salvo) usa `self::X_DEFAULTS`; `[]` = o editor apagou tudo, a lista some |
| Subcampo de linha | — | `$this->textFrom($row, 'titulo')`, `$this->image($row['imagem'] ?? null)`, `$this->iconFrom(…)` — vazio → `null` |
| Botão | `...Fields::button([...])` | `$this->link(…)` — regra própria ("Ocultar botão" + padrão, seção "Botões") |

- **Nunca** `$this->field($id, 'x') ?: 'texto'`, `$this->image(...) ?? [...]`
  nem `$row['x'] ?? 'texto'` — com isso, apagar no painel traz o Figma de volta.
  O review avisa.
- `instructions` de texto/imagem dizem o que some: "… Vazio, o título não
  aparece." (nunca "Vazio, exibe o padrão" — isso é só para botão).
- `default_rows` (repeater) usa os **names** dos subcampos; imagem/ícone na
  linha é o caminho do asset no tema. Os mesmos itens vão na `X_DEFAULTS` do
  controller (formato de saída) — os dois batem. O template da seção **não** tem
  padrões: o conteúdo do Figma vive só aqui e no controller.
- Quando o t800 já montou a seção a partir do Figma, use exatamente os textos do
  frame (os que ele relatou/deixou no retorno) no `default_value`/`default_rows` e nos
  padrões do controller.
- `@var array{…} $data` da seção: todo texto/imagem que o editor pode apagar é
  `string|null` / `ImageData|null` — o template só renderiza o elemento quando há
  valor (`<?php if ($data['titulo']) : ?>`; `fw_img()` e `shared/button` já somem com `null`).

## Lendo no controller

Helpers de `Core\Framework\Controller`: `section($postId, $section, fn)`,
`field($postId, $name)`, `fields($postId)`, `image($acfImage)`, `cf7($formId)`,
`link($postId, $name, $padrão)` / `linkFrom($row, $name, $padrão)` (botões de `Fields::button()`),
`icon()`/`picture()`/`iconFrom()` (ícones e imagens).

**Toda seção passa por `section()`**: devolve o resultado da função, ou `null`
se o editor marcou "Ocultar seção" (o view então não renderiza a seção).

```php
// public function page(int $id): array — o $id vem do WordPress
return [
    'hero' => $this->section($id, 'hero', fn(): array => [
        'titulo' => $this->text($id, 'hero_titulo', 'Texto do Figma'),   // apagado → null
        'imagem' => $this->picture($id, 'hero_imagem', 'template-parts/includes/home/hero/assets/hero.webp'),
        'botao'  => $this->link($id, 'hero_botao', ['title' => 'Falar com um expert', 'url' => '/contato']),
    ]),

    // repeater: nunca salvo → itens do Figma; salvo → só o que o editor deixou ([] = lista some)
    'services' => $this->section($id, 'services', function () use ($id): array {
        $rows = $this->rows($id, 'services_itens');
        return [
            'itens' => $rows === null ? self::SERVICES_DEFAULTS : array_map(fn(array $row) => [
                'titulo' => $this->textFrom($row, 'titulo'),
                'icone'  => $this->iconFrom($row['icone'] ?? null),
            ], $rows),
        ];
    }),
];

// flexible_content
$blocos = array_map(fn(array $b) => ['tipo' => $b['acf_fc_layout']] + $b, $this->field($id, 'post_content_blocos') ?: []);
```

- Chave da seção = section-name em camelCase (`aboutUs`), a mesma que o
  `{slug}.view.php` passa: `Part::render('{slug}/about-us', $data['aboutUs'])`; `null` = oculta.
- Dentro da seção, devolva **sempre todas as chaves** do `@var array{…} $data` do
  `{section-name}.php` (com `null`/`[]` quando vazio).
- `wysiwyg` → `wp_kses_post()`. Os demais tipos vão crus — o template escapa na saída
  (`esc_html`, `fw_img`, `fw_attrs`…).
- O cache `#[Cache(ttl: 300)]` é limpo automaticamente no `save_post`.

## Componente compartilhado (`componente: <id>` no prompt.yaml)

Mesmo layout em várias páginas, conteúdo diferente por página:
- **Um** grupo só para o componente, no bloco `shared` do `acf_fields.php`
  (o módulo `template-pages/shared/shared.module.php` existe com
  `template: false`, sem view — crie na primeira vez) com
  `Fields::section('shared', '{id}', '{Label}', [...], location: [...])` → grupo
  `group_shared_{id}`, campos `field_shared_{id}_{nome}` / name `{id}_{nome}`
  (ex: `cta_titulo`), toggle `{id}_ocultar`.
- `location` ganha **uma regra OR por página** que usa o componente
  (`[['param' => 'page_template', 'operator' => '==', 'value' => 'fw:{slug}']]`)
  — nunca duplique o grupo por página.
- No editor o componente **não** aparece como caixa separada: o `Fields`
  encaixa uma **aba** com um campo clone (`display: seamless`, sem prefixo)
  no grupo de cada página cuja regra bate. Os campos clonados mantêm o name
  (`cta_titulo`) e a key de referência (`field_shared_cta_titulo`), então o
  valor é salvo por página e o controller lê igual. O grupo `group_shared_{id}`
  fica só como origem do clone (location que nunca bate); uma regra que não
  casa com nenhuma página (ex.: CPT sem seção própria) faz o grupo aparecer
  sozinho ali.
- Posição da aba: `order` = posição na tela. Se o componente fica em posições
  diferentes em cada página, passe um array por página —
  `order: ['sobre-nos' => 3, 'verticais' => 5]` (página fora do array → última aba).
- Cada controller que usa o componente devolve
  `'{id}' => $this->section($id, '{id}', fn() => [...])` lendo `{id}_*`.

## Flexible content (`flexible: true` no prompt.yaml)

**Um** campo `flexible_content` por seção (dentro da `Fields::section()` dela), um
`layout` por tipo de bloco — nunca um grupo por layout. Na primeira chamada
crie o campo com o primeiro layout; nas seguintes, acrescente layouts.

```php
['key' => 'field_single_post_post_content_blocos', 'label' => 'Blocos', 'name' => 'post_content_blocos',
 'type' => 'flexible_content', 'button_label' => 'Adicionar bloco',
 'layouts' => [
    'layout_single_post_post_content_quote' => [
        'key' => 'layout_single_post_post_content_quote', 'name' => 'quote', 'label' => 'Citação', 'display' => 'block',
        'sub_fields' => [
            ['key' => 'field_single_post_post_content_quote_texto', 'label' => 'Texto', 'name' => 'texto', 'type' => 'textarea'],
        ],
    ],
 ]],
```
