<?php
/**
 * Contributor File
 *
 * Contains the data class for the contributors to names
 * 
 * License:
 *
 *
 * @author Tom Laudeman, Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\data;

/**
 * Contributor Class
 *
 *  See the abstract parent class for common methods setDBInfo() and getDBInfo().
 * 
 * Stores the contributor name (string) and type (a Term object)
 * 
 * @author Tom Laudeman, Robbie Hott
 *
 */
class Contributor extends AbstractData {

    /**
     * @var \snac\data\Term Script, a controlled vocabulary term object.
     *
     * From EAC-CPF tag(s):
     * vocabulary id for strings:
     * nameEntry/alternativeForm
     * nameEntry/authorizedForm
     *
     */
    private $type = null;

    /**
     * @var string Name of the contributor. A simple string.
     */
    private $name = null;


    /**
     * Return our data type. Seems like something that would be a property in AbstractData, and we would set
     * it in our constructor. This is sort of an experiment. Other objects don't (yet?) have this.
     *
     * The alternative is having test values for $data['dataType'] hard coded in at least two places:
     * toArray() and fromArray()
     */
    protected function dataType()
    {
        return "Contributor";
    }
    /**
     * Get the type controlled vocab
     *
     * @return \snac\data\Term Script controlled vocabulary term
     * 
     */ 
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the type controlled vocab
     *
     * @param \snac\data\Term $type Script controlled vocabulary term
     * 
     */ 
    public function setType(\snac\data\Term $type)
    {
        $this->type = $type;
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
            "dataType" => $this->dataType(),
            "type" => $this->type == null ? null : $this->type->toArray($shorten),
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
        if (!isset($data["dataType"]) || $data["dataType"] != $this->dataType())
            return false;

        parent::fromArray($data);

        if (isset($data["type"]))
            $this->type = new Term($data["type"]);
        else
            $this->type = null;

        if (isset($data["name"]))
            $this->name = $data["name"];
        else
            $this->name = null;

        return true;
    }
}
