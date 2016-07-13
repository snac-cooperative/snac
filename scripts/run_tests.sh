#!/bin/bash

mkdir -p coverage
vendor/bin/phpunit --coverage-html coverage/ -v  --report-useless-tests
