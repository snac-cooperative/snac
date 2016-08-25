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
 * @var \snac\server\database\DBUtil $dbu object A DBUtil object used internally to read information on
 * related constellations, necessary for the CPF XML.
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
 * Serialize (export) SNAC Constellation to EAC-CPF XML. Create EAC-CPF xml, either by reading a published ARK
 * from the database, or by extracting the toArray() form of a PHP Constellation.
 *
 * These are static classes so there's no need to instantiate this class. Simply call one of the functions:
 *
 * $cpfXML = \snac\util\EACCPFSerializer::SerializeByARK('http://n2t.net/ark:/99166/w6xd18cz');
 * $cpfXML = \snac\util\EACCPFSerializer::SerializeCore($yourConstellation->toArray())
 * $cpfXML = \snac\util\EACCPFSerializer::SerializeCore($serverResponse['constellation']);
 *
 * @author Tom Laudeman
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

    /**
     * Serialize an ARK to EAC-CPF XML
     *
     * Read a published ARK from the database and return EAC-CPF XML as a string. 
     *
     * @param string $ark An ARK to serialize from the database into CPF XML
     *
     * @return string EAC-CPF XML
     */ 
    public static function SerializeByARK($ark) {
        /*
         * There are multiple constellations with the test ARK, which is a problem to be solved, eventually.
         * The internals of readPublishedConstellationByARK() will only return a single record.
         */  
        global $dbu;
        if (! $dbu) {
            $dbu = new \snac\server\database\DBUtil();
        }
        $expCon = $dbu->readPublishedConstellationByARK($ark);
        return self::SerializeCore($expCon->toArray());
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
        global $dbu;
        if (! $dbu) {
            $dbu = new \snac\server\database\DBUtil();
        }

        $data['data'] = $expCon;
        $loader = new \Twig_Loader_Filesystem(\snac\Config::$CPF_TEMPLATE_DIR);
        $twig = new \Twig_Environment($loader, array());

        /*
         * Create a custom filter and connect it to the Twig filter via the environment. Yes, the second arg
         * is an inline anonymous function. Looks like a closure.
         */
        $filter = new \Twig_SimpleFilter('decode_entities', function ($string) {
                return html_entity_decode($string);
            });
        $twig->addFilter($filter);        
        
        self::cpfSameAs($data['data']);
        self::addEntityType($data['data']);
        /*
         * Leave off the minutes and microseconds. Not necessary, and it complicates testing. For testing we
         * want to extract by two methods and compare. Seconds and microseconds will be different which
         * complicates the testing.
         *
         * $data['currentDate'] = date('c');
         */ 
        $data['currentDate'] = date('Y-m-d');
        $data['versionHistory'] = array();
        if (array_key_exists('id', $expCon)) {
            $dbu->readVersionHistory($expCon['id']);
        }

        /* 
         * $cfile = fopen('cpf_data.txt', 'w');
         * fwrite($cfile, var_export($data, 1));
         * fclose($cfile);
         */

        $cpfXML = $twig->render("EAC-CPF_template.xml", $data);

        /* 
         * $cfile = fopen('cpf_out.xml', 'w');
         * fwrite($cfile, $cpfXML);
         * fclose($cfile);
         */

        return $cpfXML;
    }

        /* 
         * {
         *     "dataType": "SameAs",
         *     "type": {
         *         "id": "28222",
         *         "term": "sameAs",
         *         "uri": "http:\/\/socialarchive.iath.virginia.edu\/control\/term#sameAs"
         *     },
         *     "text": "George Washington university",
         *     "uri": "http:\/\/viaf.org\/viaf\/142703516",
         *     "id": "82076",
         *     "version": "364"
         *
         *     "dataType": "ConstellationRelation",
         *     "sourceConstellation": "82030",
         *     "sourceArkID": "http:\/\/n2t.net\/ark:\/99166\/w6fr4rx5",
         *     "targetArkID": "http:\/\/n2t.net\/ark:\/99166\/w6nc6tpp",
         *     "type": {
         *         "id": "28231",
         *         "term": "associatedWith",
         *         "uri": "http:\/\/socialarchive.iath.virginia.edu\/control\/term#associatedWith"
         *     },
         *     "content": "Lovell, Malcolm Read, Jr., 1921-",
         *     "id": "82079",
         *     "version": "364"
         *
         * <cpfRelation 
         * xlink:arcrole="http://socialarchive.iath.virginia.edu/control/term#sameAs"
         * xlink:href="http://viaf.org/viaf/142703516"
         * xlink:role="http://socialarchive.iath.virginia.edu/control/term#Corporatebody"
         * xlink:type="simple">
         *   <relationEntry>George Washington university</relationEntry>
         * </cpfRelation>
         */

    /**
     * Create cpfRelation sameAs and remove otherRecordIDs sameAs
     *
     * Original ingested cpfRelations that are sameAs are saved in table otherid, PHP object
     * otherRecordIDs. The xlink:href for sameAs is (not surprisingly) the same as the entityType. 
     *
     * Serializing back to CPF we need to reverse that process, but only for sameAs.
     *
     * This adds new ConstellationRelation entries, as appropriated. It also creates a replacement
     * otherRecordIDs as necessary that only contains the non-sameAs entries.
     * 
     * @param string[] $data String array reference which is the constellation as a PHP array, from Constellation->toArray().
     * Using the reference, changes are made in place.
     */
    private static function cpfSameAs(&$data) {
        $fixedOIDs = array();
        if (array_key_exists('otherRecordIDs', $data)) {
            foreach($data['otherRecordIDs'] as $oId) {
                if ($oId['type']['term'] == 'sameAs') {
                    $cpfRel = array();
                    $cpfRel['dataType'] = "ConstellationRelation"; 
                    $cpfRel['targetArkID'] = $oId['uri']; // xlink:href
                    $cpfRel['type'] = $oId['type']; // .uri is xlink:arcrole
                    if (array_key_exists('text', $oId)) {
                        $cpfRel['content'] = $oId['text']; // relationEntry
                    }
                    $cpfRel['targetEntityType']['uri'] = $data['entityType']['uri']; // xlink:role 
                    $cpfRel['targetEntityType']['term'] = $data['entityType']['term']; // xlink:role
                    array_push($data['relations'], $cpfRel);
                } else {
                    array_push($fixedOIDs, $oId);
                }
            }
            $data['otherRecordIDs'] = $fixedOIDs;
        }
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
                if ($cpfRel['type']['term'] == 'sameAs') {
                    /*
                     * Skip sameAs because they are created by cpfSameAs() above. Not our concern here.
                     */
                    continue;
                }
                $relCon = $dbu->readPublishedConstellationByARK($cpfRel['targetArkID'], true);
                if ($relCon) {
                    /*
                     * The controlled vocabulary table has type, value (aka term) both of which are always
                     * populated. It also has uri which is populated for some vocabulary. (The other
                     * vocabulary fields are: id, description, and entity_group). XML elements are limited to
                     * using the value, while XML attributes should use the uri (when available).
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