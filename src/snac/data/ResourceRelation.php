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
 * Resource Relationship.  See the abstract parent class for common methods setDBInfo() and getDBInfo().
 *
 * Data storage class for relationships of an Identity Constellation to an external Resource.
 *
 * @author Robbie Hott
 *        
 */
class ResourceRelation extends AbstractData {

    /**
     * From EAC-CPF tag(s):
     * 
     * * resourceRelation/@role
     * 
     * @var \snac\data\Term Document type
     */
    private $documentType = null;

    /**
     * From EAC-CPF tag(s):
     *
     * Daniel says this is only a hard code 'simple' and we don't need to store it, but we will hard code it
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
     * From EAC-CPF tag(s):
     * 
     * * resourceRelation/relationEntry/@localType
     * 
     * @var \snac\data\Term Relation entry type
     * 
     */
    private $entryType = null;

    /**
     * From EAC-CPF tag(s):
     * 
     * * resourceRelation/@href
     * 
     * @var string Link to external resource
     */
    private $link = null;

    /**
     * From EAC-CPF tag(s):
     * 
     * * resourceRelation/@arcrole
     * 
     * @var \snac\data\Term Role in of the relation
     */
    private $role = null;

    /**
     * From EAC-CPF tag(s):
     * 
     * * resourceRelation/resourceEntry
     * 
     * @var string Content in the relation
     */
    private $content = null;

    /**
     * From EAC-CPF tag(s):
     * 
     * * resourceRelation/objectXMLWrap
     * 
     * @var string XML source of the resource relation
     */
    private $source = null;

    /**
     * From EAC-CPF tag(s):
     * 
     * * resourceRelation/descriptiveNote
     * 
     * @var string Note attached to relation
     */
    private $note = null;

    /**
     * Get the document type for the document pointed to by this relation, such as "ArchivalResource" 
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
     * Get the xlink type.  This should not be used, as it is always "simple" 
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
     *
     */
    function getLinkType()
    {
        return $this->linkType;
    }

    /**
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
            "documentType" => $this->documentType == null ? null : $this->documentType->toArray($shorten),
            "linkType" => $this->linkType == null ? null : $this->linkType->toArray($shorten),
            "entryType" => $this->entryType == null ? null : $this->entryType->toArray($shorten),
            "link" => $this->link,
            "role" => $this->role == null ? null : $this->role->toArray($shorten),
            "content" => $this->content,
            "source" => $this->source,
            "note" => $this->note
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
        if (!isset($data["dataType"]) || $data["dataType"] != "ResourceRelation")
            return false;

        parent::fromArray($data);

        if (isset($data["documentType"]))
            $this->documentType = new \snac\data\Term($data["documentType"]);
        else
            $this->documentType = null;

        if (isset($data["linkType"]))
            $this->linkType = new \snac\data\Term($data["linkType"]);
        else
            $this->linkType = null;

        if (isset($data["entryType"]))
            $this->entryType = new \snac\data\Term($data["entryType"]);
        else
            $this->entryType = null;

        if (isset($data["link"]))
            $this->link = $data["link"];
        else
            $this->link = null;

        if (isset($data["role"]))
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
}
