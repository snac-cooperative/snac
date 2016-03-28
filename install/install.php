<?php
/**
 * Full Install Script 
 *
 * This script walks the user through fully installing SNAC on a server,
 * including creating the database, pre-populating vocabulary and users.
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 * the Regents of the University of California
 */

chdir(dirname(__FILE__));
include("../vendor/autoload.php");

printf("basename: %s dirname: %s\n", basename(__FILE__), dirname(__FILE__));

// include("src/snac/Config.php");

use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;

// Set up the global log stream
$log = new StreamHandler(\snac\Config::$LOG_DIR . \snac\Config::$SERVER_LOGFILE, Logger::DEBUG);

// Path is relative to cwd when you launch the script, not the path of the script (this file).

// The line below only works if you chdir() to the script's directory.

use \snac\Config as Config;

// Read the configuration file
echo "Reading the configuration file in src/snac/Config.php.\n";

$host = Config::$DATABASE["host"];
$port = Config::$DATABASE["port"];
$database = Config::$DATABASE["database"];
$password = Config::$DATABASE["password"];
$user = Config::$DATABASE["user"];

// Try to create the database

echo "Would you like to try creating the PostgreSQL database?\n  ('yes' or 'no'): ";
$response = trim(fgets(STDIN));
if ($response == "yes") {
    echo "  Trying to create the database.  This script requires SUDO\n".
        "  privileges to switch to the postgres user to create the \n".
        "  database.  Enter the password for SUDO next:\n";
    
    $retval = 0;
    // Run a system shell command, that sudos bash, then su's to postgres user,
    // then creates the user and database from the Config class.
    system("
sudo bash -c \"
su postgres -c '
createuser -D -l -R -P $user <<EOF
$password
$password
EOF
psql <<EOF
create database $database;
grant create,connect on database $database to $user;
EOF'
    \"\n", $retval);
    
    if ($retval != 0) {
        echo "  There was a problem creating the database.  Use the\n".
            "  following commands to create the database:\n\n".
            "  As the postgres user, in a shell:\n".
            "    postgres@server$ createuser -D -l -R -P $user\n\n".
            "  In the postgres shell as the root pgsql user:\n".
            "    psql> create database $database;\n".
            "    psql> grant create,connect on database $database to $user;\n\n";
    }
} else {
    echo "  Not creating the database.\n\n";
}


// Try to connect to the database
echo "Attempting to make a database connection.\n\n";

$dbHandle = pg_connect("host=$host port=$port dbname=$database user=$user password=$password");
// If the connection does not throw an exception, but the connector is false, then throw.
if ($dbHandle === false) {
    die("ERR: Unable to connect to database.\n");
}

echo "Would you like to load the schema (tables, indicies) into the database?\n  ('yes' or 'no'): ";
$response = trim(fgets(STDIN));
if ($response == "yes") {
    echo "  Running the SQL initialization script\n";
    $res = pg_query($dbHandle, file_get_contents("sql_files/schema.sql"));

    if (!$res) {
        $error = pg_last_error($dbHandle);
        echo "  ERR: Unable to run script due to the following error:\n";
        echo $error."\n";
        die();
    }
    echo "  Successfully loaded the schema.\n\n";
} else {
    echo "  Not loading the schema. The schema can be found in sql_files/schema.sql.\n\n";
} 

echo "Would you like to load the vocabulary schema (and drop the existing tables)?\n  ('yes' or 'no'): ";
$response = trim(fgets(STDIN));
if ($response == "yes") {
    echo "  Dropping vocabulary, if exists, and creating new tables\n";
    $res = pg_query($dbHandle, file_get_contents("sql_files/vocabulary_init.sql"));

    if (!$res) {
        $error = pg_last_error($dbHandle);
        echo "  ERR: Unable to run script due to the following error:\n";
        echo $error."\n";
        die();
    }
    echo "  Successfully refreshed the vocabulary schema.  You must load the data into these tables later.\n\n";
} else {
    echo "  Not updating the vocabulary schema.  The schema can be found in sql_files/vocabulary_init.sql.\n\n";
} 

echo "Would you like to load the place vocabulary schema (and drop the existing tables)?\n  ('yes' or 'no'): ";
$response = trim(fgets(STDIN));
if ($response == "yes") {
    echo "  Dropping place vocabulary, if exists, and creating new tables\n";
    $res = pg_query($dbHandle, file_get_contents("sql_files/places_vocabulary_init.sql"));

    if (!$res) {
        $error = pg_last_error($dbHandle);
        echo "  ERR: Unable to run script due to the following error:\n";
        echo $error."\n";
        die();
    }
    echo "  Successfully refreshed the place vocabulary schema.  You must load the data into these tables later.\n\n";
} else {
    echo "  Not updating the place vocabulary schema.  The schema can be found in sql_files/places_vocabulary_init.sql.\n\n";
} 



echo "Would you like to load the initial users into the database?\n  ('yes' or 'no'): ";
$response = trim(fgets(STDIN));
if ($response == "yes") {
    echo "  Adding system-level role to the database\n";
    $res = pg_query($dbHandle, file_get_contents("sql_files/initialize_users.sql"));

    if (!$res) {
        $error = pg_last_error($dbHandle);
        echo "  ERR: Unable to run script due to the following error:\n";
        echo $error."\n";
        die();
    }

    echo "  Successfully loaded the users.\n\n";
} else {
    echo "  Not loading the users. They can be found in sql_files/initialize_users.sql.\n\n";
}

echo "Would you like to load the language codes into the database?\n  ('yes' or 'no'): ";
$response = trim(fgets(STDIN));
if ($response == "yes") {
    echo "  Adding language codes to the database\n";
    $res = pg_query($dbHandle, file_get_contents("sql_files/languages.sql"));

    if (!$res) {
        $error = pg_last_error($dbHandle);
        echo "  ERR: Unable to run script due to the following error:\n";
        echo $error."\n";
        die();
    }

    echo "  Successfully loaded the language codes.\n\n";
} else {
    echo "  Not loading the language codes. They can be found in sql_files/languages.sql.\n\n";
}

echo "Would you like to load the script codes into the database?\n  ('yes' or 'no'): ";
$response = trim(fgets(STDIN));
if ($response == "yes") {
    echo "  Adding script codes to the database\n";
    $res = pg_query($dbHandle, file_get_contents("sql_files/scripts.sql"));

    if (!$res) {
        $error = pg_last_error($dbHandle);
        echo "  ERR: Unable to run script due to the following error:\n";
        echo $error."\n";
        die();
    }

    echo "  Successfully loaded the script codes.\n\n";
} else {
    echo "  Not loading the script codes. They can be found in sql_files/scripts.sql.\n\n";
}



echo "Would you like to load the controlled vocabulary into the database?\n  ('yes' or 'no'): ";
$response = trim(fgets(STDIN));
if ($response == "yes") {
    echo "  Adding controlled vocabulary to the database\n";
    $res = pg_query($dbHandle, file_get_contents("sql_files/vocabulary.sql"));

    if (!$res) {
        $error = pg_last_error($dbHandle);
        echo "  ERR: Unable to run script due to the following error:\n";
        echo $error."\n";
        die();
    }

    echo "  Successfully loaded the controlled vocabulary.\n\n";
} else {
    echo "  Not loading the controlled vocabulary. They can be found in sql_files/vocabulary.sql.\n\n";
}


echo "Would you like to load the controlled place vocabulary into the database?\n  ('yes' or 'no'): ";
$response = trim(fgets(STDIN));
if ($response == "yes") {
    echo "  Adding controlled place vocabulary to the database\n";
    $res = pg_query($dbHandle, file_get_contents("sql_files/places_vocabulary.sql"));

    if (!$res) {
        $error = pg_last_error($dbHandle);
        echo "  ERR: Unable to run script due to the following error:\n";
        echo $error."\n";
        die();
    }

    echo "  Successfully loaded the controlled place vocabulary.\n\n";
} else {
    echo "  Not loading the controlled place vocabulary. They can be found in sql_files/places_vocabulary.sql.\n\n";
}



echo "Would you like to load a sampling of records into the database?\n ('yes' or 'no'): "; 
$response = trim(fgets(STDIN));
if ($response == "yes") {
    echo "  What is the full path to the SNAC merged CPF? [default: /data/merge/]\n  :";
    $dir = trim(fgets(STDIN));
    if ($dir == null || $dir = "")
        $dir = "/data/merge/";
    $retval = 0;
    echo "  Attempting to ingest sample records from $dir.\n";
    // Run a system shell command, that sudos bash, then su's to postgres user,
    // then creates the user and database from the Config class.
    system("cd ../scripts && ./ingest_sample.php $dir\n", $retval);
    
    if ($retval != 0) {
        echo "  There was a problem ingesting the sample records.\n\n";
    }
} else {
    echo "  Not ingesting sample records.\n\n";
}


