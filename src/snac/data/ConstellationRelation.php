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
 * Constellation Relationship.  See the abstract parent class for common methods setDBInfo() and getDBInfo().
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
     * @var int Target constellation ID[w
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
     * @var \snac\data\SNACDate Dates of this relationship. A single SNACDate object.
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

    /**
     * getter for $this->sourceConstellation
     *
     * @return int Source constellation ID
     *
     */
    function getSourceConstellation()
    {
        return $this->sourceConstellation;
    }
        
    /**
     * getter for $this->targetConstellation
     *
     * @return int Source constellation ID
     *
     */
    function getTargetConstellation()
    {
        return $this->targetConstellation;
    }
            
    /**
     * getter for $this->sourceArkID
     *
     * @return string Source constellation ARK ID
     *
     */
    function getSourceArkID()
    {
        return $this->sourceArkID;
    }
    
    /**
     * getter for $this->targetArkID
     *
     * @return string Target constellation ARK ID
     *
     */
    function getTargetArkID()
    {
        return $this->targetArkID;
    }

    /**
     * getter for $this->targetEntityType
     *
     * * cpfRelation/@role
     *
     * @return string Target entity type
     *
     */
    function getTargetEntityType()
    {
        return $this->targetEntityType;
    }

    /**
     * getter for $this->type
     *
     * * cpfRelation/@arcrole
     *
     * @return string Type of the constellation
     *
     */
    function getType()
    {
        return $this->type;
    }

    /**
     * getter for $this->altType
     *
     * 
     * * cpfRelation/@type cpfRelation@xlink:type
     *
     * The only value this ever has is "simple". Daniel says not to save it, and implicitly hard code when
     * serializing export.
     * 
     * @return string Alternate type
     *
     */
    function getAltType()
    {
        return $this->altType;
    }

    /**
     * getter for $this->cpfRelationType
     *
     * * cpfRelation/@cpfRelationType
     * 
     * @return string CPF Relation Type
     *
     */
    function getCpfRelationType()
    {
        return $this->cpfRelationType;
    }

    /**
     * getter for $this->content
     *
     * * cpfRelation/relationEntry
     * 
     * @return string Content of the relation
     *
     */
    function getContent()
    {
        return $this->content;
    }

    /**
     * getter for NULL. Downstream foreach gets upset. When we expect an array, always return an array.
     *
     * * cpfRelation/date/*
     * * cpfRelation/dateRange/*
     * 
     * @return \snac\data\SNACDate Dates of this relationship. A single SNACDate object.
     *
     */
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

    /**
     * getter for $this->note
     *
     * * cpfRelation/descriptiveNote
     * 
     * @return string Note attached to relation
     *
     */
    function getNote()
    {
        return $this->note;
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
            "note" => $this->note,
            'dbInfo' => $this->getDBInfo()
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

        if (isset($data['dbInfo']))
        {
            $this->setDBInfo($data['dbInfo']);
        }

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
     * Set the target constellation numeric id
     *
     * @param int $targetConstellation target constellation database record id number
     */
    public function setTargetConstellation($targetConstellation)
    {
        $this->targetConstellation = $targetConstellation;
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
