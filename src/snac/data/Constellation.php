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
 * Stores all the information related to an identity constellation. Can be built in pieces, or imported
 * from an associative array.
 *
 * @author Robbie Hott
 *        
 */
class Constellation extends AbstractData {

    /**
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/control/recordId
     * 
     * @var string ARK identifier
     */
    private $ark = null;

    /**
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/cpfDescription/identity/entityType
     * 
     * @var string Entity type
     */
    private $entityType = null;

    /**
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/control/otherRecordId
     * * eac-cpf/cpfDescription/identity/entityID
     * 
     * @var string[] Other record IDs by which this constellation may be known
     */
    private $otherRecordIDs = null;

    /**
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/control/maintenanceStatus
     * 
     * @var string Current maintenance status
     */
    private $maintenanceStatus = null;

    /**
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/control/maintenanceAgency/agencyName
     * 
     * @var string Latest maintenance agency
     */
    private $maintenanceAgency = null;

    /**
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/control/maintenanceHistory/maintenanceEvent/*
     * 
     * @var \snac\data\MaintenanceEvent[] List of maintenance events performed on this constellation
     */
    private $maintenanceEvents = null;

    /**
     * From EAC-CPF tag(s):
     * 
     * * /eac-cpf/control/sources/source/@type
     * * /eac-cpf/control/sources/source/@href
     * 
     * Stored as:
     * ```
     * [ [ "type"=> type, "href"=> href ], ... ]
     * ```
     *
     * @var string[][] List of sources, each source is an array of type,value entries
     */
    private $sources = null;
    
    /**
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/cpfDescription/description/legalStatus/term
     * * eac-cpf/cpfDescription/description/legalStatus/@vocabularySource
     * 
     * Stored as:
     * ```
     * [ ["term" => term, "vocabularySource" => vocSrc], ... ]
     * ```
     *
     * @var string[][] List of legal status, each status as an array of term,vocabularySource entries
     */
    private $legalStatuses = null;

    /**
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/control/conventionDeclaration
     * 
     * @var string Convention declaration
     */
    private $conventionDeclaration = null;
    
    /**
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/control/languageDeclaration/language
     * 
     * @var string Language used for Constellation Record
     */
    private $constellationLanguage = null;
    
    /**
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/control/languageDeclaration/language/@languageCode
     * 
     * @var string Language code used for Constellation Record
     */
    private $constellationLanguageCode = null;
    
    /**
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/control/languageDeclaration/script
     * 
     * @var string Script used for Constellation Record
     */
    private $constellationScript = null;
    
    /**
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/control/languageDeclaration/script/@scriptCode
     * 
     * @var string Script code used for Constellation Record
     */
    private $constellationScriptCode = null;
    
    /**
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/cpfDescription/description/languageUsed/language
     * 
     * @var string Language used by the identity described
     */
    private $language = null;
    
    /**
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/cpfDescription/description/languageUsed/language/@languageCode
     * 
     * @var string Language code used by the identity described
     */
    private $languageCode = null;
    
    /**
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/cpfDescription/description/languageUsed/script
     * 
     * @var string Script used by the identity described
     */
    private $script = null;
    
    /**
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/cpfDescription/description/languageUsed/script/@scriptCode
     * 
     * @var string Script code used by the identity described[w
     */
    private $scriptCode = null;

    /**
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/cpfDescription/identity/nameEntry
     * 
     * @var \snac\data\NameEntry[] List of name entries for this constellation
     */
    private $nameEntries = null;

    /**
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/cpfDescription/description/occupation/*
     * 
     * @var \snac\data\Occupation[] List of occupations
     */
    private $occupations = null;

    /**
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/cpfDescription/description/biogHist
     * 
     * @var string[] BiogHist entries for this constellation (in XML strings)
     */
    private $biogHists = null;

    /**
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/cpfDescription/description/existDates/dateSet/dateRange/*
     * * eac-cpf/cpfDescription/description/existDates/dateSet/date/*
     * * eac-cpf/cpfDescription/description/existDates/dateRange/*
     * * eac-cpf/cpfDescription/description/existDates/date/*
     * 
     * @var \snac\data\SNACDate[] Exist dates for the entity
     */
    private $existDates = null;

    /**
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/cpfDescription/description/existDates/descriptiveNote
     * 
     * @var string Note about the exist dates
     */
    private $existDatesNote = null;

    /**
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/cpfDescription/relations/cpfRelation/*
     * 
     * @var \snac\data\ConstellationRelation[] Constellation relations
     */
    private $relations = null;

    /**
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/cpfDescription/relations/resourceRelation/*
     * 
     * @var \snac\data\ResourceRelation[] Resource relations
     */
    private $resourceRelations = null;

    /**
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/cpfDescription/description/function/*
     * 
     * @var \snac\data\SNACFunction Functions
     */
    private $functions = null;

    /**
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/cpfDescription/description/place/*
     * 
     * @var \snac\data\Place[] Places
     */
    private $places = null;
    
    /**
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/cpfDescription/description/localDescription/@localType=AssociatedSubject/term
     * 
     * @var [wstring[] Subjects
     */
    private $subjects = null;
    
    /**
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/cpfDescription/description/localDescription/@localType=nationalityOfEntity/term
     * 
     * @var string nationality
     */
    private $nationality = null;
    
    /**
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/cpfDescription/description/localDescription/@localType=gender/term
     * 
     * @var string Gender
     */
    private $gender = null;
    
    /**
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/cpfDescription/description/generalContext
     * 
     * @var string General Context
     */
    private $generalContext = null;
    
    /**
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/cpfDescription/description/structureOrGenealogy
     * 
     * @var string Structure Or Genealogy information
     */
    private $structureOrGenealogy = null;
    
    /**
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/cpfDescription/description/mandate
     * 
     * @var string Mandate
     */
    private $mandate = null;

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

        if ($data == null) {
            $this->otherRecordIDs = array ();
            $this->sources = array ();
            $this->maintenanceEvents = array ();
            $this->nameEntries = array ();
            $this->biogHists = array ();
            $this->occupations = array ();
            $this->relations = array ();
            $this->resourceRelations = array ();
            $this->existDates = array ();
            $this->functions = array ();
            $this->places = array ();
            $this->subjects = array();
            $this->legalStatuses = array();
        } else
            parent::__construct($data);
    }

    /**
     * getter for $this->ark
     *
     * * eac-cpf/control/recordId
     * 
     * @return string ARK identifier
     *
     */
    function getArk()
    {
        return $this->ark;
    }

    /**
     * getter for $this->entityType
     *
     * * eac-cpf/cpfDescription/identity/entityType
     * 
     * @return  string Entity type
     *
     */
    function getEntityType()
    {
        return $this->entityType;
    }

    /**
     * getter for $this->otherRecordIDs
     *
     * * eac-cpf/control/otherRecordId
     * * eac-cpf/cpfDescription/identity/entityID
     * 
     * @return string[] Other record IDs by which this constellation may be known
     *
     */
    function getOtherRecordIDs()
    {
        return $this->otherRecordIDs;
    }

    /**
     * getter for $this->maintenanceStatus
     *
     * * eac-cpf/control/maintenanceStatus
     * 
     * @return string Current maintenance status
     *
     */
    function getMaintenanceStatus()
    {
        return $this->maintenanceStatus;
    }

    /**
     * getter for $this->maintenanceAgency
     *
     * * eac-cpf/control/maintenanceAgency/agencyName
     *
     * @return string Latest maintenance agency
     *
     */
    function getMaintenanceAgency()
    {
        return $this->maintenanceAgency;
    }

    /**
     * getter for $this->maintenanceEvents
     *
     * * eac-cpf/control/maintenanceHistory/maintenanceEvent/*
     *
     * @return \snac\data\MaintenanceEvent[] List of maintenance events performed on this constellation
     *
     */
    function getMaintenanceEvents()
    {
        return $this->maintenanceEvents;
    }

    /**
     * getter for $this->sources
     *
     * * /eac-cpf/control/sources/source/@type
     * * /eac-cpf/control/sources/source/@href
     * 
     * @return string[][] List of sources, each source is an array of type,value entries
     *
     */
    function getSources()
    {
        return $this->sources;
    }

    /**
     * getter for $this->legalStatuses
     *
     * * eac-cpf/cpfDescription/description/legalStatus/term
     * * eac-cpf/cpfDescription/description/legalStatus/@vocabularySource
     *
     * Returned as:
     * ```
     * [ ["term" => term, "vocabularySource" => vocSrc], ... ]
     * ```
     * 
     * @return string[][] List of legal status, each status as an array of term,vocabularySource entries
     *
     */
    function getLegalStatuses()
    {
        return $this->legalStatuses;
    }

    /**
     * getter for $this->conventionDeclaration
     *
     * * eac-cpf/control/conventionDeclaration
     * 
     * @return string Convention declaration
     *
     */
    function getConventionDeclaration()
    {
        return $this->conventionDeclaration;
    }

    /**
     * getter for $this->constellationLanguage
     *
     * * eac-cpf/control/languageDeclaration/language
     *
     * @return string Language used for Constellation Record
     *
     */
    function getConstellationLanguage()
    {
        return $this->constellationLanguage;
    }

    /**
     * getter for $this->constellationLanguageCode
     *
     * * eac-cpf/control/languageDeclaration/language/@languageCode
     *
     * @return string Language code used for Constellation Record
     *
     */
    function getConstellationLanguageCode()
    {
        return $this->constellationLanguageCode;
    }

    /**
     * getter for $this->constellationScript
     *
     * * eac-cpf/control/languageDeclaration/script
     * 
     * @return string Script used for Constellation Record
     *
     */
    function getConstellationScript()
    {
        return $this->constellationScript;
    }

    /**
     * getter for $this->constellationScriptCode
     *
     * * eac-cpf/control/languageDeclaration/script/@scriptCode
     *
     * eac-cpf/control/languageDeclaration/script/@scriptCode
     *
     * @return string Script code used for Constellation Record
     *
     */
    function getConstellationScriptCode()
    {
        return $this->constellationScriptCode;
    }

    /**
     * getter for $this->language
     *
     * * eac-cpf/cpfDescription/description/languageUsed/language
     *
     * @return string Language used by the identity described
     *
     */
    function getLanguage()
    {
        return $this->language;
    }

    /**
     * getter for $this->languageCode
     *
     * * eac-cpf/cpfDescription/description/languageUsed/language/@languageCode
     *
     * @return string Language code used by the identity described
     *
     */
    function getLanguageCode()
    {
        return $this->languageCode;
    }

    /**
     * getter for $this->script
     *
     * * eac-cpf/cpfDescription/description/languageUsed/script
     *
     * @return string Script used by the identity described
     *
     */
    function getScript()
    {
        return $this->script;
    }

    /**
     * getter for $this->scriptCode
     *
     * * eac-cpf/cpfDescription/description/languageUsed/script/@scriptCode
     *
     * @return string Script used by the identity described
     *
     */
    function getScriptCode()
    {
        return $this->scriptCode;
    }

    /**
     * getter for $this->nameEntries
     *
     * * eac-cpf/cpfDescription/identity/nameEntry
     *
     * @return \snac\data\NameEntry[] List of name entries for this constellation
     *
     */
    function getNameEntries()
    {
        return $this->nameEntries;
    }

    /**
     * getter for $this->occupations
     *
     * * eac-cpf/cpfDescription/description/occupation/*
     * 
     * @return \snac\data\Occupation[] List of occupations
     *
     */
    function getOccupations()
    {
        return $this->occupations;
    }

    /**
     * getter for $this->biogHists (note plural)
     *
     * All the other code expects biogHist to be a single string. On the off chance that the array of
     * biogHists has more than one, concat all them into a single string and return that string.
     *
     * eac-cpf/cpfDescription/description/biogHist
     * 
     * The return value is a string[]. BiogHist entries for this constellation (in XML strings)
     *
     * @return string[] Returns biog hist as a single string, even though currently the private var biogHist
     * is an array.
     */
    function getBiogHists()
    {
        $normativeBiogHist = '';
        foreach ($this->biogHists as $singleBiogHist)
        {
            $normativeBiogHist .= $singleBiogHist;
        }
        return $normativeBiogHist;
    }

    /**
     * getter for NULL. Downstream foreach gets upset. When we expect an array, always return a
     *
     * * eac-cpf/cpfDescription/description/existDates/dateSet/dateRange/*
     * * eac-cpf/cpfDescription/description/existDates/dateSet/date/*
     * * eac-cpf/cpfDescription/description/existDates/dateRange/*
     * * eac-cpf/cpfDescription/description/existDates/date/*
     *
     * @return \snac\data\SNACDate[] Exist dates for the entity
     *
     */
    function getExistDates()
    {
        // Don't return NULL. Downstream foreach gets upset. When we expect an array, always return an
        // array. No dates is simply an empty array, but NULL implies that dates are conceptually not part of
        // this universe.
        if ($this->existDates)
        {
            return $this->existDates;
        }
        else
        {
            return array();
        }
    }

    /**
     * getter for $this->existDatesNote
     *
     * * eac-cpf/cpfDescription/description/existDates/descriptiveNote
     *
     * @return string Note about the exist dates
     *
     */
    function getExistDatesNote()
    {
        return $this->existDatesNote;
    }

    /**
     * getter for $this->relations
     *
     * * eac-cpf/cpfDescription/relations/cpfRelation/*
     *
     * @return \snac\data\ConstellationRelation[] Constellation relations
     *
     */
    function getRelations()
    {
        return $this->relations;
    }

    /**
     * See private $resourceRelations. This gets an array of ResourceRelation objects.
     *
     * From EAC-CPF tag(s):
     * 
     * * eac-cpf/cpfDescription/relations/resourceRelation/*
     * 
     * @var \snac\data\ResourceRelation[] Resource relations
     */
    function getResourceRelations()
    {
        return $this->resourceRelations;
    }

    /**
     * getter for $this->functions
     *
     * * eac-cpf/cpfDescription/description/function/*
     *
     * @return \snac\data\SNACFunction Functions
     *
     */
    function getFunctions()
    {
        return $this->functions;
    }

    /**
     * getter for $this->places
     *
     * * eac-cpf/cpfDescription/description/place/*
     *
     * @return \snac\data\Place[] Places
     *
     */
    function getPlaces()
    {
        return $this->places;
    }

    /**
     * getter for $this->subjects
     *
     * * eac-cpf/cpfDescription/description/localDescription/@localType=AssociatedSubject/term
     *
     * @return \snac\data\Place[] Places
     *
     */
    function getSubjects()
    {
        return $this->subjects;
    }

    /**
     * getter for $this->nationality
     *
     * * eac-cpf/cpfDescription/description/localDescription/@localType=nationalityOfEntity/term
     *
     * @return  string nationality
     *
     */
    function getNationality()
    {
        return $this->nationality;
    }

    /**
     * getter for $this->gender
     *
     * * eac-cpf/cpfDescription/description/localDescription/@localType=gender/term
     *
     * @return  string Gender
     *
     */
    function getGender()
    {
        return $this->gender;
    }

    /**
     * getter for $this->generalContext
     *
     * * eac-cpf/cpfDescription/description/generalContext
     *
     * @return  string General Context
     *
     */
    function getGeneralContext()
    {
        return $this->generalContext;
    }

    /**
     * getter for $this->structureOrGenealogy
     *
     * * eac-cpf/cpfDescription/description/structureOrGenealogy
     *
     * @return string Structure Or Genealogy information
     *
     */
    function getStructureOrGenealogy()
    {
        return $this->structureOrGenealogy;
    }

    /**
     * getter for $this->mandate
     *
     * * eac-cpf/cpfDescription/description/mandate
     *
     * @return string Mandate
     *
     */
    function getMandate()
    {
        return $this->mandate;
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
            "ark" => $this->ark,
            "entityType" => $this->entityType,
            "otherRecordIDs" => $this->otherRecordIDs,
            "maintenanceStatus" => $this->maintenanceStatus,
            "maintenanceAgency" => $this->maintenanceAgency,
            "maintenanceEvents" => array(),
            "sources" => $this->sources,
            "legalStatuses" => $this->legalStatuses,
            "conventionDeclaration" => $this->conventionDeclaration,
            "constellationLanguage" => $this->constellationLanguage,
            "constellationLanguageCode" => $this->constellationLanguageCode,
            "constellationScript" => $this->constellationScript,
            "constellationScriptCode" => $this->constellationScriptCode,
            "language" => $this->language,
            "languageCode" => $this->languageCode,
            "script" => $this->script,
            "scriptCode" => $this->scriptCode,
            "nameEntries" => array(),
            "occupations" => array(),
            "biogHists" => $this->biogHists,
            "existDates" => array(),
            "existDatesNote" => $this->existDatesNote,
            "relations" => array(),
            "resourceRelations" => array(),
            "functions" => array(),
            "places" => array(),
            "subjects" => $this->subjects,
            "nationality" => $this->nationality,
            "gender" => $this->gender,
            "generalContext" => $this->generalContext,
            "structureOrGenealogy" => $this->structureOrGenealogy,
            "mandate" => $this->mandate
        );

        foreach ($this->maintenanceEvents as $i => $v)
            $return["maintenanceEvents"][$i] = $v->toArray($shorten);

        foreach ($this->nameEntries as $i => $v)
            $return["nameEntries"][$i] = $v->toArray($shorten);

        foreach ($this->occupations as $i => $v)
            $return["occupations"][$i] = $v->toArray($shorten);

        foreach ($this->existDates as $i => $v)
            $return["existDates"][$i] = $v->toArray($shorten);

        foreach ($this->relations as $i => $v)
            $return["relations"][$i] = $v->toArray($shorten);

        foreach ($this->resourceRelations as $i => $v)
            $return["resourceRelations"][$i] = $v->toArray($shorten);

        foreach ($this->functions as $i => $v)
            $return["functions"][$i] = $v->toArray($shorten);

        foreach ($this->places as $i => $v)
            $return["places"][$i] = $v->toArray($shorten);

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

        unset($this->ark);
        if (isset($data["ark"]))
            $this->ark = $data["ark"];
        else
            $this->ark = null;

        unset($this->entityType);
        if (isset($data["entityType"]))
            $this->entityType = $data["entityType"];
        else
            $this->entityType = null;

        unset($this->otherRecordIDs);
        if (isset($data["otherRecordIDs"]))
            $this->otherRecordIDs = $data["otherRecordIDs"];
        else
            $this->otherRecordIDs = array();

        unset($this->maintenanceStatus);
        if (isset($data["maintenanceStatus"]))
            $this->maintenanceStatus = $data["maintenanceStatus"];
        else
            $this->maintenanceStatus = null;

        unset($this->maintenanceAgency);
        if (isset($data["maintenanceAgency"]))
            $this->maintenanceAgency = $data["maintenanceAgency"];
        else
            $this->maintenanceAgency = null;

        unset($this->sources);
        if (isset($data["sources"]))
            $this->sources = $data["sources"];
        else
            $this->sources = array();

        unset($this->legalStatuses);
        if (isset($data["legalStatuses"]))
            $this->legalStatuses = $data["legalStatuses"];
        else
            $this->legalStatuses = array();

        unset($this->conventionDeclaration);
        if (isset($data["conventionDeclaration"]))
            $this->conventionDeclaration = $data["conventionDeclaration"];
        else
            $this->conventionDeclaration = null;

        unset($this->constellationLanguage);
        if (isset($data["constellationLanguage"]))
            $this->constellationLanguage = $data["constellationLanguage"];
        else
            $this->constellationLanguage = null;

        unset($this->constellationLanguageCode);
        if (isset($data["constellationLanguageCode"]))
            $this->constellationLanguageCode = $data["constellationLanguageCode"];
        else
            $this->constellationLanguageCode = null;

        unset($this->constellationScript);
        if (isset($data["constellationScript"]))
            $this->constellationScript = $data["constellationScript"];
        else
            $this->constellationScript = null;

        unset($this->constellationScriptCode);
        if (isset($data["constellationScriptCode"]))
            $this->constellationScriptCode = $data["constellationScriptCode"];
        else
            $this->constellationScriptCode = null;

        unset($this->language);
        if (isset($data["language"]))
            $this->language = $data["language"];
        else
            $this->language = null;

        unset($this->languageCode);
        if (isset($data["languageCode"]))
            $this->languageCode = $data["languageCode"];
        else
            $this->languageCode = null;

        unset($this->script);
        if (isset($data["script"]))
            $this->script = $data["script"];
        else
            $this->script = null;

        unset($this->scriptCode);
        if (isset($data["scriptCode"]))
            $this->scriptCode = $data["scriptCode"];
        else
            $this->scriptCode = null;

        unset($this->biogHists);
        if (isset($data["biogHists"]))
            $this->biogHists = $data["biogHists"];
        else
            $this->biogHists = array();

        unset($this->existDatesNote);
        if (isset($data["existDatesNote"]))
            $this->existDatesNote = $data["existDatesNote"];
        else
            $this->existDatesNote = null;

        unset($this->subjects);
        if (isset($data["subjects"]))
            $this->subjects = $data["subjects"];
        else
            $this->subjects = array();

        unset($this->nationality);
        if (isset($data["nationality"]))
            $this->nationality = $data["nationality"];
        else
            $this->nationality = null;

        unset($this->gender);
        if (isset($data["gender"]))
            $this->gender = $data["gender"];
        else
            $this->gender = null;

        unset($this->generalContext);
        if (isset($data["generalContext"]))
            $this->generalContext = $data["generalContext"];
        else
            $this->generalContext = null;

        unset($this->structureOrGenealogy);
        if (isset($data["structureOrGenealogy"]))
            $this->structureOrGenealogy = $data["structureOrGenealogy"];
        else
            $this->structureOrGenealogy = null;

        unset($this->mandate);
        if (isset($data["mandate"]))
            $this->mandate = $data["mandate"];
        else
            $this->mandate = null;

        unset($this->maintenanceEvents);
        $this->maintenanceEvents = array();
        if (isset($data["maintenanceEvents"])) {
            foreach ($data["maintenanceEvents"] as $i => $entry)
                $this->maintenanceEvents[$i] = new MaintenanceEvent($entry);
        }

        unset($this->nameEntries);
        $this->nameEntries = array();
        if (isset($data["nameEntries"])) {
            foreach ($data["nameEntries"] as $i => $entry)
                $this->nameEntries[$i] = new NameEntry($entry);
        }

        unset($this->occupations);
        $this->occupations = array();
        if (isset($data["occupations"])) {
            foreach ($data["occupations"] as $i => $entry)
                $this->occupations[$i] = new Occupation($entry);
        }

        unset($this->existDates);
        $this->existDates = array();
        if (isset($data["existDates"])) {
            foreach ($data["existDates"] as $i => $entry)
                $this->existDates[$i] = new SNACDate($entry);
        }

        unset($this->relations);
        $this->relations = array();
        if (isset($data["relations"])) {
            foreach ($data["relations"] as $i => $entry)
                $this->relations[$i] = new ConstellationRelation($entry);
        }

        unset($this->resourceRelations);
        $this->resourceRelations = array();
        if (isset($data["resourceRelations"])) {
            foreach ($data["resourceRelations"] as $i => $entry)
                $this->resourceRelations[$i] = new ResourceRelation($entry);
        }

        unset($this->functions);
        $this->functions = array();
        if (isset($data["functions"])) {
            foreach ($data["functions"] as $i => $entry)
                $this->functions[$i] = new SNACFunction($entry);
        }

        unset($this->places);
        $this->places = array();
        if (isset($data["places"])) {
            foreach ($data["places"] as $i => $entry)
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
     * @param string $type Entity type
     */
    public function setEntityType($type) {

        $this->entityType = $type;
    }

    /**
     * Adds an alternate record id
     *
     * @param string $type Type of the alternate id
     * @param string $link Href or other link for the alternate id
     */
    public function addOtherRecordID($type, $link) {

        array_push($this->otherRecordIDs, 
                array (
                        "type" => $type,
                        "href" => $link
                ));
    }

    /**
     * Set maintenance status
     *
     * @param string $status status
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
     * @param string $type Type of the source
     * @param string $link Href or other link to source
     */
    public function addSource($type, $link) {

        array_push($this->sources, array (
                "type" => $type,
                "href" => $link
        ));
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
     * Set the convention declaration
     *
     * @param string $declaration Convention Declaration
     */
    public function setConventionDeclaration($declaration) {

        $this->conventionDeclaration = $declaration;
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
     * Set Language for constellation description
     *
     * @param string $code Short-code for language
     * @param string $value Human-readable language
     */
    public function setLanguage($code, $value) {
        $this->constellationLanguage = $value;
        $this->constellationLanguageCode = $code;
    }

    /**
     * Set Script for constellation description
     *
     * @param string $code Short-code for script
     * @param string $value Human-readable script
     */
    public function setScript($code, $value) {
        $this->constellationScript = $value;
        $this->constellationScriptCode = $code;
    }

    /**
     * Set Languaged used by constellation's identity
     *
     * @param string $code Short-code for language
     * @param string $value Human-readable language
     */
    public function setLanguageUsed($code, $value) {
        $this->language = $value;
        $this->languageCode = $code;
    }

    /**
     * Set Script used by constellation's identity
     *
     * @param string $code Short-code for script
     * @param string $value Human-readable script
     */
    public function setScriptUsed($code, $value) {
        $this->script = $value;
        $this->scriptCode = $code;
    }

    /**
     * Add the subject to this Constellation
     *
     * @param string $subject Subject to add.
     */
    public function addSubject($subject) {
        array_push($this->subjects, $subject);
    }

    /**
     * Set the nationality of this Constellation
     *
     * @param string $nationality Nationality
     */
    public function setNationality($nationality) {
        $this->nationality = $nationality;
    }

    /**
     * Set the gender of this Constellation
     *
     * @param string $gender Gender to set
     */
    public function setGender($gender) {
        $this->gender = $gender;
    }

    /**
     * Set the exist dates for this Constellation
     *
     * @param \snac\data\SNACDate $dates Date object
     */
    public function addExistDates($dates) {

        array_push($this->existDates, $dates);
    }

    /**
     * Set the note on the exist dates for this Constellation
     *
     * @param string $note The descriptive note for the dates
     */
    public function setExistDatesNote($note) {

        $this->existDatesNote = $note;
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
     * Add a place to the constellation
     * 
     * @param \snac\data\Place $place Place to add
     */
    public function addPlace($place) {

        array_push($this->places, $place);
    }
    
    /**
     * Add the general context for this constellation
     * 
     * @param string $context General context
     */
    public function setGeneralContext($context) {
        $this->generalContext = $context;
    }
    
    /**
     * Set the structure or genealogy for this constellation
     * 
     * @param string $structure StructureOrGenealogy information
     */
    public function setStructureOrGenealogy($structure) {
        $this->structureOrGenealogy = $structure;
    }
    
    /**
     * Add a legal status to this constellation
     * 
     * @param string $term Term of the status
     * @param string $vocabularySource Vocabulary source for the term
     */
    public function addLegalStatus($term, $vocabularySource) {
        array_push($this->legalStatuses, array("term"=>$term, "vocabularySource"=>$vocabularySource));
    }
    
    /**
     * Set the mandate of this constellation
     * 
     * @param string $mandate Mandate information
     */
    public function setMandate($mandate) {
        $this->mandate = $mandate;
    }
}
