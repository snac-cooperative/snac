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
class ConstellationRelation {

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
     * @var string Content of the relation
     */
    private $content = null;

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
}