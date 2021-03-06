#!/usr/bin/env php
<?php
/**
 * Fix the vocabulary 
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
// Include the global autoloader generated by composer
include "../vendor/autoload.php";

use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;

// Set up the global log stream
$log = new StreamHandler("june_2017_update.log", Logger::WARNING);

// SNAC Database Connectior
$db = new snac\server\database\DatabaseConnector();

// SNAC Postgres User Handler
$dbuser = new \snac\server\database\DBUser();
$tempUser = new \snac\data\User();
$tempUser->setUserName("system@localhost");
$user = $dbuser->readUser($tempUser);
$user->generateTemporarySession();

echo "Updating Name Contributors into SCMs.\n";

$query = "select * from vocabulary where type = 'name_type';";

$result = $db->query($query, array());

$lookup = array();

while ($res = $db->fetchRow($result)) {
    $lookup[$res["id"]] = $res["value"];
}
$query = "select * from name_contributor;";

$result = $db->query($query, array());

$data = array();

while ($res = $db->fetchRow($result)) {
    if (!isset($data[$res["name_id"]])) {
        $data[$res["name_id"]] = array();
        $data[$res["name_id"]]["contributors"] = array();
    }

    //if (isset($data[$res["name_id"]]["contributors"][$res["short_name"]]))
    //    echo "Trying to overwrite {$res["short_name"]} in id {$res["id"]}\n";

    //$data[$res["name_id"]]["contributors"][$res["short_name"]] = $lookup[$res["name_type"]];
    array_push($data[$res["name_id"]]["contributors"], array(
        "contributor" => $res["short_name"],
        "form" => $lookup[$res["name_type"]]
    ));
    
    if (!isset($data[$res["name_id"]]["version"]) || $data[$res["name_id"]]["version"] > $res["version"])
        $data[$res["name_id"]]["version"] = $res["version"];
    $data[$res["name_id"]]["ic_id"] = $res["ic_id"];
}

$insertQ = "insert into scm (fk_id, fk_table, ic_id, version, source_data, note) values ($1, 'name', $2, $3, $4, 'Contributors from initial SNAC EAC-CPF ingest');";
$db->prepare("insert_contributor", $insertQ);

echo "    Doing the inserts now: ";
$i = 0;
foreach ($data as $id => $name) {
    $cstring = json_encode($name["contributors"], JSON_PRETTY_PRINT);
    //echo "Inserting ({$name["ic_id"]}, $id, {$name["version"]})\n";
    if ($i++ % 1000 === 0) echo ".";
    $db->execute("insert_contributor", array($id, $name["ic_id"], $name["version"], $cstring));
}

echo "\n    Updated $i names.";

echo "\n\nCopying MaybeSames into the maybe_same table.\n";

$query = "select * from maybe_same;";

$result = $db->query($query, array());

$lookup = array();

while ($res = $db->fetchRow($result)) {
    if (!isset($lookup[$res["ic_id1"]]))
        $lookup[$res["ic_id1"]] = array();

    $lookup[$res["ic_id1"]][$res["ic_id2"]] = true;
}


$query = "select * from related_identity where arcrole=28270;";

$result = $db->query($query, array());

$matching = 0;
$inverse = 0;
$nonmatch = 0;

$updateQ = "insert into maybe_same (ic_id1, ic_id2, status, note) values ($1, $2, $3, 'User-suggested merge candidate');";
$db->prepare("insert_maybesame", $updateQ);


while ($res = $db->fetchRow($result)) {
    if (isset($lookup[$res["ic_id"]]) && isset($lookup[$res["ic_id"]][$res["related_id"]])) {
        // The relation is actually in the table 
        $matching++;
    } else if (isset($lookup[$res["related_id"]]) && isset($lookup[$res["related_id"]][$res["ic_id"]])) {
        echo "   The inverse was in but the original not: {$res["ic_id"]} - {$res["related_id"]}\n";
        $inverse++;
    } else {
        // missing completely
        echo "   Missing Completely: {$res["ic_id"]} - {$res["related_id"]}\n";
        $nonmatch++;
        $db->execute("insert_maybesame", array($res["ic_id"], $res["related_id"], $res["arcrole"]));
    }
}

echo "    Matching:\t $matching\n    Inverse:\t $inverse\n    NonMatch:\t $nonmatch\n\n";


$handle = $db->getHandle();

echo "DB Query: Create Lookup Table\n\n";
$query = "
-- Table for the constellation id mapping (DAG) for getting the correct constellation if an
-- given an outdated ARK/ID that has been merged or split
create table constellation_lookup (
        ic_id           int,                        -- The main ICID (to query)
        ark_id          text,                       -- The original ARK (to query)
        current_ic_id   int,                        -- The current ICID for this constellation
        current_ark_id  text,                       -- The current ARK for this constellation
        modified        timestamp default now(),    -- The time this mapping was updated
        note            text                        -- Any notes that may be useful
);
-- Forward looking index (unique)
create index constellation_lookup_idx1 on constellation_lookup(ic_id, ark_id);
-- Backward looking index (non-unique)
create index constellation_lookup_idx2 on constellation_lookup(current_ic_id, current_ark_id);
";
$result = \pg_query($handle, $query);

echo "DB Query: Fill lookup table\n\n";
$query = "
-- Prefill
insert into constellation_lookup (ic_id, ark_id) select distinct ic_id, ark_id from nrd where ark_id is not null;
update constellation_lookup set current_ic_id = ic_id, current_ark_id = ark_id;
";
$result = \pg_query($handle, $query);



echo "DB Query: Version History Updates\n\n";
$query = "
-- Version history updates for send-to reviewer
alter table version_history add column user_id_secondary int;
create index version_history_idx5 on version_history(user_id_secondary);
";
$result = \pg_query($handle, $query);


echo "DB Query: Messaging\n\n";
$query = "
-- Messaging
CREATE SEQUENCE \"message_id_seq\";
create table messages (
        id                      int primary key default nextval('message_id_seq'),
        to_user                 int,
        from_user               int,
        from_string             text,
        subject                 text,
        body                    text,
        attachment_content      text,
        attachment_filename     text,
        read                    boolean default 'f',
        deleted                 boolean default 'f',
        time_sent               timestamp default NOW()
);
create index messages_idx1 on messages (to_user, subject, read);
create index messages_idx2 on messages (from_user, subject, read);
create index messages_idx3 on messages (from_string);
create index messages_idx4 on messages (to_user, from_user, from_string);
";
$result = \pg_query($handle, $query);


echo "DB Query: Merge Permissions\n\n";
$query = "
-- Merge permissions
insert into privilege (label, description) values ('Merge', 'Merge Constellations') returning id;
insert into privilege_role_link (rid, pid)
select (select id from role where label='System Administrator'), id from privilege where 
    label in ('Merge');
";
$result = \pg_query($handle, $query);



echo "DB Query: Delete MaybeSameAs Relations\n\n";
$query = "
delete from related_identity where arcrole=28270;
";
$result = \pg_query($handle, $query);



echo "DB Query: Delete Name Contributors without rules\n\n";
$query = "
delete from name_contributor where rule is null; 
";
$result = \pg_query($handle, $query);


echo "DB Query: Change vocabulary type for maybeSameAs Relation type\n\n";
$query = "
update vocabulary set type = 'maybesame_type' where id = 28270;
";
$result = \pg_query($handle, $query);

