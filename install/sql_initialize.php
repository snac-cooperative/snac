<?php
/**
 * SQL Initialization Script
 *
 * This script should be run from the main install.sh shell script
 * to initialize the SQL database.
 *
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 * the Regents of the University of California
 */

include("../src/snac/Config.php");
use \snac\Config as Config;

// Read the configuration file
echo "Reading the configuration file.\n";

$host = Config::$DATABASE["host"];
$port = Config::$DATABASE["port"];
$database = Config::$DATABASE["database"];
$password = Config::$DATABASE["password"];
$user = Config::$DATABASE["user"];

// Try to create the database
echo "Creating the database.  This script assumes a switch user\n".
     "to the postgres user is appropriate permissions.  You may\n".
     "need to enter your password for SUDO privileges next:\n";

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
\"\n");


// Try to connect to the database
echo "Connecting to the new database as the newly-created user\n";

$dbHandle = pg_connect("host=$host port=$port dbname=$database user=$user password=$password");
// If the connection does not throw an exception, but the connector is false, then throw.
if ($dbHandle === false) {
    die("ERR: Unable to connect to database.\n");
}

echo "Running the SQL initialization script\n";
$res = pg_query($dbHandle, file_get_contents("schema.sql"));

if (!$res) {
    $error = pg_last_error($dbHandle);
    echo "ERR: Unable to run script due to the following error:\n";
    echo $error."\n";
    die();
}

echo "Adding system-level role to the database\n";
$res = pg_query($dbHandle, file_get_contents("initialize_users.sql"));

if (!$res) {
    $error = pg_last_error($dbHandle);
    echo "ERR: Unable to run script due to the following error:\n";
    echo $error."\n";
    die();
}

echo "Successfully initialized database '$database'\n";


