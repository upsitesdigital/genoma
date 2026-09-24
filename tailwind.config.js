/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: ['class'],
  content: [
    './resources/**/*.{ts,tsx}',
    './app/**/*.{ts,tsx}',
    './index.php',
  ],
  theme: {
    container: {
      center: true,
      padding: { DEFAULT: '1.25rem', sm: '2rem' }, // 20px no mobile (Figma), 32px a partir do sm
      screens: { '2xl': '1400px' },
    },
    extend: {
      colors: {
        border: 'hsl(var(--border))',
        input: 'hsl(var(--input))',
        ring: 'hsl(var(--ring))',
        background: 'hsl(var(--background))',
        foreground: 'hsl(var(--foreground))',
        primary: {
          DEFAULT: 'hsl(var(--primary))',
          foreground: 'hsl(var(--primary-foreground))',
        },
        secondary: {
          DEFAULT: 'hsl(var(--secondary))',
          foreground: 'hsl(var(--secondary-foreground))',
        },
        destructive: {
          DEFAULT: 'hsl(var(--destructive))',
          foreground: 'hsl(var(--destructive-foreground))',
        },
        muted: {
          DEFAULT: 'hsl(var(--muted))',
          foreground: 'hsl(var(--muted-foreground))',
        },
        accent: {
          DEFAULT: 'hsl(var(--accent))',
          foreground: 'hsl(var(--accent-foreground))',
        },
        popover: {
          DEFAULT: 'hsl(var(--popover))',
          foreground: 'hsl(var(--popover-foreground))',
        },
        card: {
          DEFAULT: 'hsl(var(--card))',
          foreground: 'hsl(var(--card-foreground))',
        },
        // Tokens de marca — Genoma Diagnósticos (Figma: Style Guide de Cores)
        brand: {
          purple: '#433292',
          'purple-dark': '#2D2559',
          'purple-accent': '#6E47F2',
          'purple-subtle': 'rgba(67, 40, 187, 0.05)',
          'purple-light': 'rgba(110, 71, 242, 0.2)',
          'gray-text': '#6E6E6E',
          'gray-border': '#DFDEE3',
          'light-purple': '#F5F4FB',
        },
      },
      fontFamily: {
        sans: ['Manrope', 'system-ui', 'sans-serif'],
        heading: ['Poppins', 'system-ui', 'sans-serif'],
      },
      fontSize: {
        h1: ['3rem', { lineHeight: '1.15', fontWeight: '500' }], // Poppins Medium 48px
        h2: ['2.25rem', { lineHeight: '1.3', fontWeight: '400' }], // Poppins Regular 36px
        h3: ['1.75rem', { lineHeight: '1.3', fontWeight: '400' }], // Poppins Regular 28px
        h4: ['1.5rem', { lineHeight: '1.4', fontWeight: '500' }], // Poppins Medium 24px
        h5: ['1.25rem', { lineHeight: '1.4', fontWeight: '500' }], // Poppins Medium 20px
        'h2-mobile': ['1.375rem', { lineHeight: '1.3', fontWeight: '500' }], // Poppins Medium 22px — título de seção no mobile (Figma)
        eyebrow: ['0.8125rem', { lineHeight: '1.5', letterSpacing: '0.0769em' }], // Manrope Regular 13px UPPERCASE — etiqueta de seção
        'body-lg': ['1.125rem', { lineHeight: '1.5' }], // Manrope 18px
        body: ['1rem', { lineHeight: '1.5' }], // Manrope 16px
        'body-sm': ['0.875rem', { lineHeight: '1.5' }], // Manrope 14px
      },
      borderRadius: {
        lg: 'var(--radius)',
        md: 'calc(var(--radius) - 2px)',
        sm: 'calc(var(--radius) - 4px)',
      },
      keyframes: {
        'accordion-down': {
          from: { height: '0' },
          to: { height: 'var(--radix-accordion-content-height)' },
        },
        'accordion-up': {
          from: { height: 'var(--radix-accordion-content-height)' },
          to: { height: '0' },
        },
      },
      animation: {
        'accordion-down': 'accordion-down 0.2s ease-out',
        'accordion-up': 'accordion-up 0.2s ease-out',
      },
    },
  },
  plugins: [require('tailwindcss-animate')],
}
