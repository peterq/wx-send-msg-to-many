requirejs.config({
  paths: {
    babel: 'lib/babel.min',
    es6: 'lib/es6',
    vue: 'https://unpkg.com/vue@2.5.16/dist/vue.min',
    ELEMENT: 'https://unpkg.com/element-ui@2.4.2/lib/index',
  },
  baseUrl: '/js',
  es6: {
    fileExtension: '.js' // put in .jsx for JSX transformation
  },
  babel: {
    presets: ['es2015'],
    plugins: ['transform-es2015-modules-amd']
  }
});

