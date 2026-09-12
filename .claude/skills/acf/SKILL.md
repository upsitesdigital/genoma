---
name: acf
description: Advanced Custom Fields (ACF Pro) conventions for the "logic" theme (Logic Assessoria) — ACF Pro is NOT installed as a plugin here, only a vendored-but-unrequired copy exists, and zero fields are registered anywhere. Use when adding the first ACF field group to a template, or checking whether a given field already exists before creating a new one.
---

# ACF Pro in the logic theme — not even installed yet

**No ACF field is registered or read anywhere in this theme.** Grepping the
whole theme for `get_field(`, `get_sub_field(`, `have_rows(` returns zero
matches (outside the vendored ACF plugin source itself). If you're adding
ACF to a template, you are writing the theme's *first* real field usage.

## Blocker: ACF Pro isn't active on this site at all

`wp-content/plugins/` only contains `akismet/` and `hello.php` (Hello
Dolly). **There is no `advanced-custom-fields-pro` (or free ACF) plugin
installed.** `upsites/acf/` is a full vendored copy of ACF Pro's plugin
source (`acf.php`, `includes/`, `pro/`, `assets/`), but `upsites/functions.php`
never `require`s it — dead weight, same as the sibling `wordpress` skill's
theme-shape notes describe for other vendored-but-unused code in this
boilerplate.

Practical effect: `upsites/customizer-register/inc/acf_fields.php` already
guards its body with `if (function_exists('acf_add_local_field_group'))`, so
calling `acf_add_local_field_group()` today is a silent no-op — no fatal
error, but also no field group registered, and nothing will appear in
wp-admin. **Before the t1000/t800 pipeline's ACF output can actually be
tested in wp-admin, either install+activate a real ACF Pro plugin, or wire
`upsites/functions.php` to `require` the vendored `upsites/acf/acf.php`.**
This is a one-time setup step, not something t800 should try to fix per
field group.

## Where field registration lands, and its current state

`upsites/customizer-register/inc/acf_fields.php` is the file the
Figma→theme pipeline (`.claude/agents/t800.md`) writes ACF field-group PHP
into — confirmed wired via an `include 'inc/acf_fields.php';` in
`upsites/customizer-register/customizer-register.php`. Right now it's a
3-line empty stub:
```php
<?php
if (function_exists('acf_add_local_field_group')) :
endif;
```
Zero field groups registered. Register new fields here (via
`acf_add_local_field_group()`), inside the existing `if`/`endif` guard,
rather than only through wp-admin, so definitions stay versioned in the
repo.

There is **no `acf-json` sync folder** in this repo — any field group
created through wp-admin only exists in the database unless one is set up.

Sibling files in the same directory, for context:
- `title_tagline.php` (real, ~43 lines) — registers 5 **Customizer**
  settings (not ACF) under the built-in `title_tagline` section:
  `US_link_facebook`, `US_link_instagram`, `US_link_linkedin`,
  `US_link_twitter`, `US_copyright`. Social links / copyright go here via
  `get_theme_mod()`, not ACF.
- `register_cpts.php` / `register_taxes.php` — both empty function bodies
  (`function US_register_cpts() {}` / equivalent) hooked to `init`. No
  custom post type or taxonomy exists yet.
- `banners.php` — effectively empty (5 bytes), and its `include` is
  commented out in `customizer-register.php`.

## Guarding pattern to follow once fields exist

No `get_field()`/`have_rows()` calls exist yet to copy from, but keep this
guard (doubly important here since ACF may not be active at all yet — see
blocker above) for anything added:
```php
if ( function_exists('have_rows') && have_rows('field_name') ) { ... }
if ( function_exists('get_field') && get_field('field_name') ) { ... }
```

## Shared components and flexible content (this project's pipeline)

`.claude/prompt.yaml` uses two ACF patterns beyond a plain field group per
section — see `.claude/WORKFLOW.md` regras 5b/5c and `.claude/agents/t800.md`
for the authoritative rules, summarized here:
- **`componente: <id>`** (e.g. the shared CTA/Diferenciais sections): one
  field group, `location` grows by one OR-rule per page that uses it —
  never duplicate the field group per page.
- **`flexible: true` + `layouts:`** (e.g. the "Single de cases" content
  blocks): one `flexible_content` field with one layout per block type —
  never one field group per layout.

## No `manifests/` yet — clean slate

There's no manifest history to consult for field names for this project —
`.claude/prompt.yaml` points at the Logic Assessoria Figma file
(`CiKVhTZ6nroHoc7TkJgwMY`), and the first real t1000/t800 pipeline run will
produce the first manifests and field registrations.

Before adding a new field usage, run
`grep -rohE "(get_field|get_sub_field|have_rows)\('[a-zA-Z_]+'" .` (scoped to
the theme, not `upsites/acf/`) to confirm whether a similarly-named field
already exists, rather than assuming a manifest name is already implemented.
