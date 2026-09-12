---
name: wordpress
description: WordPress theme development, review, and testing conventions for the "logic" theme (Logic Assessoria client). Use when editing PHP templates, upsites/functions.php, or src/js in this repo, when reviewing the theme for bugs, or when testing changes against the local XAMPP site.
---

# logic theme (WordPress + bundled-but-unused ACF Pro copy, vestigial WooCommerce support)

## Project shape — this is an unbuilt boilerplate, not a finished site

This theme is the raw UpSites starter boilerplate, not yet customized for
Logic Assessoria's real pages. Before assuming any template/section "should"
render real content, check whether it's actually been built — most of the
theme is still stub code:

- `style.css`'s theme header is still the unfilled boilerplate placeholder
  (`Theme Name: tema`, `Theme URI: https://tema.com`, `Text Domain: tema`).
- `functions.php` at theme root is a 3-line stub that just `require`s
  `upsites/functions.php` (~172 lines) — that's where the real setup/enqueue
  logic lives.
- **Every file in `template-pages/`** (`about.php`, `category.php`,
  `contact.php`, `default.php`, `home.php`) is a tiny stub (99–105 bytes)
  that just calls `get_header()` → echoes a literal string → `get_footer()`.
  No `get_template_part()`, no ACF, no real markup. **`home.php` is
  mislabeled**: its `Template Name:` comment says `Quem somos` ("About us")
  and it echoes "Quem somos" too — a copy-paste leftover from `about.php`
  that was never renamed. Fix the `Template Name` before assigning this file
  as the real home template, or build a proper `home.php`/`front-page.php`
  from scratch instead of reusing this stub.
- **Every file in `template-parts/`** (`archive.php`, `author.php`,
  `error.php`, `footer.php`, `header.php`, `search.php`, ~256–258 bytes each)
  is the same kind of near-empty stub. `posts/content-blog-featured.php` /
  `posts/content-blog-list.php` are 188 bytes each — effectively empty.
  `inc/inc-posts.php` (3.2K) is the one real file in `template-parts/`.
- **There is no `home.php`/`front-page.php` template that renders real
  content anywhere** — `index.php` dispatches via `is_home()`/`is_author()`/
  `is_archive()`/`is_search()`/`is_404()` to the stub `template-parts/`
  files, and both the `is_404()` and final `else` branches fall through to
  `template-parts/error`.
- `template-pages/insupport.php` (2.2K) is the one non-trivial page
  template: a real maintenance-mode gate (`get_theme_mod('US_maintenance_mode')`,
  redirects non-logged-in/non-`edit_themes` visitors home), but its copy is
  generic UpSites boilerplate about an "accounting portal" — not Logic
  content, don't mistake it for a real page.
- Theme-root `header.php`/`footer.php` route into
  `template-parts/header`/`template-parts/footer` via `get_template_part()`
  — but also carry copy-paste leftovers: `header.php`'s doc-comment says
  `@package HelloElementor` (this isn't a Hello Elementor child theme, just
  an unedited boilerplate comment) and hardcodes `<title>Titulo</title>`
  instead of a dynamic title tag.
- **No custom post type or taxonomy is registered anywhere**:
  `upsites/customizer-register/inc/register_cpts.php` and
  `register_taxes.php` are both empty function bodies
  (`function US_register_cpts() {}`) hooked to `init`.
- **No AJAX handlers exist** in the active theme code (`add_action('wp_ajax...')`
  matches only inside the vendored-but-unused `upsites/acf/` plugin copy).
- ACF Pro's full source is vendored inside `upsites/acf/` but **never
  `require`d by `functions.php`**, and **no ACF plugin is installed at all**
  in `wp-content/plugins` (only `akismet` and `hello.php`/Hello Dolly) — see
  the `acf` skill, this is a hard blocker for testing any ACF field in
  wp-admin until fixed.
- `add_theme_support('woocommerce')` + `wc-product-gallery-zoom/lightbox/slider`
  are declared (`upsites/functions.php`), but WooCommerce isn't in
  `wp-content/plugins` — dead code, leftover boilerplate.

## No `manifests/` yet — clean slate

There is no manifest history to consult yet. `.claude/prompt.yaml` points at
the real Logic Assessoria Figma file (`CiKVhTZ6nroHoc7TkJgwMY`), so the
t1000/t800 pipeline can be run against it directly whenever the build
starts.

## `functions.php` enqueue logic — hand-written, not a config table, has dead/duplicate branches

`upsites/functions.php`'s `wp_enqueue_scripts` (priority 999) is an
if/elseif chain, not a data-driven `$pages` map:
- Always enqueues Font Awesome 4.7.0 from `maxcdn.bootstrapcdn.com` — **and
  `header.php` also hardcodes a second Font Awesome `<link>` from
  `cdnjs.cloudflare.com`**. Both load today; this is a real, still-unfixed
  duplicate (unlike some sibling UpSites projects where this was already
  cleaned up) — drop one of the two.
- `mainJS`/`scrollrevealJS` (or `.min.js`) gated by
  `strpos(get_bloginfo('url'), 'localhost') !== false`. `src/js/scrollreveal.js`
  **does exist** as a source file, but neither `webpack.config.js` nor
  `gulpfile.js` has an entry/task that outputs it to `assets/js/` — the
  enqueue will 404 until a build entry for it is added (see `performance`
  skill).
- `if (is_page_template('template-pages/home.php'))` → enqueues
  `home(.min).css` / `home-mobile(.min).css` / `slick(.min).js` — reachable
  only once a page actually gets `template-pages/home.php` assigned as its
  template (currently that file is the mislabeled "Quem somos" stub above).
- `elseif (is_single())` → reuses that same `home`/`slick` bundle.
- `else` → `main(.min).css`, gated by a `local.wp4` OR `localhost` string
  check.
- `wp_localize_script('theme', 'usAjax', ...)` then `wp_enqueue_script('theme')`
  — but no `wp_enqueue_script` ever *registers* a `'theme'` handle with a
  `src` (only a `wp_enqueue_style('theme', ...)` exists elsewhere) — looks
  broken/dead, same as other UpSites boilerplate copies.
- **`assets/css/` and `assets/js/` are both currently empty on disk** — the
  build has never been run, so every one of the above enqueues 404s right
  now. Don't debug "why doesn't my CSS/JS show up" by reading the enqueue
  logic alone — check whether `assets/` actually has the built file first.

## Build system

- `package.json`: legacy-era toolchain — `webpack@1.14.0`, `gulp@3.9.1`,
  `babel-preset-es2015`. Scripts: `build` (`gulp sass:release & npm run
  scripts`), `scripts` (`webpack -p --progress --config
  webpack.production.config.js`). No `dev`/`watch` npm script — use the gulp
  tasks directly (`npx gulp <task>`, `npx gulp watch`) for local dev.
- `gulpfile.js` has **no auto-discovery** of per-page SCSS (no
  `getPageFiles()` or similar) — it hardcodes a `styles = ['main']` array
  and generates `main`/`main-mobile` (+ `:release`) tasks from it. To add a
  new page's CSS pipeline, push the page name onto `styles` (see the
  `fluid-scss-pages` skill's page-registration step) — nothing scans for new
  files automatically here.
- **`src/sass/pages/*.scss` and `src/sass/pages/mobile/*.scss` (8 files:
  about/blog/contact/home, desktop + mobile) are all 3-line stubs** — just a
  comment banner, zero real CSS rules yet.
- `webpack.config.js` (dev) and `webpack.production.config.js` (prod) both
  output to `./assets/js` with a **relative** `publicPath` (`./assets/js`) —
  no leftover wrong-theme-folder hardcoding here, unlike some sibling
  UpSites projects.
- `webpack.config.js`'s entries are `mainJS`, `seletric`, `slick` only — no
  `scrollreveal` entry, even though `src/js/scrollreveal.js` exists (see
  enqueue note above).
- `src/js/` also has extra modules not mentioned by the enqueue logic yet:
  `modules/form-masks.js`, `modules/form-validators.js`, `modules/form.js`,
  `modules/hero.js`, `modules/parallax.js`, `modules/slider.js`,
  `vendor/plugins.js`, `pages/_home.js` — check whether a given page's
  section actually needs one of these before assuming it's dead code; they
  may just not be wired into a webpack entry yet.
- `src/ejs/` (`index.ejs` + `partials/header.ejs`/`footer.ejs`) is a static
  HTML-prototype scaffold, unrelated to the WordPress build — don't confuse
  it with the real theme templates.

## Testing against the local site

- Local dev URL: `http://localhost/projetos/logic/site/` (per `.htaccess`'s
  `RewriteBase /projetos/logic/site/`). DB name is `logic` (`wp-config.php`),
  user `root`, no password, XAMPP MySQL, `WP_DEBUG` is `false`. No WP-CLI
  installed.
- Installed plugins: only `akismet` and `hello.php` (Hello Dolly) — no ACF
  Pro, no caching plugin, no WooCommerce, despite the theme-support
  declaration above.
- `debug.log` at the theme root is **not** a WordPress debug log — it's a
  handful of unrelated Chrome `crash_report_database_win.cc` lines, leftover
  from something else on this machine. Don't read it expecting PHP errors.
- Quick syntax check: `php -l <file>` (or sweep the whole theme — it's
  fast).
- The `localhost` string check in `upsites/functions.php` switches between
  raw and `.min` assets — keep that in mind when a CSS/JS change "isn't
  showing up" locally (also check `assets/` isn't just empty, see above).
- No git history exists yet for this repo (zero commits as of this
  writing) — don't expect `git log`/`git blame` to explain *why* something
  is the way it is; it's all still first-pass boilerplate.
