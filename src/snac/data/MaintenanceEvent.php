<?php

/**
 * Maintenance Event File
 *
 * Contains the information about an individual maintenance event.
 *
 * License:
 *
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\data;

/**
 * Maintenance Event Class
 *
 * Data storage class for maintenance events on an identity constellation.
 *
 * @author Robbie Hott
 *
 */
class MaintenanceEvent extends AbstractData {

    /**
     * Event type
     *
     * From EAC-CPF tag(s):
     *
     * * maintenanceEvent/eventType
     *
     * @var \snac\data\Term Event type
     */
    private $eventType;

    /**
     * Human-Readable Time
     *
     * From EAC-CPF tag(s):
     *
     * * maintenanceEvent/eventDateTime
     *
     * @var string Date and Time string of the event
     */
    private $eventDateTime;

    /**
     * Standard Date time
     *
     * From EAC-CPF tag(s):
     *
     * * maintenanceEvent/eventDateTime/@standardDateTime
     *
     * @var string Standardized date time of the event
     *
     */
    private $standardDateTime;

    /**
     * Agent Type
     *
     * From EAC-CPF tag(s):
     *
     * * maintenanceEvent/agentType
     *
     * @var \snac\data\Term Type of the agent performing the event
     */
    private $agentType;

    /**
     * Agent
     *
     * From EAC-CPF tag(s):
     *
     * * maintenanceEvent/agent
     *
     * @var string Agent that performed the event
     */
    private $agent;

    /**
     * Description
     *
     * From EAC-CPF tag(s):
     *
     * * maintenanceEvent/eventDescription
     *
     * @var string Description of the event
     */
    private $eventDescription;

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
     * Returns this object's data as an associative array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This objects data in array form
     */
    public function toArray($shorten = true) {
        $return = array(
            "dataType" => "MaintenanceEvent",
            "eventType" => $this->eventType == null ? null : $this->eventType->toArray($shorten),
            "eventDateTime" => $this->eventDateTime,
            "standardDateTime" => $this->standardDateTime,
            "agentType" => $this->agentType == null ? null : $this->agentType->toArray($shorten),
            "agent" => $this->agent,
            "eventDescription" => $this->eventDescription
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
        if (!isset($data["dataType"]) || $data["dataType"] != "MaintenanceEvent")
            return false;

        parent::fromArray($data);

        if (isset($data["eventType"]) && $data["eventType"] != null)
            $this->eventType = new \snac\data\Term($data["eventType"]);
        else
            $this->eventType = null;

        if (isset($data["eventDateTime"]))
            $this->eventDateTime = $data["eventDateTime"];
        else
            $this->eventDateTime = null;

        if (isset($data["standardDateTime"]))
            $this->standardDateTime = $data["standardDateTime"];
        else
            $this->standardDateTime = null;

        if (isset($data["agent"]))
            $this->agent = $data["agent"];
        else
            $this->agent = null;

        if (isset($data["agentType"]) && $data["agentType"] != null)
            $this->agentType = new \snac\data\Term($data["agentType"]);
        else
            $this->agentType = null;

        if (isset($data["eventDescription"]))
            $this->eventDescription = $data["eventDescription"];
        else
            $this->eventDescription = null;

        return true;
    }

    /**
     * Set the event type.
     *
     * @param \snac\data\Term $eventType Event type
     */
    public function setEventType($eventType) {

        $this->eventType = $eventType;
    }

    /**
     * Set the date and time of the event.
     *
     * @param string $eventDateTime DateTime string of the event
     */
    public function setEventDateTime($eventDateTime) {

        $this->eventDateTime = $eventDateTime;
    }

    /**
     * Set the standardized date and time of the event.
     *
     * @param string $eventDateTime DateTime string of the event
     */
    public function setStandardDateTime($eventDateTime) {

        $this->standardDateTime = $eventDateTime;
    }

    /**
     * Set the agent type.
     *
     * @param \snac\data\Term $agentType Agent type
     */
    public function setAgentType($agentType) {

        $this->agentType = $agentType;
    }

    /**
     * Set the agent that performed the event.
     *
     * @param string $agent Agent
     */
    public function setAgent($agent) {

        $this->agent = $agent;
    }

    /**
     * Set the event description.
     *
     * @param string $eventDescription Description of the event
     */
    public function setEventDescription($eventDescription) {

        $this->eventDescription = $eventDescription;
    }

    /**
     * Get the event type
     *
     * @return \snac\data\Term event type
     */
    public function getEventType() {
        return $this->eventType;
    }

    /**
     * Get the event date time
     *
     * @return string date and time string
     */
    public function getEventDateTime() {
        return $this->eventDateTime;
    }

    /**
     * Get the event description
     *
     * @return string description
     */
    public function getEventDescription() {
        return $this->eventDescription;
    }

    /**
     * Get the agent type
     *
     * @return \snac\data\Term agent type
     */
    public function getAgentType() {
        return $this->agentType;
    }

    /**
     * Get the agent
     *
     * @return string agent name
     */
    public function getAgent() {
        return $this->agent;
    }

    /**
     * Get the standard date and time
     *
     * @return string standardized date and time
     */
    public function getStandardDateTime() {
        return $this->standardDateTime;
    }

    /**
     *
     * {@inheritDoc}
     *
     * @param \snac\data\Language $other Other object
     * @param boolean $strict optional Whether or not to check id, version, and operation
     * @param boolean $checkSubcomponents optional Whether or not to check SNACControlMetadata, nameEntries contributors & components
     * @return boolean true on equality, false otherwise
     *
     * @see \snac\data\AbstractData::equals()
     */
    public function equals($other, $strict = true, $checkSubcomponents = true) {

        if ($other == null || ! ($other instanceof \snac\data\MaintenanceEvent))
            return false;

        if (! parent::equals($other, $strict, $checkSubcomponents))
            return false;

        if ($this->getEventDateTime() != $other->getEventDateTime())
            return false;
        if ($this->getStandardDateTime() != $other->getStandardDateTime())
            return false;
        if ($this->getAgent() != $other->getAgent())
            return false;
        if ($this->getEventDescription() != $other->getEventDescription())
            return false;

        if (($this->getEventType() != null && ! $this->getEventType()->equals($other->getEventType())) ||
                 ($this->getEventType() == null && $other->getEventType() != null))
            return false;
        if (($this->getAgentType() != null && ! $this->getAgentType()->equals($other->getAgentType())) ||
                 ($this->getAgentType() == null && $other->getAgentType() != null))
            return false;

        return true;
    }
}
