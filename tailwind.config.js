/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class',
  content: [
    './app/Views/**/*.php',
    './woocommerce/**/*.php',
    './header.php',
    './footer.php',
    './index.php',
    './assets/js/**/*.js',
    './template-parts/**/*.php',
    './templates/**/*.php',
    './*.php',
    './sidebar.php',
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          50: 'oklch(0.97 0.02 180)',
          100: 'oklch(0.93 0.04 180)',
          200: 'oklch(0.87 0.08 180)',
          300: 'oklch(0.78 0.12 180)',
          400: 'oklch(0.68 0.16 180)',
          500: 'oklch(0.58 0.18 180)',
          600: 'oklch(0.48 0.16 180)',
          700: 'oklch(0.40 0.14 180)',
          800: 'oklch(0.33 0.12 180)',
          900: 'oklch(0.27 0.10 180)',
        },
        dark: {
          50: 'oklch(0.97 0.01 260)',
          100: 'oklch(0.93 0.02 260)',
          200: 'oklch(0.87 0.03 260)',
          300: 'oklch(0.78 0.04 260)',
          400: 'oklch(0.65 0.05 260)',
          500: 'oklch(0.50 0.06 260)',
          600: 'oklch(0.40 0.06 260)',
          700: 'oklch(0.32 0.05 260)',
          800: 'oklch(0.25 0.04 260)',
          900: 'oklch(0.18 0.03 260)',
        },
        surface: '#ffffff',
        muted: '#6b7280',
        darkSurface: 'oklch(0.25 0.04 260)',
        darkCard: 'oklch(0.32 0.05 260)',
      },
      fontFamily: {
        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' },
        },
      },
      animation: {
        fadeIn: 'fadeIn 0.3s ease-in-out',
      },
    },
  },
  plugins: [],
};
