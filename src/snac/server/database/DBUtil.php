<?php
  /**
   * High level database abstraction layer.
   *
   * License:
   *
   *
   * @author Tom Laudeman
   * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
   * @copyright 2015 the Rector and Visitors of the University of Virginia, and
   *            the Regents of the University of California
   */

namespace snac\server\database;

/**
 * High level database class. 
 *
 * This is what the rest of the server sees as an interface to the database. There
 * is no SQL here. This knows about data structure from two points of view: constellation php data, and tables in the
 * database. Importantly, this code has no idea where the constellation comes from, nor how data gets into the
 * database. Constellation data classes are elsewhere, and SQL is elsewhere.
 *
 * You know, so far, all the functions in this class could be static, as long as the $db were passed in as an
 * arg, rather than being passed to the constructor.
 *
 * @author Tom Laudeman
 *        
 */
class DBUtil
{
    /**
     * SQL object
     *
     * @var \snac\server\database\SQL low-level SQL class
     */
    private $sql = null;
    
    /**
     * Used by setDeleted() and clearDeleted() to check table name.
     *
     * @var string[] Associative list where keys are table names legal to delete from.
     *
     */ 
    private $canDelete = null; 

    /** 
     * The constructor for the DBUtil class. 
     */
    public function __construct() 
    {
        $db = new \snac\server\database\DatabaseConnector();
        $this->sql = new SQL($db);
        $this->canDelete = array_fill_keys(array('name', 'name_component', 'name_contributor',
                                                 'contributor', 'date_range', 'source', 
                                                 'source_link', 'control', 'pre_snac_maintenance_history',
                                                 'occupation', 'place', 'function', 
                                                 'nationality', 'subject', 
                                                 'related_identity', 'related_resource'), 1);

    }

    /**
     * Utility function to return the SQL object for this DBUtil instance. Currently only used for testing, and that may be the only valid use.
     * @return \snac\server\database\SQL Return the SQL object of this DBUtil instance.
     */
    public function sqlObj()
    {
        return $this->sql;
    }

    /**
     * Get all the vocabulary from the database in tabular form
     *
     * @return string[][] array of vocabulary terms and associated information
     */
    public function getAllVocabulary() {
        return $this->sql->selectAllVocabulary();
    }

    /**
     * Access some system-wide authentication and/or current user info.
     *
     * Hard coded for now to return id and role.
     *
     * @param string $userid The text userid, sql table appuser.userid, used to get the appuser.id and the
     * user's primary role.
     *
     * @return string[] Associative list of user info data.
     */
    public function getAppUserInfo($userid)
    {
        $uInfo = $this->sql->getAppUserInfo($userid);
        return $uInfo;
    }
    
    /** 
     * Utility function to create a SNACDate object from associative list of date data.
     * 
     * Is there another word for "insert"? SQL uses insert, but this is higher level than the SQL class.
     * $id is a Constellation object
     * 
     * @param string[] Associative list of s single date's data
     * 
     * @return SNACDate
     */
    public static function buildDate($vhInfo, $singleDate)
    {
        $dateObj = new \snac\data\SNACDate();
        $dateObj->setRange($singleDate['is_range']);
        $dateObj->setFromDate($singleDate['from_date'],
                              $singleDate['from_date'],
                              $singleDate['from_type'] ); 
        $dateObj->setFromDateRange($singleDate['from_not_before'], $singleDate['from_not_after']); 
        $dateObj->setToDate($singleDate['to_date'],
                            $singleDate['to_date'],
                            $singleDate['to_type']); 
        $dateObj->setToDateRange($singleDate['to_not_before'], $singleDate['to_not_after']);
        // Set nrdVersion to null for now.
        $dateObj->setDBInfo($singleDate['version'], $singleDate['id']);
        return $dateObj;
    }    
        
    /**
     * A helper function to get a constellation from the db for testing purposes.
     *
     * @return string[] Return the standard vh_info associative list with the keys 'version' and 'main_id'
     * from the constellation.
     * 
     */
    public function demoConstellation()
    {
        list($version, $mainID) = $this->sql->randomConstellationID();
        return array('version' => $version, 'main_id' => $mainID);
    }

    /**
     * Select a given constellation from the database based on version and main_id.
     * Create an empty constellation by calling the constructor with no args. Then used the setters to add
     * individual properties of the class(es).
     *
     * | php                                                    | sql                    |
     * |--------------------------------------------------------+------------------------|
     * | setArkID                                               | ark_id                 |
     * | setEntityType                                          | entity_type            |
     * | setGender                                              | gender                 |
     * | setLanguage('language_code','language')                | language               |
     * | setLanguage('language_code','language')                | language_code          |
     * | setScript('script_code', 'script')                     | script                 |
     * | setScript('script_code', 'script')                     | script_code            |
     * | setLanguageUsed('language_used_code', 'language_used') | language_used          |
     * | setScriptUsed('script_used_code', 'script_used')       | script_used            |
     * | setNationality                                         | nationality            |
     * | addBiogHist                                            | biog_hist              |
     * | addExistDates                                          | exist_date             |
     * | setGeneralContext                                      | general_context        |
     * | setStructureOrGenealogy                                | structure_or_genealogy |
     * | setConventionDeclaration                               | convention_declaration |
     * | setMandate                                             | mandate                |
     * |                                                        |                        |
     *
     * @param string[] $vhInfo associative list with keys 'version', 'main_id', 'id'. The version and main_id you
     * want. Note that constellation component version numbers are the max() <= version requested.  main_id is
     * the unique id across all tables in this constellation. This is not the nrd.id, but is
     * version_history.main_id which is also nrd.main_id, etc.
     *
     * @param string $appUserID The internal id of the user from appuser.id. Used for locking records, and checking locks.
     *
     * @return \snac\data\Constellation A PHP constellation object.
     * 
     */
    public function selectConstellation($vhInfo, $appUserID)
    {
        $cObj = new \snac\data\Constellation();

        $row = $this->sql->selectConstellation($vhInfo);

        $cObj->setArkID($row['ark_id']);
        $cObj->setEntityType($row['entity_type']);
        $cObj->setGender($row['gender']);
        $cObj->setLanguage($row['language_code'], $row['language']);
        $cObj->setScript($row['script_code'], $row['script']);
        $cObj->setLanguageUsed('', '');
        $cObj->setScriptUsed('', '');
        $cObj->setNationality($row['nationality']);
        $cObj->addBiogHist($row['biog_hist']);
        $cObj->setGeneralContext($row['general_context']);
        $cObj->setStructureOrGenealogy($row['structure_or_genealogy']);
        $cObj->setConventionDeclaration($row['convention_declaration']);
        $cObj->setMandate($row['mandate']);

        $cObj->setVersion($vhInfo['version']);
        $cObj->setID($row['main_id']);

        $this->populateExistDate($row['id'], $cObj);

        $oridRows = $this->sql->selectOtherRecordID($vhInfo); 
        foreach ($oridRows as $singleOrid)
        {
            $cObj->addOtherRecordID($singleOrid['link_type'], $singleOrid['other_id']);
        }
        
        $subjRows = $this->sql->selectSubject($vhInfo); 
        foreach ($subjRows as $singleSubj)
        {
            $cObj->addSubject($singleSubj['subject_id']);
        }

        /*
         * Note: $cObj passed by reference and changed in place.
         */ 
        $this->populateNameEntry($vhInfo, $cObj);
        $this->populateOccupation($vhInfo, $cObj);
        $this->populateRelation($vhInfo, $cObj); // aka cpfRelation
        $this->populateRelatedResource($vhInfo, $cObj); // resourceRelation
        $this->populateFunction($vhInfo, $cObj);

        /* 
         * todo: places.
         * 
         * todo: maintenanceEvents
         * 
         */
        return $cObj;
    } // end selectConstellation

    /** 
     * nameEntry objects
     *
     * test with: scripts/get_constellation_demo.php 2 10
     *
     * That constellation has 3 name contributors.
     * 
     * | php                                        | sql table name   |
     * |--------------------------------------------+------------------|
     * | setOriginal                                | original         |
     * | setPreferenceScore                         | preference_score |
     * | setLanguage                                | language         |
     * | setScriptCode                              | script_code      |
     * | setDBInfo                                  | version, id      |
     * | addContributor(string $type, string $name) |                  |
     * 
     * | php                              | sql table name_contributor |
     * |----------------------------------+----------------------------|
     * |                                  | name_id                    |
     * | getContributors()['contributor'] | short_name                 |
     * | getContributors()['type']        | name_type                  |
     * |                                  |                            |
     * 
     * @param string[] $vhInfo associative list with keys 'version', 'main_id'.
     * @param $cObj snac\data\Constellation object, passed by reference, and changed in place
     * 
     */
    public function populateNameEntry($vhInfo, &$cObj)
    {
        $neRows = $this->sql->selectNameEntry($vhInfo);
        foreach ($neRows as $oneName)
        {
            /* 
             * printf("pn id: %s version: %s name-main_id: %s constellation-main_id: %s\n",
             *        $oneName['id'],
             *        $oneName['version'],
             *        $oneName['main_id'],
             *        $vhInfo['main_id']);
             */
            $neObj = new \snac\data\NameEntry();
            $neObj->setOriginal($oneName['original']);
            $neObj->setLanguage($oneName['language']);
            $neObj->setScriptCode($oneName['script_code']);
            $neObj->setPreferenceScore($oneName['preference_score']);
            foreach ($oneName['contributors'] as $contrib)
            {
                $neObj->addContributor($contrib['name_type'], $contrib['short_name']);
            }
            $neObj->setDBInfo($oneName['version'], $oneName['id']);
            
            $cObj->addNameEntry($neObj);
        }
    }

    /**
     * Select date range(s) from db, foreach create SNACDate object, add to Constellation object.
     *
     * Note: $cObj passed by reference and changed in place.
     *
     * @param int $rowID the nrd.id actual row id from table nrd.
     * @param $cObj snac\data\Constellation object, passed by reference, and changed in place
     */
    public function populateExistDate($rowID, &$cObj)
    {
        $dateRows = $this->sql->selectDate($rowID, $cObj->getVersion());
        foreach ($dateRows as $singleDate)
        {
            $dateObj = new \snac\data\SNACDate();
            $dateObj->setRange($singleDate['is_range']);
            $dateObj->setFromDate($singleDate['from_date'],
                                  $singleDate['from_date'],
                                  $singleDate['from_type'] ); 
            $dateObj->setFromDateRange($singleDate['from_not_before'], $singleDate['from_not_after']); 
            $dateObj->setToDate($singleDate['to_date'],
                                $singleDate['to_date'],
                                $singleDate['to_type']);
            $dateObj->setToDateRange($singleDate['to_not_before'], $singleDate['to_not_after']);
            $dateObj->setDBInfo($singleDate['version'], $singleDate['id']);
            $cObj->addExistDates($dateObj);
        }
    }

    /**
     * Get Occupation from the db, populate occupation object(s), add to Constellation object passed by
     * reference.
     *
     * Need to add date range
     * Need to add vocabulary source
     * 
     * | php                 | sql               |
     * |---------------------+-------------------|
     * | setDBInfo           | id                |
     * | setDBInfo           | version           |
     * | setDBInfo           | main_id           |
     * | setTerm             | occupation_id     |
     * | setNote             | note              |
     * | setVocabularySource | vocabulary_source |
     *
     * @param string[] $vhInfo associative list with keys 'version' and 'main_id'.
     * @param $cObj snac\data\Constellation object, passed by reference, and changed in place
     * 
     */
    public function populateOccupation($vhInfo, &$cObj)
    {
        $occRows = $this->sql->selectOccupation($vhInfo);
        foreach ($occRows as $oneOcc)
        {
            $occObj = new \snac\data\Occupation();
            $occObj->setTerm($oneOcc['occupation_id']);
            $occObj->setVocabularySource($oneOcc['vocabulary_source']);
            $occObj->setNote($oneOcc['note']);
            $occObj->setDBInfo($oneOcc['version'], $oneOcc['id']);
            $cObj->addOccupation($occObj);
        }
    }

    /**
     * Populate relation object(s), and add to existing Constellation object.
     *
     * test with: scripts/get_constellation_demo.php 2 10
     *
     * 
     * | php                                 | sql              |
     * |-------------------------------------+------------------|
     * | setDBInfo                           | id               |
     * | setDBInfo                           | version          |
     * | setDBInfo                           | main_id          |
     * | setTargetConstellation              | related_id       |
     * | setTargetArkID                      | related_ark      |
     * | setTargetType  aka targetEntityType | role             |
     * | setType                             | arcrole          |
     * | setCPFRelationType                  | relation_type    |
     * | setContent                          | relation_entry   |
     * | setDates                            | date             |
     * | setNote                             | descriptive_note |
     * 
     * @param string[] $vhInfo associative list with keys 'version' and 'main_id'.
     * @param $cObj snac\data\Constellation object, passed by reference, and changed in place
     */
    public function populateRelation($vhInfo, &$cObj)
    {
        $relRows = $this->sql->selectRelation($vhInfo);
        foreach ($relRows as $oneRel)
        {
            $relatedObj = new \snac\data\ConstellationRelation();
            $relatedObj->setTargetConstellation($oneRel['related_id']);
            $relatedObj->setTargetArkID($oneRel['related_ark']);
            $relatedObj->setTargetType($oneRel['role']);
            $relatedObj->setType($oneRel['arcrole']);
            $relatedObj->setCPFRelationType($oneRel['relation_type']);
            $relatedObj->setContent($oneRel['relation_entry']);
            $relatedObj->setDates($oneRel['date']);
            $relatedObj->setNote($oneRel['descriptive_note']);
            $relatedObj->setDBInfo($oneRel['version'], $oneRel['id']);
            $cObj->addRelation($relatedObj);
        }
    }


    /**
     * Populate the RelatedResource object(s), and add it/them to an existing Constellation object.
     *
     * resourceRelation
     *
     * 
     * | php                  | sql                 |
     * |----------------------+---------------------|
     * | setDBInfo            | id                  |
     * | setDBInfo            | version             |
     * | setDBInfo            | main_id             |
     * | setDocumentType      | role                |
     * | setRelationEntryType | relation_entry_type |
     * | setLink              | href                |
     * | setRole              | arcrole             |
     * | setContent           | relation_entry      |
     * | setSource            | object_xml_wrap     |
     * | setNote              | descriptive_note    |
     *
     * @param string[] $vhInfo associative list with keys 'version' and 'main_id'.
     * @param $cObj snac\data\Constellation object, passed by reference, and changed in place
     *
     */
    public function populateRelatedResource($vhInfo, &$cObj)
    {
        $rrRows = $this->sql->selectRelatedResource($vhInfo); 
        foreach ($rrRows as $oneRes)
        {
            $rrObj = new \snac\data\ResourceRelation();
            $rrObj->setDocumentType($oneRes['role']);
            $rrObj->setRelationEntryType($oneRes['relation_entry_type']);
            $rrObj->setLink($oneRes['href']);
            $rrObj->setRole($oneRes['arcrole']);
            $rrObj->setContent($oneRes['relation_entry']);
            $rrObj->setSource($oneRes['object_xml_wrap']);
            $rrObj->setNote($oneRes['descriptive_note']);
            $rrObj->setDBInfo($oneRes['version'], $oneRes['id']);
            $cObj->addResourceRelation($rrObj);
        }
    }


    /**
     * Populate the SNACFunction object(s), and add it/them to an existing Constellation object.
     *
     * @param string[] $vhInfo associative list with keys 'version' and 'main_id'.
     * @param $cObj snac\data\Constellation object, passed by reference, and changed in place
     *
     */
    public function populateFunction($vhInfo, &$cObj)
    {
        $funcRows = $this->sql->selectFunction($vhInfo);
        foreach ($funcRows as $oneFunc)
        {
            $fObj = new \snac\data\SNACFunction();
            $fObj->setType($oneFunc['function_type']);
            $fObj->setTerm($oneFunc['function_id']);
            $fObj->setVocabularySource($oneFunc['vocabulary_source']);
            $fObj->setNote($oneFunc['note']);
            $fObj->setDBInfo($oneFunc['version'], $oneFunc['id']);
            $fDate = $this->buildDate($vhInfo, $oneFunc['date']);
            $fObj->setDateRange($fDate);
            $cObj->addFunction($fObj);
        }
    }

    /**
     * Write a PHP Constellation object to the database. This is a new constellation, and will get new version
     * and main_id values. Calls saveConstellation() to call a sql function to do the actual writing.
     *  
     * @param Constallation $id A PHP Constellation object.
     *
     * @param string $userid The user's appuser.id value from the db. 
     *
     * @param string $role The current role.id value of the user. Comes from role.id and table appuser_role_link.
     *
     * @param string $icstatus One of the allowed status values from icstatus. This becomes the new status of the inserted constellation.
     *
     * @param string $note A user-created note for what was done to the constellation. A check-in note.
     *
     * @return string[] An associative list with keys 'version', 'main_id'. There might be a more useful
     * return value such as true for success, and false for failure. This function might need to call into the
     * system-wide user message class that we haven't written yet.
     * 
     */

    public function insertConstellation($id, $userid, $role, $icstatus, $note)
    {
        $vhInfo = $this->sql->insertVersionHistory($userid, $role, $icstatus, $note);
        $this->saveConstellation($id, $userid, $role, $icstatus, $note, $vhInfo);
        return $vhInfo;
    } // end insertConstellation


    /**
     * Update a php constellation that is already in the database. Calls saveConstellation() to call lower
     * level code to update the database.
     *  
     * @param \snac\data\Constellation $id A PHP Constellation object.
     *
     * @param string $userid The user's appuser.id value from the db. 
     *
     * @param string $role The current role.id value of the user. Comes from role.id and table appuser_role_link.
     *
     * @param string $icstatus One of the allowed status values from icstatus. This becomes the new status of the inserted constellation.
     *
     * @param string $note A user-created note for what was done to the constellation. A check-in note.
     *
     * @param int $main_id The main_id for this constellation.
     * 
     * @return string[] An associative list with keys 'version', 'main_id'. There might be a more useful
     * return value such as true for success, and false for failure. This function might need to call into the
     * system-wide user message class that we haven't written yet.
     * 
     */
    public function updateConstellation($id, $userid, $role, $icstatus, $note, $main_id)
    {
        $newVersion = $this->sql->updateVersionHistory($userid, $role, $icstatus, $note, $main_id);
        $vhInfo = array('version' => $newVersion, 'main_id' => $main_id);
        $this->saveConstellation($id, $userid, $role, $icstatus, $note, $vhInfo);
        return $vhInfo;
    }
    
    /**
     * Private function. Update a php constellation that is already in the database. This is called from
     * insertConstellation() or updateConstellation().
     *  
     * @param \snac\data\Constellation $id A PHP Constellation object.
     *
     * @param string $userid The user's appuser.id value from the db. 
     *
     * @param string $role The current role.id value of the user. Comes from role.id and table appuser_role_link.
     *
     * @param string $icstatus One of the allowed status values from icstatus. This becomes the new status of the inserted constellation.
     *
     * @param string $note A user-created note for what was done to the constellation. A check-in note.
     *
     * @param string[] $vhInfo Array with keys 'version', 'main_id' for this constellation.
     * 
     * @return string[] An associative list with keys 'version', 'main_id'. There might be a more useful
     * return value such as true for success, and false for failure. This function might need to call into the
     * system-wide user message class that we haven't written yet.
     * 
     */
    private function saveConstellation($id, $userid, $role, $icstatus, $note, $vhInfo)
    {
        $this->sql->insertNrd($vhInfo,
                              $id->getExistDates(),
                              array($id->getArk(),
                                    $id->getEntityType(),
                                    $id->getBiogHists(),
                                    $id->getNationality(),
                                    $id->getGender(),
                                    $id->getGeneralContext(),
                                    $id->getStructureOrGenealogy(),
                                    $id->getMandate(),
                                    $id->getConventionDeclaration(),
                                    $id->getConstellationLanguage(),
                                    $id->getConstellationLanguageCode(),
                                    $id->getConstellationScript(),
                                    $id->getConstellationScriptCode()));

        foreach ($id->getOtherRecordIDs() as $otherID)
        {
            $otherID['type'] = $otherID['type'];
            // Sanity check otherRecordID
            if ($otherID['type'] != 'MergedRecord' and
                $otherID['type'] != 'viafID')
            {
                $msg = sprintf("Warning: unexpected otherRecordID type: %s for ark: %s\n",
                               $otherID['type'],
                               $id->getArk());
                // TODO: Throw warning or log
            }
            // otherID as an object:
            // $oid = $otherID->getID();
            $oid = null;
            $this->sql->insertOtherID($vhInfo, $otherID['type'], $otherID['href'], $oid);
        }

        /* 
         * Constellation name entry data is already an array of name entry data. 
         * getUseDates() returns SNACDate[] (An array of SNACDate objects.)
         */
        foreach ($id->getNameEntries() as $ndata)
        {
            $this->saveName($vhInfo, $ndata);
        }

        foreach ($id->getSources() as $sdata)
        {
            // 'type' is always simple, and Daniel says we can ignore it. It was used in EAC-CPF just to quiet
            // validation.

            // Fix this whens sources becomes an object
            // $sid = $id->getID();
            $sid = null;
            $this->sql->insertSource($vhInfo,
                                     $sdata['href'],
                                     $sid);
        }

        foreach ($id->getLegalStatuses() as $sdata)
        {
            printf("Need to insert legalStatuses...\n");
        }

        // fdata is foreach data. Just a notation that the generic variable is for local use in this loop.
        foreach ($id->getOccupations() as $fdata)
        {
            $this->sql->insertOccupation($vhInfo,
                                         $fdata->getTerm(),
                                         $fdata->getVocabularySource(),
                                         $fdata->getDates(),
                                         $fdata->getNote(),
                                         $fdata->getID());
        }


        /* 
         *  | php function        | sql               | cpf                             |
         *  |---------------------+-------------------+---------------------------------|
         *  | getType             | function_type     | function/@localType             |
         *  | getTerm             | function_id       | function/term                   |
         *  | getVocabularySource | vocabulary_source | function/term/@vocabularySource |
         *  | getNote             | note              | function/descriptiveNote        |
         *  | getDates            | table date_range  | function/dateRange              |
         *
         *
         * I considered adding keys for the second arg, but is not clear that using them for sanity checking
         * would gain anything. The low level code would become more fragile, and would break "separation of
         * concerns". The sanity check would require that the low level code have knowledge about the
         * structure of things that aren't really low level. Remember: SQL code only knows how to put data in
         * the database. Any sanity check should happen up here.
         *
         *
         *  SNACFunction->getDates() returns a single SNACDate.
         */

        foreach ($id->getFunctions() as $fdata)
        {
            $this->sql->insertFunction($vhInfo,
                                       $fdata->getType(),
                                       $fdata->getVocabularySource(),
                                       $fdata->getNote(),
                                       $fdata->getTerm(),
                                       $fdata->getID(),
                                       $fdata->getDates());
        }

        foreach ($id->getSubjects() as $term)
        {
            // Fix this when subject becomes an object.
            // $sid = $subjectObject->getID();
            $sid = null;
            $this->sql->insertSubject($vhInfo, $term, $sid); 
        }

        /*
          ignored: we know our own id value: sourceConstellation, // id fk
          ignored: we know our own ark: sourceArkID,  // ark why are we repeating this?
          ignored: always 'simple', altType, cpfRelation@xlink:type vocab source_type, .type

          | placeholder | php                 | what                                          | sql               |
          |-------------+---------------------+-----------------------------------------------+-------------------|
          |           1 | $vhInfo['version']  |                                               | version           |
          |           2 | $vhInfo['main_id']  |                                               | main_id           |
          |           3 | targetConstellation | id fk to version_history                      | .related_id       |
          |           4 | targetArkID         | ark                                           | .related_ark      |
          |           5 | targetEntityType    | cpfRelation@xlink:role, vocab entity_type     | .role             |
          |           6 | type                | cpfRelation@xlink:arcrole vocab relation_type | .arcrole          |
          |           7 | cpfRelationType     | AnF only, so far                              | .relation_type    |
          |           8 | content             | cpfRelation/relationEntry, usually a name     | .relation_entry   |
          |           9 | dates               | cpfRelation/date (or dateRange)               | .date             |
          |          10 | note                | cpfRelation/descriptiveNote                   | .descriptive_note |

          New convention: when there are dates, make them the second arg. Final arg is a list of all the
          scalar values that will eventually be passed to execute() in the SQL function. This convention
          is already in use in a couple of places, but needs to be done for some existing functions.

          getDates() returns a single SNACDate object. 
          
        */

        foreach ($id->getRelations() as $fdata)
        {
            $this->sql->insertRelation($vhInfo,
                                       $fdata->getDates(),
                                       $fdata->getTargetConstellation(),
                                       $fdata->getTargetArkID(),
                                       $fdata->getTargetEntityType(),
                                       $fdata->getType(),
                                       $fdata->getCpfRelationType(),
                                       $fdata->getContent(),
                                       $fdata->getNote(),
                                       $fdata->getID());
        }

        /*
          ignored: $this->linkType, @xlink:type always 'simple', vocab source_type, .type

          | placeholder | php                 | what                                             | sql                  |
          |-------------+---------------------+--------------------------------------------------+----------------------|
          |           1 | $vhInfo['version']  |                                                  | .version             |
          |           2 | $vhInfo['main_id']  |                                                  | .main_id             |
          |           3 | documentType        | @xlink:role id fk to vocab document_type         | .role                |
          |           4 | entryType           | relationEntry@localType, AnF, always 'archival'? | .relation_entry_type |
          |           5 | link                | @xlink:href                                      | .href                |
          |           6 | role                | @xlink:arcrole vocab document_role               | .arcrole             |
          |           7 | content             | relationEntry, usually a name                    | .relation_entry      |
          |           8 | source              | objectXMLWrap                                    | .object_xml_wrap     |
          |           9 | note                | descriptiveNote                                  | .descriptive_note    |

          Final arg is a list of all the scalar values that will eventually be passed to execute() in the SQL
          function. This convention is already in use in a couple of places, but needs to be done for some
          existing functions.  
          */

        foreach ($id->getResourceRelations() as $fdata)
        {
            $this->sql->insertResourceRelation($vhInfo,
                                               $fdata->getDocumentType(),
                                               $fdata->getEntryType(),
                                               $fdata->getLink(),
                                               $fdata->getRole(),
                                               $fdata->getContent(),
                                               $fdata->getSource(),
                                               $fdata->getNote(),
                                               $fdata->getID());
        }

        return $vhInfo;
    } // end saveConstellation

    /**
     * Get ready to update by creating a new version_history record, and getting the new version number
     * back. The constellation id (main_id) is unchanged. Each table.id is also unchanged. Both main_id and
     * table.id *must* not change.
     *
     * @param snac\data\Constellation $pObj object that we are preparing to write all or part of back to the database.
     *
     * @param string $appUserID Application user id string, for example "system" or "mst3k".
     *
     * @param string $role SNAC role
     *
     * @param string $icstatus A version history status string.
     *
     * @param string $note User created note explaining this update.
     *
     * @return string[] Associative list with keys 'version', 'main_id'
     *
     */
    public function updatePrepare($pObj,
                                  $appUserID,
                                  $role,
                                  $icstatus,
                                  $note)
    {
        $mainID = $pObj->getID(); // Note: constellation id is the main_id
        $newVersion = $this->sql->updateVersionHistory($appUserID, $role, $icstatus, $note, $mainID);
        $vhInfo = array('version' => $newVersion, 'main_id' => $mainID);
        return $vhInfo;
    }

    /**
     * Save a name entry to the database.
     *
     * @param string[] $vhInfo associative list with keys 'version', 'main_id'.
     *
     * @param \snac\data\NameEntry Name entry object
     *
     */
    public function saveName($vhInfo, $ndata)
    {
        $this->sql->insertName($vhInfo, 
                               $ndata->getOriginal(),
                               $ndata->getPreferenceScore(),
                               $ndata->getContributors(), // list of type/contributor values
                               $ndata->getLanguage(),
                               $ndata->getScriptCode(),
                               $ndata->getUseDates(),
                               $ndata->getID());
    }


    /**
     * Return 100 constellations as a json string. Only 3 fields are included: version, main_id, formatted
     * name. The idea it to return enough for the UI to allow selection of a record to edit.
     *
     * @return string[] A list of 100 records, each with key: 'version', 'main_id', 'formatted_name'
     */
    public function demoConstellationList()
    {
        $demoData = $this->sql->selectDemoRecs();
        return $demoData;
    }

    /**
     * Return a constellation object that has 2 or more non-delted names. This is a helper function for testing purposes only.
     *
     * @param string $appUserID A user id string. When testing this comes from getAppUserInfo().
     * 
     * @return \snac\data\Constellation A PHP constellation object.
     *
     */
    public function multiNameConstellation($appUserID)
    {
        $vhInfo = $this->sql->sqlMultiNameConstellationID();
        $mNConstellation = $this->selectConstellation($vhInfo, $appUserID);
        return $mNConstellation;
    }


    /**
     * Delete a single record of a single table. We need the id here because we only want a single record. The
     * other code here just gets all the records (keeping their id values) and throws them into an
     * Constellation object. Delete is different and delete has single-record granularity.
     *
     * Need a helper function somewhere to associate object type with database table.
     *
     * Instead of what we have implemented here, it might be best to delete by sending a complete php object
     * to setDeleted() and that object would contain id, version, mainID (available via getters). This would
     * allow setDeleted() to work without any out-of-band information.
     * 
     *
     * @param string $userid Text userid corresponds to table appuser.userid, like a Linux username. Used to
     * create a new version_history record.
     *
     * @param string $role The current role.id value of the user. Comes from role.id and table appuser_role_link.
     * 
     * @param string $icstatus One of the allowed status values from icstatus. This becomes the new status of
     * the inserted constellation. Pass a null if unchanged. Lower level code will preserved the existing
     * setting.
     *
     * @param string $note A user-created note for what was done to the constellation. A check-in note.
     *
     * @param integer $main_id The constellation id.
     *
     * @param string $table Name of the table we are deleting from. This might be changed to object typeof()
     * and we will figure out what SQL table that corresponds to, using an associative list lookup. The
     * calling programmer should not have to know table names.
     *
     * @param integer $id The record id of the record being deleted. Corresponds to table.id.
     * 
     * @return string Non-null is success, null is failure. On succeess returns the deleted row id, which
     * should be the same as $id.
     * 
     */
    public function setDeleted($userid, $role, $icstatus, $note, $main_id, $table, $id)
    {
        if (! isset($this->canDelete[$table]))
        {
            // Hmmm. Need to warn the user and write into the log. 
            printf("Cannot set deleted on table: $table\n");
            return null;
        }
        if ($table == 'name' & $this->sql->CountNames($main_id) <= 1)
        {
            // Need a message and logging for this.
            printf("Cannot delete the only name for main_id: $main_id count: %s\n", $this->sql->CountNames($main_id) );
            return null;
        }
        $newVersion = $this->sql->updateVersionHistory($userid, $role, $icstatus, $note, $main_id);
        $this->sql->sqlSetDeleted($table, $id, $newVersion);
        return $newVersion;
    }

    /**
     * Undelete a record. 
     *
     * @param string $userid Text userid corresponds to table appuser.userid, like a Linux username. Used to
     * create a new version_history record.
     *
     * @param string $role The current role.id value of the user. Comes from role.id and table appuser_role_link.
     * 
     * @param string $icstatus Status of this record. Pass a null if unchanged. Lower level code will preserved the existing setting.
     *
     * @param string $icstatus One of the allowed status values from icstatus. This becomes the new status of the inserted constellation.
     *
     * @param string $note A user-created note for what was done to the constellation. A check-in note.
     *
     * @param integer $main_id The constellation id.
     *
     * @param string $table Name of the table we are deleting from.
     *
     * @param integer $id The record id of the record being deleted. Corresponds to table.id.
     *
     * @return string Non-null is success, null is failure. On succeess returns the deleted row id, which
     * should be the same as $id.
     *
     */
    public function clearDeleted($userid, $role, $icstatus, $note, $main_id, $table, $id)
    {
        if (! isset($this->canDelete[$table]))
        {
            // Hmmm. Need to warn the user and write into the log. 
            printf("Cannot clear deleted on table: $table\n");
            return null;
        }
        $newVersion = $this->sql->updateVersionHistory($userid, $role, $icstatus, $note, $main_id);
        $this->sql->sqlClearDeleted($table, $id, $newVersion);
        return $newVersion;
    }

}
