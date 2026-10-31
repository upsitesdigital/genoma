/**
 * JS manual (JavaScript puro) de src/js → assets/js. Executado pelo gulp (task "scripts").
 * Os stores da Interactivity API ({nome}.store.js) não passam por aqui: são script modules servidos direto.
 */
var path = require('path');
var webpack = require('webpack');

var isProduction = process.env.NODE_ENV === 'production';

module.exports = {
  mode: isProduction ? 'production' : 'development',
  entry: {
    mainJS: './src/js/mainJS.js',
  },
  output: {
    path: path.resolve(__dirname, 'assets/js'),
    publicPath: './assets/js/',
    filename: '[name].js',
  },
  devtool: isProduction || process.env.FW_NO_SOURCEMAPS ? false : 'eval-cheap-source-map',
  plugins: [
    new webpack.IgnorePlugin({ resourceRegExp: /^\.\/locale$/, contextRegExp: /moment$/ }),
  ],
  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
          options: { presets: ['@babel/preset-env'] },
        },
      },
    ],
  },
};
