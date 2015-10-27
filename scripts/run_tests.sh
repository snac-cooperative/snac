#!/bin/bash

echo -e "# Current Test Results\n" > Unit\ Test\ Summary.md
echo -e "Date: `date`\n" >> Unit\ Test\ Summary.md
echo '```' >> Unit\ Test\ Summary.md
vendor/bin/phpunit | tee -a Unit\ Test\ Summary.md
echo '```' >> Unit\ Test\ Summary.md
