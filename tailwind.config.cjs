module.exports = {
  content: ['./src/**/*.php', './src/js/**/*.js'],
  theme: {
    extend: {
      fontFamily: { sans: ['Geist', 'system-ui', 'sans-serif'] },
      colors: {
        brand: {
          50: '#f0f6f6', 100: '#dceced', 200: '#bed9dc', 300: '#8ebbc1',
          400: '#5c9ba4', 500: '#387e88', 600: '#256e78', 700: '#205c65',
          800: '#234b53', 900: '#202a30',
        },
      },
    },
  },
  plugins: [],
};
