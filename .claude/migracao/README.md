# Migração do genoma: tema React → UpWork v3 (PHP)

O site está em produção com o tema React (`upwork`, branch `add-acf`). Esta branch
(`migracao-v3`) troca o tema pelo v3 **sem mexer no conteúdo**: as páginas, os campos ACF
e as opções continuam com os mesmos nomes, e nada no banco é renomeado ou apagado.

## Status

| Etapa | O que é | Situação |
|---|---|---|
| 1. Base | v3 no lugar do React; módulos com os mesmos `fw:{slug}`; os 230 campos com as mesmas keys/names; menus `primary`/`footer` | feita |
| 2. Opções do Tema | `upwork_theme_options` → campos do ACF (Configuração do Tema), pelo `ThemeOptions::migrateLegacy()` | feita |
| 3. Visual | seções em PHP + Tailwind v4, comparadas com o **site atual** (não com o Figma) | feita: header, footer e todas as páginas (Home, O Genoma, Veterinários, Responsável, Contato, Blog, busca, post, página comum, 404) ≥ 99,9% iguais ao React, com a mesma altura, no desktop e no mobile (o que falta é reamostragem de foto pelo `srcset`) |
| 4. Comparação final | cada URL antiga × nova, desktop e mobile; Yoast; Lighthouse | em andamento: páginas, footer, campos e interações conferidos no banco local (02/11/2026); falta Yoast e Lighthouse (o Yoast não está instalado no local) |
| 5. Deploy | homologação com cópia do banco de produção → produção | a fazer |

## O que garante que o conteúdo não muda

- **Modelo da página:** `_wp_page_template = fw:{slug}` — o v3 usa o mesmo formato; os
  módulos têm os mesmos slugs, rotas e rótulos do tema React.
- **Campos:** `upsites/customizer-register/inc/acf_fields.php` tem os campos do tema React
  com as mesmas keys, names, tipos e opções; cada aba virou uma `Fields::section()` com o
  segmento que já existia nas keys (`servicos`, `sobre`, `footer_cta`…). A única novidade
  é o toggle `{seção}_ocultar` de cada seção (desmarcado = visível).
- **Opções:** os names dos campos são as chaves antigas (`cta_primary_label`…); os pares
  `*_id`/`*_url` de imagem viram `logo`, `footer_logo`, `footer_cta_image`. Valor vazio não
  migra (o React mostrava o padrão; nunca salvo no ACF faz o mesmo). `primary_color` não
  migra (não era usada no site). A option antiga fica intacta.
- **Menus:** localizações `primary` e `footer`, como no React; a pasta do tema em produção
  continua `upwork`, então as `theme_mods_upwork` (menus atribuídos) valem.

## Verificar (local ou homologação)

Os dois temas lado a lado no mesmo WordPress: o React em `themes/upwork`, esta branch em
outra pasta (aqui, `themes/upwork-migracao` — worktree do git).

1. Copie `fw-tema-local.php` para `wp-content/mu-plugins/` (só local/homologação — nunca
   produção). Ele troca o tema por requisição: cookie `fw_tema` no navegador
   (`?fw_tema=upwork-migracao` grava, `?fw_tema=0` apaga) ou `FW_TEMA` no CLI.
2. Campos e valores:

   ```bash
   FW_TEMA=upwork php .claude/migracao/verificar-campos.php <raiz do WP> react.json
   FW_TEMA=upwork-migracao php .claude/migracao/verificar-campos.php <raiz do WP> v3.json
   node .claude/migracao/comparar-campos.mjs react.json v3.json   # tem que terminar em "OK: nada mudou"
   ```

   Resultado no banco local (31/10 e de novo em 02/11/2026): 230 campos idênticos, 1.334
   referências do banco resolvidas, 148 valores iguais nas 7 páginas. As 532 referências órfãs
   são restos de versões antigas das páginas e são as mesmas nos dois temas.
3. Opções: abra o wp-admin como administrador com o cookie do tema novo — a migração roda
   uma vez (`upwork_theme_options_migrado` = 1) — e confira Configuração do Tema. Ou pelo
   terminal, depois de um backup de `wp_options`:
   `FW_TEMA=upwork-migracao php .claude/migracao/migrar-opcoes.php <raiz do WP>`.
   Banco local: migrado em 31/10/2026 pelo terminal — 15 opções gravadas; as outras 7
   (`site_name`, `footer_text`, `footer_tagline`, `footer_email`, `footer_phone`,
   `footer_rights_text`, `footer_cta_image`) estavam vazias e continuam no padrão, como no React.
4. Visual: `node .claude/migracao/capturar-referencias.mjs` (PNGs do React por seção, em
   `.fidelity/referencia/`) e, por página, `MSYS_NO_PATHCONV=1 node .claude/migracao/comparar-paginas.mjs home /`
   (o `<main>` inteiro nos dois temas, desktop e mobile; prints e diff em `.fidelity/paginas/`).
   O footer: `MSYS_NO_PATHCONV=1 node .claude/migracao/comparar-footer.mjs` (7 páginas, em
   `.fidelity/footer/`). No Git Bash, o `MSYS_NO_PATHCONV=1` evita que o `/` vire caminho do Windows.
   O tema ativo do WordPress local é o v3: o React só aparece com o cookie `fw_tema=upwork`
   (os scripts já o pedem).

## Checklist de deploy

- [ ] O zip do `npm run package` usa o **nome da pasta**: gere a partir de uma pasta
      chamada `upwork` (não `upwork-migracao`), senão o tema perde o slug e os menus.
- [ ] Homologação com cópia do banco de produção: verificação acima sem diferenças.
- [ ] Logo depois de subir em produção, abrir o wp-admin como administrador (dispara a
      migração das opções; até lá o topo e o rodapé mostram os textos padrão).
- [ ] Conferir Configuração do Tema e uma página de cada modelo no painel.
- [ ] Volta: reinstalar o tema React (os dados não foram renomeados nem apagados; o
      React ignora as `options_*` novas e os toggles `_ocultar`).
- [ ] Os endpoints `/wp-json/framework/v1/…` deixam de existir (só a SPA usava).
