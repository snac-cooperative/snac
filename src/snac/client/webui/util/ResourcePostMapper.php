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
     * @var boolean Whether or not to look up Term values in the database
     */
    private $lookupTerms = false;

    /**
    * @var \snac\client\util\ServerConnect Whether or not to look up Term values in the database
    */
    private $lookupTermsConnector = null;

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
     * Allow Term Lookups
     *
     * Calling this method allows the PostMapper to connect to the server and
     * use the vocabulary search mechanism to look up terms.
     */
    public function allowTermLookup() {
        $this->lookupTerms = true;
        $this->lookupTermsConnector = new \snac\client\util\ServerConnect();
    }

    /**
     * Disallow Term Lookups
     *
     * By default, the PostMapper is not allowed to query the server and look
     * up any terms using the vocabulary search mechanism. Calling this method
     * returns the PostMapper to that default behavior.
     */
    public function disallowTermLookup() {
        $this->lookupTerms = false;
        $this->lookupTermsConnector = null;
    }

    /**
     * Get Operation
     *
     * Gets the operation from the parameter, if it exists.  If not, it returns null
     *
     * @param string[][] $data The input POST data
     * @return string|NULL The operation associated with this data
     */
    private function getOperation($data) {

        if (isset($data['operation'])) {
            $op = $data["operation"];
            if ($op == "insert") {
                return \snac\data\AbstractData::$OPERATION_INSERT;
            } else if ($op == "update") {
                return \snac\data\AbstractData::$OPERATION_UPDATE;
            } else if ($op == "delete") {
                return \snac\data\AbstractData::$OPERATION_DELETE;
            }

            return null;
        }
        return null;
    }

    /**
     * Parse Term
     *
     * Parses and creates a Term object if the information exists in the data given.
     *
     * @param string[][] $data  Data to inspect for term object
     * @return NULL|\snac\data\Term Correct Term object or null if no term
     */
    private function parseTerm($data) {
        $term = null;
        if (isset($data) && $data != null && isset($data["id"]) && $data["id"] != "" && $data["id"] != null) {
            if ($this->lookupTerms) {
                $term = $this->lookupTermsConnector->lookupTerm($data["id"]);
            } else {
                $term = new \snac\data\Term();
                $term->setID($data["id"]);
            }
        }
        return $term;
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
    public function serializeToResource($postData) {

        $this->resource = new \snac\data\Resource();

        foreach ($postData["resources"] as $k => $data) {
            $this->resource->setID($data["id"]);
            $this->resource->setVersion($data["version"]);
            $this->resource->setOperation($data["operation"]);
            $this->resource->setTitle($data["title"]);
            $this->resource->setAbstract($data["abstract"]);
            $this->resource->setExtent($data["extent"]);
            $this->resource->setDate($data["date"]);
            $this->resource->setDisplayEntry($data["displayEntry"]);
            $this->resource->setLink($data["link"]);

            $this->resource->setDocumentType($this->parseTerm($data["documentType"]));

            if (isset($data["repo"]) && $data["repo"] !== null) {
                $repo = new \snac\data\Constellation();
                $repo->setID($data["repo"]);
                $this->resource->setRepository($repo);
            }

            // Not currently using originationName
            if (isset($data["originationName"])) {
                foreach ($data["originationName"] as $l => $oData) {
                    $part = new \snac\data\OriginationName();
                    $part->setID($oData["id"]);
                    $part->setVersion($oData["version"]);
                    if ($oData["operation"] == "insert" || $oData["operation"] == "delete")
                        $part->setOperation($this->getOperation($oData));
                    else {
                        $oData["operation"] = $this->getOperation($data);
                        $part->setOperation($this->getOperation($data));
                    }

                    $part->setName($oData["name"]);

                    $this->resource->addOriginationName($part);
                }
            }

            if (isset($data["languages"])) {
                foreach ($data["languages"] as $languages => $language) {
                    $newLanguage = new \snac\data\Language();
                    $lang = new \snac\data\Term();
                    $script = new \snac\data\Term();

                    if (isset($language['id']))
                        $newLanguage->setID($language["id"]);
                    if (isset($language['version']))
                        $newLanguage->setVersion($language["version"]);

                    $newLanguage->setOperation($language["operation"]);

                    if (!empty($language["language"]) || (!empty($language["script"]))) {
                        if (isset($language["language"]))
                            $lang->setID($language["language"]);
                            $newLanguage->setLanguage($lang);

                        if (isset($language["script"]))
                            $script->setID($language["script"]);
                            $newLanguage->setScript($script);
                        $this->resource->addLanguage($newLanguage);
                    }
                }
            }
        }

        return $this->resource;
    }

}
