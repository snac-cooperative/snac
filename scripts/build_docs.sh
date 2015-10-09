#!/bin/bash
phpdoc -d src/ -t doc/
rsync -av doc/ snac:/projects/snac_server/doc/
