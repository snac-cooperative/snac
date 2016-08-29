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
     * @var \Monolog\Logger The logger instance for this class
     */
    private $logger = null;

    /**
     * Constructor
     *
     * Creates the Serializer and adds logging
     */
    public function __construct()
    {
        global $log;
        // create a log channel
        $logger = new \Monolog\Logger('EACCPFSerializer');
        $logger->pushHandler($log);
        
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
    private function logDebug($msg, $debugArray=array())
    {
        global $logger;
        if ($logger)
        {
            $logger->addDebug($msg, $debugArray);
        }
    }


    /**
     * Serialize Constellation
     *
     * Serialize the constellation to an XML string
     *
     * @param \snac\data\Constellation $constellation The constellation to serialize
     * @return string The EAC-CPF XML for this constellation
     */
    public function serialize($constellation) {
        return $this->serializeCore($constellation->toArray());
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
    private function serializeCore($expCon) {

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
        
        $this->cpfSameAs($data['data']);
        /*
         * Leave off the minutes and microseconds. Not necessary, and it complicates testing. For testing we
         * want to extract by two methods and compare. Seconds and microseconds will be different which
         * complicates the testing.
         *
         * $data['currentDate'] = date('c');
         */ 
        $data['currentDate'] = date('Y-m-d');
        $data['versionHistory'] = array();
        //if (array_key_exists('id', $expCon)) {
        //    $dbu->readVersionHistory($expCon['id']);
        //}

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

}
