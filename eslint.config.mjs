/**
 * ESLint (flat config) — JS do tema: stores da Interactivity API ({nome}.store.js, script modules),
 * módulos do framework (upsites/core/js) e JS manual (src/js).
 * Rodar: npm run lint   ·   tudo junto (eslint + php -l + PHPCS + testes + review): npm run check
 */
import js from '@eslint/js';
import globals from 'globals';

export default [
  { ignores: ['assets/**', 'node_modules/**', 'vendor/**', 'dist/**', 'upsites/core/acf/**'] },
  {
    files: ['template-parts/**/*.js', 'template-pages/**/*.js', 'upsites/core/js/**/*.js', 'src/js/**/*.js'],
    ...js.configs.recommended,
    languageOptions: { ecmaVersion: 2022, sourceType: 'module', globals: globals.browser },
    rules: {
      ...js.configs.recommended.rules,
      'no-unused-vars': ['error', { argsIgnorePattern: '^_', varsIgnorePattern: '^_' }],
    },
  },
];
