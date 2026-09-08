<?php

include "../../vendor/autoload.php";

use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;

// Set up the global log stream
$log = new StreamHandler(\snac\Config::$LOG_DIR . \snac\Config::$SERVER_LOGFILE, Logger::DEBUG);

// SNAC Postgres DB Connector
$db = new \snac\server\database\DatabaseConnector();

pg_query($db->getHandle(),"BEGIN");

// insert new iso languages
$count = 0;
$handle = fopen("iso_639_3_inserts.csv", "r");

$db->prepare("exists","select count(*) as cnt from vocabulary where type='language_code' and value=\$1");
$db->prepare("newCode","insert into vocabulary (type,value,description) values ('language_code',\$1,\$2)");

while (($data = fgetcsv($handle, 1000, ',')) !== false) {
  $code_3 = $data[0];
  $description_3 = $data[1];


  // skip headers
  if ($count == 1)
    continue;

  $codeExists = 0;
  $exists = $db->execute("exists",array($code_3));
  if ($row = $db->fetchRow($exists)) { $codeExists = $row['cnt']; }
  if (!$codeExists) {
    $count++;
    echo "Inserting $count: $code_3, $description_3 \n";
    $db->execute("newCode",array($code_3,$description_3));
  }
}
pg_query($db->getHandle(),"COMMIT");
fclose($handle);
