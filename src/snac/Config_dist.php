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
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
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
     * @var string Session name to use for SNAC (cookie name)
     */
    public static $SESSION_NAME = 'SNACWebUI';

    /**
     * @var boolean Whether the system in OFFLINE (Maintenance) mode
     */
    public static $SITE_OFFLINE = false;

    /**
     * @var boolean Whether the system is in READONLY (Maintenance) mode.  This is a lighter lock than fully offline.
     */
    public static $READ_ONLY = false;

    /**
     * @var string The interface version: "development", "demo", or "production"
     */
    public static $INTERFACE_VERSION = "production";

    /**
     * @var boolean Whether the system is in DEBUG mode
     */
    public static $DEBUG_MODE = true;

    /**
     * @var boolean Whether or not the system is in SANDBOX mode (generate temporary/fake arks)
     */
    public static $SANDBOX_MODE = true;

    /**
     * @var the full path of this project (to the root of the repository)
     */
    public static $PROJECT_DIR = "";

    /**
     * @var string URL of the webui
     */
    public static $WEBUI_URL = "http://localhost";

    /**
     * @var string URL of the rest server
     */
    public static $REST_URL = "http://localhost:81";

    /**
     *
     * @var string URL of the openrefine endpoint
     */
    public static $OPENREFINE_URL = "http://localhost/openrefine/";

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
     * @var string directory to write the log files. Must be / terminated.
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
     *
     * @var string filename for the CRON log
     */
    public static $CRON_LOGFILE = "cron.log";

    /**
     * Whether or not the database is in testing mode. In testing mode,
     * database calls will only be logged and not committed to the database.
     *
     * @var boolean Database in testing mode.
     *
     */
    public static $DATABASE_TESTING = false;

    /**
     *  @var integer Default for offset in selectListByStatus() and selectEditList()
     */
    public static $SQL_OFFSET = 0;

    /**
     *  @var integer Default for limit selectListByStatus() and selectEditList()
     */
    public static $SQL_LIMIT = 42;

    /**
     * @var string Location of the REST commands file
     */
    public static $REST_COMMAND_FILE = "full/path/to/src/snac/client/rest/commands.json";

    /**
     * @var string Location of the cpf.rng RELAX NG files, and probably other stuff as well.
     *
     * The relative path is probably: vendor/npm-asset/eac-validator/rng
     */
    public static $RNG_DIR = "full/path/to/src/snac/util";

    /**
     * @var string Location of the EAC-CFP XML Serializer template directory. This is the same directory as
     * the serializer class.
     */
    public static $CPF_TEMPLATE_DIR = "full/path/to/src/snac/util";

    /**
     * @var string Location of the template directory
     */
    public static $TEMPLATE_LANGUAGE_DIR = "full/path/to/src/snac/client/webui/template/languages";

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

    /**
     * @var boolean Whether or not to have the server attempt to use Elastic
     *      Search indexing.
     */
    public static $USE_ELASTIC_SEARCH = true;

    /**
     * @var string Elastic Search URL
     */
    public static $ELASTIC_SEARCH_URI = "http://localhost:9200";

    /**
     * @var string Main index for the search functionality of snac
     */
    public static $ELASTIC_SEARCH_BASE_INDEX = "";

    /**
     * @var string Main type for the search functionality of snac
     */
    public static $ELASTIC_SEARCH_BASE_TYPE = "";

    /**
     * @var string All index for the all name (and alternate) search functionality of snac
     */
    public static $ELASTIC_SEARCH_ALL_INDEX = "";

    /**
     * @var string Search base for ALL of the snac name entries (and alternates)
     */
    public static $ELASTIC_SEARCH_ALL_TYPE = "";

    /**
     * @var string Resource index for the resource search functionality of snac
     */
    public static $ELASTIC_SEARCH_RESOURCE_INDEX = "";

    /**
     * @var string Resource type for the resource search functionality of snac
     */
    public static $ELASTIC_SEARCH_RESOURCE_TYPE = "";

    /**
     * @var integer Maximum number of pages to show on search result pages
     */
    public static $MAX_SEARCH_RESULT_PAGES = 15;

    /**
     * @var boolean Whether or not to have the server attempt to use Neo4J
     */
    public static $USE_NEO4J = true;

    /**
     * @var string Neo4J Bolt URL
     */
    public static $NEO4J_BOLT_URI = "bolt://user:password@localhost:7687";


    /**
     * @var string Google Analytics Tracking ID (of the form UA-xxxxxxxx-x)
     */
    public static $GOOGLE_ANALYTICS_TRACKING_ID = null;

    /**
     * @var string Location of the email template directory
     */
    public static $EMAIL_TEMPLATE_DIR = "/full/path/to/src/snac/server/mailer/templates";

    /**
     * @var boolean Whether or not to use SMTP to send emails
     */
    public static $EMAIL_SMTP = false;

    /**
     * @var string[] Email configuration.  If not using SMTP, only from_email and from_name need to be set
     */
    public static $EMAIL_CONFIG = array (
        "host" => "smtp.gmail.com",
        "smtp_auth" => true,
        "username" => "user@gmail.com",
        "password" => "password",
        "security" => "tls",
        "port" => 25, //587,
        "from_email" => "",
        "from_name" => "SNAC Web"
    );

    /**
     * @var string Static content directory
     */
    public static $STATIC_FILE_DIR = "/full/path/to/snac/src/virtualhosts/www/static/";

    /**
     * @var string[] Usernames (or emails) to send feedback messages
     */
    public static $FEEDBACK_RECIPIENTS = [];

    /**
     * @var boolean Whether or not to treat feedback recipients as email addresses (true) or snac usernames (false)
     */
    public static $FEEDBACK_EMAIL_ONLY = false;
}
