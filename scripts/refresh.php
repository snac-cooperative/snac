<?php
/**
 * Refresh Script 
 *
 * This script is used to refresh the database and indexes using a static postgres
 * output.
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2018 the Rector and Visitors of the University of Virginia, and
 * the Regents of the University of California
 */

// Move to the directory containing this script
chdir(dirname(__FILE__));

// Autoload the SNAC server codebase
include("../vendor/autoload.php");

printf("basename: %s dirname: %s\n", basename(__FILE__), dirname(__FILE__));

use \snac\Config as Config;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;

// Set up the global log stream
$log = new StreamHandler(\snac\Config::$LOG_DIR . \snac\Config::$SERVER_LOGFILE, Logger::DEBUG);

$automate = false;

if ($argc == 2 && $argv[1] == "automate")
    $automate = true;

// Read the configuration file
echo "Time: " . date("Y-m-d H:i:s") . "\n"; 
echo "Reading the configuration file in src/snac/Config.php.\n";

$host = Config::$DATABASE["host"];
$port = Config::$DATABASE["port"];
$database = Config::$DATABASE["database"];
$password = Config::$DATABASE["password"];
$user = Config::$DATABASE["user"];

// Try to create the database

echo "Time: " . date("Y-m-d H:i:s") . "\n"; 
echo "Would you like to try creating the PostgreSQL database?\n  ('yes' or 'no'): ";
$response = "no";
if (!$automate)
    $response = trim(fgets(STDIN));
else
    echo "no\n";

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
echo "Time: " . date("Y-m-d H:i:s") . "\n"; 
echo "Attempting to make a database connection.\n\n";

$dbHandle = pg_connect("host=$host port=$port dbname=$database user=$user password=$password");
// If the connection does not throw an exception, but the connector is false, then throw.
if ($dbHandle === false) {
    die("ERR: Unable to connect to database.\n");
}


$siteoffline = false;
echo "Time: " . date("Y-m-d H:i:s") . "\n"; 
echo "Would you like to take the site offline during this process?\n ('yes' or 'no'): "; 
$response = "yes";
if (!$automate)
    $response = trim(fgets(STDIN));
else
    echo "yes\n";

if ($response == "yes") {
    $retval = 0;
    echo "  Attempting to update the Config.php file.\n\n";
    system("cd ../ && sed -i 's/SITE_OFFLINE = false/SITE_OFFLINE = true/g' src/snac/Config.php\n", $retval);
    
    if ($retval != 0) {
        echo "  There was a problem taking the site offline.\n\n";
    }
    // We took the site offline during the process
    $siteoffline = true;
} else {
    echo "  Not taking the site offline.\n\n";
}


echo "Time: " . date("Y-m-d H:i:s") . "\n"; 
echo "Would you like to load the database copy?\n  ('yes' or 'no'): ";
$response = "yes";
if (!$automate)
    $response = trim(fgets(STDIN));
else
    echo "yes\n";

if ($response == "yes") {
    echo "  What is the path to the user file to import? [default: backup.sql]\n  :";
    $filename = null;
    if (!$automate)
        $filename = trim(fgets(STDIN));
    if ($filename == null || $filename == "")
        $filename = "backup.sql";
    echo "  Running the SQL initialization script\n";
    $res = pg_query($dbHandle, file_get_contents($filename));

    if (!$res) {
        $error = pg_last_error($dbHandle);
        echo "  ERR: Unable to run script due to the following error:\n";
        echo $error."\n";
        die();
    }
    echo "  Successfully loaded the database.\n";
} else {
    echo "  Not loading the database backup.\n\n";
} 

echo "Time: " . date("Y-m-d H:i:s") . "\n"; 
echo "Would you like to refresh the Elastic Search Indices?\n  ('yes' or 'no'): ";
$response = "yes";
if (!$automate)
    $response = trim(fgets(STDIN));
else
    echo "yes\n";

if ($response == "yes" && \snac\Config::$USE_ELASTIC_SEARCH) {
    echo "  Emptying the Elastic Search Indices\n";

    $eSearch = \Elasticsearch\ClientBuilder::create()
            ->setHosts([\snac\Config::$ELASTIC_SEARCH_URI])
            ->setRetries(0)
            ->build();

    if ($eSearch != null) {
        echo "   - connected to Elastic Search\n";
        try {
            $params = array("index" => \snac\Config::$ELASTIC_SEARCH_BASE_INDEX);
            $response = $eSearch->indices()->delete($params);
            echo "   - deleted search index\n";
        } catch (\Exception $e) {
            echo "   - could not delete search index. It did not exist.\n";
        }
        try {
            $params = array("index" => \snac\Config::$ELASTIC_SEARCH_RESOURCE_INDEX);
            $response = $eSearch->indices()->delete($params);
            echo "   - deleted resource search index\n";
        } catch (\Exception $e) {
            echo "   - could not delete resource search index. It did not exist.\n";
        }
        
        /* 
         * Run a system shell command, that sudos bash, then su's to postgres user,
         * then creates the user and database from the Config class.
         *
         * If you are looking for the string Parsing: that shows up in the output, you want ingest_sample.php,
         * which is the shell command being run below.
         */
        system("php rebuild_elastic.php nowiki\n", $retval);
        if ($retval != 0) {
            echo "   - here was a problem rebuilding the elastic search index.\n\n";
        }

        system("php rebuild_resource_elastic.php\n", $retval);
        if ($retval != 0) {
            echo "   - here was a problem rebuilding the elastic search resource index.\n\n";
        }

        echo "  Successfully refreshed the Elastic Search Indices.\n\n";
    } else {
        echo "  ERR: Unable to connect or delete Elastic Search index.\n";
    }
} else {
    echo "  Not refreshing the Elastic Search Indices.\n\n";
}


if ($siteoffline) {
    $retval = 0;
    echo "Attempting to bring the site back online.\n";
    system("cd ../ && sed -i 's/SITE_OFFLINE = true/SITE_OFFLINE = false/g' src/snac/Config.php\n", $retval);
    
    if ($retval != 0) {
        echo "  There was a problem bringing the site back online.\n\n";
    }
}
