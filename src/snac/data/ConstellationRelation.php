<?php
/**
 * Constellation Relation File
 *
 * Contains the data class for the constellation relations.
 *
 * License:
 *
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\data;

/**
 * Constellation Relationship
 *
 * Data class to store the information about a relationship between Constellations
 *
 * @author Robbie Hott
 *        
 */
class ConstellationRelation extends AbstractData {

    /**
     *
     * @var int Source constellation ID
     */
    private $sourceConstellation = null;

    /**
     *
     * @var int Target constellation ID
     */
    private $targetConstellation = null;

    /**
     *
     * @var string Source constellation ARK ID
     */
    private $sourceArkID = null;

    /**
     *
     * @var string Target constellation ARK ID
     */
    private $targetArkID = null;
    
    /**
     * 
     * @var string Target entity type
     */
    private $targetEntityType = null;

    /**
     *
     * @var string Type of the constellation
     */
    private $type = null;

    /**
     *
     * @var string Alternate type
     */
    private $altType = null;
    
    /**
     * 
     * @var string CPF Relation Type
     */
    private $cpfRelationType = null;

    /**
     *
     * @var string Content of the relation
     */
    private $content = null;
    
    /**
     * @var \snac\data\SNACDate Dates of thie relationship
     */
    private $dates = null;    
    
    /**
     *
     * @var string Note attached to relation
     */
    private $note = null;
    
    /**
     * Returns this object's data as an associative array
     *
     * @return string[][] This objects data in array form
     */
    public function toArray() {
        $return = array(
            "dataType" => "ConstellationRelation",
            "sourceConstellation" => $this->sourceConstellation,
            "targetConstellation" => $this->targetConstellation,
            "sourceArkID" => $this->sourceArkID,
            "targetArkID" => $this->targetArkID,
            "targetEntityType" => $this->targetEntityType,
            "type" => $this->type,
            "altType" => $this->altType,
            "cpfRelationType" => $this->cpfRelationType,
            "content" => $this->content,
            "dates" => $this->dates == null ? null : $this->dates->toArray(),
            "note" => $this->note
        );
        return $return;
    }

    /**
     * Replaces this object's data with the given associative array
     *
     * @param string[][] $data This objects data in array form
     * @return boolean true on success, false on failure
     */
    public function fromArray($data) {
        if (!isset($data["dataType"]) || $data["dataType"] != "ConstellationRelation")
            return false;

        if (isset($data["sourceConstellation"]))
            $this->sourceConstellation = $data["sourceConstellation"];
        else
            $this->sourceConstellation = null;

        if (isset($data["targetConstellation"]))
            $this->targetConstellation = $data["targetConstellation"];
        else
            $this->targetConstellation = null;

        if (isset($data["sourceArkID"]))
            $this->sourceArkID = $data["sourceArkID"];
        else
            $this->sourceArkID = null;

        if (isset($data["targetArkID"]))
            $this->targetArkID = $data["targetArkID"];
        else
            $this->targetArkID = null;

        if (isset($data["targetEntityType"]))
            $this->targetEntityType = $data["targetEntityType"];
        else
            $this->targetEntityType = null;

        if (isset($data["type"]))
            $this->type = $data["type"];
        else
            $this->type = null;

        if (isset($data["altType"]))
            $this->altType = $data["altType"];
        else
            $this->altType = null;

        if (isset($data["cpfRelationType"]))
            $this->cpfRelationType = $data["cpfRelationType"];
        else
            $this->cpfRelationType = null;

        if (isset($data["content"]))
            $this->content = $data["content"];
        else
            $this->content = null;

        if (isset($data["dates"]))
            $this->dates = new SNACDate($data["dates"]);
        else
            $this->dates = null;

        if (isset($data["note"]))
            $this->note = $data["note"];
        else
            $this->note = null;

        return true;
    }

    /**
     * Set the target ARK ID
     *
     * @param string $ark Target ARK ID
     */
    public function setTargetArkID($ark) {

        $this->targetArkID = $ark;
    }
    
    /**
     * Set the target entity type
     * 
     * @param string $type Target's entity type
     */
    public function setTargetType($type) {
        $this->targetEntityType = $type;
    }

    /**
     * Set the relation type
     *
     * @param string $type Type of the relation
     */
    public function setType($type) {

        $this->type = $type;
    }
    
    /**
     * Set the CPF Relation type
     * 
     * @param string $type CPF Relation Type
     */
    public function setCPFRelationType($type) {
        $this->cpfRelationType = $type;
    }

    /**
     * Set the relation's alternate type
     *
     * @param string $type Alternate type of the relation
     */
    public function setAltType($type) {

        $this->altType = $type;
    }

    /**
     * Set the content of the relation
     * 
     * @param string $content Relation content
     */
    public function setContent($content) {

        $this->content = $content;
    }


    /**
     * Set the dates of this relation
     * 
     * @param \snac\data\SNACDate $date The date or range of this relation
     */
    public function setDates($date) {
        $this->dates = $date;
    }
    
    /**
     * Set the note for this constellation relation
     * 
     * @param string $note Resource note
     */
    public function setNote($note) {

        $this->note = $note;
    }
}
