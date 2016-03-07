<?php

/**
 * Configuration File
 *
 * Contains the configuration options for this instance of the server
 *
 * License:
 *
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac;

/**
 * Configuration class
 *
 * This class contains all the configuration variables for the entire system. It makes use of only
 * public static fields that may be read by any other class in the system. We use this to better scope
 * the configuration settings and avoid global variables and constants.
 *
 * @author Robbie Hott
 *
 */
class Config {


    /**
     * @var boolean Whether the system is in DEBUG mode
     */
    public static $DEBUG_MODE = true;

    /**
     * @var the full path of this project (to the root of the repository)
     */
    public static $PROJECT_DIR = "";

    /**
     *
     * @var string URL of the back-end server
     */
    public static $INTERNAL_SERVERURL = "http://localhost:82";

    /**
     * Full database connection information
     *
     * Connection information for the POSTGRES database
     *
     * @var array database connection information
     */
    public static $DATABASE = array (
        "database" => "db_name",
        "host" => "hostname.com",
        "port" => 5432,
        "user" => "user_id",
        "password" => "full_password"
    );

    /**
     *
     * @var string directory to write the log files
     */
    public static $LOG_DIR = "";
    
    /**
     *
     * @var string filename for the Server log
     */
    public static $SERVER_LOGFILE = "server.log";
    
    /**
     *
     * @var string filename for the WebUI log
     */
    public static $WEBUI_LOGFILE = "webui.log";
    
    /**
     *
     * @var string filename for the REST log
     */
    public static $REST_LOGFILE = "rest.log";
    
    /**
     *
     * @var string filename for the UnitTesting log
     */
    public static $UNITTEST_LOGFILE = "unit_test.log";
    
    /**
     * Whether or not the database is in testing mode. In testing mode,
     * database calls will only be logged and not committed to the database.
     *
     * @var boolean Database in testing mode.
     *
     */
    public static $DATABASE_TESTING = false;

    /**
     * @var string Location of the template directory
     */
    public static $TEMPLATE_DIR = "full/path/to/src/snac/client/webui/templates";

    /**
     * @var string Location of the template cache directory
     */
    public static $TEMPLATE_CACHE = "/tmp/";

    /**
     * @var string[][] OAuth connection information.  Each entry should have all the
     *      required information to connect to that provider.
     */
    public static $OAUTH_CONNECTION = array (
        "google" => array(
            "client_id" => 'XXXXXXXXX',
            "client_secret" => 'XXXXXXXX',
            "redirect_uri" => 'XXXXXXX',
        )
    );

    /**
     * @var int The maximum number of objects allowed in a list of objects.
     */
    public static $MAX_LIST_SIZE = 50000;
}
