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
     * * cpfRelation where type=sameAs
     *
     * @var \snac\data\SameAs[] Other record IDs by which this constellation may be known
     */
    private $otherRecordIDs = null;


    /**
    * EntityID List
    *
    * From EAC-CPF tag(s):
    *
    * * eac-cpf/cpfDescription/identity/entityID
    *
    * @var \snac\data\EntityId[] Other external record IDs by which this constellation may be known
    */
    private $entityIDs = null;

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
     * Preferred Name Entry
     *
     * @var \snac\data\NameEntry|null The preferred name entry on view (based on the viewing user)
     */
    private $preferredNameEntry = null;

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
     * * eac-cpf/cpfdescription/relations/cpfrelation/*
     *
     * @var \snac\data\constellationrelation[] constellation relations
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
     * Images for this constellation
     *
     * @var \snac\data\Image[] Images
     */
    private $images = null;

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
            $this->otherRecordIDs = array();
            $this->sources = array();
            $this->maintenanceEvents = array();
            $this->nameEntries = array();
            $this->biogHists = array();
            $this->occupations = array();
            $this->relations = array();
            $this->resourceRelations = array();
            $this->functions = array();
            $this->places = array();
            $this->subjects = array();
            $this->legalStatuses = array();
            $this->genders = array();
            $this->nationalities = array();
            $this->languagesUsed = array();
            $this->conventionDeclarations = array();
            $this->generalContexts = array();
            $this->structureOrGenealogies = array();
            $this->mandates = array();
            $this->entityIDs = array();
            $this->images = array();
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
    * Get the other entityIDs
    *
    * @return \snac\data\EntityId[] Other entity IDs by which this constellation may be known
    *
    */
    public function getEntityIDs()
    {
        return $this->entityIDs;
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
     * Gets the preferred name entry for this constellation.  If the preferred
     * name entry has been set by the server, that one is returned.  If not, it
     * gets the nameEntry in this constellation with the highest score, or the
     * first one if the scores are equal, or null if there is no name entry
     *
     * @return \snac\data\NameEntry Preferred name entry for this constellation
     *
     */
    public function getPreferredNameEntry()
    {
        if (count($this->nameEntries) < 1)
            return null;

        if ($this->preferredNameEntry != null)
            return $this->preferredNameEntry;

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
     * Get the preferred name only
     *
     * This method should return only the parts of the name that are considered to
     * be actually the name, i.e. Surname, Forename, or Name.  Currently, it is only
     * an alias to `getPreferredNameEntry`.
     *
     * @return \snac\data\NameEntry preferred name entry string
     */
    public function getPreferredNameOnly() {
        return $this->getPreferredNameEntry();
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
     * Get images
     *
     * Returns the list of images associated with this Constellation
     *
     * @return \snac\data\Image[] List of Image objects
     */
    public function getImages() {
        return $this->images;
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
            "entityIDs" => array(),
            "maintenanceStatus" => $this->maintenanceStatus == null ? null : $this->maintenanceStatus->toArray($shorten),
            "maintenanceAgency" => $this->maintenanceAgency,
            "maintenanceEvents" => array(),
            "sources" => array(),
            "legalStatuses" => array(),
            "conventionDeclarations" => array(),
            "languagesUsed" => array(),
            "nameEntries" => array(),
            "preferredNameEntry" => $this->preferredNameEntry == null ? null : $this->preferredNameEntry->toArray($shorten),
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
            "mandates" => array(),
            "images" => array()
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

        foreach ($this->entityIDs as $i => $v)
            $return["entityIDs"][$i] = $v->toArray($shorten);

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

        foreach ($this->images as $i => $v)
            $return["images"][$i] = $v->toArray($shorten);

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

        unset($this->entityIDs);
        $this->entityIDs = array();
        if (isset($data["entityIDs"]))
            foreach ($data["entityIDs"] as $i => $entry)
                if ($entry != null)
                    $this->entityIDs[$i] = new \snac\data\EntityId($entry);

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

        unset($this->preferredNameEntry);
        if (isset($data["preferredNameEntry"]) && $data["preferredNameEntry"] != null)
            $this->preferredNameEntry = new \snac\data\NameEntry($data["preferredNameEntry"]);
        else
            $this->preferredNameEntry = null;

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

        unset($this->images);
        $this->images = array();
        if (isset($data["images"])) {
            foreach ($data["images"] as $i => $entry)
                if ($entry != null)
                    $this->images[$i] = new Image($entry);
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
     * Get the ARK ID
     *
     * @return string Ark ID for this constellation
     */
    public function getArkID() {

        return $this->ark;
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
     * Add an alternate entityId
     *
     * @param \snac\data\EntityId $other The other entityId in an EntityId object
     */
    public function addEntityID($other) {

        array_push($this->entityIDs, $other);
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
     * Set the list of name entries
     *
     * Sets the list of name entries to the list provided.  This will overwrite any other
     * name entries currently set for this constellation.
     *
     * @param \snac\data\NameEntry[] $nameEntries Name entry list to set
     */
    public function setNameEntries($nameEntries) {

        $this->nameEntries = $nameEntries;
    }

    /**
     * Set the preferred name entry
     *
     * Sets the preferred name entry for this constellation.  The name entry to prefer
     * MUST already be in the list of name entries.  If it does not appear, this method
     * will fail.
     *
     * @param \snac\data\NameEntry $nameEntry Name entry in the list of name entries to prefer
     * @return boolean True on success, false on failure
     */
    public function setPreferredNameEntry($nameEntry) {
        $inArray = false;
        foreach ($this->nameEntries as $nE) {
            if ($nE->equals($nameEntry)) {
                $inArray = true;
                break;
            }
        }

        if ($inArray) {
            $this->preferredNameEntry = $nameEntry;
            return true;
        }

        return false;
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
     * Remove all BiogHist entries
     */
    public function removeAllBiogHists() {
        $this->biogHists = array();
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
     * Empty Constellation Relations
     *
     * Drops all constellation relations for this constellation
     *
     */
    public function emptyRelations() {
        $this->relations = array();
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
     * Empty Resource Relations
     *
     * Drop all resource relations for this constellation.
     *
     */
    public function emptyResourceRelations() {
        $this->resourceRelations = array();
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
     * Add an image
     *
     * @param \snac\data\Image $image Image to set
     */
    public function addImage($image) {
        array_push($this->images, $image);
    }

    /**
     *
     * {@inheritDoc}
     *
     * @param \snac\data\Constellation $other Other object
     * @param boolean $strict optional Whether or not to check id, version, and operation
     * @return boolean true on equality, false otherwise
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
         * Currently, we are not checking the maintenance events or images for equality
        *    if ($this->getMaintenanceAgency() != $other->getMaintenanceAgency())
        *        return false;
        *    if (($this->getMaintenanceStatus() != null && ! $this->getMaintenanceStatus()->equals($other->getMaintenanceStatus(), $strict)) ||
        *         ($this->getMaintenanceStatus() == null && $other->getMaintenanceStatus() != null))
        *        return false;
        *    if (!$this->checkArrayEqual($this->getMaintenanceEvents(), $other->getMaintenanceEvents(), $strict))
        *        return false;
        *    if (!$this->checkArrayEqual($this->getImages(), $other->getImages(), $strict))
        *        return false;
        **/

        if (!$this->checkArrayEqual($this->getOtherRecordIDs(), $other->getOtherRecordIDs(), $strict))
            return false;
        if (!$this->checkArrayEqual($this->getEntityIDs(), $other->getEntityIDs(), $strict))
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

    /**
     * Is Constellation Empty
     *
     * Checkes whether this constellation is empty.
     *
     * @return boolean True if empty, false otherwise
     */
    public function isEmpty() {
        $emptyConstellation = new \snac\data\Constellation();

        return $this->equals($emptyConstellation, true);
    }

    /**
     * Collect SCM Citations by Source
     *
     * This method parses through each section of the constellation, looking for SCMs
     * and attaching them to the Source they cite.  This allows for the caller to ask
     * for the list of sources (and associated SCMs) for this constellation.
     *
     * @return \snac\data\Source[] A list of Source objects with their SCMs filled out
     */
    public function collateAllSCMCitationsBySource() {
        $sources = array();
        // Do a deep copy of the sources
        foreach ($this->sources as $source) {
            $sources[$source->getID()] = new \snac\data\Source($source->toArray());
        }
        $sources[0] = new \snac\data\Source();
        $sources[0]->setDisplayName("Unknown Source");

        parent::collateSCMCitationsBySource($sources);

        foreach ($this->mandates as &$element)
            $element->collateSCMCitationsBySource($sources);

        foreach ($this->structureOrGenealogies as &$element)
            $element->collateSCMCitationsBySource($sources);

        foreach ($this->generalContexts as &$element)
            $element->collateSCMCitationsBySource($sources);

        foreach ($this->biogHists as &$element)
            $element->collateSCMCitationsBySource($sources);

        foreach ($this->conventionDeclarations as &$element)
            $element->collateSCMCitationsBySource($sources);

        foreach ($this->nationalities as &$element)
            $element->collateSCMCitationsBySource($sources);

        foreach ($this->otherRecordIDs as &$element)
            $element->collateSCMCitationsBySource($sources);

        foreach ($this->entityIDs as &$element)
            $element->collateSCMCitationsBySource($sources);

        foreach ($this->languagesUsed as &$element)
            $element->collateSCMCitationsBySource($sources);

        foreach ($this->legalStatuses as &$element)
            $element->collateSCMCitationsBySource($sources);

        foreach ($this->sources as &$element)
            $element->collateSCMCitationsBySource($sources);

        foreach ($this->genders as &$element)
            $element->collateSCMCitationsBySource($sources);

        foreach ($this->nameEntries as &$element)
            $element->collateSCMCitationsBySource($sources);

        foreach ($this->occupations as &$element)
            $element->collateSCMCitationsBySource($sources);

        foreach ($this->relations as &$element)
            $element->collateSCMCitationsBySource($sources);

        foreach ($this->resourceRelations as &$element)
            $element->collateSCMCitationsBySource($sources);

        foreach ($this->functions as &$element)
            $element->collateSCMCitationsBySource($sources);

        foreach ($this->places as &$element)
            $element->collateSCMCitationsBySource($sources);

        foreach ($this->subjects as &$element)
            $element->collateSCMCitationsBySource($sources);


        return $sources; 
    }

    /**
     * Update All SCM Citations
     *
     * This method goes through each section of the constellation, looking for SCMs and
     * updating their citations.  If the SCM points to oldSource, this method will update
     * it to point to newSource.
     *
     * @param  \snac\data\Source $oldSource Source to replace
     * @param  \snac\data\Source $newSource Source to replace with
     */
    public function updateAllSCMCitations($oldSource, $newSource) {
        parent::updateSCMCitation($oldSource, $newSource);

        foreach ($this->mandates as &$element)
            $element->updateSCMCitation($oldSource, $newSource);

        foreach ($this->structureOrGenealogies as &$element)
            $element->updateSCMCitation($oldSource, $newSource);

        foreach ($this->generalContexts as &$element)
            $element->updateSCMCitation($oldSource, $newSource);

        foreach ($this->biogHists as &$element)
            $element->updateSCMCitation($oldSource, $newSource);

        foreach ($this->conventionDeclarations as &$element)
            $element->updateSCMCitation($oldSource, $newSource);

        foreach ($this->nationalities as &$element)
            $element->updateSCMCitation($oldSource, $newSource);

        foreach ($this->otherRecordIDs as &$element)
            $element->updateSCMCitation($oldSource, $newSource);

        foreach ($this->entityIDs as &$element)
            $element->updateSCMCitation($oldSource, $newSource);

        foreach ($this->languagesUsed as &$element)
            $element->updateSCMCitation($oldSource, $newSource);

        foreach ($this->legalStatuses as &$element)
            $element->updateSCMCitation($oldSource, $newSource);

        foreach ($this->sources as &$element)
            $element->updateSCMCitation($oldSource, $newSource);

        foreach ($this->genders as &$element)
            $element->updateSCMCitation($oldSource, $newSource);

        foreach ($this->nameEntries as &$element)
            $element->updateSCMCitation($oldSource, $newSource);

        foreach ($this->occupations as &$element)
            $element->updateSCMCitation($oldSource, $newSource);

        foreach ($this->relations as &$element)
            $element->updateSCMCitation($oldSource, $newSource);

        foreach ($this->resourceRelations as &$element)
            $element->updateSCMCitation($oldSource, $newSource);

        foreach ($this->functions as &$element)
            $element->updateSCMCitation($oldSource, $newSource);

        foreach ($this->places as &$element)
            $element->updateSCMCitation($oldSource, $newSource);

        foreach ($this->subjects as &$element)
            $element->updateSCMCitation($oldSource, $newSource);
    }

    /**
     * Perform a diff
     *
     * Compares this constellation to the "other."  This method produces a "diff" of the Constellation,
     * creating three new constellations.  First, it produces an intersection, which contains all bits
     * that in both this and other (note first-level data must be the same -- it does not make sense to
     * keep Name Components or SCMs that are the same without their containing NameEntry).  Second,
     * the "this" and "other" return Constellations contain the parts of `$this` and `$other` that are NOT
     * included in the intersection.  If any of the return constellations would be empty, they will be
     * returned as `null` instead.
     *
     * This method does NOT diff maintenance history, maintenance status, or images.
     *
     * @param  \snac\data\Constellation $other Constellation object to diff
     * @param boolean $strict optional If true, will check IDs and Versions.  Else (default) only checks data
     * @return \snac\data\Constellation[] Associative array of "intersection," "this," and "other" Constellations.
     */
    public function diff($other, $strict = false) {
        $return = array (
            "intersection" => null,
            "this" => null,
            "other" => null
        );

        if ($other == null || ! ($other instanceof \snac\data\Constellation)) {
            $return["this"] = $this;
            return $return;
        }

        $intersection = new \snac\data\Constellation();
        $first = new \snac\data\Constellation();
        $second = new \snac\data\Constellation();


        if ($this->getArk() === $other->getArk()) {
            $intersection->setArkID($this->getArk());
        }

        if ($this->getEntityType() != null && $this->getEntityType()->equals($other->getEntityType(), $strict)) {
            $intersection->setEntityType($this->getEntityType());
        }

        $result = $this->diffArray($this->getOtherRecordIDs(), $other->getOtherRecordIDs(), $strict);
        $intersection->otherRecordIDs = $result["intersection"];
        $first->otherRecordIDs = $result["first"];
        $second->otherRecordIDs = $result["second"];

        $result = $this->diffArray($this->getEntityIDs(), $other->getEntityIDs(), $strict);
        $intersection->entityIDs = $result["intersection"];
        $first->entityIDs = $result["first"];
        $second->entityIDs = $result["second"];

        $result = $this->diffArray($this->getSources(), $other->getSources(), $strict);
        $intersection->sources = $result["intersection"];
        $first->sources = $result["first"];
        $second->sources = $result["second"];

        $result = $this->diffArray($this->getLegalStatuses(), $other->getLegalStatuses(), $strict);
        $intersection->legalStatuses = $result["intersection"];
        $first->legalStatuses = $result["first"];
        $second->legalStatuses = $result["second"];

        $result = $this->diffArray($this->getConventionDeclarations(), $other->getConventionDeclarations(), $strict);
        $intersection->conventionDeclarations = $result["intersection"];
        $first->conventionDeclarations = $result["first"];
        $second->conventionDeclarations = $result["second"];

        $result = $this->diffArray($this->getLanguagesUsed(), $other->getLanguagesUsed(), $strict);
        $intersection->languagesUsed = $result["intersection"];
        $first->languagesUsed = $result["first"];
        $second->languagesUsed = $result["second"];

        $result = $this->diffArray($this->getNameEntries(), $other->getNameEntries(), $strict);
        $intersection->nameEntries = $result["intersection"];
        $first->nameEntries = $result["first"];
        $second->nameEntries = $result["second"];

        $result = $this->diffArray($this->getOccupations(), $other->getOccupations(), $strict);
        $intersection->occupations = $result["intersection"];
        $first->occupations = $result["first"];
        $second->occupations = $result["second"];

        $result = $this->diffArray($this->getBiogHistList(), $other->getBiogHistList(), $strict);
        $intersection->biogHists = $result["intersection"];
        $first->biogHists = $result["first"];
        $second->biogHists = $result["second"];

        $result = $this->diffArray($this->getRelations(), $other->getRelations(), $strict);
        $intersection->relations = $result["intersection"];
        $first->relations = $result["first"];
        $second->relations = $result["second"];

        $result = $this->diffArray($this->getResourceRelations(), $other->getResourceRelations(), $strict);
        $intersection->resourceRelations = $result["intersection"];
        $first->resourceRelations = $result["first"];
        $second->resourceRelations = $result["second"];

        $result = $this->diffArray($this->getFunctions(), $other->getFunctions(), $strict);
        $intersection->functions = $result["intersection"];
        $first->functions = $result["first"];
        $second->functions = $result["second"];

        $result = $this->diffArray($this->getPlaces(), $other->getPlaces(), $strict);
        $intersection->places = $result["intersection"];
        $first->places = $result["first"];
        $second->places = $result["second"];

        $result = $this->diffArray($this->getSubjects(), $other->getSubjects(), $strict);
        $intersection->subjects = $result["intersection"];
        $first->subjects = $result["first"];
        $second->subjects = $result["second"];

        $result = $this->diffArray($this->getNationalities(), $other->getNationalities(), $strict);
        $intersection->nationalities = $result["intersection"];
        $first->nationalities = $result["first"];
        $second->nationalities = $result["second"];

        $result = $this->diffArray($this->getGenders(), $other->getGenders(), $strict);
        $intersection->genders = $result["intersection"];
        $first->genders = $result["first"];
        $second->genders = $result["second"];

        $result = $this->diffArray($this->getGeneralContexts(), $other->getGeneralContexts(), $strict);
        $intersection->generalContexts = $result["intersection"];
        $first->generalContexts = $result["first"];
        $second->generalContexts = $result["second"];

        $result = $this->diffArray($this->getStructureOrGenealogies(), $other->getStructureOrGenealogies(), $strict);
        $intersection->structureOrGenealogies = $result["intersection"];
        $first->structureOrGenealogies = $result["first"];
        $second->structureOrGenealogies = $result["second"];

        $result = $this->diffArray($this->getMandates(), $other->getMandates(), $strict);
        $intersection->mandates = $result["intersection"];
        $first->mandates = $result["first"];
        $second->mandates = $result["second"];

        $result = $this->diffArray($this->getDateList(), $other->getDateList(), $strict);
        $intersection->dateList = $result["intersection"];
        $first->dateList = $result["first"];
        $second->dateList = $result["second"];

        $result = $this->diffArray($this->getSNACControlMetadata(), $other->getSNACControlMetadata(), $strict);
        $intersection->snacControlMetadata = $result["intersection"];
        $first->snacControlMetadata = $result["first"];
        $second->snacControlMetadata = $result["second"];
        
        if (!$intersection->isEmpty())
            $return["intersection"] = $intersection;

        if (!$first->isEmpty()) {
            $first->setID($this->getID());
            $first->setVersion($this->getVersion());
            $first->setArkID($this->getArk());
            $first->setEntityType($this->getEntityType());
            $return["this"] = $first;
        }

        if (!$second->isEmpty()) {
            $second->setID($other->getID());
            $second->setVersion($other->getVersion());
            $second->setArkID($other->getArk());
            $second->setEntityType($other->getEntityType());
            $return["other"] = $second;
        }

        return $return;
    }

    /**
     * Combine Into
     *
     * Combines the data from the Constellation passed in into this Constellation,
     * removing IDs and version numbers for all elements except sources.  Sets all
     * other operations to INSERT so they are considered new in this Constellation.
     *
     * @param \snac\data\Constellation $other The constellation to combine with this one
     * @return boolean True if success, false otherwise (currently no failure)
     */
    public function combine(&$other) {
        //      merge the sources, keeping a list of other's sources
        // do a diff of this and other (strict = false)
        // update the "other" sources, if we can?
        // foreach over the other and combine with this (they won't overlap: not in intersection)
        //      - remove other's ID, Version, and set operation = INSERT
        // return

        $diff = $this->diff($other, false);
        $combine = $diff["other"];

        if ($combine == null || $combine->isEmpty()) {
            return true;
        }

        // Dates, gained by AbstractData
        foreach ($combine->getDateList() as &$element) {
            $element->setID(null);
            $element->setVersion(null);
            $element->setOperation(\snac\data\AbstractData::$OPERATION_INSERT);
            $element->cleanseSubElements();
            $this->addDate($element);
        }

        // SCM, gained by AbstractData (but shouldn't be used on the high-level constellation)
        foreach ($combine->snacControlMetadata as &$element) {
            $element->setID(null);
            $element->setVersion(null);
            $element->setOperation(\snac\data\AbstractData::$OPERATION_INSERT);
            $element->cleanseSubElements();
            $this->addSNACControlMetadata($element);
        }

        foreach ($combine->sources as &$element) {
            // Sources need their IDs in tact if we plan to fix up SCMs
            $element->setVersion(null);
            $element->setOperation(\snac\data\AbstractData::$OPERATION_INSERT);
            $element->cleanseSubElements();
            $this->addSource($element);
        }

        foreach ($combine->mandates as &$element) {
            $element->setID(null);
            $element->setVersion(null);
            $element->setOperation(\snac\data\AbstractData::$OPERATION_INSERT);
            $element->cleanseSubElements();
            $this->addMandate($element);
        }

        foreach ($combine->structureOrGenealogies as &$element) {
            $element->setID(null);
            $element->setVersion(null);
            $element->setOperation(\snac\data\AbstractData::$OPERATION_INSERT);
            $element->cleanseSubElements();
            $this->addStructureOrGenealogy($element);
        }

        foreach ($combine->generalContexts as &$element) {
            $element->setID(null);
            $element->setVersion(null);
            $element->setOperation(\snac\data\AbstractData::$OPERATION_INSERT);
            $element->cleanseSubElements();
            $this->addGeneralContext($element);
        }

        foreach ($combine->biogHists as &$element) {
            $element->setID(null);
            $element->setVersion(null);
            $element->setOperation(\snac\data\AbstractData::$OPERATION_INSERT);
            $element->cleanseSubElements();
            // Add if a new one, append to the first bioghist if not
            if (empty($this->biogHists)) {
                $this->addBiogHist($element);
            } else {
                $this->biogHists[0]->append($element);
            }
        }

        foreach ($combine->conventionDeclarations as &$element) {
            $element->setID(null);
            $element->setVersion(null);
            $element->setOperation(\snac\data\AbstractData::$OPERATION_INSERT);
            $element->cleanseSubElements();
            $this->addConventionDeclaration($element);
        }

        foreach ($combine->nationalities as &$element) {
            $element->setID(null);
            $element->setVersion(null);
            $element->setOperation(\snac\data\AbstractData::$OPERATION_INSERT);
            $element->cleanseSubElements();
            $this->addNationality($element);
        }

        foreach ($combine->otherRecordIDs as &$element) {
            $element->setID(null);
            $element->setVersion(null);
            $element->setOperation(\snac\data\AbstractData::$OPERATION_INSERT);
            $element->cleanseSubElements();
            $this->addOtherRecordID($element);
        }

        foreach ($combine->entityIDs as &$element) {
            $element->setID(null);
            $element->setVersion(null);
            $element->setOperation(\snac\data\AbstractData::$OPERATION_INSERT);
            $element->cleanseSubElements();
            $this->addEntityID($element);
        }

        foreach ($combine->languagesUsed as &$element) {
            $element->setID(null);
            $element->setVersion(null);
            $element->setOperation(\snac\data\AbstractData::$OPERATION_INSERT);
            $element->cleanseSubElements();
            $this->addLanguageUsed($element);
        }

        foreach ($combine->legalStatuses as &$element) {
            $element->setID(null);
            $element->setVersion(null);
            $element->setOperation(\snac\data\AbstractData::$OPERATION_INSERT);
            $element->cleanseSubElements();
            $this->addLegalStatus($element);
        }

        foreach ($combine->genders as &$element) {
            $element->setID(null);
            $element->setVersion(null);
            $element->setOperation(\snac\data\AbstractData::$OPERATION_INSERT);
            $element->cleanseSubElements();
            $this->addGender($element);
        }

        foreach ($combine->nameEntries as &$element) {
            $element->setID(null);
            $element->setVersion(null);
            $element->setOperation(\snac\data\AbstractData::$OPERATION_INSERT);
            $element->cleanseSubElements();
            $this->addNameEntry($element);
        }

        foreach ($combine->occupations as &$element) {
            $element->setID(null);
            $element->setVersion(null);
            $element->setOperation(\snac\data\AbstractData::$OPERATION_INSERT);
            $element->cleanseSubElements();
            $this->addOccupation($element);
        }

        foreach ($combine->relations as &$element) {
            $element->setID(null);
            $element->setVersion(null);
            $element->setOperation(\snac\data\AbstractData::$OPERATION_INSERT);
            $element->cleanseSubElements();
            $this->addRelation($element);
        }

        foreach ($combine->resourceRelations as &$element) {
            $element->setID(null);
            $element->setVersion(null);
            $element->setOperation(\snac\data\AbstractData::$OPERATION_INSERT);
            $element->cleanseSubElements();
            $this->addResourceRelation($element);
        }

        foreach ($combine->functions as &$element) {
            $element->setID(null);
            $element->setVersion(null);
            $element->setOperation(\snac\data\AbstractData::$OPERATION_INSERT);
            $element->cleanseSubElements();
            $this->addFunction($element);
        }

        foreach ($combine->places as &$element) {
            $element->setID(null);
            $element->setVersion(null);
            $element->setOperation(\snac\data\AbstractData::$OPERATION_INSERT);
            $element->cleanseSubElements();
            $this->addPlace($element);
        }

        foreach ($combine->subjects as &$element) {
            $element->setID(null);
            $element->setVersion(null);
            $element->setOperation(\snac\data\AbstractData::$OPERATION_INSERT);
            $element->cleanseSubElements();
            $this->addSubject($element);
        }

        return true;
    }

}
