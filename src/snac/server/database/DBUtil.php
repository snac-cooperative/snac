<?php

  /*
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

  /* 
   * namespace is confusing. Are they path relative? Are they arbitrary? How much of the leading directory
   * tree can be left out of the namespace? I just based this file's namespace on the parser example below.
   * 
   * namespace snac\util;
   *       src/snac/util/EACCPFParser.php
   */

namespace snac\server\database;

/* 
 * Util function to strip namespace text from vocabulary terms.
 * 
 * @param string $arg the string to strip the namespace from
 * @return string without the namespace
 */
function stripNS($arg)
{
    $arg = preg_replace('/^.*#(.*)$/', '$1', $arg );
    return $arg;
}


/* 
 * We need utility functions to write stuff to log files, stdout, stderr. Put this in some util class.  This
 * is none too efficient since it opens and closes the stream on every call.
 *
 * @param string $message The message we want printed to stderr. A newline is post-pended.
 * 
 */
function quick_stderr ($message)
{
    $stderr = fopen('php://stderr', 'w');
    fwrite($stderr,"  $message\n");
    fclose($stderr); 
}

/**
 * High level database class. This is what the rest of the server sees as an interface to the database. There
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

    /* 
     * The constructor for the DBUtil class. Originally, the idea was to call the constructor with an existing
     * DatabaseConnectory object, but the calling code doesn't need to know that or do anything dabase
     * related. That is the concern of the DBUtil class.
     * 
     * @return No return value, but sets the private var $sql to be a new SQL object.
     */
    public function __construct() 
    {
        $db = new \snac\server\database\DatabaseConnector();
        $this->sql = new SQL($db);
    }

    
    /**
     * Access some system-wide authentication and/or current user info. Hard coded for now.
     *
     * @param string $userid The text userid, sql table appuser.userid, used to get the appuser.id and the
     * user's primary role.
     *
     * @return string[] Associative list of user info data.
     */
    function getAppUserInfo($userid)
    {
        // $uInfo is array($row['id'], $row['role'])
        $uInfo = $this->sql->getAppUserInfo($userid);
        return $uInfo;
    }
    
    /* 
     * Utility function to create a SNACDate object from associative list of date data.
     * 
     * Is there another word for "insert"? SQL uses insert, but this is higher level than the SQL class.
     * $id is a Constellation object
     * 
     * @param string[] Associative list of s single date's data
     * 
     * @return SNACDate
     */
    public static function buildDate($singleDate)
    {
        $dateObj = new \snac\data\SNACDate();
        $dateObj->setRange($singleDate['is_range']);
        // No separate setter for fromType, fromBC, fromDateOriginal
        $dateObj->setFromDate($singleDate['from_date'],
                              $singleDate['from_date'],
                              $singleDate['from_type'] ); // $original, $standardDate, $type);
        $dateObj->setFromDateRange($singleDate['from_not_before'], $singleDate['from_not_after']); //$notBefore, $notAfter);
        $dateObj->setToDate($singleDate['to_date'],
                            $singleDate['to_date'],
                            $singleDate['to_type']); // $original, $standardDate, $type);
        $dateObj->setToDateRange($singleDate['to_not_before'], $singleDate['to_not_after']);// $notBefore, $notAfter);
        // I thought everything is a date range. Why these two setters?
        // $dateObj->setDate($original, $standardDate, $type);
        // $dateObj->setDateRange($notBefore, $notAfter);
            
        // What is a note? We don't have a field for it in the db, yet.
        // $dateObj->setNote($singleDate['note']); // $note);
        return $dateObj;
    }    
        
    /*
     * A helper function to get a constellation from the db for testing purposes
     *
     * @return string[] Standard vh_info associative list with the keys 'version' and 'main_id' from the constellation.
     * 
     */
    public function demoConstellation()
    {
        list($cid, $version, $main_id) = $this->sql->randomConstellationID();
        return array('version' => $version, 'main_id' => $main_id);
    }

    /*
     * Select a given constellation from the database based on version and main_id.
     *
     * @param array vhInfo associative list with keys 'version' and 'main_id'. The version and main_id you
     * want. Note that constellation component version numbers are the max() <= version requested.  main_id is
     * the unique id across all tables in this constellation. This is not the nrd.id, but is
     * version_history.main_id which is also nrd.main_id, etc.
     *
     * @param string $appUserID The internal id of the user from appuser.id. Used for locking records, and checking locks.
     *
     * @return \snac\data\Constellation $cObj A PHP constellation object.
     * 
     */
    public function selectConstellation($vhInfo, $appUserID)
    {
        /* 
         * Create an empty constellation by calling the constructor with no args. Then used the setters to add
         * individual properties of the class(es).
         */

        /*

          | php                                                    | sql                    |
          |--------------------------------------------------------+------------------------|
          | setArkID                                               | ark_id                 |
          | setEntityType                                          | entity_type            |
          | setGender                                              | gender                 |
          | setLanguage('language_code','language')                | language               |
          | setLanguage('language_code','language')                | language_code          |
          | setScript('script_code', 'script')                     | script                 |
          | setScript('script_code', 'script')                     | script_code            |
          | setLanguageUsed('language_used_code', 'language_used') | language_used          |
          | setScriptUsed('script_used_code', 'script_used')       | script_used            |
          | setNationality                                         | nationality            |
          | addBiogHist                                            | biog_hist              |
          | addExistDates                                          | exist_date             |
          | setGeneralContext                                      | general_context        |
          | setStructureOrGenealogy                                | structure_or_genealogy |
          | setConventionDeclaration                               | convention_declaration |
          | setMandate                                             | mandate                |
          |                                                        |                        |

         */

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
        
        // add the existDates

        $dateRows = $this->sql->selectDate($row['id']);
        foreach ($dateRows as $singleDate)
        {
            $dateObj = new \snac\data\SNACDate();
            $dateObj->setRange($singleDate['is_range']);
            // No separate setter for fromType, fromBC, fromDateOriginal
            $dateObj->setFromDate($singleDate['from_date'],
                                  $singleDate['from_date'],
                                  $singleDate['from_type'] ); // $original, $standardDate, $type);
            $dateObj->setFromDateRange($singleDate['from_not_before'], $singleDate['from_not_after']); //$notBefore, $notAfter);
            $dateObj->setToDate($singleDate['to_date'],
                                $singleDate['to_date'],
                                $singleDate['to_type']); // $original, $standardDate, $type);
            $dateObj->setToDateRange($singleDate['to_not_before'], $singleDate['to_not_after']);// $notBefore, $notAfter);
            // I thought everything is a date range. Why these two setters?
            // $dateObj->setDate($original, $standardDate, $type);
            // $dateObj->setDateRange($notBefore, $notAfter);
            
            // What is a note? We don't have a field for it in the db, yet.
            // $dateObj->setNote($singleDate['note']); // $note);

            $cObj->addExistDates($dateObj);
        }

        /* 
         * Keys are the same as the database field names. Function description below is from addOtherRecordID.
         *
         * "other_id": "nypl\/mss18809.r17075"
         * "link_type": "MergedRecord"
         *
         *  addOtherRecordID($type, $link)
         * @param string $type Type of the alternate id
         * @param string $link Href or other link for the alternate id
         * 
         */
        $oridRows = $this->sql->selectOtherRecordIDs($vhInfo); 
        foreach ($oridRows as $singleOrid)
        {
            $cObj->addOtherRecordID($singleOrid['link_type'], $singleOrid['other_id']);
        }
        
        /* 
         * Add subjects.
         * 
         * test with: scripts/get_constellation_demo.php 5 49
         * @param string $subject Subject to add.
         * addSubject($subject)
         *
         * returns array with keys: id, version, main_id, subject_id
         * 
         */
        $subjRows = $this->sql->selectSubjects($vhInfo); 
        foreach ($subjRows as $singleSubj)
        {
            $cObj->addSubject($singleSubj['subject_id']);
        }


        /* 
         * nameEntries
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
         * | addContributor(string $type, string $name) |                  |
         * 
         * | php                              | sql table name_contributor |
         * |----------------------------------+----------------------------|
         * |                                  | name_id                    |
         * | getContributors()['contributor'] | short_name                 |
         * | getContributors()['type']        | name_type                  |
         * |                                  |                            |
         * 
         * 
         */

        $neRows = $this->sql->selectNameEntries($vhInfo);
        foreach ($neRows as $oneName)
        {
            $neObj = new \snac\data\NameEntry();
            $neObj->setOriginal($oneName['original']);
            $neObj->setLanguage($oneName['language']);
            $neObj->setScriptCode($oneName['script_code']);
            // setUseDates($date);
            $neObj->setPreferenceScore($oneName['preference_score']);
            foreach ($oneName['contributors'] as $contrib)
            {
                $neObj->addContributor($contrib['name_type'], $contrib['short_name']);
            }
            
            $cObj->addNameEntry($neObj);
        }

        /* 
         * occupations
         * Need to add date range
         * Need to add vocabulary source
         *
         * 
         * | php                 | sql               |
         * |---------------------+-------------------|
         * |                     | id                |
         * |                     | version           |
         * |                     | main_id           |
         * | setTerm             | occupation_id     |
         * | setNote             | note              |
         * | setVocabularySource | vocabulary_source |
         */

        $occRows = $this->sql->selectOccupations($vhInfo);
        foreach ($occRows as $oneOcc)
        {
            $occObj = new \snac\data\Occupation();
            $occObj->setTerm($oneOcc['occupation_id']);
            // $occObj->setDateRange(new \snac\data\SNACDate());
            $occObj->setVocabularySource($oneOcc['vocabulary_source']);
            $occObj->setNote($oneOcc['note']);
            $cObj->addOccupation($occObj);
        }

        /* 
         * relations
         * test with: scripts/get_constellation_demo.php 2 10
         *
         * 
         * | php                                 | sql              |
         * |-------------------------------------+------------------|
         * |                                     | id               |
         * | $vh_info['verion']                  | version          |
         * | $vh_info['main_id']                 | main_id          |
         * | setTargetConstellation              | related_id       |
         * | setTargetArkID                      | related_ark      |
         * | setTargetType  aka targetEntityType | role             |
         * | setType                             | arcrole          |
         * | setCPFRelationType                  | relation_type    |
         * | setContent                          | relation_entry   |
         * | setDates                            | date             |
         * | setNote                             | descriptive_note |
         * 
         */

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
            $cObj->addRelation($relatedObj);
        }

        
        /*
         * resourceRelations
         *
         * 
         * | php                  | sql                 |
         * |----------------------+---------------------|
         * |                      | id                  |
         * | $vh_info['version']  | version             |
         * | $vh_info['main_id']  | main_id             |
         * | setDocumentType      | role                |
         * | setRelationEntryType | relation_entry_type |
         * | setLink              | href                |
         * | setRole              | arcrole             |
         * | setContent           | relation_entry      |
         * | setSource            | object_xml_wrap     |
         * | setNote              | descriptive_note    |
         */

        $rrRows = $this->sql->selectRelatedResources($vhInfo); 

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
            $cObj->addResourceRelation($rrObj);
        }

        /*
         * functions
         *
        */

        $funcRows = $this->sql->selectFunctions($vhInfo);
        foreach ($funcRows as $oneFunc)
        {
            $fObj = new \snac\data\SNACFunction();
            $fObj->setType($oneFunc['function_type']);
            $fObj->setTerm($oneFunc['function_id']);
            $fObj->setVocabularySource($oneFunc['vocabulary_source']);
            $fObj->setNote($oneFunc['note']);
            $fDate = $this->buildDate($oneFunc['date']);
            $fObj->setDateRange($fDate);
            $cObj->addFunction($fObj);
        }

        /* 
         * todo: places.
         * 
         * todo: maintenanceEvents
         * 
         */
        return $cObj;
    } // end selectConstellation

    /*
     * Write a PHP Constellation object to the database.
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
     * @return array $vh_info An associative list with keys 'version', 'main_id'. There might be a more useful
     * return value such as true for success, and false for failure. This function might need to call into the
     * system-wide user message class that we haven't written yet.
     * 
     */

    public function insertConstellation($id, $userid, $role, $icstatus, $note)
    {
        // This is proabably a good place to start using named args to methods, esp in class SQL.

        // Move those sanity checks up here, and decide what kind of exception to throw, and/or message to log
        
        // vh_info: version_history.id, version_history.main_id,
        $vh_info = $this->sql->insertVersionHistory($userid, $role, $icstatus, $note);

        // Sanity check bioghist
        // $cdata = $id->toArray(false);

        /* 
         * if (count($cdata['biogHists']) > 1)
         * {
         *     $msg = sprintf("Warning: multiple biogHists (%s)\n", count($cdata['biogHists']));
         *     quick_stderr($msg);
         * }
         */
        
        // Sanity check existDates. Only 1 allowed here
        /* 
         * if (count($cdata['existDates']) > 1)
         * {
         *     $msg = sprintf("Warning: more than 1 existDates: %s for ark: %s\n",
         *                    $count($cdata['existDates']),
         *                    $cdata['ark']);
         *     quick_stderr($msg);
         * }
         */
        
        // $id->getLanguage(),
        // $id->getLanguageCode(),
        // $id->getScript(),
        // $id->getScriptCode()
        $this->sql->insertNrd($vh_info,
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
        // printf("insertNRD done\n");

        foreach ($id->getOtherRecordIDs() as $otherID)
        {
            $otherID['type'] = stripNS($otherID['type']);
            // Sanity check otherRecordID
            if ($otherID['type'] != 'MergedRecord' and
                $otherID['type'] != 'viafID')
            {
                $msg = sprintf("Warning: unexpected otherRecordID type: %s for ark: %s\n",
                               $otherID['type'],
                               $id->getArk());
                quick_stderr($msg);
            }

            $this->sql->insertOtherID($vh_info, $otherID['type'], $otherID['href']);
            // printf("insertOtherID done\n");

        }

        // Constellation name entry data is already an array of name entry data. 
        foreach ($id->getNameEntries() as $ndata)
        {
            $name_id = $this->sql->insertName($vh_info, 
                                              $ndata->getOriginal(),
                                              $ndata->getPreferenceScore(),
                                              $ndata->getContributors(), // list of type/contributor values
                                              stripNS($ndata->getLanguage()),
                                              stripNS($ndata->getScriptCode()),
                                              $ndata->getUseDates());
        }

        foreach ($id->getSources() as $sdata)
        {
            // 'type' is always simple, and Daniel says we can ignore it. It was used in EAC-CPF just to quiet
            // validation.
            $this->sql->insertSource($vh_info,
                                     $sdata['href']);
        }

        foreach ($id->getLegalStatuses() as $sdata)
        {
            printf("Need to insert legalStatuses...\n");
        }

        // fdata is foreach data. Just a notation that the generic variable is for local use in this loop.
        foreach ($id->getOccupations() as $fdata)
        {
            $this->sql->insertOccupation($vh_info,
                                         $fdata->getTerm(),
                                         $fdata->getVocabularySource(),
                                         $fdata->getDates(),
                                         $fdata->getNote());
        }


        /* 
         * This seems confusing.
         * vocabularySource is 
         * note is function/descriptiveNote
         * 
         * 
         *  | php                 | sql               | cpf                             |
         *  |---------------------+-------------------+---------------------------------|
         *  | getType             | function_type     | function/@localType             |
         *  | getTerm             | function_id       | function/term                   |
         *  | getVocabularySource | vocabulary_source | function/term/@vocabularySource |
         *  | getNote             | note              | function/descriptiveNote        |
         *  | getDates            | table date_range  | function/dateRange                                |
         */
        

        foreach ($id->getFunctions() as $fdata)
        {
            $this->sql->insertFunction($vh_info,
                                       array('function_type' => $fdata->getType(),
                                             'vocabulary_source' => $fdata->getVocabularySource(),
                                             'note' => $fdata->getNote(),
                                             'function_id' => $fdata->getTerm()),
                                       $fdata->getDates());
        }

        foreach ($id->getSubjects() as $term)
        {
            $this->sql->insertSubject($vh_info,
                                       $term);
        }

        /*
          ignored: we know our own id value: sourceConstellation, // id fk
          ignored: we know our own ark: sourceArkID,  // ark why are we repeating this?
          ignored: always 'simple', altType, cpfRelation@xlink:type vocab source_type, .type

          | placeholder | php                 | what                                          | sql               |
          |-------------+---------------------+-----------------------------------------------+-------------------|
          |           1 | $vh_info['version'] |                                               | version           |
          |           2 | $vh_info['main_id'] |                                               | main_id           |
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
        */

        foreach ($id->getRelations() as $fdata)
        {
            $this->sql->insertRelation($vh_info,
                                        $fdata->getDates(),
                                        array($fdata->getTargetConstellation(),
                                              $fdata->getTargetArkID(),
                                              $fdata->getTargetEntityType(),
                                              $fdata->getType(),
                                              $fdata->getCpfRelationType(),
                                              $fdata->getContent(),
                                              $fdata->getNote()));
        }

        /*
          ignored: $this->linkType, @xlink:type always 'simple', vocab source_type, .type

          | placeholder | php                 | what                                             | sql                  |
          |-------------+---------------------+--------------------------------------------------+----------------------|
          |           1 | $vh_info['version'] |                                                  | .version             |
          |           2 | $vh_info['main_id'] |                                                  | .main_id             |
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
            $this->sql->insertResourceRelation($vh_info,
                                               array($fdata->getDocumentType(),
                                                     $fdata->getEntryType(),
                                                     $fdata->getLink(),
                                                     $fdata->getRole(),
                                                     $fdata->getContent(),
                                                     $fdata->getSource(),
                                                     $fdata->getNote()));
        }

        return $vh_info;
    } // end insertConstellation
}
