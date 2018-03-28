<?php
/**
 * Contributor File
 *
 * Contains the data class for the contributors to names
 *
 * License:
 *
 *
 * @author Tom Laudeman
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\data;

/**
 * Contributor Class
 *
 * Stores the contributor name (string) and type (a Term object)
 *
 * @author Tom Laudeman
 * @author Robbie Hott
 *
 */
class Contributor extends AbstractData {

    /**
     * @var \snac\data\Term Type of the contributor
     *
     *
     * From EAC-CPF tag(s):
     * vocabulary id for strings:
     * nameEntry/alternativeForm
     * nameEntry/authorizedForm
     *
     */
    private $type = null;

    /**
     * @var \snac\data\Term Rule the contributor used to define this name entry
     *
     */
    private $rule = null;

    /**
     * @var string Name of the contributor.
     *
     * A simple string.
     */
    private $name = null;

    /**
     * Constructor
     *
     * @param string[] $data optional An array of data to pre-fill this object
     */
    public function __construct($data = null) {
        $this->setMaxDateCount(0);
        parent::__construct($data);
    }

    /**
     * Get the type controlled vocab
     *
     * @return \snac\data\Term Type controlled vocabulary term
     *
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the type controlled vocab
     *
     * @param \snac\data\Term $type Type controlled vocabulary term
     *
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Get the rule controlled vocab
     *
     * @return \snac\data\Term Rule controlled vocabulary term
     *
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * Set the rule controlled vocab
     *
     * @param \snac\data\Term $rule Rule controlled vocabulary term
     *
     */
    public function setRule($rule)
    {
        $this->rule = $rule;
    }

    /**
     * Get the name
     *
     * @return string Name of the contributor
     *
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the name
     *
     * @param string $name Name of the contributor
     *
     */
    public function setName($name)
    {
        $this->name = $name;
    }


    /**
     * Returns this object's data as an associative array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This objects data in array form
     */
    public function toArray($shorten = true) {
        $return = array(
            "dataType" => "Contributor",
            "type" => $this->type == null ? null : $this->type->toArray($shorten),
            "rule" => $this->rule == null ? null : $this->rule->toArray($shorten),
            "name" => $this->name
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
        if (!isset($data["dataType"]) || $data["dataType"] != "Contributor")
            return false;

        parent::fromArray($data);

        if (isset($data["type"]) && $data["type"] != null)
            $this->type = new Term($data["type"]);
        else
            $this->type = null;

        if (isset($data["rule"]) && $data["rule"] != null)
            $this->rule = new Term($data["rule"]);
        else
            $this->rule = null;

        if (isset($data["name"]))
            $this->name = $data["name"];
        else
            $this->name = null;

        return true;
    }

    /**
     *
     * {@inheritDoc}
     *
     * @param \snac\data\Contributor $other Other object
     * @param boolean $strict optional Whether or not to check id, version, and operation
     * @param boolean $checkSubcomponents optional Whether or not to check SNACControlMetadata, nameEntries contributors & components
     * @return boolean true on equality, false otherwise
     *
     * @see \snac\data\AbstractData::equals()
     */
    public function equals($other, $strict = true, $checkSubcomponents = true) {

        if ($other == null || !($other instanceof \snac\data\Contributor))
            return false;

        if (!parent::equals($other, $strict, $checkSubcomponents))
            return false;

        if ($this->getName() != $other->getName())
            return false;

        if (($this->getType() != null && !($this->getType()->equals($other->getType()))) ||
                ($this->getType() == null && $other->getType() != null))
            return false;

        return true;
    }
}
