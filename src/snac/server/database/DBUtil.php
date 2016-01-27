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
 * This is what the rest of the server sees as an interface to the database. There is no SQL here. This knows
 * about data structure from two points of view: constellation php data, and tables in the
 * database. Importantly, this code has no idea where the constellation comes from, nor how data gets into the
 * database. Constellation data classes are elsewhere, and SQL is elsewhere.
 *
 * Functions populateFoo() are defined here and know about column names from the database (but not how SQL managed to get the column names).
 *
 * Functions selectFoo(), updateFoo(), insertFoo() are defined in SQL.php and return an associative list where the keys are column names.
 *
 * You know, so far, all the functions in this class could (almost?) be static, as long as the $db were passed in as an
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
     * Database connector object
     * 
     * @var \snac\server\database\DatabaseConnector object.
     */
    private $db = null;

    /** 
     * The constructor for the DBUtil class. 
     */
    public function __construct() 
    {
        $this->db = new \snac\server\database\DatabaseConnector();
        $this->sql = new SQL($this->db);
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
     * This is called "build" because SQL uses "insert", but this is higher level than the SQL class.
     * $id is a Constellation object
     * 
     * @param string[] Associative list of s single date's data
     * 
     * @return SNACDate
     */
    /* replaced by populateExistDate() */
    /* 
     * public static function buildDate($vhInfo, $singleDate)
     * {
     *     $dateObj = new \snac\data\SNACDate();
     *     $dateObj->setRange($singleDate['is_range']);
     *     $dateObj->setFromDate($singleDate['from_date'],
     *                           $singleDate['from_date'],
     *                           $singleDate['from_type'] ); 
     *     $dateObj->setFromDateRange($singleDate['from_not_before'], $singleDate['from_not_after']); 
     *     $dateObj->setToDate($singleDate['to_date'],
     *                         $singleDate['to_date'],
     *                         $singleDate['to_type']); 
     *     $dateObj->setToDateRange($singleDate['to_not_before'], $singleDate['to_not_after']);
     *     // Set nrdVersion to null for now.
     *     $dateObj->setDBInfo($singleDate['version'], $singleDate['id']);
     *     return $dateObj;
     * }    
     */
        
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
     * | addLanguagesUsed($langObj)                             | language               |
     * | n/a, use a language object                             | language_code          |
     * | n/a, use a language object                             | script                 |
     * | n/a, use a language object                             | script_code            |
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

        $row = $this->sql->selectNrd($vhInfo);
        $cObj->setArkID($row['ark_id']);
        $cObj->setEntityType($row['entity_type']);
        $cObj->setID($vhInfo['main_id']); // constellation ID, $row['main_id'] has the same value.
        $cObj->setVersion($vhInfo['version']);
        $this->populateDate($cObj); // exist dates for the constellation; in SQL these dates are linked to table nrd.

        $this->populateBiogHist($vhInfo, $cObj);
        $this->populateGender($vhInfo, $cObj);
        $this->populateMandate($vhInfo, $cObj);
        $this->populateConventionDeclaration($vhInfo, $cObj);
        $this->populateStructureOrGenealogy($vhInfo, $cObj);
        $this->populateGeneralContext($vhInfo, $cObj);
        $this->populateNationality($vhInfo, $cObj);
        $this->populateLanguage($vhInfo, $cObj);

        $oridRows = $this->sql->selectOtherRecordID($vhInfo); 
        foreach ($oridRows as $singleOrid)
        {
            $cObj->addOtherRecordID($singleOrid['link_type'], $singleOrid['other_id']);
            $this->populateDate($singleOrid);
        }
        
        $subjRows = $this->sql->selectSubject($vhInfo); 
        foreach ($subjRows as $singleSubj)
        {
            $cObj->addSubject($singleSubj['subject_id']);
            $this->populateDate($singleSubj);
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
            $neObj = new \snac\data\NameEntry();
            $neObj->setOriginal($oneName['original']);
            /* 
             * $neObj->setLanguage($oneName['language']);
             * $neObj->setScriptCode($oneName['script_code']);
             */
            $neObj->setPreferenceScore($oneName['preference_score']);
            $neObj->setDBInfo($oneName['version'], $oneName['id']);
            $this->populateLanguage($neObj);
            foreach ($oneName['contributors'] as $contrib)
            {
                $neObj->addContributor($contrib['name_type'], $contrib['short_name']);
            }
            $this->populateDate($neObj);
            $cObj->addNameEntry($neObj);
        }
    }

    /**
     * Select date range(s) from db, foreach create SNACDate object, add to the object $cObj, which may be any
     * kind of object that extends AbstractData.
     *
     * Currently, we call insertDate() for: nrd, occupation, function, relation, 
     *
     * Note: $cObj passed by reference and changed in place.
     *
     * @param int $rowID the nrd.id actual row id from table nrd.
     * @param $cObj snac\data\Constellation object, passed by reference, and changed in place
     */
    public function populateDate($rowID, &$cObj)
    {
        /*
         * Sanity check the number of dates allowed for this object $cObj. If zero, then immediately
         * return. If one then set a flag to break the foforeach after the first iteration. Else we are
         * allowed >=1 dates, and we won't exit the foreach after the first interation.
         */ 
        $breakAfterOne = false;
        if ($cObj->getMaxDateCount() == 0)
        {
            return;
        }
        elseif ($cObj->getMaxDateCount() == 1)
        {
            $breakAfterOne = true;
        }
        $dateRows = $this->sql->selectDate($cObj->getID(), $cObj->getVersion());
        // These could be globals. Or they can stay here as local vars.
        $fromTypeTerm = populateTerm($singleDate['from_type']);
        $toTypeTerm = populateTerm($singleDate['to_type']);
        foreach ($dateRows as $singleDate)
        {
            $dateObj = new \snac\data\SNACDate();
            $dateObj->setRange($singleDate['is_range']);
            $dateObj->setFromDate($singleDate['from_date'],
                                  $singleDate['from_date'],
                                  $fromTypeTerm);
            $dateObj->setFromDateRange($singleDate['from_not_before'], $singleDate['from_not_after']); 
            $dateObj->setToDate($singleDate['to_date'],
                                $singleDate['to_date'],
                                $toTypeTerm);
            $dateObj->setToDateRange($singleDate['to_not_before'], $singleDate['to_not_after']);
            $dateObj->setDBInfo($singleDate['version'], $singleDate['id']);
            // This will break for non-Constellation objects.
            $cObj->addExistDates($dateObj);
            if ($breakAfterOne)
            {
                break;
            }
        }
    }

    /**
     * Return a vocabulary term object, src\snac\data\Term which is used by many objects for controlled
     * vocabulary "terms". We use "term" broadly in the sense of an object that meets all needs of the the
     * user interface.
     *
     * @param integer $termID A unique integer record id from the database table vocabulary.
     *
     */ 
    public function populateTerm($termID)
    {
        $newObj = new \snac\data\Term();
        $row = selectTerm($termID);
        $newObj->setID($row['id']);
        $newObj->setTerm($row['term']);
        $newObj->setURI($row['uri']);
        $newObj->setDescription($row['description']);
        $this->populateDate($newObj);
        return $newObj;
    }

    // $this->populateConventionDeclaration($vhInfo, $cObj);
    public function populateConventionDeclaration($vhInfo, &$cObj)
    {
        $rows = $this->sql->selectConventionDeclaration($vhInfo);
        foreach ($rows as $item)
        {
            $newObj = new \snac\data\ConventionDeclaration();
            $newObj->setTerm(populateTerm($item['term_id']));
            $newObj->setDBInfo($item['version'], $item['id']);
            $this->populateDate($newObj);
            $cObj->addConventionDeclaration($newObj);
        }
    }


    // $this->populateStructureOrGenealogy($vhInfo, $cObj);
    public function populateStructureOrGenealogy($vhInfo, &$cObj)
    {
        $rows = $this->sql->selectStructureOrGenealogy($vhInfo);
        foreach ($rows as $item)
        {
            $newObj = new \snac\data\StructureOrGenealogy();
            $newObj->setTerm(populateTerm($item['term_id']));
            $newObj->setDBInfo($item['version'], $item['id']);
            $this->populateDate($newObj);
            $cObj->addStructureOrGenealogy($newObj);
        }
    }


    // $this->populateGeneralContext($vhInfo, $cObj);
    public function populateGeneralContext($vhInfo, &$cObj)
    {
        $rows = $this->sql->selectGeneralContext($vhInfo);
        foreach ($rows as $item)
        {
            $newObj = new \snac\data\GeneralContext();
            $newObj->setTerm(populateTerm($item['term_id']));
            $newObj->setDBInfo($item['version'], $item['id']);
            $this->populateDate($newObj);
            $cObj->addGeneralContext($newObj);
        }
    }


    /* 
     * $this->populateNationality($vhInfo, $cObj);
     * When there is only one term and that term corresponds to the table name, the field is "term_id".
     */
    public function populateNationality($vhInfo, &$cObj)
    {
        $rows = $this->sql->selectNationality($vhInfo);
        foreach ($rows as $item)
        {
            $newObj = new \snac\data\Nationality();
            $newObj->setTerm(populateTerm($item['term_id']));
            $newObj->setDBInfo($item['version'], $item['id']);
            $this->populateDate($newObj);
            $cObj->addNationality($newObj);
        }
    }

    /* 
     * Select language from the database, create a language object, add the language to the object referencedy
     * by $cObj.
     *
     * We have two term ids, language_id and script_id, so they need unique names (keys) and not the usual
     * "term_id".
     * 
     */
    public function populateLanguage($vhInfo, &$cObj)
    {
        $rows = $this->sql->selectLanguage($cObj->getID(), $cObj->getVersion());
        foreach ($rows as $item)
        {
            $newObj = new \snac\data\Language();
            $newObj->setLanguage(populateTerm($item['language_id']));
            $newObj->setScript(populateTerm($item['script_id']));
            $newObj->setVocabularySource($item['vocabulary_source']);
            $newObj->setNote($item['note']);
            $newObj->setDBInfo($item['version'], $item['id']);
            $cObj->addLanguage($newObj);
        }
    }


    /* 
     * $this->populateMandate($vhInfo, $cObj);
     *  When there is only one term and that term corresponds to the table name, the field is "term_id".
     */

    public function populateMandate($vhInfo, &$cObj)
    {
        $rows = $this->sql->selectMandate($vhInfo);
        foreach ($rows as $item)
        {
            $newObj = new \snac\data\Mandate();
            $newObj->setTerm(populateTerm($item['term_id']));
            $newObj->setDBInfo($item['version'], $item['id']);
            $this->populateDate($newObj);
            $cObj->addMandate($newObj);
        }
    }
    
    /**
     * setDataType() is called by the constructor, so we don't need to worry about that.
     * When there is only one term and that term corresponds to the table name, the field is "term_id".
     *
     *
     *
     */
    public function populateGender($vhInfo, &$cObj)
    {
        $rows = $this->sql->selectGender($vhInfo);
        foreach ($rows as $item)
        {
            $newObj = new \snac\data\Gender();
            $newObj->setTerm(populateTerm($item['term_id']));
            $newObj->setDBInfo($item['version'], $item['id']);
            $this->populateDate($newObj);
            $cObj->addGender($newObj);
        }
    }

    /**
     * Get BiogHist from database, create relevant object and add to the constellation object passed as an
     * argument.
     *
     *
     *
     *
     */
    public function populateBiogHist($vhInfo, &$cObj)
    {
        $rows = $this->sql->selectBiogHist($vhInfo);
        foreach ($rows as $item)
        {
            $newObj = new \snac\data\BiogHist();
            $newObj->setText($item['text']);
            $newObj->setDBInfo($item['version'], $item['id']);
            // $newObj->setLanguage(populateLanguage($item['language_id']));
            $this->populateLanguage($newObj);
            $this->populateDate($newObj);
            $cObj->addBiogHist($newObj);
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
            $this->populateDate($occObj);
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
            $this->populateDate($relatedObj);
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
            $this->populateDate($rrObj);
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

            // Must call $fOjb->setDBInfo() before calling populateExistDate()
            // $fDate = $this->buildDate($vhInfo, $oneFunc['date']);
            $fDate = populateExistDate($fObj->getID(), $fObj);

            $fObj->setDateRange($fDate);
            $cObj->addFunction($fObj);
            $this->populateDate($fObj);
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
     * The id->getID() has been populated by the calling code, whether this is new or exists in the
     * database. This is due to constellation id values coming out of table version_history, unlike all other
     * tables. For this reason, insertNrd() does not return the nrd.id value.
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
        /*
         * Unlike other insert functions, insertNrd() does not return the id value. The id for nrd is the
         * constellation id, aka $vhInfo['main_id'] aka main_id aka version_history.main_id, and as always,
         * $id->getID() once the Constellation has been saved to the database. The $vhInfo arg is created by
         * accessing the database, so it is guaranteed to be "new" or at least, up-to-date.
         *
         * The entityType may be null because toArray() can't tell the differnce between an empty class and a
         * non-empty class, leading to empty classes littering the JSON with empty json. To avoid that, we use
         * null for an empty class, and test with the ternary operator.
         */
        $this->sql->insertNrd($vhInfo,
                              $id->getArk(),
                              $id->getEntityType() == null ? null : $id->getEntityType()->getID());

        foreach ($id->getDateList() as $date)
        {
            /* 
             * $date is a SNACDate object.
             * getFromType() must be a Term object
             * getToType() must be a Term object
             *
             * What does it mean to have a date with no fromType? Could be an unparseable date, I guess.
             */
            $this->sql->insertDate($vhInfo,
                                   $date->getID(),
                                   $this->db->boolToPg($date->getIsRange()),
                                   $date->getFromDate(),
                                   $date->getFromType()==null?null:$date->getFromType()->getID(),
                                   $this->db->boolToPg($date->getFromBc()),
                                   $date->getFromRange()['notBefore'],
                                   $date->getFromRange()['notAfter'],
                                   $date->getToDate(),
                                   $date->getToType()==null?null:$date->getToType()->getID(),
                                   $this->db->boolToPg($date->getToBc()),
                                   $date->getToRange()['notBefore'],
                                   $date->getToRange()['notAfter'],
                                   $date->getFromDateOriginal() . ' - ' . $date->getToDateOriginal(),
                                   'nrd',
                                   $vhInfo['main_id']);
        }

        foreach ($id->getLanguage() as $lang)
        {
            $this->sql->insertLanguage($vhInfo,
                                       $lang->getID(),
                                       $lang->getLanguage()->getID(),
                                       $lang->getScript()->getID(),
                                       $lang->getVocabularySource(),
                                       $lang->getNote(),
                                       'nrd',
                                       $vhInfo['main_id']);
        }

        foreach ($id->getBiogHistList() as $biogHist)
        {
            $this->saveBiogHist($vhInfo, $biogHist);
        }

        /*
         * Other record id can be found in the SameAs class.
         *
         * Here $otherID is a SameAs object. SameAs->getType() is a Term object. SameAs->getURI() is a string.
         * Term->getTerm() is a string. SameAs->getText() is a string.
         */ 
        foreach ($id->getOtherRecordIDs() as $otherID)
        {
            if ($otherID->getType()->getTerm() != 'MergedRecord' and
                $otherID->getType()->getTerm() != 'viafID')
            {
                $msg = sprintf("Warning: unexpected otherRecordID type: %s for ark: %s\n",
                               $otherID->getType()->getTerm(),
                               $otherID->getURI());
                // TODO: Throw warning or log
            }
            $this->sql->insertOtherID($vhInfo,
                                      $otherID->getID(),
                                      $otherID->getText(),
                                      $otherID->getType()->getID(),
                                      $otherID->getURI());
        }

        /* 
         * Constellation name entry data is already an array of name entry data. 
         * getUseDates() returns SNACDate[] (An array of SNACDate objects.)
         */
        foreach ($id->getNameEntries() as $ndata)
        {
            $this->saveName($vhInfo, $ndata);
        }

        foreach ($id->getSources() as $fdata)
        {
            // 'type' is always simple, and Daniel says we can ignore it. It was used in EAC-CPF just to quiet
            // validation.

            $sid = $this->sql->insertSource($vhInfo,
                                            $fdata->getID(),
                                            $fdata->getText(),
                                            $fdata->getNote(),
                                            $fdata->getURI(),
                                            $fdata->getType()->getID(),
                                            $fdata->getID());
            $lang = $fdata->getLanguage();
            if ($lang)
            {
                $this->sql->insertLanguage($vhInfo,
                                           $lang->getID(),
                                           $lang->getLanguage()->getID(),
                                           $lang->getScript()->getID(),
                                           $lang->getVocabularySource(),
                                           $lang->getNote(),
                                           'source',
                                           $sid);
            }
        }

        foreach ($id->getLegalStatuses() as $fdata)
        {
            $this->sql->insertLegalStatus($vhInfo,
                                          $fdata->getID(),
                                          $fdata->getTerm()->getID());
        }

        /*
         * Insert an occupation. If this is a new occupation, or a new constellation we will get a new
         * occupation id which we save in $occID and use for the related dates.
         *
         * fdata is foreach data. Just a notation that the generic variable is for local use in this loop. 
         */
        foreach ($id->getOccupations() as $fdata)
        {
            $occID = $this->sql->insertOccupation($vhInfo,
                                                  $fdata->getID(),
                                                  $fdata->getTerm()->getID(),
                                                  $fdata->getVocabularySource(),
                                                  $fdata->getNote());
            foreach ($fdata->getDateList() as $date)
            {
                $date_fk = $this->sql->insertDate($vhInfo,
                                             $date->getID(),
                                             $this->db->boolToPg($date->getIsRange()),
                                             $date->getFromDate(),
                                             $date->getFromType()->getID(),
                                             $this->db->boolToPg($date->getFromBc()),
                                             $date->getFromRange()['notBefore'],
                                             $date->getFromRange()['notAfter'],
                                             $date->getToDate(),
                                             $date->getToType()==null?null:$date->getToType()->getID(),
                                             $this->db->boolToPg($date->getToBc()),
                                             $date->getToRange()['notBefore'],
                                             $date->getToRange()['notAfter'],
                                             $date->getFromDateOriginal() . ' - ' . $date->getToDateOriginal(),
                                             'occupation',
                                             $occID);
            }
        }


        /* 
         *  | php function        | sql               | cpf                             |
         *  |---------------------+-------------------+---------------------------------|
         *  | getType             | function_type     | function/@localType             |
         *  | getTerm             | function_id       | function/term                   |
         *  | getVocabularySource | vocabulary_source | function/term/@vocabularySource |
         *  | getNote             | note              | function/descriptiveNote        |
         *  | getDateList         | table date_range  | function/dateRange              |
         *
         *
         * I considered adding keys for the second arg, but is not clear that using them for sanity checking
         * would gain anything. The low level code would become more fragile, and would break "separation of
         * concerns". The sanity check would require that the low level code have knowledge about the
         * structure of things that aren't really low level. Remember: SQL code only knows how to put data in
         * the database. Any sanity check should happen up here.
         *
         *
         * Functions have a type (Term object) derived from function/@localType. The function/term is a Term object.
         *
         * Example files: /data/extract/anf/FRAN_NP_050744.xml
         * 
         */

        foreach ($id->getFunctions() as $fdata)
        {
            $funID = $this->sql->insertFunction($vhInfo,
                                                $fdata->getID(), // record id
                                                $fdata->getType()==null?null:$fdata->getType()->getID(), // function type, aka localType, Term object
                                                $fdata->getVocabularySource(),
                                                $fdata->getNote(),
                                                $fdata->getTerm()->getID()); // function term id aka vocabulary.id, Term object
            /*
             * getDateList() always returns a list of SNACDate objects. If no dates then list is empty,
             * but it is still a list that we can foreach on without testing for null and count>0.
             */ 
            foreach ($fdata->getDateList() as $date)
            {
                $date_fk = $this->sql->insertDate($vhInfo, 
                                                  $date->getID(),
                                                  $this->db->boolToPg($date->getIsRange()),
                                                  $date->getFromDate(),
                                                  $date->getFromType()->getID(),
                                                  $this->db->boolToPg($date->getFromBc()),
                                                  $date->getFromRange()['notBefore'],
                                                  $date->getFromRange()['notAfter'],
                                                  $date->getToDate(),
                                                  $date->getToType()==null?null:$date->getToType()->getID(),
                                                  $this->db->boolToPg($date->getToBc()),
                                                  $date->getToRange()['notBefore'],
                                                  $date->getToRange()['notAfter'],
                                                  $date->getFromDateOriginal() . ' - ' . $date->getToDateOriginal(),
                                                  'function',
                                                  $funID);
            }
        }

        /*
         * getID() is the subject object record id.
         *
         * getTerm()->getID() is the vocabulary id of the Term object inside subject.
         * 
         */ 
        foreach ($id->getSubjects() as $term)
        {
            $this->sql->insertSubject($vhInfo, 
                                      $term->getID(),
                                      $term->getTerm()->getID()); 
        }

        /*
          ignored: we know our own id value: sourceConstellation, // id fk
          ignored: we know our own ark: sourceArkID,  // ark why are we repeating this?
          ignored: always 'simple', altType, cpfRelation@xlink:type vocab source_type, .type

          | placeholder | php                 | what                                                       | sql               |
          |-------------+---------------------+------------------------------------------------------------+-------------------|
          |           1 | $vhInfo['version']  |                                                            | version           |
          |           2 | $vhInfo['main_id']  |                                                            | main_id           |
          |           3 | targetConstellation | id fk to version_history                                   | .related_id       |
          |           4 | targetArkID         | ark                                                        | .related_ark      |
          |           5 | targetEntityType    | cpfRelation@xlink:role, vocab entity_type, Term object     | .role             |
          |           6 | type                | cpfRelation@xlink:arcrole vocab relation_type, Term object | .arcrole          |
          |           7 | cpfRelationType     | AnF only, so far                                           | .relation_type    |
          |           8 | content             | cpfRelation/relationEntry, usually a name                  | .relation_entry   |
          |           9 | dates               | cpfRelation/date (or dateRange)                            | .date             |
          |          10 | note                | cpfRelation/descriptiveNote                                | .descriptive_note |

          New convention: when there are dates, make them the second arg. Final arg is a list of all the
          scalar values that will eventually be passed to execute() in the SQL function. This convention
          is already in use in a couple of places, but needs to be done for some existing functions.

          Ignore ConstellationRelation->$altType. It was always "simple".

        */

        foreach ($id->getRelations() as $fdata)
        {
            /*
             * altType is cpfRelationType, at least in the CPF. 
             */ 
            $cpfRelTypeID = null;
            if ($cr = $fdata->getcpfRelationType())
            {
                $cpfRelTypeID = $cr->getID();
            }
            $relID = $this->sql->insertRelation($vhInfo,
                                                $fdata->getTargetConstellation(),
                                                $fdata->getTargetArkID(),
                                                $fdata->getTargetEntityType()->getID(),
                                                $fdata->getType()->getID(),
                                                $cpfRelTypeID,
                                                $fdata->getContent(),
                                                $fdata->getNote(),
                                                $fdata->getID());
            foreach ($fdata->getDateList() as $date)
            {
                $date_fk = $this->sql->insertDate($vhInfo, 
                                             $date->getID(),
                                             $this->db->boolToPg($date->getIsRange()),
                                             $date->getFromDate(),
                                             $date->getFromType()->getID(),
                                             $this->db->boolToPg($date->getFromBc()),
                                             $date->getFromRange()['notBefore'],
                                             $date->getFromRange()['notAfter'],
                                             $date->getToDate(),
                                             $date->getToType()==null?null:$date->getToType()->getID(),
                                             $this->db->boolToPg($date->getToBc()),
                                             $date->getToRange()['notBefore'],
                                             $date->getToRange()['notAfter'],
                                             $date->getFromDateOriginal() . ' - ' . $date->getToDateOriginal(),
                                             'related_identity',
                                             $relID);
            }
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
                                               $fdata->getDocumentType()->getID(),
                                               $fdata->getEntryType()== null ? null : $fdata->getEntryType()->getID(),
                                               $fdata->getLink(),
                                               $fdata->getRole()->getID(),
                                               $fdata->getContent(),
                                               $fdata->getSource(),
                                               $fdata->getNote(),
                                               $fdata->getID());
        }

        return $vhInfo;
    } // end saveConstellation

    /**
     * Save the biogHist, biogHist language, and biogHist date(s?). This is a private function that exists to
     * keep the code organized. It is probably only called from saveConstellation().
     *
     * @param array[] $vhInfo Associative list with keys version, main_id
     *
     * @param \snac\data\BiogHist A single BiogHist object.
     */ 
    private function saveBiogHist($vhInfo, $biogHist)
    {
        $bid = $this->sql->insertBiogHist($vhInfo,
                              $biogHist->getID(),
                              $biogHist->getText());
        
        $lang = $biogHist->getLanguage();
        $this->sql->insertLanguage($vhInfo,
                                   $lang->getID(),
                                   $lang->getLanguage()->getID(),
                                   $lang->getScript()->getID(),
                                   $lang->getVocabularySource(),
                                   $lang->getNote(),
                                   'biog_hist',
                                   $bid);
        
        foreach ($biogHist->getDateList() as $date)
        {
            $this->sql->insertDate($vhInfo,
                                   $date->getID(),
                                   $this->db->boolToPg($date->getIsRange()),
                                   $date->getFromDate(),
                                   $date->getFromType()->getID(),
                                   $this->db->boolToPg($date->getFromBc()),
                                   $date->getFromRange()['notBefore'],
                                   $date->getFromRange()['notAfter'],
                                   $date->getToDate(),
                                   $date->getToType()==null?null:$date->getToType()->getID(),
                                   $this->db->boolToPg($date->getToBc()),
                                   $date->getToRange()['notBefore'],
                                   $date->getToRange()['notAfter'],
                                   $date->getFromDateOriginal() . ' - ' . $date->getToDateOriginal(),
                                   'biog_hist',
                                   $bid);
        }
    }

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
     * Save a name entry to the database. This exists primarily to make the code here in DBUtil more legible.
     *
     * Note about \snac\data\Language objects. This is the Language of the entry. Language object's
     * getLanguage() returns a Term object. Language getScript() returns a Term object for the script. The
     * database only uses the id of each Term.
     *
     * When saving a name, the database assigns it a new id, and returns that id. We must be sure to use
     * $nameID for related dates, etc.
     *
     * @param string[] $vhInfo associative list with keys 'version', 'main_id'.
     *
     * @param \snac\data\NameEntry Name entry object
     *
     */
    private function saveName($vhInfo, $ndata)
    {
        $nameID = $this->sql->insertName($vhInfo, 
                                         $ndata->getOriginal(),
                                         $ndata->getPreferenceScore(),
                                         $ndata->getContributors(), // list of type/contributor values
                                         $ndata->getID());
        if ($lang = $ndata->getLanguage())
        {
            $this->sql->insertLanguage($vhInfo,
                                       $lang->getID(),
                                       $lang->getLanguage()->getID(),
                                       $lang->getScript()->getID(),
                                       $lang->getVocabularySource(),
                                       $lang->getNote(),
                                       'name',
                                       $nameID);
        }
        $dateList = $ndata->getDateList();
        foreach ($ndata->getDateList() as $date)
        {
            $this->sql->insertDate($vhInfo,
                                   $date->getID(),
                                   $this->db->boolToPg($date->getIsRange()),
                                   $date->getFromDate(),
                                   $date->getFromType()->getID(),
                                   $this->db->boolToPg($date->getFromBc()),
                                   $date->getFromRange()['notBefore'],
                                   $date->getFromRange()['notAfter'],
                                   $date->getToDate(),
                                   $date->getToType()==null?null:$date->getToType()->getID(),
                                   $this->db->boolToPg($date->getToBc()),
                                   $date->getToRange()['notBefore'],
                                   $date->getToRange()['notAfter'],
                                   $date->getFromDateOriginal() . ' - ' . $date->getToDateOriginal(),
                                   'name',
                                   $nameID);
        }
            
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
