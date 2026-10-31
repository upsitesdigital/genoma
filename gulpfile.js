/**
 * Gulp = compilação final do CSS e do JS manual do tema.
 *
 * CSS: src/sass/main.scss → Sass → PostCSS (Tailwind v4, a base) → assets/css/
 *   ajustes manuais do DEV vão em SCSS (src/sass), por cima do Tailwind.
 * JS:  src/js/mainJS.js (JS puro, ajustes manuais) → webpack → assets/js/
 *
 * Cada asset sai em duas versões — o WP escolhe pelo ambiente (CoreSupportAsset):
 *   aberto  main.css / mainJS.js          → localhost
 *   .min    main.min.css / mainJS.min.js  → homologação e produção
 *
 *   gulp dev      watch do CSS (.scss + classes nos .php/.js) e do JS manual (src/js)
 *   gulp release  versões aberta + .min de todo CSS e JS (roda no npm run build)
 *   gulp fonts    copia as fontes locais src/fonts/ → assets/fonts/ (também no dev e no release)
 * Os stores da Interactivity API ({nome}.store.js) são script modules servidos direto — sem build.
 */
var gulp = require('gulp');
var sass = require('gulp-sass')(require('sass'));
var sourcemaps = require('gulp-sourcemaps');
var postcss = require('gulp-postcss');
var tailwind = require('@tailwindcss/postcss');
var rename = require('gulp-rename');
var changed = require('gulp-changed');
var svgmin = require('gulp-svgmin');
var svgstore = require('gulp-svgstore');
var log = require('fancy-log');
var path = require('path');
var browserSync = require('browser-sync').create();

var webpack = require('webpack');
var webpackConfig = require('./webpack.config.js');

var isWatching = false;

// npm run package liga FW_NO_SOURCEMAPS: o zip de produção não leva sourcemaps nem no CSS/JS aberto
var sourceMaps = !process.env.FW_NO_SOURCEMAPS;

/**
 * SVG
 */
function svg() {
  return gulp
    .src(['./src/img/svg/*.svg'], { allowEmpty: true })
    .pipe(changed('./assets/img/'))
    .pipe(svgmin())
    .pipe(gulp.dest('./assets/img/'));
}

/**
 * Fontes locais: src/fonts/*.woff2 (versionadas) → assets/fonts/ (o @font-face do main.scss
 * aponta para ../fonts/). Nada de Google Fonts por @import/<link> — expõe o IP do visitante (LGPD).
 * Os .txt (licença da fonte, ex.: OFL.txt) vão junto: a OFL exige a licença com a fonte distribuída.
 * encoding: false — no gulp 5 o padrão é ler como texto, o que corrompe binário.
 */
function fonts() {
  return gulp
    .src(['./src/fonts/**/*.{woff2,woff,txt}'], { allowEmpty: true, encoding: false })
    .pipe(changed('./assets/fonts/'))
    .pipe(gulp.dest('./assets/fonts/'));
}
gulp.task('fonts', fonts);

/**
 * Icons
 */
function svgicons() {
  return gulp
    .src(['./src/img/icons/*.svg'], { allowEmpty: true })
    .pipe(
      svgmin(function (file) {
        var prefix = path.basename(file.relative, path.extname(file.relative));
        return {
          plugins: [
            { name: 'removeAttrs', params: { attrs: 'fill' } },
            { name: 'cleanupIds', params: { prefix: prefix + '-', minify: true } },
          ],
        };
      })
    )
    .pipe(svgstore())
    .pipe(gulp.dest('./assets/img/'));
}

/**
 * Sass
 */
var styles = [
  'main',
].concat(
  // critical CSS por módulo: src/sass/{módulo}-above-the-fold.scss (ex.: home) — impresso
  // inline no <head> da página (US_critical_css, upsites/functions.php)
  require('fs').readdirSync('./src/sass')
    .filter(function (f) { return /-above-the-fold\.scss$/.test(f); })
    .map(function (f) { return f.replace(/\.scss$/, ''); })
);

/**
 * Sass → Tailwind. As diretivas do Tailwind (@import 'tailwindcss', @source,
 * @theme, @utility, @apply) atravessam o Sass e são resolvidas pelo PostCSS.
 * minify: true gera {name}.min.css sem sourcemap (prefixos e minificação do Tailwind).
 */
function compileSass(name, minify) {
  var task = function () {
    var stream = gulp.src(['./src/sass/' + name + '.scss'], { allowEmpty: true });
    var maps = !minify && sourceMaps;
    if (maps) stream = stream.pipe(sourcemaps.init());

    stream = stream
      .pipe(
        sass({
          includePaths: ['./node_modules/'],
          outputStyle: 'expanded',
          silenceDeprecations: ['legacy-js-api', 'import'],
        }).on('error', sass.logError)
      )
      .pipe(
        postcss([tailwind({ optimize: minify ? { minify: true } : false })]).on('error', function (err) {
          log.error('[tailwind]', err.message);
          this.emit('end');
        })
      );

    if (minify) stream = stream.pipe(rename({ suffix: '.min' }));
    else if (maps) stream = stream.pipe(sourcemaps.write());

    return stream.pipe(gulp.dest('./assets/css/')).pipe(browserSync.stream());
  };
  task.displayName = minify ? name + ':release' : name;
  return task;
}

var styleWatch = [];
var styleRelease = [];

styles.forEach(function (style) {
  [style, style + '-mobile'].forEach(function (name) {
    /* Desktop / Mobile */
    gulp.task(name, compileSass(name));
    /* Desktop:release / Mobile:release */
    gulp.task(name + ':release', compileSass(name, true));

    styleWatch.push(name);
    styleRelease.push(name + ':release');
  });
});


/**
 * Javascript
 */
function scripts(cb) {
  var bundler = webpack(webpackConfig);
  var handler = function (err, stats) {
    if (err) {
      log.error('[webpack]', err);
      return cb(err);
    }

    log('[webpack]', stats.toString({ colors: true, modules: false }));
    browserSync.reload();
    if (!isWatching) cb();
  };

  if (isWatching) {
    bundler.watch({ aggregateTimeout: 200 }, handler);
    cb();
  } else {
    bundler.run(handler);
  }
}
gulp.task('scripts', scripts);

/**
 * Javascript:release — build de produção do webpack direto em {name}.min.js
 * (sem os eval/sourcemaps do build aberto).
 */
gulp.task('scripts:release', function (cb) {
  var config = Object.assign({}, webpackConfig, {
    mode: 'production',
    devtool: false,
    output: Object.assign({}, webpackConfig.output, { filename: '[name].min.js' }),
  });
  webpack(config).run(function (err, stats) {
    if (err || stats.hasErrors()) {
      log.error('[webpack:release]', err || stats.toString('errors-only'));
      return cb(err || new Error('webpack:release falhou'));
    }
    log('[webpack:release]', stats.toString({ colors: true, modules: false }));
    cb();
  });
});

/**
 * CSS (Tailwind + Sass)
 */
gulp.task('css', gulp.parallel.apply(gulp, styleWatch));
gulp.task('css:release', gulp.parallel.apply(gulp, styleRelease));

/* Classes Tailwind nascem nos .php (e nos stores .js) — qualquer mudança neles recompila o CSS */
gulp.task('css:watch', function () {
  gulp.watch(
    ['./src/sass/**/*.scss', './*.php', './template-pages/**/*.{php,js}', './template-parts/**/*.{php,js}', './upsites/core/Framework/*.php'],
    gulp.series('css')
  );
});

/* JS manual (src/js) recompilado a cada mudança */
gulp.task('js:watch', function (cb) {
  isWatching = true;
  scripts(cb);
});

/* watch do CSS e do JS manual */
gulp.task('fonts:watch', function () {
  gulp.watch(['./src/fonts/**/*'], gulp.series('fonts'));
});

gulp.task('dev', gulp.series(gulp.parallel('css', 'fonts'), gulp.parallel('css:watch', 'js:watch', 'fonts:watch')));

/* Build final: aberto (localhost) + .min (homolog/prod) de todo CSS e JS, + fontes locais */
gulp.task('release', gulp.parallel('css', 'css:release', 'scripts', 'scripts:release', 'fonts'));
gulp.task('svg', svg);
gulp.task('svgicons', svgicons);

/**
 * Live preview
 * Para usar com o WP local: BS_PROXY=localhost/projetos/meu-site gulp
 */
gulp.task('serve', function (cb) {
  // só nesta máquina (listen: localhost) e sem o painel da porta 3001: na rede (Wi-Fi, coworking)
  // ninguém acessa o WP local pelo proxy nem baixa arquivos do tema. Sem proxy, serve só
  // assets/ — nunca a raiz do tema (.git/, .claude/, fontes).
  var common = { open: false, listen: 'localhost', ui: false };
  browserSync.init(
    process.env.BS_PROXY
      ? Object.assign({ proxy: process.env.BS_PROXY }, common)
      : Object.assign({ server: { baseDir: './assets', serveStaticOptions: { dotfiles: 'deny' } } }, common)
  );
  cb();
});

/**
 * Build
 */
gulp.task(
  'build',
  gulp.series(gulp.parallel.apply(gulp, styleWatch.concat(['scripts', 'svg', 'fonts'])), function done(cb) {
    log('Build is finished');
    cb();
  })
);

gulp.task('build:min', gulp.series('release'));

/**
 * Watch files
 */
gulp.task(
  'watch',
  gulp.series('serve', function watch() {
    isWatching = true;

    /* Watch styles (inclui os .php/.js — fonte das classes Tailwind) */
    gulp.watch(
      ['./src/sass/**/*.scss', './*.php', './template-pages/**/*.{php,js}', './template-parts/**/*.{php,js}', './upsites/core/Framework/*.php'],
      gulp.parallel.apply(gulp, styleWatch)
    );

    /* Watch SVG */
    gulp.watch(['./src/img/icons/*.svg'], gulp.series('svgicons'));
    gulp.watch(['./src/img/svg/*.svg'], gulp.series('svg'));

    /* Watch JS */
    scripts(function () {});
  })
);

/**
 * Default task
 */
gulp.task('default', gulp.series(gulp.parallel('build', 'svgicons'), 'watch'));
