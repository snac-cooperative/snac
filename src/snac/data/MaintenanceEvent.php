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
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
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
class MaintenanceEvent {

    /**
     *
     * @var string Event type
     */
    private $eventType;

    /**
     *
     * @var string Date and Time string of the event
     */
    private $eventDateTime;

    /**
     *
     * @var string Type of the agent performing the event
     */
    private $agentType;

    /**
     *
     * @var string Agent that performed the event
     */
    private $agent;

    /**
     *
     * @var string Description of the event
     */
    private $eventDescription;

    /**
     * Set the event type.
     * 
     * @param string $eventType Event type
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
     * Set the agent type.
     * 
     * @param string $agentType Agent type
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
}