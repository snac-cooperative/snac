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
     * @var string URL to redirect after Login
     */
    public static $REDIRECT_AFTER_LOGIN_URL = 'https://snac-server.ddev.site/dashboard';

    /**
     * @var string URL to redirect after Logout
     */
    public static $REDIRECT_AFTER_LOGOUT_URL = 'https://snac-server.ddev.site';

    /**
     * @var string URL for SNAC Laravel login endpoint
     */
    public static $LARAVEL_LOGIN_URL = 'https://snac-laravel.ddev.site/login/snac';

    /**
     * @var string URL for SNAC Laravel logout endpoint
     */
    public static $LARAVEL_LOGOUT_URL = 'https://snac-laravel.ddev.site/logoff';

    /**
     * @var string URL for SNAC Laravel redirect after login
     */
    public static $LARAVEL_REDIRECT_AFTER_LOGIN_URL = 'https://snac-laravel.ddev.site';

    /**
     * @var string URL for SNAC Laravel redirect after logout
     */
    public static $LARAVEL_REDIRECT_AFTER_LOGOUT_URL = 'https://snac-laravel.ddev.site/logoff';

    /**
     * @var boolean Whether or not to have the server attempt to use Laravel Authentication
     */
    public static $USE_LARAVEL_AUTHENTICATION = true;

    /**
     * @var string SNAC Laravel base url
     */
    public static $LARAVEL_URL = 'https://snac-laravel.ddev.site';

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
     * @var boolean Whether or not to include development features. Include development feautures (true) or hide development features (false)
     */
    public static $INCLUDE_DEVELOPMENT_FEATURES = false;

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
    public static $WEBUI_URL = "https://www.snac-server.ddev.site";

    /**
     * @var string URL of the rest server
     */
    public static $REST_URL = "https://rest.snac-server.ddev.site";

    /**
     *
     * @var string URL of the openrefine endpoint
     */
    public static $OPENREFINE_URL = "https://openrefine.snac-server.ddev.site/";

    /**
     *
     * @var string Name of the openrefine reconciliation service
     */
    public static $OPENREFINE_ENDPOINT_NAME = "Development SNAC Reconciliation for OpenRefine";

    /**
     *
     * @var string URL of the back-end server
     */
    public static $INTERNAL_SERVERURL = "https://internal.snac-server.ddev.site";

    /**
     * Full database connection information
     *
     * Connection information for the POSTGRES database
     *
     * @var array database connection information
     */
    public static $DATABASE = array (
        "database" => "db",
        "host" => "db",
        "port" => 5432,
        "user" => "db",
        "password" => "db"
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
    public static $REST_COMMAND_FILE = "/var/www/html/src/snac/client/rest/commands.json";

    /**
     * @var string Location of the cpf.rng RELAX NG files, and probably other stuff as well.
     *
     * The relative path is probably: vendor/npm-asset/eac-validator/rng
     */
    public static $RNG_DIR = "/var/www/html/src/snac/util";

    /**
     * @var string Location of the EAC-CFP XML Serializer template directory. This is the same directory as
     * the serializer class.
     */
    public static $CPF_TEMPLATE_DIR = "/var/www/html/src/snac/util";

    /**
     * @var string Location of the template directory
     */
    public static $TEMPLATE_LANGUAGE_DIR = "/var/www/html/src/snac/client/webui/template/languages";

    /**
     * @var string Location of the template directory
     */
    public static $TEMPLATE_DIR = "/var/www/html/src/snac/client/webui/templates";

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
    public static $ELASTIC_SEARCH_URI = "http://elasticsearch:9200";

    /**
     * @var string Main index for the search functionality of snac
     */
    public static $ELASTIC_SEARCH_BASE_INDEX = "local";

    /**
     * @var string All index for the all name (and alternate) search functionality of snac
     */
    public static $ELASTIC_SEARCH_ALL_INDEX = "local_all";

    /**
     * @var string Resource index for the resource search functionality of snac
     */
    public static $ELASTIC_SEARCH_RESOURCE_INDEX = "local_resources";

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
    public static $NEO4J_BOLT_URI = "bolt://neo4j:7687";


    /**
     * @var string Google Analytics Tracking ID (of the form UA-xxxxxxxx-x)
     */
    public static $GOOGLE_ANALYTICS_TRACKING_ID = null;

    /**
     * @var string Location of the email template directory
     */
    public static $EMAIL_TEMPLATE_DIR = "/var/www/html/src/snac/server/mailer/templates";

    /**
     * @var boolean Whether or not to use SMTP to send emails
     */
    public static $EMAIL_SMTP = true;

    /**
     * @var string[] Email configuration.  If not using SMTP, only from_email and from_name need to be set
     */
    public static $EMAIL_CONFIG = array (
        "host" => "localhost",
        "smtp_auth" => false,
        "username" => null,
        "password" => null,
        "security" => "tls",
        "port" => 1025,
        "from_email" => "",
        "from_name" => "SNAC Web"
    );

    /**
     * @var string Static content directory
     */
    public static $STATIC_FILE_DIR = "/var/www/html/snac/src/virtualhosts/www/static/";

    /**
     * @var string[] Usernames (or emails) to send feedback messages
     */
    public static $FEEDBACK_RECIPIENTS = [];

    /**
     * @var boolean Whether or not to treat feedback recipients as email addresses (true) or snac usernames (false)
     */
    public static $FEEDBACK_EMAIL_ONLY = false;


    /**
     * @var string Temporary directory to parse EAD
     */
    public static $EAD_PARSETMP_DIR = "/tmp";

    /**
     * @var string Location of EAD XSLT and schema files
     */
    public static $EAD_PARSER_DIR = "/path/to/snac/vendor/snac/snac-ead-parser";

    /**
     * @var string Location of SAXON jar file
     */
    public static $SAXON_JARFILE = "/home/jrhott/snac/lib/saxon9he.jar";

    /**
     * @var string Maximum upload file size
     */
    public static $MAX_UPLOAD_SIZE = "500000000";
}
