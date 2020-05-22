const path = require('path');
const js_path = path.join(__dirname, "src/virtualhosts/www/javascript")

module.exports = (env) => {
  const isProduction = env === 'production';
  return {
    mode: isProduction ? 'production' : 'development',
    entry: {
      bundle: path.join(js_path, "src", "main.js"),
      resource_admin: path.join(js_path,  "src", "resource_admin.js"),
      edit_scripts: path.join(js_path,  "src", "edit_scripts.js"),
      select_loaders: path.join(js_path,  "src", "select_loaders.js"),
    },
    output: {
      path: js_path,
      filename: "[name].js",
      libraryTarget: "window"
    },
    devtool: isProduction ? 'source-map' : "cheap-module-eval-source-map",
    watch: !isProduction,
    module: {
      rules: [{
        loader: "babel-loader",
        test: /\.js$/,
        exclude: /node_modules/
      }]
    },
  };
};
