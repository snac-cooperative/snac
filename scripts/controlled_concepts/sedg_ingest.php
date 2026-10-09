<?php

include "../../vendor/autoload.php";

use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;

// Set up the global log stream
$log = new StreamHandler(\snac\Config::$LOG_DIR . \snac\Config::$SERVER_LOGFILE, Logger::DEBUG);

// SNAC Postgres DB Connector
$db = new \snac\server\database\DatabaseConnector();


pg_query($db->getHandle(),"BEGIN");

$broaders = [
  'Abolition related CPF',
  'Advertisement related CPF',
  'African American church related CPF',
  'Alternative to freedom status persons',
  'Bookkeeping related CPF',
  'Broader Concept Name',
  'Capture/seizure of person related CPF',
  'Chained person related CPF',
  'Confinement related persons',
  'Convict leasing related CPF',
  'Correspondence related CPF',
  'Creation related CPF',
  'Crime related CPF',
  'Crop production related CPF',
  'Domestic services related CPF',
  'Election related CPF',
  'Enslaved person auction related CPF',
  'Escheat related CPF',
  'Freedmen school related CPF',
  'Freedmen town and settlement related CPF',
  'Freedom seeker related CPF',
  'Funeral related CPF',
  'Hotel/lodging services related CPF',
  'Hunting of enslaved person(s) related CPF',
  'Incarceration related CPF',
  'Insurance related CPF',
  'Lynching related CPF',
  'Maltreatment related CPF',
  'Management related CPF',
  'Manufacturing related CPF',
  'Maternal/maternity related persons',
  'Medical related CPF',
  'Migrant related CPF',
  'Military related CPF',
  'Mortgage related CPF',
  'Nations with enslaver status',
  'Overland transport of enslaved persons related CPF',
  'Persons with subjugated status',
  'Protection document related persons',
  'Provisions related CPF',
  'Redemption of enslaved persons related CPF',
  'Residential related CPF',
  'Ritual practice related CPF',
  'Slave patrol related CPF',
  'Slavery era agriculture related CPF',
  'Slavery era demographic group education related CPF',
  'Slavery era finance related CPF',
  'Slavery era property owners',
  'Slavery era related CPF',
  'Slavery era transportation related CPF',
  'Social group related CPF',
  'Spirituality related CPF',
  'Tithe related CPF',
  'Underground railroad related CPF',
  'Waterway transport of enslaved persons related CPF',
  'Waterway vessel operators',
  'Waterway vessel owners',
  'Waterway vessel user'
];
$broaderTerms = [];

$sedg = $db->query('select id from vocabulary where type=$1 and value=$2',array('concept_category','Slavery Era Demographic Groups (SEDG)'));
if ($row = $db->fetchRow($sedg)) { $sedgId = $row['id']; }
else { exit("SEDG ID not found"); }

$db->prepare("exists","select count(*) as cnt from terms t, concept_categories c where t.concept_id=c.concept_id and c.category_id=$sedgId and text=\$1");
$db->prepare("newTerm","insert into terms (concept_id,text,preferred) values (\$1,\$2,\$3)");
$db->prepare("newCategory","insert into concept_categories (concept_id,category_id) values (\$1,\$2)");

$db->prepare("getBroaderId","select c.concept_id from terms t, concept_categories c where t.concept_id=c.concept_id and c.category_id=$sedgId and text=\$1");
$db->prepare("newRelation","insert into concept_relationships (concept_id,related_concept_id,relationship_type) values (\$1,\$2,'broader')");

echo "Inserting inital Broader Concepts \n";
// Insert inital Broader Concepts
foreach ($broaders as $broader) {
  // Check if broader already exists for this concept category
  $termExists = 0;
  $exists = $db->execute("exists",array($broader));
  if ($row = $db->fetchRow($exists)) { $termExists = $row['cnt']; }

  if (!$termExists) {
    echo "Inserting: $broader \n";

    $newConcept = $db->query("insert into concepts default values returning id",array());
    if ($row = $db->fetchRow($newConcept)) { $conceptId = $row['id']; }
    echo "  inserting using id: $conceptId\n";
    $db->execute("newTerm",array($conceptId,$broader,'true'));
    $db->execute("newCategory",array($conceptId,$sedgId));
  }
}

// Insert Concepts and Preferred and Alternative Terms
echo "Inserting Concepts and Preferred and Alternate Terms \n";

$handle = fopen("sedg_ingest_07_2025.csv", "r");

$count = 0;
while (($data = fgetcsv($handle, 1000, ',')) !== false) {
  $count++;

  // skip headers
  if ($count == 1)
    continue;

  $preferredTerm = $data[0];
  $altTerms = $data[1];
  // $scopeNote = $data[2];
  $broaderTerm = $data[3];

  echo "$preferredTerm \n";

  $termExists = 0;
  $exists = $db->execute("exists",array($preferredTerm));
  if ($row = $db->fetchRow($exists)) { $termExists = $row['cnt']; }
  if (!$termExists) {
    echo "  Inserting: $preferredTerm \n";
    $newConcept = $db->query("insert into concepts default values returning id",array());
    if ($row = $db->fetchRow($newConcept)) { $conceptId = $row['id']; }
    echo "  inserting using id: $conceptId\n";
    $db->execute("newTerm",array($conceptId,$preferredTerm,'true'));
    $db->execute("newCategory",array($conceptId,$sedgId));

    if (isset($altTerms)) {
      $altTerms = array_map('trim', explode(';', $altTerms));
      foreach ($altTerms as $altTerm) {
        if (isset($altTerm) && !empty($altTerm)) {
          echo "  altTerm: $altTerm \n";
          $db->execute("newTerm",array($conceptId,$altTerm,'false'));

        }
      }
    }

    if (isset($broaderTerm) && !empty($broaderTerm)) {
      echo "  relating to: $broaderTerm \n";
      $getBroader = $db->execute("getBroaderId",array($broaderTerm));
      if ($row = $db->fetchRow($getBroader)) { $broaderConceptId = $row['concept_id']; }
      if (isset($broaderConceptId) && !empty($broaderConceptId)) {
        echo "  relating $conceptId to $broaderConceptId\n";
        $db->execute("newRelation",array($conceptId,$broaderConceptId));
      }
    }
  }
}
pg_query($db->getHandle(),"COMMIT");
fclose($handle);
