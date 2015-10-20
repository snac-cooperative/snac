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
 * Resource Relationship
 *
 * Data storage class for relationships of an Identity Constellation to an external Resource.
 *
 * @author Robbie Hott
 *        
 */
class ResourceRelation {

    /**
     *
     * @var string Document type
     */
    private $documentType = null;

    /**
     *
     * @var string Link type
     */
    private $linkType = null;

    /**
     *
     * @var string Link to external resource
     */
    private $link = null;

    /**
     *
     * @var string Role in of the relation
     */
    private $role = null;

    /**
     *
     * @var string Content in the relation
     */
    private $content = null;

    /**
     *
     * @var string XML source of the resource relation
     */
    private $source = null;

    /**
     *
     * @var string Note attached to relation
     */
    private $note = null;

    /**
     * Set the document type for this relation
     *
     * @param string $type Document type
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
     * @param string $type Link type
     */
    public function setLinkType($type) {

        $this->linkType = $type;
    }

    /**
     * Set the role of this resource relation
     *
     * @param string $role Relation role
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
}