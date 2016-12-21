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
     * Resource to which this is related
     * @var \snac\data\Resource The resource that this relation points to
     */
    private $resource = null;

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
     * Constructor
     *
     * @param string[] $data optional An associative array representation of this object to create
     */
    public function __construct($data = null) {
        $this->setMaxDateCount(0);

        // always call the parent constructor
        parent::__construct($data);
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
     * Get the resource of this relation
     *
     * @return \snac\data\Resource The resource pointed to by this relation
     *
     */
    function getResource()
    {
        return $this->resource;
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
            "resource" => $this->resource == null ? null : $this->resource->toArray($shorten),
            "role" => $this->role == null ? null : $this->role->toArray($shorten),
            "content" => $this->content,
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

        if (isset($data["resource"]) && $data["resource"] != null)
            $this->resource = new \snac\data\Resource($data["resource"]);
        else
            $this->resource = null;

        if (isset($data["role"]) && $data["role"] != null)
            $this->role = new \snac\data\Term($data["role"]);
        else
            $this->role = null;

        if (isset($data["content"]))
            $this->content = $data["content"];
        else
            $this->content = null;

        if (isset($data["note"]))
            $this->note = $data["note"];
        else
            $this->note = null;

        return true;
    }


    /**
     * Set the resource for this relation
     *
     * @param \snac\data\Resource $resource Resource to which this relation points
     */
    public function setResource($resource) {

        $this->resource = $resource;
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

        if ($this->getContent() != $other->getContent())
            return false;
        if ($this->getNote() != $other->getNote())
            return false;

        if (($this->getResource() != null && ! $this->getResource()->equals($other->getResource())) ||
                 ($this->getResource() == null && $other->getResource() != null))
            return false;
        if (($this->getRole() != null && ! $this->getRole()->equals($other->getRole())) ||
                 ($this->getRole() == null && $other->getRole() != null))
            return false;

        return true;
    }
}
