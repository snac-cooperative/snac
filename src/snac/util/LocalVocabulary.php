<?php
/**
 * Local Vocabulary Class File
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\util;

/**
 * Local Vocabulary Class
 *
 * This class provides access to the vocabulary table stored in postgres
 * as a list of id, value, uri, and description pairs.  It allows the
 * seaching for a Term object from a string of the term or ID.
 *
 * @author Robbie Hott
 *        
 */
class LocalVocabulary implements \snac\util\Vocabulary {

    /**
     * @var string[][] The vocabulary array, preloaded in the constructor
     */
    private $vocab = null;
    
    /**
     * @var \snac\server\database\DBUtil database utility handle
     */
    private $db = null;

    /**
     * @var \snac\server\ServerExecutor Server executor that can make calls to database utils
     */
    private $serverExecutor = null;

    /**
     * Constructor
     *
     * Loads all the vocabulary from the database, then organizes it by type
     * to aid in lookups.
     */
    public function __construct() {
        $this->serverExecutor = new \snac\server\ServerExecutor();
    }

    /**
     * Set Contellation Store
     *
     * Sets a Constellation Store (DBUtil) into this instance of a
     * Local Vocabulary.  This is not necessary to be run, since if a
     * Constellation Store is not available when a term has been requested,
     * this class will automatically instantiate a DBUtil instance.
     *
     * This is best used when calling the EAC-CPF parser from ServerExecutor,
     * which already has a Constellation Store instantiated.
     *
     * @param \snac\server\database\DBUtil $cStore The Constellation Store to set
     */
    public function setConstellationStore(&$cStore) {
        $this->db = $cStore;
    }

    /**
     * Setup Constellation Store
     *
     * Checks to see if there is a Constellation Store (DBUtil) set for this
     * instance.  If so, it does nothing.  If not, then this method will attempt
     * to instantiate a default DBUtil instance.
     */
    private function setupConstellationStore() {
        if ($this->db == null)
            $this->db = new \snac\server\database\DBUtil();
    }

    /**
     * Get a Term by string value/term
     *
     * @param string $value The value of the term to search for
     * @param string $type The type of controlled vocab
     * @return \snac\data\Term The Term object for the value
     */
    public function getTermByValue($value, $type) {
        $this->setupConstellationStore();
        return $this->db->populateTerm(null, $value, $type);
    }

    /**
     * Get a Term by integer id 
     *
     * @param string $id The persistent ID (int) for the term
     * @param string $type The type of controlled vocab
     * @return \snac\data\Term The Term object for the id
     */
    public function getTermByID($id, $type) {
        $this->setupConstellationStore();
        return $this->db->populateTerm($id);
    }
    

    /**
     * Get a GeoTerm by URI
     *
     * @param string $uri The uri to look up
     * @return \snac\data\GeoTerm The GeoTerm object for the uri
     */
    public function getGeoTermByURI($uri) {
        $this->setupConstellationStore();
        return $this->db->getPlaceByURI($uri);
    }

    public function getConstellation($ark) {
        return $this->db->readPublishedConstellationByARK($ark, \snac\server\database\DBUtil::$READ_SHORT_SUMMARY);
    }

    /**
     * Get a Resource by Resource object
     *
     * @param \snac\data\Resource $resource The resource to search
     * @return \snac\data\Resource|null The resource object found in the database
     */
    public function getResource($resource) {
        // First try to search resources for the link.  If it exists, then use that
        // resource.  (Only the first one will be chosen).
        if ($resource->getLink() !== null && $resource->getLink() !== "") {
            $query = array("term" => $resource->getLink());
            $searchResults = $this->serverExecutor->searchResources($query);
            if (isset($searchResults["results"]) && !empty($searchResults["results"]) && isset($searchResults["results"][0]))
                return new \snac\data\Resource($searchResults["results"][0]);
        }
        // If there was no link or searching the link didn't work, then try searching
        // the title.  This still returns only the first entry, if it exists
        $query = array("term" => $resource->getTitle());
        $searchResults = $this->serverExecutor->searchResources($query);
        if (isset($searchResults["results"]) && !empty($searchResults["results"]) && isset($searchResults["results"][0]))
            return new \snac\data\Resource($searchResults["results"][0]);

        // Create a resource if one does not exist!
        $resource->setOperation(\snac\data\AbstractData::$OPERATION_INSERT);
        $query = array("resource" => $resource->toArray());
        $result = $this->serverExecutor->writeResource($query);
        if (isset($result["resource"]))
            return new \snac\data\Resource($result["resource"]);

        // If something went wrong in creating a resource, then just return the one we had without an ID/version
        return $resource;
    }

}
