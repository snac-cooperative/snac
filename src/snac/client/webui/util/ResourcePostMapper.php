<?php

/**
 * Resource Post Mapper Class File
 *
 * Contains the mapper class between Resources and POST data from the WebUI
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2016 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\client\webui\util;

/**
 * Resource POST Mapper
 *
 * This utility class provides the methods to convert input POST variables from the web user interface
 * into a PHP Resource.  It also provides ways to get the input id mappings from a secondary resource
 * that has more information (i.e. the resource after having performed a server update and database write)
 *
 * @author Robbie Hott
 *
 */
class ResourcePostMapper {

    /**
     * @var \snac\data\Resource $resource The resource serialized
     */
    private $resource = null;

    /**
     * @var \Monolog\Logger $logger Logger for this class
     */
    private $logger = null;

    /**
     * Constructor
     */
    public function __construct() {
        global $log;
        $this->mapping = array();

        // create a log channel
        $this->logger = new \Monolog\Logger('ResourcePOSTMapper');
        $this->logger->pushHandler($log);
    }

    /**
     * Serialize post data to Resource 
     *
     * Takes the POST data from a SAVE operation and generates
     * a Resource object to be used by the rest of the system
     *
     * @param string[][] $postData The POST input data from the WebUI user interface
     * @return \snac\data\Resource
     */
    public function serializeToConstellation($postData) {

        $this->resource = new \snac\data\Resource();

        // Rework the input into arrays of sections
        $nested = array ();
        $nested["resource"] = array ();

        foreach ($postData as $k => $v) {
            // Try to split on underscore
            $parts = explode("_", $k);

            // Empty should be null
            if ($v == "")
                $v = null;

             if (count($parts) == 3) {
                // three parts: mulitple-vals repeating
                // key_subkey_index => value ==> nested[key][index][subkey] = value
                if (! isset($nested[$parts[0]][$parts[2]]))
                    $nested[$parts[0]][$parts[2]] = array ();
                $nested[$parts[0]][$parts[2]][$parts[1]] = $v;
            } else if (count($parts) == 4) {
                // four parts: controlled vocabulary repeating
                // key_subkey_subsubkey_index => value ==> nested[key][index][subkey][subsubkey] = value
                if (! isset($nested[$parts[0]][$parts[3]]))
                    $nested[$parts[0]][$parts[3]] = array ();
                if (! isset($nested[$parts[0]][$parts[3]][$parts[1]]))
                    $nested[$parts[0]][$parts[3]][$parts[1]] = array ();
                $nested[$parts[0]][$parts[3]][$parts[1]][$parts[2]] = $v;
            } else if (count($parts) == 5) {
                // five parts: non-scm repeating
                // nameEntry_contributor_23_name_0
                // nameEntry_contributor_{{j}}_id_{{i}}
                // key, index = nameEntry, 0
                // subkey, index = contributor, 23
                // subsubkey = name
                // 0___1______2_________3________4
                // key_subkey_subindex_subsubkey_index => value ==>
                //                      nested[key][index][subkey][subindex][subsubkey] = value
                if (! isset($nested[$parts[0]][$parts[4]]))
                    $nested[$parts[0]][$parts[4]] = array ();
                if (! isset($nested[$parts[0]][$parts[4]][$parts[1]]))
                    $nested[$parts[0]][$parts[4]][$parts[1]] = array ();
                if (! isset($nested[$parts[0]][$parts[4]][$parts[1]][$parts[2]]))
                    $nested[$parts[0]][$parts[4]][$parts[1]][$parts[2]] = array ();
                $nested[$parts[0]][$parts[4]][$parts[1]][$parts[2]][$parts[3]] = $v;
            }
        }

        $this->logger->addDebug("parsed values", $nested);

        foreach ($nested["resource"] as $k => $data) {
            $relation->setID($data["id"]);
            $relation->setVersion($data["version"]);

            $relation->setTitle($data["title"]);
            $relation->setAbstract($data["abstract"]);
            $relation->setExtent($data["extent"]);
            $relation->setLink($data["link"]);
            $relation->setSource($data["source"]);
            $relation->setNote($data["note"]);

            $relation->setDocumentType($this->parseTerm($data["documentType"]));

            $relation->setRole($this->parseTerm($data["role"]));

            $relation->setAllSNACControlMetadata($this->parseSCM($data, "resourceRelation", $k));

            $this->addToMapping("resourceRelation", $k, $data, $relation);

        }



}
