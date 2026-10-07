# Guia de Uso do Site Genoma

Atualizado em 07/10/2026 · Documentação técnica: [SITE.md](SITE.md)

Todo o conteúdo do site é editado no painel do WordPress, preenchendo os campos de cada página. Este guia mostra onde fica cada campo e como preenchê-lo.

## Sumário

1. [Acesso ao painel](#1-acesso-ao-painel)
2. [Como preencher cada tipo de campo](#2-como-preencher-cada-tipo-de-campo)
3. [Página por página](#3-página-por-página)
4. [Banner "Fale conosco" do rodapé](#4-banner-fale-conosco-do-rodapé)
5. [Opções do Tema](#5-opções-do-tema)
6. [Publicar um post no blog](#6-publicar-um-post-no-blog)
7. [Páginas novas e menus](#7-páginas-novas-e-menus)
8. [Problemas comuns](#8-problemas-comuns)

## 1. Acesso ao painel

Entre em **seudominio.com.br/wp-admin** com seu usuário e senha. Tudo o que o visitante vê está em um destes menus do lado esquerdo:

| Menu do painel | O que você edita ali |
| --- | --- |
| **Páginas** | Conteúdo de cada página do site (Home, O Genoma, Veterinários, Responsável, Contato, Blog) |
| **Posts** | Artigos do blog |
| **Mídia** | Todas as imagens e ícones enviados |
| **Aparência → Menus** | Itens do menu do topo e do rodapé |
| **UpWork → Opções do Tema** | Logo, botões do topo, banner "Fale conosco" e rodapé |

Para editar uma página: **Páginas → passe o mouse sobre o nome → Editar**. Os campos aparecem abaixo do título, agrupados por seção do site (Hero, Serviços, Exames...). Depois de mudar, clique em **Atualizar** (canto superior direito).

## 2. Como preencher cada tipo de campo

São só seis tipos de campo. Saber como cada um se comporta evita quase todos os problemas.

| Tipo | Como aparece no painel | Como preencher |
| --- | --- | --- |
| **Texto** | Uma linha | Texto curto: etiquetas, títulos, textos de botão, nomes |
| **Área de texto** | Caixa com várias linhas | Parágrafos. **Enter** quebra a linha no site; uma **linha em branco** separa parágrafos |
| **Link** | Uma linha | Endereço completo, começando com https:// (ex.: https://wa.me/5511999999999, mailto:contato@..., ou o endereço de uma página do site) |
| **Imagem** | Botão "Adicionar imagem" | Escolha da Mídia ou envie do computador. Preencha o **Texto alternativo** da imagem na Mídia |
| **Lista de itens** (repetidor) | Linhas numeradas + botão "Adicionar..." | Cada linha é um card, slide ou item. Arraste pelo número para reordenar; use o **−** para remover |
| **Sim / Não** | Chave liga/desliga | Liga ou desliga um comportamento (autoplay, botão secundário, categoria aberta) |

Regras que valem para o site todo:

- **Campo vazio = item escondido.** Um botão sem texto não aparece; uma lista sem itens esconde a seção inteira.
- **Etiqueta** é o texto pequeno acima do título (ex.: "DEPOIMENTOS"). Onde o rótulo diz **(H1)**, aquele texto é o título principal da página para o Google: mantenha-o sempre preenchido e descritivo.
- **Quebra de título:** nos campos com a dica "Use uma quebra de linha", aperte Enter onde quiser que o título quebre no computador.
- **Intervalo do autoplay** é em milissegundos: 6000 = 6 segundos (mínimo 2000).
- **Ícones:** envie SVG ou PNG com fundo transparente.

## 3. Página por página

Os grupos de campos seguem a ordem das seções na tela, de cima para baixo. O nome antes do travessão no rótulo ("Serviços — Título") diz a qual seção o campo pertence.

### Home

| Seção | Campos | Dicas |
| --- | --- | --- |
| Hero (carrossel do topo) | **Hero — Slides do Carrossel**: Imagem de Fundo, Imagem Mobile, Subtítulo (H1), Título, Subtítulo, CTA Primário e Secundário (texto + link) | Um slide por linha. **Imagem Mobile**: PNG com fundo transparente, proporção 393×459, aparece na base do slide no celular. Setas e bolinhas só aparecem com 2 ou mais slides |
| Serviços | Etiqueta, Título, **Serviços — Itens** (Imagem, Título, Descrição, botão), CTA Final | Mostra 3 cards; com mais de 3, aparece o botão "Ver mais" |
| Exames | Etiqueta, Título, Descrição, **Exames — Categorias (acordeão)** | Cada categoria tem Título, Texto opcional e a lista de exames (Nome do Exame, Prazo, Tipo de Amostra) |
| Sobre | Etiqueta, Título, Fotos 1 a 3, Texto, Frase de Destaque, Diferenciais (cards) | No Texto, deixe uma linha em branco entre parágrafos |
| Diferenciais | Etiqueta, Título, Imagem de Fundo, Itens (Ícone + Texto) | |
| Estrutura | Etiqueta, Título, Descrição, Fotos 1 a 3 | |
| Depoimentos | Etiqueta, Título, **Itens** (Ícone = foto do cliente, Depoimento, Nome do Cliente), Autoplay e Intervalo | No celular os depoimentos aparecem empilhados |

### O Genoma

| Seção | Campos | Dicas |
| --- | --- | --- |
| Hero | Slides: Etiqueta (H1), Título, Imagem 1 (menor), Imagem 2 (maior), Texto em destaque | As duas imagens ficam lado a lado abaixo do texto |
| Sobre nós | Etiqueta, Título, Texto, Texto em destaque | |
| Compromisso | Etiqueta, Título, Texto, Imagem de fundo | |
| Diagnóstico | Título, Texto, Texto em destaque | No painel o rótulo aparece como "Diaginostico" |

### Veterinários

| Seção | Campos | Dicas |
| --- | --- | --- |
| Hero | Slides: Imagem, Etiqueta (H1), Título, Subtítulo, CTAs | |
| Suporte | Etiqueta, Título, Texto, Texto em destaque, Imagens 1 e 2 | |
| Praticidade | Etiqueta, Título, Texto, Ícone dos Horários, **Horários** (um por linha), Imagem | Horários de coleta do motoboy; linhas vazias são ignoradas |
| Exames | Etiqueta, Título, Texto, CTA (texto + link), Imagem | O botão leva à lista de exames |
| Estrutura | Etiqueta, Título, Texto, Texto em destaque, Imagens 1 e 2, **Card de Contato** (Ícone + Texto) | |
| Benefícios | Etiqueta, Título, Texto, Itens (Ícone + Texto) | Fica bem com 4 itens (uma linha no computador) |

### Responsável

| Seção | Campos | Dicas |
| --- | --- | --- |
| Hero | Slides: Imagem, Etiqueta (H1), Título, Subtítulo, CTAs | |
| Suporte | Etiqueta, Título, Texto, Quote/Destaque, Imagem 1 (retrato), Imagem 2 (paisagem) | |
| Planos | Etiqueta, Título, **Logos** (Logo + Nome) | O Nome não aparece na tela: é o texto lido por leitores de tela |
| Exames (jejum) | Etiqueta, Título, Texto, **Itens de Jejum** (Ícone, Texto, Texto em destaque), Texto de Rodapé, Imagem de Fundo | O Texto em destaque aparece em negrito logo após o Texto (ex.: "Cães e gatos:" + "jejum de 8 horas") |
| Serviços | Etiqueta, Título, Texto, **Categorias de Exames** (Nome, Aberta por padrão?, Descrição, Exames da Categoria) | Cada exame: Nome, Prazo (ex.: 7 Dias), Tipo de Amostra. Ligue "Aberta por padrão?" para a categoria já vir expandida |
| Resultados | Etiqueta, Título, Texto, Imagem | |
| Benefícios | Etiqueta, Título, Itens (Ícone + Texto), Texto de destaque | O texto de destaque aparece abaixo dos cards |

### Contato

| Seção | Campos | Dicas |
| --- | --- | --- |
| Hero | Etiqueta, Título, Descrição, **Canais de Contato** (Ícone, Título, Valor, Link) | Com Link preenchido, o card fica clicável: https://wa.me/55DDDNUMERO para WhatsApp, mailto:email para e-mail |
| Lista de contatos | **Lista de Contatos — Cards** | Ao clicar em "Adicionar Card", escolha o modelo: **Título + Itens** (ícone + texto, para endereço e horários) ou **Título + Texto** (com botão opcional). Sem cards, a seção some |

### Blog e Resultado da busca

| Página | Campos | Dicas |
| --- | --- | --- |
| Blog | Etiqueta (H1), Título, Busca (placeholder do campo) | Os posts e as categorias vêm do menu **Posts** (seção 6) |
| Resultado da busca | Etiqueta (H1), Busca (placeholder), Mensagem — Nenhum resultado encontrado | É a página aberta quando alguém usa a busca do blog |

## 4. Banner "Fale conosco" do rodapé

O banner com foto, título e botões logo acima do rodapé tem um texto padrão para o site todo, que pode ser trocado em quatro páginas: Home, O Genoma, Veterinários e Responsável.

1. **Texto padrão (site todo):** UpWork → Opções do Tema → campos "Rodapé — Banner CTA" e "Rodapé — Botão CTA".
2. **Só em uma página:** edite a página e preencha os campos que começam com **Rodapé —** no fim do formulário:
    - **Rodapé — CTA Título:** aperte Enter para quebrar o título em duas linhas.
    - **Rodapé — CTA Primário** (texto e link): o primeiro botão.
    - **Rodapé — Mostrar Botão Secundário:** desligado, fica só um botão (roxo).
    - **Rodapé — CTA Secundário** (texto e link): aparece quando a chave acima está ligada.
3. **Campo deixado em branco** na página usa o texto padrão das Opções do Tema.

## 5. Opções do Tema

Em **UpWork → Opções do Tema** ficam os itens que se repetem em todas as páginas. Clique em **Salvar opções** no fim da tela.

| Campo | Onde aparece |
| --- | --- |
| Logo do Header / Logo do Rodapé | Topo e rodapé de todas as páginas (use SVG ou PNG com fundo transparente) |
| Botão do Header — Primário e Secundário | Botões "Resultados" e "Contato" do topo e do menu do celular |
| Rodapé — Banner CTA, Imagem, Botões CTA | Banner "Fale conosco" padrão (seção 4) |
| Texto do Rodapé | Linha de copyright, ex.: © Copyright 2026 Genoma Diagnósticos |
| Rodapé — Link de Política de Privacidade | Texto e endereço do link no rodapé |
| Rodapé — Frase abaixo do logo | Rodapé no celular |
| Rodapé — E-mail e Telefone | Rodapé no celular, clicáveis. Em branco, não aparecem |
| Rodapé — Direitos reservados | Linha abaixo do copyright no celular |

O **Nome do Site**, a **Cor Primária** e o **Texto de Créditos** raramente precisam mudar.

## 6. Publicar um post no blog

1. **Posts → Adicionar novo.**
2. Escreva o **título** e o **conteúdo** no editor. Use os blocos de Título, Lista e Imagem do próprio editor.
3. No painel da direita:
    - **Imagem destacada:** aparece no card do blog e no topo do post. Sem ela, o site usa uma imagem genérica.
    - **Categorias:** marque uma. A primeira marcada aparece no card e no topo do post.
    - **Resumo** (opcional): texto do card. Em branco, o site usa as primeiras 20 palavras do conteúdo.
4. Clique em **Publicar**.

O post entra no topo da lista do blog. No fim de cada post, a seção "Veja também" mostra 3 posts da mesma categoria.

- **Filtros do blog:** cada categoria com pelo menos um post vira um botão de filtro. Para fixar uma categoria em 2º lugar (logo após "Todos"), dê a ela o slug **em-destaque** (Posts → Categorias → Editar).
- **Posts por página:** Configurações → Leitura → "As páginas do blog mostram no máximo".

## 7. Páginas novas e menus

**Página de texto simples** (Política de Privacidade, Termos de Uso):

1. Páginas → Adicionar nova.
2. Escreva título e conteúdo no editor, deixando o **Modelo** como "Modelo por omissão".
3. Publicar. O site exibe o título e o texto com o visual padrão.

**Páginas com layout próprio** (Home, O Genoma, Veterinários, Responsável, Contato, Blog) usam um Modelo "Página · Nome" no painel **Página → Modelo**. Ao escolher um desses modelos, o editor de texto some e aparecem os campos da seção 3. Uma página com layout novo precisa ser criada pelo desenvolvedor.

**Menus** (Aparência → Menus):

- **Menu Principal:** topo do site e menu do celular. Arraste um item para a direita, abaixo de outro, para ele virar submenu.
- **Menu Rodapé:** links do rodapé no computador. No celular, o rodapé mostra a frase, o e-mail e o telefone das Opções do Tema.
- Clique em **Salvar menu** ao terminar.

## 8. Problemas comuns

| O que acontece | Causa provável | O que fazer |
| --- | --- | --- |
| Mudei e o site não mudou | Página não salva, ou navegador mostrando a versão antiga | Confira se clicou em **Atualizar**. Recarregue com **Ctrl + F5** (no Mac, Cmd + Shift + R) |
| Uma seção inteira sumiu | A lista de itens dela ficou vazia (slides, cards, depoimentos) | Adicione ao menos um item na lista da seção |
| Um botão sumiu | O campo de texto do botão está vazio | Preencha o texto (e o link) do botão |
| Botão não leva a lugar nenhum | Link vazio ou sem https:// | Cole o endereço completo, começando com https:// |
| Imagem cortada | A imagem preenche um espaço de proporção fixa e corta as bordas | Envie imagens na mesma proporção da anterior, com o assunto no centro |
| Site lento para carregar imagens | Fotos muito pesadas | Envie fotos com no máximo 2000 px de largura, em JPG ou WebP |
| Título quebrando no lugar errado | Quebra de linha no campo | Apague ou mova o Enter dentro do campo de Título |

Se algo não se resolver por aqui, anote a página, a seção e o que foi alterado e envie ao desenvolvedor.
