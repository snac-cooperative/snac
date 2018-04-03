<?php

/**
 * Assertion File
 *
 * Contains the information about assertions in the system.
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
 * Assertion Class
 *
 * Storage class for information about an assertion (not-same).
 *
 * @author Robbie Hott
 *
 */
class Assertion extends AbstractData {

    /**
     * @var \snac\data\User The User who made the assertion
     */
    private $user;

    /**
     * @var string The Assertion type
     */
    private $type;

    /**
     * @var string The Assertion string
     */
    private $text;

    /**
     * @var \snac\data\Constellation[] Constellations in the assertion
     */
    private $constellations;

    /**
     * @var string Timestamp of the assertion
     */
    private $timestamp;

    /**
     * Constructor
     *
     * @param string[] $data Array object of User information
     */
    public function __construct($data = null) {
        $this->constellations = array();
        $this->setMaxDateCount(\snac\Config::$MAX_LIST_SIZE);
        if ($data != null)
            $this->fromArray($data);
        // always call the parent constructor
        parent::__construct($data);
    }


    /**
     * Set Timestamp
     *
     * @param string $timestamp Timestamp
     */
    public function setTimestamp($timestamp) {
        $this->timestamp = $timestamp;
    }

    /**
     * Add Constellation
     *
     * @param \snac\data\Constellation $constellation Constellation object to add to the assertion
     */
    public function addConstellation($constellation) {
        array_push($this->constellations, $constellation);
    }

    /**
     * Set Text
     *
     * @param string $text User-supplied text of the assertion (reasoning)
     */
    public function setText($text) {
        $this->text = $text;
    }

    /**
     * Set User
     *
     * @param \snac\data\User $user The User making the assertion
     */
    public function setUser($user) {
        $this->user = $user;
    }

    /**
     * Set Type
     *
     * @param string $type The type of the assertion being made
     */
    public function setType($type) {
        $this->type = $type;
    }

    /**
     * Get Type
     *
     * @return string The type of the assertion being made
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Get User
     *
     * @return \snac\data\User The user who made the assertion
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * Get Text
     *
     * @return string The assertion reasoning
     */
    public function getText() {
        return $this->text;
    }

    /**
     * Get Timestamp
     *
     * @return string The tiem the assertion was made
     */
    public function getTimestamp() {
        return $this->timestamp;
    }

    /**
     * Get Constellations
     *
     * @return \snac\data\Constellation[] The array of Constellations in this assertion
     */
    public function getConstellations() {
        return $this->constellations;
    }


    /**
     * Returns this object's data as an associative array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This objects data in array form
     */
    public function toArray($shorten = true) {

        $return = array (
                "text" => $this->text,
                "type" => $this->type,
                "timestamp" => $this->timestamp,
                "user" => $this->user==null?null:$this->user->toArray($shorten),
                "constellations" => array()
        );

        foreach ($this->constellations as $i => $v)
            $return["constellations"][$i] = $v->toArray($shorten);

        $return = array_merge($return, parent::toArray($shorten));

        // Shorten if necessary
        if ($shorten) {
            $return2 = array ();
            foreach ($return as $i => $v)
                if ($v != null && ! empty($v))
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

        parent::fromArray($data);

        foreach ($this->constellations as $i => $v)
            $return["constellations"][$i] = $v->toArray($shorten);

        if (isset($data["type"]))
            $this->type = $data["type"];
        else
            $this->type = null;

        if (isset($data["text"]))
            $this->text = $data["text"];
        else
            $this->text = null;

        if (isset($data["timestamp"]))
            $this->timestamp = $data["timestamp"];
        else
            $this->timestamp = null;

        if (isset($data["user"]) && $data['user'] != null)
            $this->user = new \snac\data\User($data["user"]);
        else
            $this->user = null;

        unset($this->constellations);
        $this->constellations = array();
        if (isset($data["constellations"])) {
            foreach ($data["constellations"] as $i => $entry) {
                if ($entry != null)
                    $this->constellations[$i] = new \snac\data\Constellation($entry);
            }
        }

        return true;
    }

    /**
     * Serialization Method
     *
     * Allows PHP's serialize() method to correctly serialize the object.
     *
     * {@inheritDoc}
     *
     * @return string The serialized form of this object
     */
    public function serialize() {
        return $this->toJSON();
    }

    /**
     * Un-Serialization Method
     *
     * Allows PHP's unserialize() method to correctly unserialize the object.
     *
     * {@inheritDoc}
     *
     * @param string $data the serialized object
     */
    public function unserialize($data) {
        $this->fromJSON($data);
    }

}
