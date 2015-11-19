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
     * Postgres ID (source)
     * 
     * @var int Source constellation ID
     */
    private $sourceConstellation = null;

    /**
     * Postgres ID (target)
     * 
     * @var int Target constellation ID
     */
    private $targetConstellation = null;

    /**
     * ArkID of source
     * 
     * @var string Source constellation ARK ID
     */
    private $sourceArkID = null;

    /**
     * From EAC-CPF tag(s):
     * 
     * * cpfRelation/@href
     * 
     * @var string Target constellation ARK ID
     */
    private $targetArkID = null;
    
    /**
     * From EAC-CPF tag(s):
     * 
     * * cpfRelation/@role
     * 
     * @var string Target entity type
     */
    private $targetEntityType = null;

    /**
     * From EAC-CPF tag(s):
     * 
     * * cpfRelation/@arcrole
     * 
     * @var string Type of the constellation
     */
    private $type = null;

    /**
     * From EAC-CPF tag(s):
     * 
     * * cpfRelation/@type cpfRelation@xlink:type
     *
     * The only value this ever has is "simple". Daniel says not to save it, and implicitly hard code when
     * serializing export.
     * 
     * @var string Alternate type
     */
    private $altType = null;
    
    /**
     * From EAC-CPF tag(s):
     * 
     * * cpfRelation/@cpfRelationType
     * 
     * @var string CPF Relation Type
     */
    private $cpfRelationType = null;

    /**
     * From EAC-CPF tag(s):
     * 
     * * cpfRelation/relationEntry
     * 
     * @var string Content of the relation
     */
    private $content = null;
    
    /**
     * From EAC-CPF tag(s):
     * 
     * * cpfRelation/date/*
     * * cpfRelation/dateRange/*
     * 
     * @var \snac\data\SNACDate Dates of thie relationship
     */
    private $dates = null;    
    
    /**
     * From EAC-CPF tag(s):
     * 
     * * cpfRelation/descriptiveNote
     * 
     * @var string Note attached to relation
     */
    private $note = null;

    function getSourceConstellation()
    {
        $this->sourceConstellation;
    }
        
    function getTargetConstellation()
    {
        $this->targetConstellation;
    }
            
    function getSourceArkID()
    {
        $this->sourceArkID;
    }
    
    function getTargetArkID()
    {
        $this->targetArkID;
    }

    function getTargetEntityType()
    {
        $this->targetEntityType;
    }

    function getType()
    {
        $this->type;
    }

    function getAltType()
    {
        $this->altType;
    }

    function getCpfRelationType()
    {
        $this->cpfRelationType;
    }

    function getContent()
    {
        $this->content;
    }

    function getDates()
    {
        // Don't return NULL. Downstream foreach gets upset. When we expect an array, always return an
        // array. No dates is simply an empty array, but NULL implies that dates are conceptually not part of
        // this universe.
        if ($this->dates)
        {
            return $this->dates;
        }
        else
        {
            return array();
        }
    }

    function getNote()
    {
        $this->note;
    }


    /**
     * Returns this object's data as an associative array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This objects data in array form
     */
    public function toArray($shorten = true) {
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
            "dates" => $this->dates == null ? null : $this->dates->toArray($shorten),
            "note" => $this->note
        );

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
