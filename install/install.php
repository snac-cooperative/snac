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
echo "Reading the configuration file in src/snac/Config.php.\n";

$host = Config::$DATABASE["host"];
$port = Config::$DATABASE["port"];
$database = Config::$DATABASE["database"];
$password = Config::$DATABASE["password"];
$user = Config::$DATABASE["user"];

// Try to create the database

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
echo "Attempting to make a database connection.\n\n";

$dbHandle = pg_connect("host=$host port=$port dbname=$database user=$user password=$password");
// If the connection does not throw an exception, but the connector is false, then throw.
if ($dbHandle === false) {
    die("ERR: Unable to connect to database.\n");
}


$siteoffline = false;
echo "Would you like to take the site offline during this process?\n ('yes' or 'no'): "; 
$response = "yes";
if (!$automate)
    $response = trim(fgets(STDIN));
else
    echo "yes\n";

if ($response == "yes") {
    $retval = 0;
    echo "  Attempting to update the Config.php file.\n";
    system("cd ../ && sed -i 's/SITE_OFFLINE = false/SITE_OFFLINE = true/g' src/snac/Config.php\n", $retval);
    
    if ($retval != 0) {
        echo "  There was a problem taking the site offline.\n\n";
    }
    // We took the site offline during the process
    $siteoffline = true;
} else {
    echo "  Not taking the site offline.\n\n";
}


echo "Would you like to load the schema (tables, indicies) into the database?\n  ('yes' or 'no'): ";
$response = "yes";
if (!$automate)
    $response = trim(fgets(STDIN));
else
    echo "yes\n";

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
$response = "yes";
if (!$automate)
    $response = trim(fgets(STDIN));
else
    echo "yes\n";

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
$response = "yes";
if (!$automate)
    $response = trim(fgets(STDIN));
else
    echo "yes\n";

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
$response = "yes";
if (!$automate)
    $response = trim(fgets(STDIN));
else
    echo "yes\n";

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
$response = "yes";
if (!$automate)
    $response = trim(fgets(STDIN));
else
    echo "yes\n";

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
$response = "yes";
if (!$automate)
    $response = trim(fgets(STDIN));
else
    echo "yes\n";

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
$response = "yes";
if (!$automate)
    $response = trim(fgets(STDIN));
else
    echo "yes\n";

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
$response = "yes";
if (!$automate)
    $response = trim(fgets(STDIN));
else
    echo "yes\n";

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

echo "Would you like to empty the Elastic Search Indices?\n  ('yes' or 'no'): ";
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
        echo "  Successfully emptied the Elastic Search Indices.\n\n";
    } else {
        echo "  ERR: Unable to connect or delete Elastic Search index.\n";
    }
} else {
    echo "  Not emptying the Elastic Search Indices.\n\n";
}


echo "Would you like to load a small sampling of records (100) into the database?\n ('yes' or 'no'): "; 
$response = "yes";
if (!$automate)
    $response = trim(fgets(STDIN));
else
    echo "yes\n";

if ($response == "yes") {
    echo "  What is the full path to the SNAC merged CPF? [default: /data/merge/]\n  :";
    $dir = null;
    if (!$automate)
        $dir = trim(fgets(STDIN));
    if ($dir == null || $dir == "")
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


echo "Would you like to load the May 2016 sample set of records into the database?\n";
echo "This will take a SIGNIFICANT amount of time!\n ('yes' or 'no'): "; 
$response = "yes";
if (!$automate)
    $response = trim(fgets(STDIN));
else
    echo "yes\n";

if ($response == "yes") {
    echo "  What is the full path to the SNAC merged CPF? [default: /data/merge/]\n  :";
    $dir = null;
    if (!$automate)
        $dir = trim(fgets(STDIN));
    if ($dir == null || $dir == "")
        $dir = "/data/merge/";
    $retval = 0;
    echo "  Attempting to ingest May 2016 sample records from $dir.\n";
    system("cd ../scripts && ./ingest_list.php $dir ../install/setup_files/may2016-list.txt\n", $retval);
    
    if ($retval != 0) {
        echo "  There was a problem ingesting the May 2016 sample records.\n\n";
    }
} else {
    echo "  Not ingesting May 2016 sample records.\n\n";
}

echo "Would you like to load the set of institution records into the database?\n";
echo "These include most instutitions participating in the SNAC cooperative, and are needed for SNAC Users\n  ('yes' or 'no'): "; 
$response = "yes";
if (!$automate)
    $response = trim(fgets(STDIN));
else
    echo "yes\n";

if ($response == "yes") {
    echo "  What is the full path to the SNAC merged CPF? [default: /data/merge/]\n  :";
    $dir = null;
    if (!$automate)
        $dir = trim(fgets(STDIN));
    if ($dir == null || $dir == "")
        $dir = "/data/merge/";
    $retval = 0;
    echo "  Attempting to ingest institution records from $dir.\n";
    system("cd ../scripts && ./ingest_institutions.php $dir ../install/setup_files/institutions.csv\n", $retval);
    
    if ($retval != 0) {
        echo "  There was a problem ingesting the institution records.\n\n";
    }
} else {
    echo "  Not ingesting institution records.\n\n";
}

echo "Would you like to load a set of users into the database?\n See setup_files/users_dist.csv for the file format\n ('yes' or 'no'): "; 
$response = "yes";
if (!$automate)
    $response = trim(fgets(STDIN));
else
    echo "yes\n";

if ($response == "yes") {
    echo "  What is the path to the user file to import? [default: setup_files/users.csv]\n  :";
    $filename = null;
    if (!$automate)
        $filename = trim(fgets(STDIN));
    if ($filename == null || $filename == "")
        $filename = "setup_files/users.csv";
    $retval = 0;
    echo "  Attempting to read and import user accounts from $filename.\n";
    system("cd ../scripts/add_users.php $filename setup_files/institutions.csv\n", $retval);
    
    if ($retval != 0) {
        echo "  There was a problem importing the users.\n\n";
    }
} else {
    echo "  Not importing user accounts.\n\n";
}

if ($siteoffline) {
    $retval = 0;
    echo "Attempting to bring the site back online.\n";
    system("cd ../ && sed -i 's/SITE_OFFLINE = true/SITE_OFFLINE = false/g' src/snac/Config.php\n", $retval);
    
    if ($retval != 0) {
        echo "  There was a problem bringing the site back online.\n\n";
    }
}
