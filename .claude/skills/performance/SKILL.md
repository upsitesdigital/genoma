---
name: performance
description: Known performance risk areas in the "logic" WordPress theme (Logic Assessoria) — no cache headers, duplicate Font Awesome load, a scrollreveal enqueue with no matching build entry, and a build that has never been run (assets/ is empty). Use when investigating PageSpeed/Lighthouse scores, slow page loads, or before running/fixing the build.
---

# Performance notes for the logic theme

This theme is an unbuilt boilerplate (see the `wordpress` skill) — most
"performance" issues right now are about the build/enqueue pipeline never
having been run, not about optimizing real page content, because there
isn't much real page content yet (`template-pages/`/`template-parts/` are
stubs, `src/sass/pages/*.scss` are 3-line stubs with zero real CSS).

## Build has never been run — `assets/css/` and `assets/js/` are empty

Both directories exist but contain zero files. Every `wp_enqueue_style`/
`wp_enqueue_script` call in `upsites/functions.php` currently 404s. Run
`npx gulp sass:release` (CSS) and `npm run scripts` (webpack, JS) — or
`npm run build` for both — before evaluating any real page-weight/PageSpeed
number; right now a slow/empty page load is a build-pipeline symptom, not a
real optimization problem.

## Font Awesome loaded twice

`header.php` hardcodes `<link href="https://cdnjs.cloudflare.com/.../font-awesome.min.css">`
directly in `<head>`, **and** `upsites/functions.php`'s `wp_enqueue_scripts`
separately enqueues Font Awesome 4.7.0 from `maxcdn.bootstrapcdn.com`. Both
requests fire on every page load. Drop the hardcoded `<link>` in
`header.php` and keep only the enqueued version (or vice versa) — same fix
pattern documented in sibling UpSites-boilerplate projects, but **not yet
applied here**.

## `scrollreveal` enqueue has no build entry to satisfy it

`upsites/functions.php` enqueues `scrollrevealJS` from
`{$assets_src}/js/scrollreveal(.min).js`. The source file
`src/js/scrollreveal.js` **does exist** (unlike some sibling projects where
it's missing entirely), but neither `webpack.config.js`'s `entry` map
(`mainJS`, `seletric`, `slick` only) nor `gulpfile.js`'s `jsBuild` array
includes it — so `assets/js/scrollreveal.js` will never be produced by the
current build, and the enqueue will 404 regardless of whether the build has
been run. Either add a webpack entry (and a matching `gulp` `:release` task)
for it, or remove the enqueue if the feature isn't actually used yet — grep
the theme for `ScrollReveal(` to check before assuming it's needed.

## `gulpfile.js` has no per-page auto-discovery (by design, not a bug)

Unlike some sibling UpSites projects, this `gulpfile.js` doesn't scan a
folder for page SCSS files — it hardcodes a `styles = ['main']` array and
builds `main`/`main-mobile` (+`:release`) from it. As real pages get built
(see the `fluid-scss-pages` skill), add each page name to that array
directly; there's no auto-discovery path to fix or debug here, just entries
to add as pages come online.

## No cache headers, no compression config

`.htaccess` (site root) only has the stock WordPress rewrite block — no
`Expires`/`Cache-Control` for static assets, no `mod_deflate`/gzip
directive. No caching plugin is installed (plugins present: only Akismet
and Hello Dolly).

## Legacy build toolchain

`webpack@1.14.0`, `gulp@3.9.1`, `babel-preset-es2015` — a pre-modern
toolchain (no code-splitting, no tree-shaking beyond `lodash-webpack-plugin`,
no ES modules). Not itself a bug, but worth knowing before assuming modern
webpack behavior (e.g. `optimization.splitChunks`) applies — this version
predates that API (it uses `webpack.optimize.DedupePlugin`/
`OccurrenceOrderPlugin` instead, both removed in later webpack majors).

## No ACF Pro installed — no field-driven images/content to audit yet

See the `acf` skill: no ACF plugin is active at all, so there's no
`wp_get_attachment_image()`/ACF-image-field usage to audit for `srcset`/
`loading` yet. Once real sections get built (via the t1000/t800 pipeline),
audit each hero/LCP image for `fetchpriority="high"`/eager loading instead
of a blanket `loading="lazy"` inherited from a shared template pattern —
this is a "when you get there" note, not a current bug, since no image
markup exists in any real template yet.

## Debugging technique: real network capture beats guessing from static HTML

The PageSpeed Insights web report is a JS-rendered SPA — `WebFetch`/`curl` on
its URL only returns the empty shell, and the public PSI API has a 0-quota
daily limit without an API key. A local headless Chrome capture against the
live/local URL resolves ambiguous network requests reliably:
```bash
"/c/Program Files/Google/Chrome/Application/chrome.exe" --headless=new --disable-gpu \
  --virtual-time-budget=20000 --log-net-log=netlog.json <url>
```
Then parse `netlog.json` for `URL_REQUEST_START_JOB` events (grouped by
`source.id`, matched against `constants.logEventTypes`) to get every
request's URL, start time, and duration — useful once there's an actual
built page to profile, e.g. to confirm the duplicate Font Awesome load or
check whether the scrollreveal 404 above is actually firing.
