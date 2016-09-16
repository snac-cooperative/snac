<?php

/**
 * Resource Relation File
 *
 * Contains the data class for the resource relations.
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
 * Resource Relation
 *
 * Data storage class for relationships of an Identity Constellation to an external (archival) Resource.
 *
 * @author Robbie Hott
 *        
 */
class ResourceRelation extends AbstractData {

    /**
     * Document Type
     * 
     * From EAC-CPF tag(s):
     * 
     * * resourceRelation/@role
     * 
     * @var \snac\data\Term Document type
     */
    private $documentType = null;

    /**
     * Link Type
     * 
     * From EAC-CPF tag(s):
     *
     * Daniel says this is only a hard coded 'simple' and we don't need to store it, but we will hard code it
     * in the export template.
     *
     * (old comment:) resourceRelation/@type
     * Actually: resourceRelation@xlink:type
     *
     * 'linkType' => 'simple',
     * 
     * @var \snac\data\Term Link type
     */
    private $linkType = null;
    
    /**
     * Entry Type
     * 
     * From EAC-CPF tag(s):
     * 
     * * resourceRelation/relationEntry/@localType
     * 
     * @var \snac\data\Term Relation entry type
     * 
     */
    private $entryType = null;

    /**
     * Link URI
     * 
     * From EAC-CPF tag(s):
     * 
     * * resourceRelation/@href
     * 
     * @var string Link to external resource
     */
    private $link = null;

    /**
     * Role
     * 
     * From EAC-CPF tag(s):
     * 
     * * resourceRelation/@arcrole
     * 
     * @var \snac\data\Term Role in of the relation
     */
    private $role = null;

    /**
     * Content
     * 
     * From EAC-CPF tag(s):
     * 
     * * resourceRelation/resourceEntry
     * 
     * @var string Content in the relation
     */
    private $content = null;

    /**
     * XML source
     * 
     * From EAC-CPF tag(s):
     * 
     * * resourceRelation/objectXMLWrap
     * 
     * @var string XML source of the resource relation
     */
    private $source = null;

    /**
     * Descriptive Note
     * 
     * From EAC-CPF tag(s):
     * 
     * * resourceRelation/descriptiveNote
     * 
     * @var string Note attached to relation
     */
    private $note = null;

    /**
     * Title of the archival resource
     *
     * @var string Resource title
     */
    private $title = null;
    
    /**
     * Abstract of the archival resource
     *
     * 
     * @var string Abstract describing the resource
     */
    private $abstract = null;


    /**
     * Extent of the resource
     *
     * @var string Extent of the materials, for example "1 box", "3 linear feet"
     */ 
    private $extent = null;


    /**
     * Repository ic_id
     *
     * @var integer The ic_id of the constellation that is the repository holding this related archival
     * resource.
     */ 
    private $repoIcId = null;


    /**
     * Origination (creator) of the resource
     * @var string[] List of origination names (names of the creators) of this resource.
     */
    private $relatedResourceOriginationName = null;

    /**
     * Constructor
     *
     * Now that ResourceRelation has a property that is an array, we need a constructor that can initialize it.
     *
     */ 
    public function __construct($data = null) {
        $this->setMaxDateCount(0);
        if ($data == null) {
            $this->relatedResourceOriginationName = array();
        }
        // always call the parent constructor
        parent::__construct($data);
    }
    
    /**
     * Get title of the archival resource
     *
     * @return string Resource title
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Set title of the archival resource
     *
     * @param string Resource title
     */
    public function setTitle($title) {
        $this->title = $title;
    }

    /**
     * Get abstract of the archival resource
     *
     * 
     * @return string Abstract describing the resource
     */
    public function getAbstract() {
        return $this->abstract;
    }
    
    /**
     * Set abstract of the archival resource
     *
     * 
     * @param string Abstract describing the resource
     */
    public function setAbstract($abstract) {
        $this->abstract = $abstract;
    }

    /**
     * Get extent of the resource
     *
     * @return string Extent of the materials, for example "1 box", "3 linear feet"
     */ 
    public function getExtent() {
        return $this->extent;
    }
    
    /**
     * Set extent of the resource
     *
     * @param string Extent of the materials, for example "1 box", "3 linear feet"
     */ 
    public function setExtent($extent) {
        $this->extent = $extent;
    }

    /**
     * Get repository ic_id
     *
     * @return integer The ic_id of the constellation that is the repository holding this related archival
     * resource.
     */ 
    public function getRepoIcId() {
        return $this->repoIcId;
    }
    
    /**
     * Set repository ic_id
     *
     * @param integer The ic_id of the constellation that is the repository holding this related archival
     * resource.
     */ 
    public function setRepoIcId($repoIcId) {
        $this->repoIcId = $repoIcId;
    }

    /**
     * Get list of origination (creator) of the resource
     *
     * @return \snac\data\RROriginationName[] List of origination names (names of the creators) of this resource.
     */
    public function getRelatedResourceOriginationName() {
        return $this->relatedResourceOriginationName;
    }

    /**
     * Add an origination (creator) of the resource
     *
     * @param \snac\data\RROriginationName[] List of origination names (names of the creators) of this resource.
     */
    public function AddRelatedResourceOriginationName($relatedResourceOriginationName) {
        array_push($this->relatedResourceOriginationName, $relatedResourceOriginationName);
    }


    /**
     * Get the document type
     * 
     *  Get the document type for the document pointed to by this relation, such as "ArchivalResource" 
     *
     * * resourceRelation/@role
     * 
     * @return \snac\data\Term Document type
     *
     */
    function getDocumentType()
    {
        return $this->documentType;
    }

    /**
     * Get the xlink type
     * 
     * This should not be used, as it is always "simple" 
     *
     * Daniel says this is only a hard code 'simple' and we don't need to store it, but we will hard code it
     * in the export template.
     *
     * (old comment:) resourceRelation/@type
     * Actually: resourceRelation@xlink:type
     *
     * 'linkType' => 'simple',
     * 
     * @return \snac\data\Term Link type
     * @deprecated
     *
     */
    function getLinkType()
    {
        return $this->linkType;
    }

    /**
     * Get Secondary Type
     * 
     * Get the secondary type of the document pointed to by this relation.  The ANF use
     * this field to repeat (in short form) the document type from @role, such as
     * "archival" for "ArchivalResource"
     *
     * * resourceRelation/relationEntry/@localType
     * 
     * @return \snac\data\Term Relation entry type
     *
     */
    function getEntryType()
    {
        return $this->entryType;
    }

    /**
     * Get URI Link
     * 
     * Get the URI link for the document pointed to by this relation
     *
     * * resourceRelation/@href
     * 
     * @return string Link to external resource
     *
     */
    function getLink()
    {
        return $this->link;
    }

    /**
     * Get Role
     * 
     * Get the role the constellation played with respect to this resource,
     * such as "authorOf" or ""
     *
     * * resourceRelation/@arcrole
     * 
     * @return \snac\data\Term Role in of the relation
     *
     */
    function getRole()
    {
        return $this->role;
    }

    /**
     * Get the text/xml content of this relation 
     *
     * * resourceRelation/resourceEntry
     * 
     * @return string Content in the relation
     *
     */
    function getContent()
    {
        return $this->content;
    }

    /**
     * Get the source XML of this relation 
     *
     * * resourceRelation/objectXMLWrap
     * 
     * @return string XML source of the resource relation
     *
     */
    function getSource()
    {
        return $this->source;
    }

    /**
     * Get the human-readable descriptive note for this relation 
     *
     * * resourceRelation/descriptiveNote
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
            "dataType" => "ResourceRelation",
            "relatedResourceOriginationName" => array(),            
            "documentType" => $this->documentType == null ? null : $this->documentType->toArray($shorten),
            "linkType" => $this->linkType == null ? null : $this->linkType->toArray($shorten),
            "entryType" => $this->entryType == null ? null : $this->entryType->toArray($shorten),
            "link" => $this->link,
            "role" => $this->role == null ? null : $this->role->toArray($shorten),
            "content" => $this->content,
            "source" => $this->source,
            "note" => $this->note
        );
            
        foreach ($this->relatedResourceOriginationName as $vv) {
            array_push($return['relatedResourceOriginationName'], $vv->toArray($shorten));
        }

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
        if (!isset($data["dataType"]) || $data["dataType"] != "ResourceRelation")
            return false;

        parent::fromArray($data);

        unset($this->relatedResourceOriginationName);
        $this->relatedResourceOriginationName = array();
        if (isset($data['relatedResourceOriginationName'])) {
            foreach ($data['relatedResourceOriginationName'] as $entry) {
                if ($entry != null) {
                    array_push($this->relatedResourceOriginationName, new \snac\data\RROriginationName($entry));
                }
            }
        }

        if (isset($data["documentType"]) && $data["documentType"] != null)
            $this->documentType = new \snac\data\Term($data["documentType"]);
        else
            $this->documentType = null;

        if (isset($data["linkType"]) && $data["linkType"] != null)
            $this->linkType = new \snac\data\Term($data["linkType"]);
        else
            $this->linkType = null;

        if (isset($data["entryType"]) && $data["entryType"] != null)
            $this->entryType = new \snac\data\Term($data["entryType"]);
        else
            $this->entryType = null;

        if (isset($data["link"]))
            $this->link = $data["link"];
        else
            $this->link = null;

        if (isset($data["role"]) && $data["role"] != null)
            $this->role = new \snac\data\Term($data["role"]);
        else
            $this->role = null;

        if (isset($data["content"]))
            $this->content = $data["content"];
        else
            $this->content = null;

        if (isset($data["source"]))
            $this->source = $data["source"];
        else
            $this->source = null;

        if (isset($data["note"]))
            $this->note = $data["note"];
        else
            $this->note = null;

        return true;
    }


    /**
     * Set the document type for this relation
     *
     * @param \snac\data\Term $type Document type
     */
    public function setDocumentType($type) {

        $this->documentType = $type;
    }

    /**
     * Set the HREF link for this resource relation
     *
     * @param string $href Link
     */
    public function setLink($href) {

        $this->link = $href;
    }

    /**
     * Set the link type for this relation
     * 
     * @param \snac\data\Term $type Link type
     */
    public function setLinkType($type) {

        $this->linkType = $type;
    }

    /**
     * Set the role of this resource relation
     *
     * @param \snac\data\Term $role Relation role
     */
    public function setRole($role) {

        $this->role = $role;
    }

    /**
     * Set the XML source of this resource relation
     *
     * @param string $xml XML content for the resource relation
     */
    public function setSource($xml) {

        $this->source = $xml;
    }

    /**
     * Set the content for this relation
     * 
     * @param string $content Content
     */
    public function setContent($content) {

        $this->content = $content;
    }

    /**
     * Set the note for this resource relation
     * 
     * @param string $note Resource note
     */
    public function setNote($note) {

        $this->note = $note;
    }
    
    /**
     * Set the relation entry type
     * 
     * @param \snac\data\Term $type Relation entry type
     */
    public function setRelationEntryType($type) {
        $this->entryType = $type;
    }

    /**
     *
     * {@inheritDoc}
     *
     * @param \snac\data\ResourceRelation $other Other object
     * @param boolean $strict optional Whether or not to check id, version, and operation
     * @return boolean true on equality, false otherwise
     *       
     * @see \snac\data\AbstractData::equals()
     */
    public function equals($other, $strict = true) {

        if ($other == null || ! ($other instanceof \snac\data\ResourceRelation))
            return false;
        
        if (! parent::equals($other, $strict))
            return false;

        if ($this->getTitle() != $other->getTitle())
            return false;
        if ($this->getAbstract() != $other->getAbstract())
            return false;
        if ($this->getExtent() != $other->getExtent())
            return false;
        if ($this->getRepoIcId() != $other->getRepoIcId())
            return false;
        if (!$this->checkArrayEqual($this->getRelatedResourceOriginationName(), $other->getRelatedResourceOriginationName(), $strict))
            return false;

        if ($this->getSource() != $other->getSource())
            return false;
        if ($this->getLink() != $other->getLink())
            return false;
        if ($this->getContent() != $other->getContent())
            return false;
        if ($this->getNote() != $other->getNote())
            return false;
        
        if (($this->getDocumentType() != null && ! $this->getDocumentType()->equals($other->getDocumentType())) ||
                 ($this->getDocumentType() == null && $other->getDocumentType() != null))
            return false;
        if (($this->getLinkType() != null && ! $this->getLinkType()->equals($other->getLinkType())) ||
                 ($this->getLinkType() == null && $other->getLinkType() != null))
            return false;
        if (($this->getEntryType() != null && ! $this->getEntryType()->equals($other->getEntryType())) ||
                 ($this->getEntryType() == null && $other->getEntryType() != null))
            return false;
        if (($this->getRole() != null && ! $this->getRole()->equals($other->getRole())) ||
                 ($this->getRole() == null && $other->getRole() != null))
            return false;
        
        return true;
    }
}
