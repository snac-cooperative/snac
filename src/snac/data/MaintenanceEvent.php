<?php

namespace snac\data;

class MaintenanceEvent {

    private $eventType;

    private $eventDateTime;

    private $agentType;

    private $agent;

    private $eventDescription;

    public function setEventType($eventType) {

        $this->eventType = $eventType;
    }

    public function setEventDateTime($eventDateTime) {

        $this->eventDateTime = $eventDateTime;
    }

    public function setAgentType($agentType) {

        $this->agentType = $agentType;
    }

    public function setAgent($agent) {

        $this->agent = $agent;
    }

    public function setEventDescription($eventDescription) {

        $this->eventDescription = $eventDescription;
    }
}