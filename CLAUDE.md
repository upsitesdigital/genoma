# Genoma — UpWork v3 (migração do tema React)

Site em produção migrando do tema React para o v3. Siga as skills do framework e,
acima delas, estas regras do projeto. Estado e procedimento: `.claude/migracao/README.md`.

- **Nunca renomeie key nem name de campo existente** (`acf_fields.php`): o conteúdo de
  produção está gravado com eles. Pode acrescentar `instructions`, `Fields::icon()`/
  `Fields::image()` e campos novos; não pode trocar tipo, name, key ou estrutura de repeater.
- **Seções** têm o nome do segmento que já existia nas keys, em português (`servicos`,
  `sobre`, `footer_cta`…), e a pasta em `template-parts/includes/{slug}/` segue esse nome em
  kebab-case (`footer-cta`). Seções com `// legado:` têm keys fora do padrão (o-genoma com
  hífen, hero de resultado-pesquisa) — o review não cobra o prefixo delas.
- **Campo novo** segue o padrão normal: `field_{slug}_{seção}_{nome}` / `{seção}_{nome}`.
- **Aba "Rodapé" de cada página** (`footer_cta_*`, marcada `// layout:`) não é seção: o
  `layout/footer` lê esses campos da página atual (`get_queried_object_id()`) e, vazios, usa
  as Opções do Tema (`footer_cta_title`…) — como o `useFooterCtaOverride` do React.
- **`id` das `<section>` = os do React** (âncoras dos menus de produção: `#servicos`,
  `#portfolio-de-exames`, `#tipos-de-exames`, `#contatos`…), mesmo quando diferem da pasta.
- **Referência visual é o site atual** (tema React, `themes/upwork`, branch `add-acf`), não o
  Figma: o site teve muitos ajustes depois do layout. O JSX (`app/{slug}/{slug}.view.tsx`,
  `resources/components/`) é a fonte da estrutura e das classes; os PNGs em
  `.fidelity/referencia/` (`.claude/migracao/capturar-referencias.mjs`) são o alvo do fidelity:
  `FW_TEMA=upwork-migracao npm run fidelity -- {slug}/{seção} --figma-png .fidelity/referencia/{slug}/{id}-desktop.png --page {ID}`.
- **Opções do Tema:** names = chaves da option antiga (`cta_primary_label`, `footer_cta_title`…);
  leitura com `ThemeOptions::text()`/`value()`.
- **Menus:** localizações `primary` e `footer` (`MenuApi::tree('primary')`).
- **Classe alternada por store** (`data-wp-class--{classe}`): o Tailwind não a lê no nome do
  atributo — acrescente-a ao `@source inline(…)` do `src/sass/main.scss`.
- **Imagens novas:** `palavra-chave-seo` do prompt.yaml (`diagnostico-veterinario-em-sp-{NN}`).
