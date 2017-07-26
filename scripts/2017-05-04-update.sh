#!/bin/bash

echo -e "Taking the site into read-only mode\n========================\n\n"
sed -i 's/READ_ONLY = false/READ_ONLY = true/g' ../src/snac/Config.php

echo -e "\n\n\nFixing Relations\n========================\n\n"
echo "" > fix_relations.log
php fix_relations.php 2>&1 | tee 2017-05-04-fix-relations.out

echo "" > sql_queries.log
echo -e "\n\n\nRemoving Null Relations\n========================\n\n"
php sql_queries.php nullrelations 2>&1 | tee 2017-05-04-null-relations.out

echo -e "\n\n\nRemoving Resource descriptive notes\n==================================\n\n"
php sql_queries.php removeresourcenote 2>&1 > 2017-05-04-fix-resourcenotes.out

echo -e "\n\n\nAuto-confirming Place - Geoplace links\n==================================\n\n"
php sql_queries.php placelink 2>&1 | tee 2017-05-04-place-link.out

echo -e "\n\n\nBuilding Browse Name Index\n=========================\n\n"
echo "" > rebuild_browse_index.log
php rebuild_browse_index.php 2>&1 | tee 2017-05-04-browse-index.out


