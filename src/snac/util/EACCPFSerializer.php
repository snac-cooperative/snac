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
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2016 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\util;


/**
 * EAC-CPF Serializer
 *
 * Serialize (export) SNAC Constellation to EAC-CPF XML. Create EAC-CPF xml form of a PHP Constellation.
 *
 * @author Tom Laudeman
 * @author Robbie Hott
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

        $data['currentDate'] = date('Y-m-d');

        $xml = new \SimpleXMLElement($twig->render("EAC-CPF_template.xml", $data));
        $domxml = new \DOMDocument('1.0');
        $domxml->preserveWhiteSpace = false;
        $domxml->formatOutput = true;
        $domxml->loadXML($xml->asXML());
        $cpfXML = $domxml->saveXML();

        return $cpfXML;
    }

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
            if (!isset($data['relations'])) {
                $data['relations'] = array();
            }
            foreach($data['otherRecordIDs'] as $oId) {
                if (isset($oId['type']) && $oId['type']['term'] == 'sameAs') {
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
