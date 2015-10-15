<?php


namespace snac\data;

class Constellation {
  
    private $ark = null;
    private $entityType = null;
    private $otherRecordIDs = null;
    private $maintenanceStatus = null;
    private $maintenanceAgency = null;
    private $maintenanceEvents = null;
    private $sources = null;
    private $conventionDeclaration = null;    
    private $nameEntries = null;
    private $occupations = null;
    private $biogHists = null;
    
    public function __construct() {
        $this->otherRecordIDs = array();
        $this->sources = array();
        $this->maintenanceEvents = array();
        $this->nameEntries = array();
        $this->biogHists = array();
        $this->occupations = array();
    }
    
    public function setArkID($ark) {
        $this->ark = $ark;
    }
    
    public function setEntityType($type) {
        $this->entityType = $type;
    }
    
    public function addOtherRecordID($type, $link) {
        array_push($this->otherRecordIDs, array("type" => $type, "href" => $link));
    }
    
    public function setMaintenanceStatus($status) {
        $this->maintenanceStatus = $status;
        
    }
    public function setMaintenanceAgency($agency) {
        $this->maintenanceAgency = $agency;
    }
    
    public function addSource($type, $link) {
        array_push($this->sources, array("type"=>$type, "href"=>$link));
    }
    
    public function addMaintenanceEvent($event) {
        array_push($this->maintenanceEvents, $event);
    }
    
    public function setConventionDeclaration($declaration) {
        $this->conventionDeclaration = $declaration;
    }
    
    public function addNameEntry($nameEntry) {
        array_push($this->nameEntries, $nameEntry);
    }
    
    public function addBiogHist($biog) {
        array_push($this->biogHists, $biog);
    }

    public function addOccupation($occupation) {
        array_push($this->occupations, $occupation);
    }
    
    public function setLanguage($code, $value) {
        //TODO
    }
    
    public function setScript($code, $value) {
        //TODO
    }
    
    public function setLanguageUsed($code, $value) {
        //TODO
    }
    
    public function setScriptUsed($code, $value) {
        //TODO
    }
    
}