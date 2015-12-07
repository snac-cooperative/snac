#!/bin/bash
vendor/bin/phpdoc -d src/ -t doc/ -i src/snac/Config.php
rsync -av doc/ snac:/projects/snac_server/doc/
