module.exports = {
  content: ['./src/**/*.php', './src/js/**/*.js'],
  theme: {
    extend: {
      fontFamily: { sans: ['Geist', 'system-ui', 'sans-serif'] },
      colors: {
        brand: {
          50: '#f1f5fc',
          100: '#dce8fa',
          200: '#bfd3f2',
          300: '#91b2e6',
          400: '#5d8ad6',
          500: '#2d63b8',
          600: '#1a4b8c',
          700: '#143a70',
          800: '#0f2a56',
          900: '#0c234b',
          950: '#071633',
        },
        unefa: {
          navy: '#0c234b',
          primary: '#0f2a56',
          royal: '#1a4b8c',
          gold: '#d49b1a',
          'gold-bright': '#f0be49',
          'gold-soft': '#fdf4dc',
          'gold-ink': '#734d06',
          crimson: '#a1242b',
          'crimson-soft': '#fcebed',
          'crimson-ink': '#8b1d23',
        },
      },
    },
  },
  plugins: [],
};
