<?php

/**
 * Serialize a PHP Constellation object to EAC-CPF XML
 *
 * Contains the serializer for into PHP Identity Constellation objects to EAC-CPF files.
 *
 * License:
 *
 *
 * @author Tom Laudeman
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2016 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\util;

/**
 * Database utility object
 * 
 * @var \snac\server\database\DBUtil $dbu object
 */
$dbu = null;
/**
 * @var \Monolog\Logger $logger the logger for this server
 *
 * See enableLogging() in this file.
 */
$logger = null;


/**
 * EAC-CPF Serializer
 *
 * Create EAC-CPF xml from a PHP Constellation object. Constellation objects are read out of the SQL database,
 * so this completes the chain from SQL to XML.
 *
 * @author Tom Laudeman
 *        
 */
class EACCPFSerializer {

    /**
     * Enable logging
     *
     * Call this to enabled logging. For various reasons, logging is not enabled by default.
     *
     * Check that we don't have a logger before creating a new one. This can be called as often as one wants
     * with no problems.
     */
    private static function enableLogging()
    {
        global $log;
        global $logger;
        if (! $logger)
        {
            // create a log channel
            $logger = new \Monolog\Logger('EACCPFSerializer');
            $logger->pushHandler($log);
        }
    }

    /**
     * Wrap logging
     *
     * When logging is disabled, we don't want to call the logger because we don't want to generate errors. We
     * also don't want logs to just magically start up. Doing logging should be very intentional, especially
     * in a low level class like SQL. Call enableLogging() before calling logDebug().
     *
     * @param string $msg The logging messages
     *
     * @param string[] $debugArray An associative list of keys and values to send to the logger.
     */
    private static function logDebug($msg, $debugArray=array())
    {
        global $logger;
        if ($logger)
        {
            $logger->addDebug($msg, $debugArray);
        }
    }

    public function __construct() {
        
    }


    /**
     * Serialize an ARK to EAC-CPF XML
     *
     * Read a published ARK from the database and return EAC-CPF XML as a string. 
     *
     * @param string $ark An ARK to serialize from the database into CPF XML
     *
     * @return string EAC-CPF XML
     */ 
    public static function SerializeByARK($ark, $debug=false) {
        /*
         * There are multiple constellations with the test ARK, which is a problem to be solved, eventually.
         * The internals of readPublishedConstellationByARK() will only return a single record.
         */  
        global $dbu;
        if (! $dbu) {
            $dbu = new \snac\server\database\DBUtil();
        }
        $expCon = $dbu->readPublishedConstellationByARK($ark);
        return self::SerializeCore($expCon->toArray(), $debug);
    }

        
    /**
     * Export a constellation as EAC-CPF
     *
     * The input is a constellation in toArray() format, that is a PHP associative array. If you have a
     * Constellation object and want to export that, simply pass $yourConstellation->toArray() as param
     * $expCon.
     *
     * @param string[] $expCon Array of string that is the result of Constellation->toArray()
     * @return string $cpfXML a string containing an EAC-CPF XML file.
     */ 
    public static function SerializeCore($expCon) {
        $data['data'] = $expCon;
        $loader = new \Twig_Loader_Filesystem(\snac\Config::$CPF_TEMPLATE_DIR);
        $twig = new \Twig_Environment($loader, array());
        // Create a custom filter and connect it to the Twig filter via the environment
        $filter = new \Twig_SimpleFilter('decode_entities', function ($string) {
                return html_entity_decode($string);
            });
        $twig->addFilter($filter);        
        
        self::addEntityType($data['data']);
        $data['currentDate'] = date('c');

        $cpfXML = $twig->render("EAC-CPF_template.xml", $data);
        return $cpfXML;
    }

    /**
     * Look up related constellation entityType
     *
     * Get the summary constellation for each constellation relation in the $data which is a constellation in
     * array form, passed by reference, and we change it in place, adding a new key array 'targetEntityType'
     * with keys 'term' and 'uri'.
     *
     * @param string $data Constellation as array, pass by reference.
     */
    private static function addEntityType(&$data) {
        /*
         * Note foreach by reference, so we can change in place without using an index.
         */
        global $dbu;
        if (! $dbu) {
            $dbu = new \snac\server\database\DBUtil();
        }
        if ($data['relations']) {
            foreach($data['relations'] as &$cpfRel) {
                $relCon = $dbu->readPublishedConstellationByARK($cpfRel['targetArkID'], true);
                if ($relCon) {
                    /*
                     * The controlled vocabulary table has type, value (aka term) both of which are always
                     * populated. It also has uri which is populated for some vocabulary. (Other fields are as
                     * well as id, description, and entity_group). XML elements are limited to using the value,
                     * while XML attributes should use the uri (when available).
                     */
                    $cpfRel['targetEntityType']['term'] = $relCon->getEntityType()->getTerm();
                    $cpfRel['targetEntityType']['uri'] = $relCon->getEntityType()->getURI();
                }
                else {
                    /*
                     * In the production code this should be rare. Log a warning.
                     */ 
                    $cpfRel['targetEntityType']['term'] = '';
                    $cpfRel['targetEntityType']['uri'] = '';
                    $msg = sprintf("\nWarning: cannot get constellation for ARK: %s\n", $cpfRel['targetArkID']);
                    self::enableLogging();
                    self::logDebug($msg);
                }
            }
        }
    }
}