#!/bin/bash
echo -e "Building all documentation for all methods\n"
vendor/bin/phpdoc -d src/ -d test/ -t doc/ --cache-folder cache/ -i src/snac/Config.php --template="clean" --title="SNAC Programming API" --validate --defaultpackagename="snac_server" --force
