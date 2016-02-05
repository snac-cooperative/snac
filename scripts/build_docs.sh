#!/bin/bash
vendor/bin/phpdoc -d src/ -t doc/ -i src/snac/Config.php --template="responsive-twig" --title="SNAC Programming API" --validate --defaultpackagename="snac_server" --force
