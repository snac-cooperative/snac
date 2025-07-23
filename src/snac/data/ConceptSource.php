<?php
/**
 * Snac Concept Source File
 *
 * Contains the data class for Concept source information
 *
 * License:
 *
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2025 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\data;

/**
 * Source
 *
 * A "source" is a citation source, and has qualities of an authority file although every
 * source is independent, even if it seems to be a duplicate.  This appears to derive from
 * /eac-cpf/control/source in the CPF. Going forward we use it for all sources.  For example,
 * SNACControlMetadata->citation is a Source object. Constellation->sources is a list of sources.
 *
 * @author Robbie Hott
 *
 */
class ConceptSource extends AbstractConceptData {

    /**
     * @var string Text of this source.
     */
    private $foundData;

    /**
     * @var string Note related to this source
     */
    private $note;

    /**
     * @var string URI of this source
     */
    private $uri;

    /**
     * @var string Citation of this source.
     */
    private $citation;

    /**
     * Constructor
     */
    public function __construct($data = null) {
        parent::__construct($data);
    }

    /**
     * Get the note of this source
     *
     * @return string The note attached to this source
     *
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Get the foundData of this source
     *
     * @return string The description foundData/xml
     *
     */
    public function getFoundData()
    {
        return $this->foundData;
    }

    /**
     * Get the citation of this source
     *
     * @return string The citation 
     *
     */
    public function getCitation()
    {
        return $this->citation;
    }


    /**
     * Get the URI of this source
     *
     * @return string The uri of this source
     */
    public function getURI() {
        return $this->uri;
    }

    /**
     * Returns this object's data as an associative array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This objects data in array form
     */
    public function toArray($shorten = true) {
        $return = array(
            "dataType" => "ConceptSource",
            "found_data" => $this->foundData,
            "citation" => $this->citation,
            "note" => $this->note,
            "uri" => $this->uri
        );

        $return = array_merge($return, parent::toArray($shorten));

        // Shorten if necessary
        if ($shorten) {
            $return2 = array();
            foreach ($return as $i => $v)
                if ($v != null && !empty($v))
                    $return2[$i] = $v;
            unset($return);
            $return = $return2;
        }

        return $return;
    }

    /**
     * Replaces this object's data with the given associative array
     *
     * @param string[][] $data This objects data in array form
     * @return boolean true on success, false on failure
     */
    public function fromArray($data) {
        if (!isset($data["dataType"]) || $data["dataType"] != "ConceptSource")
            return false;

        parent::fromArray($data);

        if (isset($data["uri"]))
            $this->uri = $data["uri"];
        else
            $this->uri = null;

        if (isset($data["citation"]))
            $this->citation = $data["citation"];
        else
            $this->citation = null;

        if (isset($data["found_data"]))
            $this->foundData = $data["found_data"];
        else
            $this->foundData = null;

        if (isset($data["note"]))
            $this->note = $data["note"];
        else
            $this->note = null;

        return true;

    }

    /**
     * Set the foundData/xml of this Source
     *
     * @param string $foundData The full foundData/xml of this source
     */
    public function setFoundData($foundData) {

        $this->foundData = $foundData;
    }

    /**
     * Set the citation of this Source
     *
     * @param string $text The bibiographic citation of this source 
     */
    public function setCitation($citation) {

        $this->citation = $citation;
    }

    /**
     * Set the note of this Source
     *
     * @param string $note the note attached to this source
     */
    public function setNote($note) {

        $this->note = $note;
    }

    /**
     * Set the URI of this source
     *
     * @param string $uri The uri
     */
    public function setURI($uri) {
        $this->uri = $uri;
    }

}
