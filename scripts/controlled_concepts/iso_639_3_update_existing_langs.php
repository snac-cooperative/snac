<?php

include "../../vendor/autoload.php";

use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;

// Set up the global log stream
$log = new StreamHandler(\snac\Config::$LOG_DIR . \snac\Config::$SERVER_LOGFILE, Logger::DEBUG);

// SNAC Postgres DB Connector
$db = new \snac\server\database\DatabaseConnector();

pg_query($db->getHandle(),"BEGIN");

// update iso languages
$count = 0;
$handle = fopen("iso_639_3_update_existing_langs.csv", "r");

$db->prepare("newCode","update vocabulary set value=\$3, description=\$4 where id=\$1 and value=\$2");
while (($data = fgetcsv($handle, 1000, ',')) !== false) {
  $id = $data[0];
  $code_2 = $data[1];
  $description = $data[2];
  $new_code = $data[3];
  $new_desc = $data[4];

  $count++;

  // skip headers
  if ($count == 1)
    continue;

  //if ($code_2 == $new_code && $description == $new_desc) { continue; }
  echo "Updating $id to $new_code, $new_desc \n";
  $result = $db->execute("newCode",array($id,$code_2,$new_code,$new_desc));

}
pg_query($db->getHandle(),"COMMIT");
fclose($handle);
