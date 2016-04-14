<?php

/**
 * Identity Constellation File
 *
 * Contains the constellation information for an entire entity.
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
 * Identity Constellation
 *
 * Stores all the information related to an identity constellation.  This is the root of an identity
 * constellation, and has fields and methods to store all parts of the constellation.  Any of them may be left
 * null, if they are unused.
 *
 * @author Robbie Hott
 *        
 */
class Constellation extends AbstractData {

    /**
     * ARK ID
     * 
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/control/recordId
     * 
     * @var string ARK identifier
     */
    private $ark = null;

    /**
     * Entity Type
     * 
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/cpfDescription/identity/entityType
     * 
     * @var \snac\data\Term Entity type
     */
    private $entityType = null;

    /**
     * Other Record ID List
     * 
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/control/otherRecordId
     * * eac-cpf/cpfDescription/identity/entityID
     * 
     * @var \snac\data\SameAs[] Other record IDs by which this constellation may be known
     */
    private $otherRecordIDs = null;

    /**
     * Maintenace Status
     * 
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/control/maintenanceStatus
     * 
     * @var \snac\data\Term Current maintenance status
     */
    private $maintenanceStatus = null;

    /**
     * Maintenance Agency
     * 
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/control/maintenanceAgency/agencyName
     * 
     * @var string Latest maintenance agency
     */
    private $maintenanceAgency = null;

    /**
     * Maintenance Event List
     *
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/control/maintenanceHistory/maintenanceEvent/*
     * 
     * @var \snac\data\MaintenanceEvent[] List of maintenance events performed on this constellation
     */
    private $maintenanceEvents = null;

    /**
     * Source list
     * 
     * From EAC-CPF tag(s):
     * 
     * * /eac-cpf/control/sources/source/@type
     * * /eac-cpf/control/sources/source/@href
     * 
     * @var \snac\data\Source[] List of sources
     */
    private $sources = null;
    
    /**
     * Legal Status List
     * 
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/cpfDescription/description/legalStatus/term
     * * eac-cpf/cpfDescription/description/legalStatus/@vocabularySource
     * 
     *
     * @var \snac\data\LegalStatus[] List of legal statuses
     */
    private $legalStatuses = null;

    /**
     * Convention Declaration List
     * 
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/control/conventionDeclaration
     * 
     * @var \snac\data\ConventionDeclaration[] Convention declarations
     */
    private $conventionDeclarations = null;
    
    /**
     * Languages Used List
     * 
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/cpfDescription/description/languageUsed/language
     * * eac-cpf/cpfDescription/description/languageUsed/language/@languageCode
     * * eac-cpf/cpfDescription/description/languageUsed/script
     * * eac-cpf/cpfDescription/description/languageUsed/script/@scriptCode
     * 
     * @var \snac\data\Language[] Languages used by the identity described
     */
    private $languagesUsed = null;

    /**
     * Name Entry List
     * 
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/cpfDescription/identity/nameEntry
     * 
     * @var \snac\data\NameEntry[] List of name entries for this constellation
     */
    private $nameEntries = null;

    /**
     * Occupation List
     *
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/cpfDescription/description/occupation/*
     * 
     * @var \snac\data\Occupation[] List of occupations
     */
    private $occupations = null;

    /**
     * BiogHist List
     * 
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/cpfDescription/description/biogHist
     * 
     * @var \snac\data\BiogHist[] BiogHist entries for this constellation (in XML strings)
     */
    private $biogHists = null;

    /**
     * Constellation Relation List
     *
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/cpfDescription/relations/cpfRelation/*
     * 
     * @var \snac\data\ConstellationRelation[] Constellation relations
     */
    private $relations = null;

    /**
     * Resource Relation List
     *
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/cpfDescription/relations/resourceRelation/*
     * 
     * @var \snac\data\ResourceRelation[] Resource relations
     */
    private $resourceRelations = null;

    /**
     * Function list
     *
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/cpfDescription/description/function/*
     * 
     * @var \snac\data\SNACFunction[] Functions
     */
    private $functions = null;

    /**
     * Place list
     *
     * A list of Place objects. 
     *
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/cpfDescription/description/place/*
     * 
     * @var \snac\data\Place[] Places
     */
    private $places = null;
    
    /**
     * Subject List
     * 
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/cpfDescription/description/localDescription/@localType=AssociatedSubject/term
     * 
     * @var \snac\data\Subject[] Subjects
     */
    private $subjects = null;
    
    /**
     * Nationality List
     * 
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/cpfDescription/description/localDescription/@localType=nationalityOfEntity/term
     * 
     * @var \snac\data\Nationality[] nationalities of this entity
     */
    private $nationalities = null;
    
    /**
     * Gender List
     * 
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/cpfDescription/description/localDescription/@localType=gender/term
     * 
     * @var \snac\data\Gender[] Gender
     */
    private $genders = null;
    
    /**
     * General Contexts List
     * 
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/cpfDescription/description/generalContext
     * 
     * @var \snac\data\GeneralContext[] General Contexts
     */
    private $generalContexts = null;
    
    /**
     * Structure or Genealogies List
     * 
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/cpfDescription/description/structureOrGenealogy
     * 
     * @var \snac\data\StructureOrGenealogy[] Structure Or Genealogy information
     */
    private $structureOrGenealogies = null;
    
    /**
     * Mandate List
     * 
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/cpfDescription/description/mandate
     * 
     * @var \snac\data\Mandate[] Mandates
     */
    private $mandates = null;
    
    /**
     * Status of the constellation
     * 
     * The status of the constellation in the system.  This allows the system to note whether the
     * constellation is editable by a given user.
     * 
     * @var string|null Status of the constellation
     */
    private $status = null;

    /**
     * Constructor for the class.
     *
     * @param string[] $data A list of data suitable for fromArray(). This exists for use by internal code to
     * send objects around the system, not for generally creating a new object. Normal use is to call the
     * constructor without an argument, get an empty class and use the setters to fill in the properties.
     *
     * @return Constellation object
     * 
     */
    public function __construct($data = null) {
        $this->setMaxDateCount(\snac\Config::$MAX_LIST_SIZE);
        if ($data == null) {
            $this->entityType = null;
            $this->otherRecordIDs = array ();
            $this->sources = array ();
            $this->maintenanceEvents = array ();
            $this->nameEntries = array ();
            $this->biogHists = array ();
            $this->occupations = array ();
            $this->relations = array ();
            $this->resourceRelations = array ();
            $this->functions = array ();
            $this->places = array ();
            $this->subjects = array();
            $this->legalStatuses = array();
            $this->genders = array();
            $this->nationalities = array();
            $this->languagesUsed = array();
            $this->conventionDeclarations = array();
            $this->generalContexts = array();
            $this->structureOrGenealogies = array();
            $this->mandates = array();
        }
        // always call the parent constructor
        parent::__construct($data);
    }

    /**
     * Get the ARK identifier URI
     * 
     * @return string ARK identifier
     *
     */
    public function getArk()
    {
        return $this->ark;
    }

    /**
     * Get the entity type
     *
     * @return \snac\data\Term Entity type
     *
     */
    public function getEntityType()
    {
        return $this->entityType;
    }

    /**
     * Get the other record IDs
     *
     * @return \snac\data\SameAs[] Other record IDs by which this constellation may be known
     *
     */
    public function getOtherRecordIDs()
    {
        return $this->otherRecordIDs;
    }

    /**
     * Get the maintenance Status
     * 
     * @return \snac\data\Term Current maintenance status
     *
     */
    public function getMaintenanceStatus()
    {
        return $this->maintenanceStatus;
    }

    /**
     * Get the maintenance agency
     *
     * @return string Latest maintenance agency
     *
     */
    public function getMaintenanceAgency()
    {
        return $this->maintenanceAgency;
    }

    /**
     * Get the list of maintenance events
     *
     * @return \snac\data\MaintenanceEvent[] List of maintenance events performed on this constellation
     *
     */
    public function getMaintenanceEvents()
    {
        return $this->maintenanceEvents;
    }

    /**
     * Get the list of sources
     * 
     * @return \snac\data\Source[] List of sources
     *
     */
    public function getSources()
    {
        return $this->sources;
    }

    /**
     * Get the list of legal statuses
     * 
     * @return \snac\data\LegalStatus[] List of legal statuses
     *
     */
    public function getLegalStatuses()
    {
        return $this->legalStatuses;
    }

    /**
     * Get the convention declarations
     *
     * @return \snac\data\ConventionDeclaration[] Convention declarations
     *
     */
    public function getConventionDeclarations()
    {
        return $this->conventionDeclarations;
    }

    /**
     * Get the Languages Used
     *
     * @return \snac\data\Language[] Languages and scripts used by the identity described
     *
     */
    public function getLanguagesUsed()
    {
        return $this->languagesUsed;
    }

    /**
     * Alias function for getLanguagesUsed(). 
     * 
     * Get the Languages Used. Called in DBUtil.
     *
     * @return \snac\data\Language[] Languages and scripts used by the identity described
     * @deprecated
     */
    public function getLanguage()
    {
        return $this->getLanguagesUsed();
    }



    /**
     * Get the name entries
     *
     * @return \snac\data\NameEntry[] List of name entries for this constellation
     *
     */
    public function getNameEntries()
    {
        return $this->nameEntries;
    }

    /**
     * Get the preferred name
     *
     * Gets the nameEntry in this constellation with the highest score, or the
     * first one if the scores are equal, or null if there is no name entry
     *
     * @return \snac\data\NameEntry Preferred name entry for this constellation
     *
     */
    public function getPreferredNameEntry()
    {
        if (count($this->nameEntries) < 1)
            return null;

        $max = 0;
        $id = 0;
        foreach ($this->nameEntries as $i => $entry) {
            if ($entry->getPreferenceScore() > $max) {
                $max = $entry->getPreferenceScore();
                $id = $i;
            }
        }
        return $this->nameEntries[$id];
    }

    /**
     * Get the occupations
     * 
     * @return \snac\data\Occupation[] List of occupations
     *
     */
    public function getOccupations()
    {
        return $this->occupations;
    }

    /**
     * Get the list of BiogHists
     * Each BiogHist is presumed to be a translation in a
     * specific language.
     *
     * @return \snac\data\BiogHist[] An array of BiogHist ordered by language 3 letter code, or an empty list
     * if no BiogHist exists for this Constellation
     */
    public function getBiogHistList()
    {
        return $this->biogHists;
    }


    /**
     * Get the BiogHist
     *
     * This will by default get the first BiogHist for the entity.  If another
     * language is desired, it may be passed as a parameter.  In that case,
     * the biogHist will be given for that language.  If no biogHist exists
     * for that language, the first will be returned.
     *
     * @param \snac\data\Language $language optional Language of the desired BiogHist 
     *
     * @return \snac\data\BiogHist The desired BiogHist for this language, the first
     * BiogHist, or null if no BiogHist exists for this Constellation
     */
    public function getBiogHist($language = null)
    {
        if (count($this->biogHists) > 0) {
            if ($language == null) {
                // No language, so return the first
                return $this->biogHists[0];
            } else {
                // We have a language.  Start from the end, return matching language or first
                // entry
                $i = count($this->biogHists) - 1;
                for (; $i >= 0; $i--) {
                    // If languages match, then break and return this biogHist.
                    if ($this->biogHists[$i]->getLanguage()->getLanguage()->getID() == 
                        $language->getLanguage()->getID())
                        break;
                }
                // Will return either the appropriate biogHist or the biogHist[0]
                return $this->biogHists[$i];
            }
        }
        return null;
    }

    /**
     * Get the constellation relations
     *
     * @return \snac\data\ConstellationRelation[] Constellation relations
     *
     */
    public function getRelations()
    {
        return $this->relations;
    }

    /**
     * Get the resource relations
     *
     * @var \snac\data\ResourceRelation[] Resource relations
     */
    public function getResourceRelations()
    {
        return $this->resourceRelations;
    }

    /**
     * Get the Functions
     *
     * @return \snac\data\SNACFunction[] Functions
     *
     */
    public function getFunctions()
    {
        return $this->functions;
    }

    /**
     * Get the places
     * 
     * Get the places associated with this identity constellation
     *
     * @return \snac\data\Place[] Places
     *
     */
    public function getPlaces()
    {
        return $this->places;
    }

    /**
     * Get the subjects
     *
     * @return \snac\data\Subject[] Subjects
     *
     */
    public function getSubjects()
    {
        return $this->subjects;
    }

    /**
     * Get the nationality
     * 
     * Get the first nationality associated with this constellation.  If there are more than one,
     * this will return the first.
     *
     * @return \snac\data\Nationality nationality
     *
     */
    public function getNationality()
    {
        if (count($this->nationalities) > 0)
            return $this->nationalities[0];
        else
            return null;
    }

    /**
     * Get all nationalities
     *
     * @return \snac\data\Nationality[] nationalities
     *
     */
    public function getNationalities()
    {
        return $this->nationalities;
    }

    /**
     * Get the gender
     * If there are multiple, this will return the first gender in the list.
     *
     * @return \snac\data\Gender First Gender stored for this constellation
     *
     */
    public function getGender()
    {
        if (count($this->genders) > 0)
            return $this->genders[0];
        else
            return null;
    }

    /**
     * Get all genders
     *
     * @return \snac\data\Gender[] all genders
     *
     */
    public function getGenders()
    {
        return $this->genders;
    }

    /**
     * Get all the general contexts
     *
     * @return \snac\data\GeneralContext[] General Contexts
     *
     */
    public function getGeneralContexts()
    {
        return $this->generalContexts;
    }

    /**
     * Get the structureOrGenealogies
     *
     * @return \snac\data\StructureOrGenealogy[] list of Structure Or Genealogy information
     *
     */
    public function getStructureOrGenealogies()
    {
        return $this->structureOrGenealogies;
    }

    /**
     * Get the mandates
     *
     * @return \snac\data\Mandate[] list of Mandates
     *
     */
    public function getMandates()
    {
        return $this->mandates;
    }

    /**
     * Get the Status
     *
     * Get the status for this Constellation object
     *
     * @return string|NULL Status if one is set, or null if the status is empty
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * Returns this object's data as an associative array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This objects data in array form
     */
    public function toArray($shorten = true) {
        $return = array(
            "dataType" => "Constellation",
            "status" => $this->status,
            "ark" => $this->ark,
            "entityType" => $this->entityType == null ? null : $this->entityType->toArray($shorten),
            "otherRecordIDs" => array(), 
            "maintenanceStatus" => $this->maintenanceStatus == null ? null : $this->maintenanceStatus->toArray($shorten),
            "maintenanceAgency" => $this->maintenanceAgency,
            "maintenanceEvents" => array(),
            "sources" => array(),
            "legalStatuses" => array(), 
            "conventionDeclarations" => array(),
            "languagesUsed" => array(),
            "nameEntries" => array(),
            "occupations" => array(),
            "biogHists" => array(),
            "relations" => array(),
            "resourceRelations" => array(),
            "functions" => array(),
            "places" => array(),
            "subjects" => array(),
            "nationalities" => array(),
            "genders" => array(),
            "generalContexts" => array(),
            "structureOrGenealogies" => array(),
            "mandates" => array()
        );
        
        foreach ($this->mandates as $i => $v)
            $return["mandates"][$i] = $v->toArray($shorten);

        foreach ($this->structureOrGenealogies as $i => $v)
            $return["structureOrGenealogies"][$i] = $v->toArray($shorten);

        foreach ($this->generalContexts as $i => $v)
            $return["generalContexts"][$i] = $v->toArray($shorten);

        foreach ($this->biogHists as $i => $v)
            $return["biogHists"][$i] = $v->toArray($shorten);

        foreach ($this->conventionDeclarations as $i => $v)
            $return["conventionDeclarations"][$i] = $v->toArray($shorten);

        foreach ($this->nationalities as $i => $v)
            $return["nationalities"][$i] = $v->toArray($shorten);

        foreach ($this->otherRecordIDs as $i => $v)
            $return["otherRecordIDs"][$i] = $v->toArray($shorten);

        foreach ($this->maintenanceEvents as $i => $v)
            $return["maintenanceEvents"][$i] = $v->toArray($shorten);
        
        foreach ($this->languagesUsed as $i => $v)
            $return["languagesUsed"][$i] = $v->toArray($shorten);

        foreach ($this->legalStatuses as $i => $v)
            $return["legalStatuses"][$i] = $v->toArray($shorten);

        foreach ($this->sources as $i => $v)
            $return["sources"][$i] = $v->toArray($shorten);

        foreach ($this->genders as $i => $v)
            $return["genders"][$i] = $v->toArray($shorten);

        foreach ($this->nameEntries as $i => $v)
            $return["nameEntries"][$i] = $v->toArray($shorten);

        foreach ($this->occupations as $i => $v)
            $return["occupations"][$i] = $v->toArray($shorten);

        foreach ($this->relations as $i => $v)
            $return["relations"][$i] = $v->toArray($shorten);

        foreach ($this->resourceRelations as $i => $v)
            $return["resourceRelations"][$i] = $v->toArray($shorten);

        foreach ($this->functions as $i => $v)
            $return["functions"][$i] = $v->toArray($shorten);

        foreach ($this->places as $i => $v)
            $return["places"][$i] = $v->toArray($shorten);
        
        foreach ($this->subjects as $i => $v)
            $return["subjects"][$i] = $v->toArray($shorten);
            
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
     * Need docs about the keys in the array passed to this. This is called by the AbstractData class
     * constructor which was called by this class' constructor via parent::__construct($data);
     * 
     * @param string[][] $data This objects data in array form
     * @return boolean true on success, false on failure
     */
    public function fromArray($data) {
        if (!isset($data["dataType"]) || $data["dataType"] != "Constellation")
            return false;

        parent::fromArray($data);

        unset($this->status);
        if (isset($data["status"]))
            $this->status = $data["status"];
        else
            $this->status = null;
            
        unset($this->ark);
        if (isset($data["ark"]))
            $this->ark = $data["ark"];
        else
            $this->ark = null;

        unset($this->entityType);
        if (isset($data["entityType"]) && $data["entityType"] != null)
            $this->entityType = new \snac\data\Term($data["entityType"]);
        else
            $this->entityType = null;

        unset($this->otherRecordIDs);
        $this->otherRecordIDs = array();
        if (isset($data["otherRecordIDs"]))
            foreach ($data["otherRecordIDs"] as $i => $entry)
                if ($entry != null)
                    $this->otherRecordIDs[$i] = new \snac\data\SameAs($entry);

        unset($this->maintenanceStatus);
        if (isset($data["maintenanceStatus"]) && $data["maintenanceStatus"] != null)
            $this->maintenanceStatus = new \snac\data\Term($data["maintenanceStatus"]);
        else
            $this->maintenanceStatus = null;

        unset($this->maintenanceAgency);
        if (isset($data["maintenanceAgency"]))
            $this->maintenanceAgency = $data["maintenanceAgency"];
        else
            $this->maintenanceAgency = null;

        unset($this->sources);
        $this->sources = array();
        if (isset($data["sources"]))
            foreach ($data["sources"] as $i => $entry)
                if ($entry != null)
                    $this->sources[$i] = new Source($entry);

        unset($this->legalStatuses);
        $this->legalStatuses = array();
        if (isset($data["legalStatuses"]))
            foreach ($data["legalStatuses"] as $i => $entry)
                if ($entry != null)
                    $this->legalStatuses[$i] = new LegalStatus($entry);

        unset($this->conventionDeclarations);
        $this->conventionDeclarations = array();
        if (isset($data["conventionDeclarations"]))
            foreach ($data["conventionDeclarations"] as $i => $entry)
                if ($entry != null)
                    $this->conventionDeclarations[$i] = new \snac\data\ConventionDeclaration($entry);

        unset($this->languagesUsed);
        $this->languagesUsed = array();
        if (isset($data["languagesUsed"]))
            foreach ($data["languagesUsed"] as $i => $entry)
                if ($entry != null)
                    $this->languagesUsed[$i] = new Language($entry);

        unset($this->biogHists);
        $this->biogHists = array();
        if (isset($data["biogHists"])) {
            foreach ($data["biogHists"] as $i => $entry) {
                if ($entry != null)
                    $this->biogHists[$i] = new BiogHist($entry);
            }
        }

        unset($this->subjects);
        $this->subjects = array();
        if (isset($data["subjects"]))
            foreach ($data["subjects"] as $i => $entry)
                if ($entry != null)
                    $this->subjects[$i] = new Subject($entry);

        unset($this->nationalities);
        $this->nationalities = array();
        if (isset($data["nationalities"]))
            foreach ($data["nationalities"] as $i => $entry)
                if ($entry != null)
                    $this->nationalities[$i] = new Nationality($entry);

        unset($this->genders);
        $this->genders = array();
        if (isset($data["genders"]))
            foreach ($data["genders"] as $i => $entry)
                if ($entry != null)
                    $this->genders[$i] = new Gender($entry);

        unset($this->generalContexts);
        $this->generalContexts = array();
        if (isset($data["generalContexts"]))
            foreach ($data["generalContexts"] as $i => $entry)
                if ($entry != null)
                    $this->generalContexts[$i] = new GeneralContext($entry);

        unset($this->structureOrGenealogies);
        $this->structureOrGenealogies = array();
        if (isset($data["structureOrGenealogies"]))
            foreach ($data["structureOrGenealogies"] as $i => $entry)
                if ($entry != null)
                    $this->structureOrGenealogies[$i] = new StructureOrGenealogy($entry);

        unset($this->mandates);
        $this->mandates = array();
        if (isset($data["mandates"]))
            foreach ($data["mandates"] as $i => $entry)
                if ($entry != null)
                    $this->mandates[$i] = new Mandate($entry);

        unset($this->maintenanceEvents);
        $this->maintenanceEvents = array();
        if (isset($data["maintenanceEvents"])) {
            foreach ($data["maintenanceEvents"] as $i => $entry)
                if ($entry != null)
                    $this->maintenanceEvents[$i] = new MaintenanceEvent($entry);
        }

        unset($this->nameEntries);
        $this->nameEntries = array();
        if (isset($data["nameEntries"])) {
            foreach ($data["nameEntries"] as $i => $entry)
                if ($entry != null)
                    $this->nameEntries[$i] = new NameEntry($entry);
        }

        unset($this->occupations);
        $this->occupations = array();
        if (isset($data["occupations"])) {
            foreach ($data["occupations"] as $i => $entry)
                if ($entry != null)
                    $this->occupations[$i] = new Occupation($entry);
        }

        unset($this->relations);
        $this->relations = array();
        if (isset($data["relations"])) {
            foreach ($data["relations"] as $i => $entry)
                if ($entry != null)
                    $this->relations[$i] = new ConstellationRelation($entry);
        }

        unset($this->resourceRelations);
        $this->resourceRelations = array();
        if (isset($data["resourceRelations"])) {
            foreach ($data["resourceRelations"] as $i => $entry)
                if ($entry != null)
                    $this->resourceRelations[$i] = new ResourceRelation($entry);
        }

        unset($this->functions);
        $this->functions = array();
        if (isset($data["functions"])) {
            foreach ($data["functions"] as $i => $entry)
                if ($entry != null)
                    $this->functions[$i] = new SNACFunction($entry);
        }

        unset($this->places);
        $this->places = array();
        if (isset($data["places"])) {
            foreach ($data["places"] as $i => $entry)
                if ($entry != null)
                    $this->places[$i] = new Place($entry);
        }

        return true;
    }

    /**
     * Set the ARK ID
     *
     * @param string $ark Ark ID for this constellation
     */
    public function setArkID($ark) {

        $this->ark = $ark;
    }

    /**
     * Set Entity type
     *
     * @param \snac\data\Term $type Entity type
     */
    public function setEntityType($type) {

        $this->entityType = $type;
    }

    /**
     * Adds an alternate record id
     *
     * @param \snac\data\SameAs $other The other record ID in a SameAs object
     */
    public function addOtherRecordID($other) {

        array_push($this->otherRecordIDs, $other); 
    }

    /**
     * Set maintenance status
     *
     * @param \snac\data\Term $status status
     */
    public function setMaintenanceStatus($status) {

        $this->maintenanceStatus = $status;
    }

    /**
     * Set maintenance agency
     *
     * @param string $agency agency
     */
    public function setMaintenanceAgency($agency) {

        $this->maintenanceAgency = $agency;
    }

    /**
     * Adds a source to the list of sources for this constellation
     *
     * @param \snac\data\Source $source Source to add 
     */
    public function addSource($source) {

        array_push($this->sources, $source);
    }
    

    /**
     * Sets all sources to the list of sources fin the parameter
     *
     * @param \snac\data\Source[] $sources list of sources
     */
    public function setAllSources($sources) {
    
        $this->sources = $sources;
    }

    /**
     * Add a maintenance event
     *
     * @param \snac\data\MaintenanceEvent $event Event to add
     */
    public function addMaintenanceEvent($event) {

        array_push($this->maintenanceEvents, $event);
    }

    /**
     * Add a convention declaration
     *
     * @param \snac\data\ConventionDeclaration $declaration Convention Declaration
     */
    public function addConventionDeclaration($declaration) {

        array_push($this->conventionDeclarations,  $declaration);
    }

    /**
     * Adds a name entry to the known entries for this constellation
     *
     * @param \snac\data\NameEntry $nameEntry Name entry to add
     */
    public function addNameEntry($nameEntry) {

        array_push($this->nameEntries, $nameEntry);
    }

    /**
     * Add biogHist entry
     *
     * @param string $biog BiogHist to add
     */
    public function addBiogHist($biog) {

        array_push($this->biogHists, $biog);
    }

    /**
     * Add occupation
     *
     * @param \snac\data\Occupation $occupation Occupation to add
     */
    public function addOccupation($occupation) {

        array_push($this->occupations, $occupation);
    }

    /**
     * Add function
     *
     * @param \snac\data\SNACFunction $function Function object
     */
    public function addFunction($function) {

        array_push($this->functions, $function);
    }

    /**
     * Add Language Used 
     * 
     * Add a language used by this constellation's identity.
     *
     * @param  \snac\data\Language Language and script used by this identity
     */
    public function addLanguageUsed($language) {
        array_push($this->languagesUsed, $language);
    }

    /**
     * Alias for Add Language Used
     * 
     * Calls addLanguageUsed() and serves as an alias in DBUtil.
     *
     * In retrospect it doesn't help that much because DBUtil populateLanguage() needs to test the class
     * regardless due to api inconsistency. 
     *
     * Add a language used by this constellation's identity. (You might be tempted to call this
     * setLanguages() or the singular setLanguage() as the converse of getLanguages().)
     *
     * @param  \snac\data\Language Language and script used by this identity
     * @deprecated
     */ 
    public function addLanguage(\snac\data\Language $language) {
        $this->addLanguageUsed($language);
    }



    /**
     * Add a subject
     *
     * @param \snac\data\Subject $subject Subject to add.
     */
    public function addSubject($subject) {
        array_push($this->subjects, $subject);
    }

    /**
     * Add a nationality
     *
     * @param \snac\data\Nationality $nationality Nationality
     */
    public function addNationality($nationality) {
        array_push($this->nationalities, $nationality);
    }

    /**
     * Add a gender
     *
     * @param \snac\data\Gender $gender Gender to set
     */
    public function addGender($gender) {
        array_push($this->genders, $gender);
    }

    /**
     * Set the gender 
     * 
     * Set the gender of this Constellation to be this sole gender.
     * Removes all the other genders.
     *
     * @param \snac\data\Gender $gender Gender to set
     */
    public function setGender($gender) {
        unset($this->genders);
        $this->genders = array();
        array_push($this->genders, $gender);
    }

    /**
     * Add relation to another constellation
     *
     * @param \snac\data\ConstellationRelation $relation Relation object defining the relationship
     */
    public function addRelation($relation) {

        array_push($this->relations, $relation);
    }

    /**
     * Add relation to a resource
     *
     * @param \snac\data\ResourceRelation $relation Relation object defining the relationship
     */
    public function addResourceRelation($relation) {

        array_push($this->resourceRelations, $relation);
    }

    /**
     * Add a place
     * 
     * @param \snac\data\Place $place Place to add
     */
    public function addPlace($place) {

        array_push($this->places, $place);
    }
    
    /**
     * Add a general context
     * 
     * @param \snac\data\GeneralContext $context General context
     */
    public function addGeneralContext($context) {
        array_push($this->generalContexts, $context);
    }
    
    /**
     * Add a structure or genealogy
     * 
     * @param \snac\data\StructureOrGenealogy $structure StructureOrGenealogy information
     */
    public function addStructureOrGenealogy($structure) {
        array_push($this->structureOrGenealogies, $structure);
    }
    
    /**
     * Add a legal status
     * 
     * @param \snac\data\LegalStatus $legalStatus The legal status to add 
     */
    public function addLegalStatus($legalStatus) {
        array_push($this->legalStatuses, $legalStatus);
    }
    
    /**
     * Add a mandate
     * 
     * @param \snac\data\Mandate $mandate Mandate information
     */
    public function addMandate($mandate) {
        array_push($this->mandates, $mandate);
    }
    
    /**
     * Set the Status
     * 
     * Set the status of this Constellation object
     * 
     * @param string|null $status Status for the constellation
     */
    public function setStatus($status) {
        $this->status = $status;
    }

    /**
     *
     * {@inheritDoc}
     *
     * @param \snac\data\Constellation $other Other object
     *       
     * @see \snac\data\AbstractData::equals()
     */
    public function equals($other, $strict = true) {

        if ($other == null || ! ($other instanceof \snac\data\Constellation))
            return false;
        
        if (! parent::equals($other, $strict))
            return false;
        
        if ($this->getArk() != $other->getArk())
            return false;
        
        if (($this->getEntityType() != null && ! $this->getEntityType()->equals($other->getEntityType())) ||
                 ($this->getEntityType() == null && $other->getEntityType() != null))
            return false;
                 
        /**    
         * Currently, we are not checking the maintenance events for equality
            if ($this->getMaintenanceAgency() != $other->getMaintenanceAgency())
                return false;
            if (($this->getMaintenanceStatus() != null && ! $this->getMaintenanceStatus()->equals($other->getMaintenanceStatus(), $strict)) ||
                 ($this->getMaintenanceStatus() == null && $other->getMaintenanceStatus() != null))
                return false;
            if (!$this->checkArrayEqual($this->getMaintenanceEvents(), $other->getMaintenanceEvents(), $strict))
                return false;
        **/
                 
        if (!$this->checkArrayEqual($this->getOtherRecordIDs(), $other->getOtherRecordIDs(), $strict))
            return false;
        if (!$this->checkArrayEqual($this->getSources(), $other->getSources(), $strict))
            return false;
        if (!$this->checkArrayEqual($this->getLegalStatuses(), $other->getLegalStatuses(), $strict))
            return false;
        if (!$this->checkArrayEqual($this->getConventionDeclarations(), $other->getConventionDeclarations(), $strict))
            return false;
        if (!$this->checkArrayEqual($this->getLanguagesUsed(), $other->getLanguagesUsed(), $strict))
            return false;
        if (!$this->checkArrayEqual($this->getNameEntries(), $other->getNameEntries(), $strict))
            return false;
        if (!$this->checkArrayEqual($this->getOccupations(), $other->getOccupations(), $strict))
            return false;
        if (!$this->checkArrayEqual($this->getBiogHistList(), $other->getBiogHistList(), $strict))
            return false;
        if (!$this->checkArrayEqual($this->getRelations(), $other->getRelations(), $strict))
            return false;
        if (!$this->checkArrayEqual($this->getResourceRelations(), $other->getResourceRelations(), $strict))
            return false;
        if (!$this->checkArrayEqual($this->getFunctions(), $other->getFunctions(), $strict))
            return false;
        if (!$this->checkArrayEqual($this->getPlaces(), $other->getPlaces(), $strict))
            return false;
        if (!$this->checkArrayEqual($this->getSubjects(), $other->getSubjects(), $strict))
            return false;
        if (!$this->checkArrayEqual($this->getNationalities(), $other->getNationalities(), $strict))
            return false;
        if (!$this->checkArrayEqual($this->getGenders(), $other->getGenders(), $strict))
            return false;
        if (!$this->checkArrayEqual($this->getGeneralContexts(), $other->getGeneralContexts(), $strict))
            return false;
        if (!$this->checkArrayEqual($this->getStructureOrGenealogies(), $other->getStructureOrGenealogies(), $strict))
            return false;
        if (!$this->checkArrayEqual($this->getMandates(), $other->getMandates(), $strict))
            return false;
        
        return true;
    }
}
