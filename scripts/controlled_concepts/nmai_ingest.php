<?php

include "../../vendor/autoload.php";

use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;

// Set up the global log stream
$log = new StreamHandler(\snac\Config::$LOG_DIR . \snac\Config::$SERVER_LOGFILE, Logger::DEBUG);

// SNAC Postgres DB Connector
$db = new \snac\server\database\DatabaseConnector();


echo "Inserting Concepts and Preferred and Alternate Terms \n";

$nmai = $db->query('select id from vocabulary where type=$1 and value=$2',array('concept_category','National Museum of the American Indian Culture Thesaurus'));
if ($row = $db->fetchRow($nmai)) { $nmaiId = $row['id']; }
else { exit("NMAI ID not found"); }


$db->prepare("exists","select count(*) as cnt from terms t, concept_categories c where t.concept_id=c.concept_id and c.category_id=$nmaiId and text=\$1");
$db->prepare("newTerm","insert into terms (concept_id,text,preferred) values (\$1,\$2,\$3)");
$db->prepare("newCategory","insert into concept_categories (concept_id,category_id) values (\$1,\$2)");
$db->prepare("newSource","insert into concept_sources (concept_id,url,citation,found_data,note) values (\$1,\$2,\$3,\$4,\$5)");

pg_query($db->getHandle(),"BEGIN");

$handle = fopen("nmai_ingest_07_2025.csv", "r");

$count = 0;
while (($data = fgetcsv($handle, 1000, ',')) !== false) {
  $count++;

  // skip headers
  if ($count == 1 || $count == 2)
    continue;

  $sourceUrl =         $data[0];  // Concept Source: Url
  $sourceCitation =    $data[1];  // Concept Source: Citation
  $preferredTerm =     $data[2];
  $scopeNote =         $data[3];
  $altTerms =          $data[4];
  $scopeNoteAddition = $data[5];
  $foundData =         $data[6];  // Concept Source: Found data

  echo "$preferredTerm \n";

  $termExists = 0;
  $exists = $db->execute("exists",array($preferredTerm));  
  if ($row = $db->fetchRow($exists)) { $termExists = $row['cnt']; }

  if (!$termExists) {
    echo "  Inserting: $preferredTerm \n";

    $conceptId = 0;
    $newConcept = $db->query("insert into concepts default values returning id",array());
    if ($row = $db->fetchRow($newConcept)) { $conceptId = $row['id']; }
    echo "  inserting using id: $conceptId\n";
    
    $db->execute("newTerm",array($conceptId,$preferredTerm,'true'));  
    $db->execute("newCategory",array($conceptId,$nmaiId));  


    if (isset($altTerms) && !empty($altTerms)) {
      $altTerms = array_map('trim', explode(';', $altTerms));
      foreach ($altTerms as $altTerm) {
        if (isset($altTerm)) {
          echo "  altTerm: $altTerm \n";
          $db->execute("newTerm",array($conceptId,$altTerm,'false'));  
        }
      }
    }
    $note = trim($scopeNote . "\n" . $scopeNoteAddition);
    $db->execute("newSource",array($conceptId,$sourceUrl,$sourceCitation,$foundData,$note));  
  }
}
pg_query($db->getHandle(),"ROLLBACK");
fclose($handle);
