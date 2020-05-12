const path = require('path');
const js_path = path.join(__dirname, "src/virtualhosts/www/javascript")

module.exports = {
  entry: path.join(js_path, "main.js"),
	output: {
		path: js_path,
		filename: "bundle.js"
	}
};
