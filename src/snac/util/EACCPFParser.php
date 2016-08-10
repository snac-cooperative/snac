<?php

/**
 * EAC-CPF Parser File
 *
 * Contains the parser for EAC-CPF files into PHP Identity Constellation objects.
 *
 * License:
 *
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\util;

/**
 * EAC-CPF Parser
 *
 * This class provides the utility to parser EAC-CPF XML files into PHP Identity constellations.
 * After parsing, it returns the \snac\data\Constellation object and provides a method to
 * access any tags or attributes from the file (including their values) that were not
 * understood by the parser.
 *
 * @author Robbie Hott
 *
 */
class EACCPFParser {



    /**
     *
     * @var string[] ARK ID used for warnings and error messages. Set after parsing begins. See setArkID();
     */
    private $arkID = "";


    /**
     *
     * @var string[] The list of namespaces in the document
     */
    private $namespaces;

    /**
     *
     * @var string[] The list of unknown elements and their values
     */
    private $unknowns;

    /**
     * @var string $operation The operation to add to each element that is parsed
     */
    private $operation = null;

    /**
     * @var \snac\util\Vocabulary An object allowing the interaction with vocabulary terms from the
     * database or another source.  It must provide the \snac\util\Vocabulary interface.
     */
    private $vocabulary;

    /**
     * Constructor. This reads all the vocab from the database, and therefore takes a second or two. You
     * probably do not want multiple instances of this parser. Just create one instance and used it as a
     * static class.
     *
     * Order of operations to initialize is a bit unclear. The vocabulary can't exist until the data is
     * parsed, so there must be some pre-parsing step. Generally, once the vocaulary table is populated, we
     * keep using it, even if the database is reset.
     *
     *
     */
    public function __construct() {
        $this->vocabulary = null;
    }

    /**
     * Set Vocabulary Source
     *
     * Use this method to change the vocabulary source. This is useful when testing to replace the vocabulary
     * with a test version.
     *
     * @param \snac\util\Vocabulary $vocab A vocabulary to replace the object in this parser.
     */
    public function setVocabulary($vocab) {
        $this->vocabulary = $vocab;
    }

    /**
     * Use the Default Vocabulary
     *
     * Use this method to instantiate the default vocabulary.  This should be done if the vocabulary
     * has never been overwritten by setVocabulary().
     */
    private function instantiateVocabulary() {
        $this->vocabulary = new \snac\util\LocalVocabulary();
    }

    /**
     * Set the Operation
     *
     * Set the operation for the entire constellation when parsing.  This sets the operation in every
     * legal data structure (all except Term and GeoTerm) to be this operation.  If this method is not
     * called, the resulting PHP Constellation object will have no operations.
     *
     * @param string $operation The operation to set
     */
    public function setConstellationOperation($operation) {
        $this->operation = $operation;
    }

    /**
     * Parse a file into an identity constellation.
     *
     * @param string $filename Filename of the file to parse
     * @return \snac\data\Constellation The resulting constellation
     */
    public function parseFile($filename) {

        try {
            return $this->parse(file_get_contents($filename));
        } catch (\Exception $e) {
            throw new \snac\exceptions\SNACParserException($e->getMessage());
        }
    }

    /**
     * Create a Term object. Assume the vocab has already been initialized.
     *
     *  record_type, script_code, entity_type, event_type, name_type, occupation, language_code, gender,
     *  nationality, maintenance_status, agent_type, document_role, document_type, function_type, function,
     *  subject, date_type, relation_type, place_match, place_type, place_role, source_type
     *
     *  Contributor uses name_type. Find the type 'name_type' for contributor by querying the vocabulary
     *  table. We know that a contributor type could be authorizedForm, therefore:
     *
     *  select * from vocabulary where value ilike '%authorizedform%';
     *
     * @param string $termString A string that can be found in the vocabulary
     *
     * @param string $vocab A vocabulary category such as "record_type", "language_code"
     *
     * @return \snac\data\Term A Term object.
     */
    public function getTerm($termString, $vocab) {
        if ($this->vocabulary == null)
            $this->instantiateVocabulary();

        $term = null;
        if ($this->vocabulary != null) {
            $term = $this->vocabulary->getTermByValue($termString, $vocab);
        } else {
            $term = new \snac\data\Term();
            $term->setTerm($termString);
        }
        return $term;
    }

    /**
     * Generate Display Name for Source
     *
     * Generates a display name for a given source by inspecting the source and creating a name
     * for this entity.  This will be stored in the database until updated by a human to create
     * a more meaningful name.
     *
     * @param \snac\data\Source $source Source object to create name for
     * @param int $i optional The index into the source list
     * @return string the display name
     */
    private function generateSourceDisplayName($source, $i = null) {

        $display = "";

        if ($i != null) {
            $display .= "Source $i: ";
        }

        if (stristr($source->getText(), "ead_entity"))
            $display .= "EAD from ";

        if ($source->getURI() != null && $source->getURI() != "") {
            $display .= $source->getURI();
        }

        return $display;
    }

    /**
     * Parse a string containing EAC-CPF XML into an identity constellation.
     *
     * @param string $xmlText XML text to parse
     * @return \snac\data\Constellation The resulting constellation
     */
    public function parse($xmlText) {

        $xml = simplexml_load_string($xmlText);

        $identity = new \snac\data\Constellation();

        $this->unknowns = array ();
        $this->namespaces = $xml->getNamespaces(true);

        $languageDeclaration = new \snac\data\Language();

        $sourceCounter = 1;

        foreach ($this->getChildren($xml) as $node) {
            if ($node->getName() == "control") {

                foreach ($this->getChildren($node) as $control) {
                    $catts = $this->getAttributes($control);
                    switch ($control->getName()) {
                    case "recordId":
                        $identity->setArkID((string) $control);
                        $this->arkID = (string) $control;
                        $this->markUnknownAtt(
                            array (
                                $node->getName(),
                                $control->getName()
                            ), $catts);
                        break;
                    case "otherRecordId":
                        $term = $this->getTerm($this->getValue($catts["localType"]),"record_type");
                        $sameas = new \snac\data\SameAs();
                        $sameas->setType($term);
                        $sameas->setURI((string) $control);
                        $sameas->setOperation($this->operation);
                        $identity->addOtherRecordID($sameas);
                        break;
                    case "maintenanceStatus":
                        $identity->setMaintenanceStatus($this->getTerm((string) $control, "maintenance_status"));
                        $this->markUnknownAtt(
                            array (
                                $node->getName(),
                                $control->getName()
                            ), $catts);
                        break;
                    case "maintenanceAgency":
                        $agencyInfo = $this->getChildren($control);
                        for($i = 1; $i < count($agencyInfo); $i++)
                            $this->markUnknownTag(
                                array (
                                    $node->getName,
                                    $control->getName()
                                ),
                                array (
                                    $agencyInfo[$i]
                                ));
                        $identity->setMaintenanceAgency(trim((string) $agencyInfo[0]));
                        $this->markUnknownAtt(
                            array (
                                $node->getName(),
                                $control->getName(),
                                $agencyInfo[0]->getName()
                            ), $this->getAttributes($agencyInfo[0]));
                        $this->markUnknownAtt(
                            array (
                                $node->getName(),
                                $control->getName()
                            ), $catts);
                        break;
                    case "languageDeclaration":
                        foreach ($this->getChildren($control) as $lang) {
                            $latts = $this->getAttributes($lang);
                            switch ($lang->getName()) {
                            case "language":
                                if (isset($latts["languageCode"])) {
                                    $code = $latts["languageCode"];
                                    unset($latts["languageCode"]);
                                }
                                /*
                                 * Set the language term globally
                                 * Setting the language Term of a Language object.
                                 */
                                $languageTerm = $this->getTerm($code, "language_code");
                                $languageDeclaration->setLanguage($languageTerm);
                                $this->markUnknownAtt(
                                    array (
                                        $node->getName(),
                                        $control->getName(),
                                        $lang->getName()
                                    ), $latts);
                                break;
                            case "script":
                                if (isset($latts["scriptCode"])) {
                                    $code = $latts["scriptCode"];
                                    unset($latts["scriptCode"]);
                                }

                                /*
                                 * Set the script term globally
                                 * Setting the script Term of a Language object.
                                 */
                                $scriptTerm = $this->getTerm($code, "script_code");
                                $languageDeclaration->setScript($scriptTerm);
                                $this->markUnknownAtt(
                                    array (
                                        $node->getName(),
                                        $control->getName(),
                                        $lang->getName()
                                    ), $latts);
                                break;
                            default:
                                $this->markUnknownTag(
                                    array (
                                        $node->getName(),
                                        $control->getName()
                                    ), $lang);
                            }
                        }
                        $this->markUnknownAtt(
                            array (
                                $node->getName(),
                                $control->getName()
                            ), $catts);
                        break;
                    case "maintenanceHistory":
                        foreach ($this->getChildren($control) as $mevent) {
                            $event = new \snac\data\MaintenanceEvent();
                            foreach ($this->getChildren($mevent) as $mev) {
                                $eatts = $this->getAttributes($mev);
                                switch ($mev->getName()) {
                                case "eventType":
                                    $event->setEventType($this->getTerm((string) $mev, "event_type"));
                                    break;
                                case "eventDateTime":
                                    $event->setEventDateTime((string) $mev);
                                    if (isset($eatts["standardDateTime"])) {
                                        $event->setStandardDateTime($eatts["standardDateTime"]);
                                        unset($eatts["standardDateTime"]);
                                    }
                                    break;
                                case "agentType":
                                    $event->setAgentType($this->getTerm((string) $mev, "agent_type"));
                                    break;
                                case "agent":
                                    $event->setAgent((string) $mev);
                                    break;
                                case "eventDescription":
                                    $event->setEventDescription((string) $mev);
                                    break;
                                default:
                                    $this->markUnknownTag(
                                        array (
                                            $node->getName(),
                                            $control->getName(),
                                            $mevent->getName()
                                        ), $mev);
                                }
                                $this->markUnknownAtt(
                                    array (
                                        $node->getName(),
                                        $control->getName(),
                                        $mevent->getName(),
                                        $mev->getName()
                                    ), $eatts);
                            }
                            $this->markUnknownAtt(
                                array (
                                    $node->getName(),
                                    $control->getName(),
                                    $mevent->getName()
                                ), $this->getAttributes($mevent));

                            $event->setOperation($this->operation);
                            $identity->addMaintenanceEvent($event);
                        }
                        $this->markUnknownAtt(
                            array (
                                $node->getName(),
                                $control->getName()
                            ), $catts);
                        break;
                    case "conventionDeclaration":
                        $cd = new \snac\data\ConventionDeclaration();
                        $cd->setText($control->asXML());
                        $cd->setOperation($this->operation);
                        $identity->addConventionDeclaration($cd);
                        $this->markUnknownAtt(
                            array (
                                $node->getName(),
                                $control->getName()
                            ), $catts);
                        break;
                    case "sources":
                        foreach ($this->getChildren($control) as $source) {
                            $satts = $this->getAttributes($source);
                            $sourceObj = new \snac\data\Source();
                            if (isset($satts["type"])) {
                                $sourceObj->setType($this->getTerm($this->getValue($satts['type']), "source_type"));
                                unset($satts["type"]);
                            }
                            if (isset($satts["href"])) {
                                $sourceObj->setURI($satts['href']);
                                unset($satts["href"]);
                            }
                            foreach ($this->getChildren($source) as $innerSource) {
                                if ($innerSource->getName() == "objectXMLWrap")
                                    $sourceObj->setText($innerSource->asXML());
                                else if ($innerSource->getName() == "descriptiveNote")
                                    $sourceObj->setNote($innerSource->asXML());
                                else
                                    $this->markUnknownTag(
                                        array (
                                            $node->getName(),
                                            $control->getName(),
                                            $source->getName()
                                        ), $innerSource);
                            }
                            $sourceObj->setDisplayName($this->generateSourceDisplayName($sourceObj, $sourceCounter++));
                            $sourceObj->setOperation($this->operation);
                            $identity->addSource($sourceObj);
                        }
                        break;
                    default:
                        $this->markUnknownTag(
                            array (
                                $node->getName()
                            ),
                            array (
                                $control
                            ));
                    }
                }
            } elseif ($node->getName() == "cpfDescription") {

                foreach ($this->getChildren($node) as $desc) {
                    $datts = $this->getAttributes($desc);

                    switch ($desc->getName()) {
                    case "identity":
                        foreach ($this->getChildren($desc) as $ident) {
                            $iatts = $this->getAttributes($ident);
                            switch ($ident->getName()) {
                            case "entityId":
                                $term = $this->getTerm("unknown","entityid_type");
                                if (isset($iatts["localType"])) {
                                    $term = $this->getTerm($this->getValue($iatts["localType"]), "entityid_type");
                                    unset($iatts["localType"]);
                                }
                                $sameas = new \snac\data\EntityId();
                                $sameas->setType($term);
                                $sameas->setText((string) $ident);
                                $sameas->setOperation($this->operation);
                                $identity->addEntityID($sameas);
                                break;
                            case "entityType":
                                $identity->setEntityType($this->getTerm((string) $ident, "entity_type"));
                                break;
                            case "nameEntry":
                                $nameEntry = new \snac\data\NameEntry();
                                if (isset($iatts["preferenceScore"])) {
                                    $nameEntry->setPreferenceScore($iatts["preferenceScore"]);
                                    unset($iatts["preferenceScore"]);
                                }
                                $nameLanguage = new \snac\data\Language();
                                if (isset($iatts["lang"])) {
                                    $nameLanguage->setLanguage($this->getTerm($iatts["lang"], "language_code"));
                                    unset($iatts["lang"]);
                                }
                                if (isset($iatts["scriptCode"])) {
                                    $nameLanguage->setScript($this->getTerm($iatts["scriptCode"], "script_code"));
                                    unset($iatts["scriptCode"]);
                                }
                                if (!$nameLanguage->isEmpty()) {
                                    $nameLanguage->setOperation($this->operation);
                                    $nameEntry->setLanguage($nameLanguage);
                                }

                                foreach ($this->getChildren($ident) as $npart) {
                                    switch ($npart->getName()) {
                                    case "part":
                                        $nameEntry->setOriginal((string) $npart);
                                        // Add the part as a Component as well
                                        $component = new \snac\data\NameComponent();
                                        $component->setText((string) $npart);
                                        $component->setType($this->getTerm("Name", "name_component"));
                                        $component->setOperation($this->operation);
                                        $nameEntry->addComponent($component);
                                        break;
                                    case "alternativeForm":
                                    case "authorizedForm":
                                        $ctObj = new \snac\data\Contributor();
                                        $ctObj->setType($this->getTerm($this->getValue($npart->getName()), 'name_type'));
                                        $ctObj->setName((string) $npart);
                                        $ctObj->setOperation($this->operation);
                                        $nameEntry->addContributor($ctObj);
                                        break;
                                    case "useDates":
                                        foreach ($this->getChildren($npart) as $dateEntry) {
                                            if ($dateEntry->getName() == "dateRange" ||
                                                $dateEntry->getName() == "date") {
                                                $nameEntry->addDate(
                                                    $this->parseDate($dateEntry,
                                                                     array (
                                                                         $node->getName(),
                                                                         $desc->getName(),
                                                                         $ident->getName(),
                                                                         $npart->getName()
                                                                     )));
                                            } else {
                                                $this->markUnknownTag(
                                                    array (
                                                        $node->getName(),
                                                        $desc->getName(),
                                                        $ident->getName(),
                                                        $npart->getName()
                                                    ), array (
                                                        $dateEntry
                                                    ));
                                            }
                                        }
                                        break;
                                    default:
                                        $this->markUnknownTag(
                                            array (
                                                $node->getName(),
                                                $desc->getName(),
                                                $ident->getName()
                                            ),
                                            array (
                                                $npart
                                            ));
                                    }
                                    $this->markUnknownAtt(
                                        array (
                                            $node->getName(),
                                            $desc->getName(),
                                            $ident->getName(),
                                            $npart->getName()
                                        ), $this->getAttributes($npart));
                                }
                                $nameEntry->setOperation($this->operation);
                                $identity->addNameEntry($nameEntry);
                                break;
                            default:
                                $this->markUnknownTag(
                                    array (
                                        $node->getName(),
                                        $desc->getName()
                                    ),
                                    array (
                                        $ident
                                    ));
                            }
                            $this->markUnknownAtt(
                                array (
                                    $node->getName(),
                                    $desc->getName(),
                                    $ident->getName()
                                ), $iatts);
                        }
                        break;
                    case "description":
                        foreach ($this->getChildren($desc) as $desc2) {
                            $d2atts = $this->getAttributes($desc2);
                            switch ($desc2->getName()) {
                            case "existDates":
                                foreach ($this->getChildren($desc2) as $dates) {
                                    switch ($dates->getName()) {
                                    case "dateSet":
                                        foreach ($this->getChildren($dates) as $dateEntry) {
                                            $dateEntryName = $dateEntry->getName();
                                            if ($dateEntryName == "dateRange" || $dateEntryName == "date") {
                                                $date = $this->parseDate($dateEntry,
                                                                         array (
                                                                             $node->getName(),
                                                                             $desc->getName(),
                                                                             $desc2->getName(),
                                                                             $dates->getName()
                                                                         ));
                                                /*
                                                 * Old code: $identity->addExistDates($date);
                                                 * Add a date object to the list of date objects for this constellation.
                                                 */
                                                $date->setOperation($this->operation);
                                                $identity->addDate($date);
                                            } else {
                                                $this->markUnknownTag(
                                                    array (
                                                        $node->getName(),
                                                        $desc->getName(),
                                                        $desc2->getName(),
                                                        $dates->getName()
                                                    ),
                                                    array (
                                                        $dateEntry
                                                    ));
                                            }
                                        }
                                        break;
                                    case "dateRange":
                                    case "date":
                                        $date = $this->parseDate($dates,
                                                                 array (
                                                                     $node->getName() . $desc->getName(),
                                                                     $desc2->getName()
                                                                 ));
                                        $date->setOperation($this->operation);
                                        $identity->addDate($date);
                                        break;
                                    case "descriptiveNote":
                                        /*
                                         *
                                         * eac-cpf/cpfDescription/description/existDates/descriptiveNote
                                         *
                                         * Unclear intent of the original code.
                                         *
                                         * $identity->setExistDatesNote((string) $dates);
                                         *
                                         * $dates is an array SimpleXMLElement[], which is being typecast to a
                                         * string. Whatever it does, we try to do the same thing with the new
                                         * code.
                                         *
                                         * The original setExistDatesNote() from Constellation.php did this:
                                         *
                                         * $this->existDatesNote = $note;
                                         *
                                         * The original code clearly assumes only one date. Lets go with
                                         * adding a note to the first date in the $identity Constellation
                                         * object.
                                         */
                                        if ($firstDate = $identity->getDateList()[0])
                                        {
                                            $note = "";
                                            if ($dates->count() == 0)
                                                $note = (string) $dates;
                                            else {
                                                foreach ($dates->children() as $noteChild)
                                                    $note .= $noteChild->asXML();
                                            }
                                            $firstDate->setNote($note);
                                        }
                                        else
                                        {
                                            $message = sprintf("Warning: exists date note, but no exists date: %s\n", $this->arkID);
                                            $stderr = fopen('php://stderr', 'w');
                                            fwrite($stderr,"  $message\n");
                                            fclose($stderr);
                                        }
                                        $this->markUnknownAtt(
                                            array (
                                                $node->getName(),
                                                $desc->getName(),
                                                $desc2->getName(),
                                                $dates->getName()
                                            ), $this->getAttributes($dates));
                                        break;
                                    default:
                                        $this->markUnknownTag(
                                            array (
                                                $node->getName(),
                                                $desc->getName(),
                                                $desc2->getName()
                                            ),
                                            array (
                                                $dates
                                            ));
                                    }
                                }
                                break;
                            case "place":
                                // Create a list of places to acrete
                                $newPlaces = array();
                                $place = new \snac\data\Place();
                                $platts = $this->getAttributes($desc2);
                                if (isset($platts["localType"])) {
                                    $place->setType($this->getTerm($this->getValue($platts["localType"]), "place_type"));
                                    unset($platts["localType"]);
                                }
                                foreach ($this->getChildren($desc2) as $placePart) {
                                    switch ($placePart->getName()) {
                                    case "date":
                                    case "dateRange":
                                        $place->addDate(
                                            $this->parseDate($placePart,
                                                             array (
                                                                 $node->getName(),
                                                                 $desc->getName(),
                                                                 $desc2->getName()
                                                             )));
                                        break;
                                    case "descriptiveNote":
                                        $place->setNote((string) $placePart);
                                        $this->markUnknownAtt(
                                            array (
                                                $node->getName(),
                                                $desc->getName(),
                                                $desc2->getName(),
                                                $placePart->getName()
                                            ), $this->getAttributes($placePart));
                                        break;
                                    case "placeRole":
                                        $place->setRole($this->getTerm((string) $placePart, "place_role"));
                                        $this->markUnknownAtt(
                                            array (
                                                $node->getName(),
                                                $desc->getName(),
                                                $desc2->getName(),
                                                $placePart->getName()
                                            ), $this->getAttributes($placePart));
                                        break;
                                    case "placeEntry":
                                        // placeEntry is now the new Place.  So we need to gather each of these
                                        // individually, take everything from the place tag and add it to the
                                        // Place objects created by the placeEntry tags, then add those Places
                                        // to the identity constellation
                                        array_push($newPlaces, $this->parsePlaceEntry($placePart,
                                                                   array (
                                                                       $node->getName(),
                                                                       $desc->getName(),
                                                                       $desc2->getName()
                                                                   )));

                                        break;
                                    default:
                                        $this->markUnknownTag(
                                            array (
                                                $node->getName(),
                                                $desc->getName(),
                                                $desc2->getName()
                                            ),
                                            array (
                                                $placePart
                                            ));
                                    }
                                }
                                $this->markUnknownAtt(
                                    array (
                                        $node->getName(),
                                        $desc->getName(),
                                        $desc2->getName()
                                    ), $platts);

                                // Go through each placeEntry found and add the Place to the IC with
                                // all the information from the <place> tag appended.
                                foreach ($newPlaces as $newPlace) {
                                    $newPlace->setType($place->getType());
                                    foreach ($place->getDateList() as $date) {
                                        $newPlace->addDate(clone($date));
                                    }
                                    $newPlace->setNote($place->getNote());
                                    $newPlace->setRole($place->getRole());
                                    $newPlace->setOperation($this->operation);
                                    $identity->addPlace($newPlace);
                                }

                                break;

                            case "localDescription":
                                $subTags = $this->getChildren($desc2);
                                $subTag = $subTags[0];
                                for($i = 1; $i < count($subTags); $i++) {
                                    $this->markUnknownTag(
                                        array (
                                            $node->getName(),
                                            $desc->getName(),
                                            $desc2->getName()
                                        ),
                                        array (
                                            $subTags[$i]
                                        ));
                                }
                                switch ($d2atts["localType"]) {
                                    // Each of these is in a sub element
                                case "http://socialarchive.iath.virginia.edu/control/term#AssociatedSubject":
                                    $subject = new \snac\data\Subject();
                                    $subject->setTerm($this->getTerm((string) $subTag, "subject"));
                                    $subject->setOperation($this->operation);
                                    $identity->addSubject($subject);
                                    break;
                                case "http://viaf.org/viaf/terms#nationalityOfEntity":
                                    //TODO Sometimes nationality has non-standard placeEntry with only country code
                                    $term = $this->getTerm((string) $subTag, "nationality");
                                    $nationality = new \snac\data\Nationality();
                                    $nationality->setTerm($term);
                                    $nationality->setOperation($this->operation);
                                    $identity->addNationality($nationality);
                                    break;
                                case "http://viaf.org/viaf/terms#gender":
                                    $gender = new \snac\data\Gender();
                                    $gender->setTerm($this->getTerm($this->getValue((string) $subTag), "gender"));
                                    $gender->setOperation($this->operation);
                                    $identity->addGender($gender);
                                    break;
                                default:
                                    $this->markUnknownTag(
                                        array (
                                            $node->getName(),
                                            $desc->getName()
                                        ),
                                        array (
                                            $desc2
                                        ));
                                }
                                break;
                            case "languageUsed":
                                /*
                                 * The test example test1.xml doesn't have langaugeUsed, only languageDeclaration, yet
                                 * the test code clearly used to pass constellation langauge tests. How was that possible?
                                 */
                                $language = new \snac\data\Language();
                                $updatedLanguage = false;
                                foreach ($this->getChildren($desc2) as $lang) {
                                    $latts = $this->getAttributes($lang);
                                    switch ($lang->getName()) {
                                    case "language":
                                        if (isset($latts["languageCode"])) {
                                            $code = $latts["languageCode"];
                                            unset($latts["languageCode"]);
                                        }
                                        $tmp = $this->getTerm($code, "language_code");
                                        $language->setLanguage($tmp);
                                        $updatedLanguage = true;
                                        $this->markUnknownAtt(
                                            array (
                                                $node->getName(),
                                                $desc->getName(),
                                                $desc2->getName(),
                                                $lang->getName()
                                            ), $latts);
                                        break;
                                    case "script":
                                        if (isset($latts["scriptCode"])) {
                                            $code = $latts["scriptCode"];
                                            unset($latts["scriptCode"]);
                                        }
                                        $tmp = $this->getTerm($code, "script_code");
                                        $language->setScript($tmp);
                                        $updatedLanguage = true;
                                        $this->markUnknownAtt(
                                            array (
                                                $node->getName(),
                                                $desc->getName(),
                                                $desc2->getName(),
                                                $lang->getName()
                                            ), $latts);
                                        break;
                                    default:
                                        $this->markUnknownTag(
                                            array (
                                                $node->getName(),
                                                $desc->getName(),
                                                $desc2->getName()
                                            ), $lang);
                                    }
                                }
                                if ($updatedLanguage) {
                                    $language->setOperation($this->operation);
                                    $identity->addLanguageUsed($language);
                                }
                                $this->markUnknownAtt(
                                    array (
                                        $node->getName(),
                                        $desc->getName(),
                                        $desc2->getName()
                                    ), $d2atts);
                                break;
                            case "generalContext":
                                $gc = new \snac\data\GeneralContext();
                                $gc->setText($desc2->asXML());
                                $gc->setOperation($this->operation);
                                $identity->addGeneralContext($gc);
                                break;
                            case "legalStatus":
                                $legalStatusTerm = null;
                                foreach ($this->getChildren($desc2) as $legal) {
                                    $legalAtts = $this->getAttributes($legal);
                                    switch ($legal->getName()) {
                                    case "term":
                                        $legalStatusTerm = $this->getTerm((string) $legal, "legal_status");
                                        if (isset($legalAtts["vocabularySource"])) {
                                            $legalStatusTerm->setURI($legalAtts["vocabularySource"]);
                                            unset($legalAtts["vocabularySource"]);
                                        }
                                        break;
                                    default:
                                        $this->markUnknownTag(
                                            array (
                                                $node->getName(),
                                                $desc->getName(),
                                                $desc2->getName()
                                            ),
                                            array (
                                                $legal
                                            ));
                                    }
                                    $this->markUnknownAtt(
                                        array (
                                            $node->getName(),
                                            $desc->getName(),
                                            $desc2->getName(),
                                            $legal->getName()
                                        ), $legalAtts);
                                }
                                if ($legalStatusTerm != null && !$legalStatusTerm->isEmpty()) {
                                    $legalStatus = new \snac\data\LegalStatus();
                                    $legalStatus->setTerm($legalStatusTerm);
                                    $legalStatus->setOperation($this->operation);
                                    $identity->addLegalStatus($legalStatus);
                                }
                                break;
                            case "mandate":
                                $mandate = new \snac\data\Mandate();
                                $mandate->setText($desc2->asXML());
                                $mandate->setOperation($this->operation);
                                $identity->addMandate($mandate);
                                break;
                            case "structureOrGenealogy":
                                $sog = new \snac\data\StructureOrGenealogy();
                                $sog->setText($desc2->asXML());
                                $sog->setOperation($this->operation);
                                $identity->addStructureOrGenealogy($sog);
                                break;
                            case "occupation":
                                $occupation = new \snac\data\Occupation();
                                foreach ($this->getChildren($desc2) as $occ) {
                                    $oatts = $this->getAttributes($occ);
                                    switch ($occ->getName()) {
                                    case "term":
                                        $occupation->setTerm($this->getTerm((string) $occ, "occupation"));
                                        if (isset($oatts["vocabularySource"])) {
                                            $occupation->setVocabularySource($oatts["vocabularySource"]);
                                            unset($oatts["vocabularySource"]);
                                        }
                                        break;
                                    case "descriptiveNote":
                                        $occupation->setNote((string) $occ);
                                        break;
                                    case "dateRange":
                                        $date = $this->parseDate($occ,
                                                                 array (
                                                                     $node->getName(),
                                                                     $desc->getName(),
                                                                     $desc2->getName()
                                                                 ));
                                        /*
                                         * Occupation extends AbstractData which has a date list.
                                         *
                                         * change setDateRange() to addDate()
                                         */
                                        $occupation->addDate($date);
                                        break;
                                    default:
                                        $this->markUnknownTag(
                                            array (
                                                $node->getName(),
                                                $desc->getName(),
                                                $desc2->getName()
                                            ),
                                            array (
                                                $occ
                                            ));
                                    }
                                    $this->markUnknownAtt(
                                        array (
                                            $node->getName(),
                                            $desc->getName(),
                                            $desc2->getName(),
                                            $occ->getName()
                                        ), $oatts);
                                }
                                $occupation->setOperation($this->operation);
                                $identity->addOccupation($occupation);
                                $this->markUnknownAtt(
                                    array (
                                        $node->getName(),
                                        $desc->getName(),
                                        $desc2->getName()
                                    ), $this->getAttributes($desc2));
                                break;
                            case "function":
                                $function = new \snac\data\SNACFunction();
                                foreach ($this->getChildren($desc2) as $fun) {
                                    $fatts = $this->getAttributes($fun);
                                    switch ($fun->getName()) {
                                    case "term":
                                        $function->setTerm($this->getTerm((string) $fun, "function"));
                                        if (isset($fatts["vocabularySource"])) {
                                            $function->setVocabularySource($fatts["vocabularySource"]);
                                            unset($fatts["vocabularySource"]);
                                        }
                                        break;
                                    case "descriptiveNote":
                                        $function->setNote((string) $fun);
                                        break;
                                    case "dateRange":
                                        $date = $this->parseDate($fun,
                                                                 array (
                                                                     $node->getName(),
                                                                     $desc->getName(),
                                                                     $desc2->getName()
                                                                 ));
                                        /*
                                         * Function extends AbstractData which has a date list.
                                         *
                                         * change setDateRange() to addDate()
                                         */
                                        $function->addDate($date);
                                        break;
                                    default:
                                        $this->markUnknownTag(
                                            array (
                                                $node->getName(),
                                                $desc->getName(),
                                                $desc2->getName()
                                            ),
                                            array (
                                                $fun
                                            ));
                                        $this->markUnknownAtt(
                                            array (
                                                $node->getName(),
                                                $desc->getName(),
                                                $desc2->getName(),
                                                $fun->getName()
                                            ), $fatts);
                                    }
                                }
                                $fatts = $this->getAttributes($desc2);
                                if (isset($fatts["localType"])) {
                                    $function->setType(new \snac\data\Term($fatts["localType"]));
                                    unset($fatts["localType"]);
                                }
                                $function->setOperation($this->operation);
                                $identity->addFunction($function);
                                $this->markUnknownAtt(
                                    array (
                                        $node->getName(),
                                        $desc->getName(),
                                        $desc2->getName()
                                    ), $fatts);
                                break;
                            case "biogHist":
                                $bh = new \snac\data\BiogHist();
                                $bh->setText($desc2->asXML());
                                /*
                                 * Is it correct to set the language of the biogHist to the <languageDeclaration> element?
                                 */
                                $languageDeclaration->setOperation($this->operation);
                                $bh->setLanguage($languageDeclaration);
                                $bh->setOperation($this->operation);
                                $identity->addBiogHist($bh);
                                break;
                            default:
                                $this->markUnknownTag(
                                    array (
                                        $node->getName(),
                                        $desc->getName()
                                    ),
                                    array (
                                        $desc2
                                    ));
                            }
                        }
                        break;
                    case "relations":
                        foreach ($this->getChildren($desc) as $rel) {
                            $ratts = $this->getAttributes($rel);
                            // We want 'href' to always exist. If it doesn't, warn, and set it to the empty string.
                            if ( ! isset($ratts['href']))
                            {
                                // In retrospect, we can silently just make this an empty string, probably.
                                /*
                                 * $message = sprintf("Warning: empty href in relations for: %s\n", $this->arkID);
                                 * $stderr = fopen('php://stderr', 'w');
                                 * fwrite($stderr,"  $message\n");
                                 * fclose($stderr);
                                 */
                                $ratts['href'] = "";
                            }
                            switch ($rel->getName()) {
                            case "cpfRelation":

                                // If this is a sameAs, we need to treat it differently:
                                if ($this->getValue($ratts["arcrole"]) == "sameAs") {
                                    $sameas = new \snac\data\SameAs();
                                    $sameas->setURI($ratts['href']);
                                    $sameas->setType($this->getTerm($this->getValue($ratts["arcrole"]), "record_type"));
                                    unset($ratts["arcrole"]);
                                    unset($ratts["href"]);
                                    unset($ratts["role"]);
                                    unset($ratts["type"]);

                                    $children = $this->getChildren($rel);
                                    if (! empty($children)) {
                                        $sameas->setText((string) $children[0]);
                                    }
                                    foreach ($children as $child) {
                                        switch ($child->getName()) {
                                            case "relationEntry":
                                                $sameas->setText((string) $child);
                                                break;
                                            case "date":
                                            case "dateRange":
                                                // SameAs cannot have dates
                                                break;
                                            case "descriptiveNote":
                                                $scm = new \snac\data\SNACControlMetadata();
                                                $scm->setSourceData($child->asXML());
                                                $scm->setNote("Descriptive note parsed from EAC-CPF");
                                                $scm->setOperation($this->operation);
                                                $sameas->addSNACControlMetadata($scm);
                                                $this->markUnknownAtt(
                                                        array (
                                                                $node->getName(),
                                                                $desc->getName(),
                                                                $rel->getName(),
                                                                $child->getName()
                                                        ), $this->getAttributes($child));
                                                break;
                                            default:
                                                $this->markUnknownTag(
                                                    array (
                                                        $node->getName(),
                                                        $desc->getName(),
                                                        $rel->getName()
                                                    ),
                                                    array (
                                                        $child
                                                    ));
                                        }
                                    }
                                    $this->markUnknownAtt(
                                            array (
                                                    $node->getName(),
                                                    $desc->getName(),
                                                    $rel->getName()
                                            ), $ratts);
                                    $sameas->setOperation($this->operation);
                                    $identity->addOtherRecordID($sameas);
                                } else { // real relation

                                    $relation = new \snac\data\ConstellationRelation();

                                    $relation->setType($this->getTerm($this->getValue($ratts["arcrole"]), "relation_type"));
                                    $relation->setSourceArkID($identity->getArk());
                                    $relation->setTargetArkID($ratts['href']);
                                    if (isset($ratts["role"]))
                                        $relation->setTargetEntityType($this->getTerm($this->getValue($ratts['role']), "entity_type"));
                                    /*
                                     * cpfRelation/@type cpfRelation@xlink:type
                                     *
                                     * The only value this ever has is "simple". Daniel says not to save it, and implicitly hard code when
                                     * serializing export.
                                     */
                                    $relation->setAltType($this->getTerm($this->getValue($ratts["type"]), "relation_type"));
                                    if (isset($ratts['cpfRelationType'])) {
                                        $relation->setCPFRelationType($this->getTerm($ratts['cpfRelationType'], "relation_type"));
                                        unset($ratts["cpfRelationType"]);
                                    }
                                    unset($ratts["arcrole"]);
                                    unset($ratts["href"]);
                                    unset($ratts["role"]);
                                    unset($ratts["type"]);
                                    $children = $this->getChildren($rel);
                                    if (! empty($children)) {
                                        $relation->setContent((string) $children[0]);
                                    }
                                    foreach ($children as $child) {
                                        switch ($child->getName()) {
                                        case "relationEntry":
                                            $relation->setContent((string) $child);
                                            break;
                                        case "date":
                                        case "dateRange":
                                            /*
                                             * setDates() changed to addDate()
                                             */
                                            $relation->addDate(
                                                $this->parseDate($child,
                                                                 array (
                                                                     $node->getName(),
                                                                     $desc->getName(),
                                                                     $rel->getName()
                                                                 )));
                                            break;
                                        case "descriptiveNote":
                                            $relation->setNote($child->asXML());
                                            $this->markUnknownAtt(
                                                array (
                                                    $node->getName(),
                                                    $desc->getName(),
                                                    $rel->getName(),
                                                    $child->getName()
                                                ), $this->getAttributes($child));
                                            break;
                                        default:
                                            $this->markUnknownTag(
                                                array (
                                                    $node->getName(),
                                                    $desc->getName(),
                                                    $rel->getName()
                                                ),
                                                array (
                                                    $child
                                                ));
                                        }
                                    }
                                    $this->markUnknownAtt(
                                        array (
                                            $node->getName(),
                                            $desc->getName(),
                                            $rel->getName()
                                        ), $ratts);
                                    $relation->setOperation($this->operation);
                                    $identity->addRelation($relation);
                                }
                                break;
                            case "resourceRelation":
                                $relation = new \snac\data\ResourceRelation();
                                $relation->setDocumentType($this->getTerm($this->getValue($ratts["role"]), "document_type"));
                                $relation->setLink($ratts['href']);
                                $relation->setLinkType($this->getTerm($this->getValue($ratts['type']), "document_type"));
                                $relation->setRole($this->getTerm($this->getValue($ratts['arcrole']), "document_role"));
                                foreach ($this->getChildren($rel) as $relItem) {
                                    switch ($relItem->getName()) {
                                    case "relationEntry":
                                        $relation->setContent((string) $relItem);
                                        $relAtts = $this->getAttributes($relItem);
                                        if (isset($relAtts["localType"])) {
                                            $relation->setRelationEntryType($this->getTerm($this->getValue($relAtts["localType"]), "relation_type"));
                                            unset($relAtts["localType"]);
                                        }
                                        $this->markUnknownAtt(
                                            array (
                                                $node->getName(),
                                                $desc->getName(),
                                                $rel->getName(),
                                                $relItem->getName()
                                            ), $relAtts);
                                        break;
                                    case "objectXMLWrap":
                                        $relation->setSource($relItem->asXML());
                                        $this->markUnknownAtt(
                                            array (
                                                $node->getName(),
                                                $desc->getName(),
                                                $rel->getName(),
                                                $relItem->getName()
                                            ), $this->getAttributes($relItem));
                                        break;
                                    case "descriptiveNote":
                                        $relation->setNote($relItem->asXML());
                                        $this->markUnknownAtt(
                                            array (
                                                $node->getName(),
                                                $desc->getName(),
                                                $rel->getName(),
                                                $relItem->getName()
                                            ), $this->getAttributes($relItem));
                                        break;
                                    default:
                                        $this->markUnknownTag(
                                            array (
                                                $node->getName(),
                                                $desc->getName(),
                                                $rel->getName()
                                            ),
                                            array (
                                                $relItem
                                            ));
                                    }
                                }
                                $relation->setOperation($this->operation);
                                $identity->addResourceRelation($relation);
                                break;
                            default:
                                $this->markUnknownTag(
                                    array (
                                        $node->getName(),
                                        $desc->getName()
                                    ),
                                    array (
                                        $rel
                                    ));
                            }
                        }
                        break;
                    default:
                        $this->markUnknownTag(
                            array (
                                $node->getName()
                            ),
                            array (
                                $desc
                            ));
                    }
                }
            } else {
                $this->markUnknownTag(array (), array (
                    $node->getName()
                ));
            }
        }
        $identity->setOperation($this->operation);
        return $identity;
    }

    /**
     * Get the tags and attributes that were not understood by this parser.
     * The resulting strings are
     * <code>
     * full/path/to/tag :: value
     * full/path/to/@att :: value
     * </code>
     *
     * @return string[] List of tags and attributes and their values
     */
    public function getMissing() {

        return $this->unknowns;
    }

    /**
     * Get the value of the parameter string, stripping off any namespace
     * portions and returning only the text value.
     *
     * Currently, this splits the string based on the pound sign (#) and
     * returns the end of the string.
     *
     * @param string $value Tag/Attribute value to strip the controlled vocab from
     * @return string Cleaned string, with no namespace text
     */
    private function getValue($value) {
        $parts = explode("#", $value);
        if (count($parts) == 2)
            return $parts[1];
        else
            return $value;
    }

    /**
     * Get the attributes for a given SimpleXMLElement, ignoring all namespaces.
     * This is a way
     * to get around the need to query for each namespace separately
     *
     * @param SimpleXMLElement $element Element to query for attributes
     * @return string[] Attributes and values, attName => value
     */
    private function getAttributes($element) {

        $att = array ();

        foreach ($element->attributes() as $k => $v)
            $att[$k] = (string) $v;

        foreach ($this->namespaces as $s => $n) {
            foreach ($element->attributes($n) as $k => $v)
                $att[$k] = (string) $v;
        }
        return $att;
    }

    /**
     * Get the children for a given SimpleXMLElement, ignoring all namespaces.
     * This is a way to
     * get around the need to query for each namespace separately.
     *
     * @param SimpleXMLElement $element Element to query for children
     * @return SimpleXMLElement[] array of children elements from any namespace
     */
    private function getChildren($element) {

        $children = array ();

        foreach ($element->children() as $k => $v) {
            // if (!isset($children[$k])) $children[$k] = array();
            // array_push($children, $v);
        }

        foreach ($this->namespaces as $s => $n) {
            foreach ($element->children($n) as $k => $v)
                // if (!isset($children[$k])) $children[$k] = array();
                array_push($children, $v);
        }
        return $children;
    }

    /**
     * Mark a tag or element as unknown to this parser.
     *
     * Adds the given information to the list of missing data.
     *
     * @param string[] $xpath Ordered array of the path names down to the current element.
     * @param string[] $missing Array of missing elements (tag or att) as "name"=>"value" pairs.
     * @param boolean $isTag Flag to determine if the $missing is a list of tags or attributes.
     */
    private function markUnknowns($xpath, $missing, $isTag) {

        $path = implode("/", $xpath);
        $path .= "/";

        if (! $isTag) {
            $path .= "@";
        }

        foreach ($missing as $k => $v) {
            array_push($this->unknowns, $path . $k . " :: " . $v);
        }
    }

    /**
     * Mark an unknown attribute from the given path and element
     *
     * @param string[] $xpath Ordered array of the path names down to just before the current element.
     * @param string[] $missing Array of missing tags as "name"=>"value" pairs.
     */
    private function markUnknownAtt($xpath, $missing) {

        $this->markUnknowns($xpath, $missing, false);
    }

    /**
     * Mark an unknown tag from the given path and element
     *
     * @param string[] $xpath Ordered array of the path names down to the current tag.
     * @param string[] $missing Array of missing attributes as "name"=>"value" pairs.
     */
    private function markUnknownTag($xpath, $missing) {

        foreach ($missing as $m) {
            // Mark this tag as missing
            $this->markUnknowns($xpath, array (
                $m->getName() => (string) $m
            ), true);
            // Mark all attributes of this tag as missing
            $this->markUnknowns(array_merge($xpath, array (
                $m->getName()
            )), $this->getAttributes($m), false);

            // Traverse down the children
            $this->markUnknownTag(array_merge($xpath, array (
                $m->getName()
            )), $this->getChildren($m));
        }
    }

    /**
     * Parse the given date element into its appropriate object.
     * This requires testing for both a dateRange, as well
     * as notBefore and notAfter ranges on each date given.
     *
     * @param \SimpleXMLElement $dateElement Date element to parse
     * @param string[] $xpath All pieces of the xpath leading up to the date to parse
     * @return \snac\data\SNACDate The resulting date object
     */
    private function parseDate($dateElement, $xpath) {

        $date = new \snac\data\SNACDate();
        if ($dateElement->getName() == "dateRange") {
            // Handle the date range
            $date->setRange(true);
            foreach ($this->getChildren($dateElement) as $dateTag) {
                /*
                 *   File /data/merge/99166-w62r7n84.xml ok.
                 * PHP Notice:  Undefined index: standardDate in /lv1/home/twl8n/snac/src/snac/util/EACCPFParser.php on line 1255
                 * PHP Notice:  Undefined index: standardDate in /lv1/home/twl8n/snac/src/snac/util/EACCPFParser.php on line 1279
                 */

                /*
                 * File /data/merge/99166-w6163116.xml ok.
                 * PHP Notice:  Undefined index: localType in /lv1/home/twl8n/snac/src/snac/util/EACCPFParser.php on line 1256
                 */

                $dateAtts = $this->getAttributes($dateTag);
                switch ($dateTag->getName()) {
                case "fromDate":
                    if (((string) $dateTag) != null && ((string) $dateTag) != '') {
                        $dateType = null;
                        if (isset($dateAtts["localType"]))
                            $dateType = $this->getTerm($this->getValue($dateAtts["localType"]), "date_type");

                        if (! isset($dateAtts['standardDate']))
                        {
                            $dateAtts['standardDate'] = '';
                        }
                        $date->setFromDate((string) $dateTag,
                                           $dateAtts["standardDate"],
                                           $dateType);
                        $notBefore = null;
                        $notAfter = null;
                        if (isset($dateAtts["notBefore"]))
                            $notBefore = $dateAtts["notBefore"];
                        if (isset($dateAtts["notAfter"]))
                            $notAfter = $dateAtts["notAfter"];
                        $date->setFromDateRange($notBefore, $notAfter);

                        unset($dateAtts["notBefore"]);
                        unset($dateAtts["notAfter"]);
                        unset($dateAtts["standardDate"]);
                        unset($dateAtts["localType"]);
                        $this->markUnknownAtt(
                            array_merge($xpath,
                                        array (
                                            $dateElement->getName(),
                                            $dateTag->getName()
                                        )), $dateAtts);
                    }
                    break;
                case "toDate":
                    if (((string) $dateTag) != null && ((string) $dateTag) != '') {
                        $dateType = null;
                        if (isset($dateAtts["localType"]))
                            $dateType = $this->getTerm($this->getValue($dateAtts["localType"]), "date_type");
                        if (! isset($dateAtts['standardDate']))
                        {
                            $dateAtts['standardDate'] = '';
                        }
                        $date->setToDate((string) $dateTag,
                                $dateAtts["standardDate"],
                                $dateType);
                        $notBefore = null;
                        $notAfter = null;
                        if (isset($dateAtts["notBefore"]))
                            $notBefore = $dateAtts["notBefore"];
                        if (isset($dateAtts["notAfter"]))
                            $notAfter = $dateAtts["notAfter"];
                        $date->setToDateRange($notBefore, $notAfter);

                        unset($dateAtts["notBefore"]);
                        unset($dateAtts["notAfter"]);
                        unset($dateAtts["standardDate"]);
                        unset($dateAtts["localType"]);
                        $this->markUnknownAtt(
                            array_merge($xpath,
                                        array (
                                            $dateElement->getName(),
                                            $dateTag->getName()
                                        )), $dateAtts);
                    }
                    break;
                default:
                    $this->markUnknownTag(
                        array_merge($xpath,
                                    array (
                                        $dateElement->getName()
                                    )),
                        array (
                            $dateTag
                        ));
                }
            }
        } elseif ($dateElement->getName() == "date") {
            /*
             * Sanity check standardDate. Unclear what should happen if we don't have a standardDate
             * value. This code leaves $date unchanged, and hopes that the initialization was sane.
             */
            if (isset($dateAtts["standardDate"]))
            {

                // Handle the single date that appears
                $date->setRange(false);
                $dateAtts = $this->getAttributes($dateElement);

                $dateType = null;
                if (isset($dateAtts["localType"]))
                    $dateType = $this->getTerm($this->getValue($dateAtts["localType"]), "date_type");
                $date->setDate((string) $dateElement,
                        $dateAtts["standardDate"],
                        $dateType);
                $notBefore = null;
                $notAfter = null;
                if (isset($dateAtts["notBefore"]))
                    $notBefore = $dateAtts["notBefore"];
                if (isset($dateAtts["notAfter"]))
                    $notAfter = $dateAtts["notAfter"];
                $date->setDateRange($notBefore, $notAfter);

                unset($dateAtts["notBefore"]);
                unset($dateAtts["notAfter"]);
                unset($dateAtts["standardDate"]);
                unset($dateAtts["localType"]);
                $this->markUnknownAtt(
                    array_merge($xpath,
                                array (
                                    $dateElement->getName()
                                )), $dateAtts);
            }
            else
            {
                /* Silently make dates with no standard date only partial complete.
                 *
                 * Arg 3 is type which is Term object
                 *
                 * $message = sprintf("Warning: empty standardDate in date for: %s\n", $this->arkID);
                 * $stderr = fopen('php://stderr', 'w');
                 * fwrite($stderr,"  $message\n");
                 * fclose($stderr);
                 *
                 *
                 *
                 */
                $date->setDate((string) $dateElement, '', null);
            }
        }
        $date->setOperation($this->operation);
        return $date;
    }

    /**
     * Parse a place entry XML tag into a \snac\data\Place object.
     * This is a recursively called function, since
     * some placeEntry tags are nested. The best example is the snac:placeEntry tag, which may include the following
     * placeEntry tag variants:
     *
     * <ul>
     * <li>placeEntry</li>
     * <li>placeEntryMaybeSame</li>
     * <li>placeEntryLikelySame</li>
     * <li>placeEntryBestMaybeSame</li>
     * </ul>
     *
     * @param \SimpleXMLElement $placeTag Place element to parse
     * @param string[] $xpath all pieces of the xpath leading up to the $placeTag element
     * @return \snac\data\Place resulting Place object
     */
    private function parsePlaceEntry($placeTag, $xpath) {

        $plAtts = $this->getAttributes($placeTag);
        $place = new \snac\data\Place();
        $geoTerm = new \snac\data\GeoTerm();

        $geoInfoSet = false;
        $score = 0;

        if (isset($plAtts["latitude"])) {
            $geoInfoSet = true;
            $geoTerm->setLatitude($plAtts["latitude"]);
            unset($plAtts["latitude"]);
        }
        if (isset($plAtts["longitude"])) {
            $geoInfoSet = true;
            $geoTerm->setLongitude($plAtts["longitude"]);
            unset($plAtts["longitude"]);
        }
        if (isset($plAtts["localType"])) {
            $place->setType($this->getTerm($plAtts["localType"], "place_type"));
            unset($plAtts["localType"]);
        }
        if (isset($plAtts["administrationCode"])) {
            $geoInfoSet = true;
            $geoTerm->setAdministrationCode($plAtts["administrationCode"]);
            unset($plAtts["administrationCode"]);
        }
        if (isset($plAtts["countryCode"])) {
            $geoInfoSet = true;
            $geoTerm->setCountryCode($plAtts["countryCode"]);
            unset($plAtts["countryCode"]);
        }
        if (isset($plAtts["vocabularySource"])) {
            $geoInfoSet = true;
            $geoTerm->setVocabularySource($plAtts["vocabularySource"]);
            unset($plAtts["vocabularySource"]);
        }
        if (isset($plAtts["certaintyScore"])) {
            $score = $plAtts["certaintyScore"];
            unset($plAtts["certaintyScore"]);
        }
        $this->markUnknownAtt(array_merge($xpath, array (
                $placeTag->getName()
        )), $plAtts);

        // Set the original string. If this is a snac:placeEntry, it will be empty, but there will be a sub-placeEntry
        // that will be found below to overwrite the original string
        $place->setOriginal((string) $placeTag);

        // Look for the children, check for a snac:placeEntryBestMaybeSame or snac:placeEntryLikelySame
        foreach ($this->getChildren($placeTag) as $child) {
            switch ($child->getName()) {
                case "placeEntryBestMaybeSame":
                case "placeEntryLikelySame":
                    // Use this as the GeoTerm for this object!
                    $plAtts = $this->getAttributes($child);

                    if (isset($plAtts["latitude"])) {
                        $geoInfoSet = true;
                        $geoTerm->setLatitude($plAtts["latitude"]);
                        unset($plAtts["latitude"]);
                    }
                    if (isset($plAtts["longitude"])) {
                        $geoInfoSet = true;
                        $geoTerm->setLongitude($plAtts["longitude"]);
                        unset($plAtts["longitude"]);
                    }
                    if (isset($plAtts["administrationCode"])) {
                        $geoInfoSet = true;
                        $geoTerm->setAdministrationCode($plAtts["administrationCode"]);
                        unset($plAtts["administrationCode"]);
                    }
                    if (isset($plAtts["countryCode"])) {
                        $geoInfoSet = true;
                        $geoTerm->setCountryCode($plAtts["countryCode"]);
                        unset($plAtts["countryCode"]);
                    }
                    if (isset($plAtts["vocabularySource"])) {
                        $geoInfoSet = true;
                        $geoTerm->setVocabularySource($plAtts["vocabularySource"]);
                        unset($plAtts["vocabularySource"]);
                    }
                    if (isset($plAtts["certaintyScore"])) {
                        $score = $plAtts["certaintyScore"];
                        unset($plAtts["certaintyScore"]);
                    }

                    $geoTerm->setName((string) $child);

                    $this->markUnknownAtt(array_merge(
                            $xpath,
                            array (
                                $placeTag->getName(),
                                $child->getName()
                            )), $plAtts);

                    break;
                case "placeEntryMaybeSame":
                    // Silently ignore the rest of the list
                    break;
                case "placeEntry":
                    // If a snac:placeEntry exists, then the original string is stored in another
                    // internal placeEntry, so we must set it again.
                    $place->setOriginal((string) $child);
                    $this->markUnknownAtt(
                            array_merge($xpath,
                                    array (
                                            $placeTag->getName(),
                                            $child->getName()
                                    )), $this->getAttributes($child));
                    break;
                default:
                    $this->markUnknownTag(
                            array_merge($xpath,
                                    array (
                                            $placeTag->getName()
                                    )), array (
                                    $child
                            ));
            }
        }

        // Create a SCM object for this place to store the original.

        $scm = new \snac\data\SNACControlMetadata();
        $scm->setSourceData($place->getOriginal());
        $scm->setNote("Parsed from SNAC EAC-CPF.");
        $scm->setOperation($this->operation);
        $place->addSNACControlMetadata($scm);

        // If the geo information was set, try to find the real term
        if ($geoInfoSet) {
            $realGeoPlace = null;
            if ($this->vocabulary != null) {
                $realGeoPlace = $this->vocabulary->getGeoTermByURI($geoTerm->getVocabularySource());
            }
            if ($realGeoPlace != null)
                $place->setGeoTerm($realGeoPlace);
            else
                $place->setGeoTerm($geoTerm);
            $place->setScore($score);
        }

        $place->setOperation($this->operation);
        return $place;
    }
}
