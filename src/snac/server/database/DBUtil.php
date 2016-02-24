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
 * All "create" here is based on SQL select queries.
 *
 * Functions populateFoo() create an object and add it to an existing object. These functions know about
 * column names from the database (but not how SQL managed to get the column names).
 *
 * Functions saveFoo() are broad wrappers that traverse objects save to the database via more granular
 * functions.
 *
 * Need: high level "populate", "build", "read" equivalent to saveFoo() like readFoo().
 *
 * Functions buildFoo() create and return an object using data selected from the database
 *
 * Functions selectFoo(), updateFoo(), insertFoo() are defined in SQL.php and return an associative list where
 * the keys are column names.
 *
 * Most (or all?) of the functions in this class could be static, as long as the $db were passed in as an arg,
 * rather than being passed to the constructor.
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
     * Constructor
     *
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
     * Safely call object getID method
     *
     * Call this so we don't have to sprinkle ternary ops in our code.
     * Works for any class that has a getID() method. Intended to use with Language, Term, Source,
     *
     * @param mixed $thing Some object that when not null has a getID() method.
     *
     * @return integer The record id of the thing
     */
    private function thingID($thing)
    {
        return $thing==null?null:$thing->getID();
    }


    /**
     * Get the SQL object
     *
     * Utility function to return the SQL object for this DBUtil instance. Currently only used for testing,
     * and that may be the only valid use.
     *
     * @return \snac\server\database\SQL Return the SQL object of this DBUtil instance.
     */
    public function sqlObj()
    {
        return $this->sql;
    }

    /**
     * Get entire vocabulary
     *
     * Get all the vocabulary from the database in tabular form.
     *
     * @return string[][] array of vocabulary terms and associated information
     */
    public function getAllVocabulary() {
        return $this->sql->selectAllVocabulary();
    }

    /**
     * Get user info
     *
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
     * Get a demo constellation
     *
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
     * Get a constellation from the database
     *
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
        $cObj->setEntityType($this->populateTerm($row['entity_type']));
        $cObj->setID($vhInfo['main_id']); // constellation ID, $row['main_id'] has the same value.
        $cObj->setVersion($vhInfo['version']);
        $this->populateDate($cObj); // exist dates for the constellation; in SQL these dates are linked to table nrd.
        $this->populateLanguage($cObj);
        $this->populateSource($cObj);
        $this->populateBiogHist($vhInfo, $cObj);
        $this->populateGender($vhInfo, $cObj);
        $this->populateMandate($vhInfo, $cObj);
        $this->populateConventionDeclaration($vhInfo, $cObj);
        $this->populateStructureOrGenealogy($vhInfo, $cObj);
        $this->populateGeneralContext($vhInfo, $cObj);
        $this->populateNationality($vhInfo, $cObj);
        $this->populateNameEntry($vhInfo, $cObj);
        $this->populateOccupation($vhInfo, $cObj);
        $this->populateRelation($vhInfo, $cObj); // aka cpfRelation
        $this->populateResourceRelation($vhInfo, $cObj); // resourceRelation
        $this->populateFunction($vhInfo, $cObj);
        $this->populatePlace($vhInfo, $cObj);
        $this->populateSubject($vhInfo, $cObj);
        $this->populateLegalStatus($vhInfo, $cObj);

        /*
         * Other record id can be found in the SameAs class.
         *
         * Here $otherID is a SameAs object. SameAs->getType() is a Term object. SameAs->getURI() is a string.
         * Term->getTerm() is a string. SameAs->getText() is a string.
         */
        $oridRows = $this->sql->selectOtherID($vhInfo);
        foreach ($oridRows as $rec)
        {
            $gObj = new \snac\data\SameAs();
            $gObj->setText($rec['text']); // the text of this sameAs or otherRecordID
            $gObj->setURI($rec['uri']); // the URI of this sameAs or otherRecordID
            $gObj->setType($this->populateTerm($rec['type'])); // \snac\data\Term Type of this sameAs or otherRecordID
            $cObj->addOtherRecordID($gObj);
        }

        /*
         * todo: maintenanceEvents and maintenanceStatus added to version history and managed from there.
         */
        return $cObj;
    } // end selectConstellation

    /**
     * Build class Place objects for this constellation, selecting from the database. Place gets data from
     * place_link, scm, and geo_place.
     *
     *
     * | php            | sql      |
     * |----------------+----------|
     * | setID()        | id       |
     * | setVersion()   | version  |
     * | setType() Term | type     |
     * | setOriginal()  | original |
     * | setNote()      | note     |
     * | setRole() Term | role     |
     *
     * | php                                             | sql                         | geonames.org         |
     * |-------------------------------------------------+-----------------------------+----------------------|
     * | setID()                                         | id                          |                      |
     * | setVersion()                                    | version                     |                      |
     * | setLatitude()                                   | geo_place.latitude          | lat                  |
     * | setLongitude()                                  | geo_place.longitude         | lon                  |
     * | setAdminCode() renamed from setAdmistrationCode | geo_place.admin_code        | adminCode            |
     * | setCountryCode()                                | geo_place.country_code      | countryCode          |
     * | setName()                                       | geo_place.name              | name                 |
     * | setGeoNameId()                                  | geo_place.geonamed_id       | geonameId            |
     * | setSource()                                     | scm.source_data             |                      |
     *
     * @param string[] $vhInfo associative list with keys 'version', 'main_id'.
     *
     * @param $cObj snac\data\Constellation object, passed by reference, and changed in place
     *
     */
    public function populatePlace($vhInfo, &$cObj)
    {
        /*
         * $gRows where g is for generic. As in "a generic object". Make this as idiomatic as possible.
         */
        $gRows = $this->sql->selectPlace($cObj->getID(), $vhInfo['version']);
        foreach ($gRows as $rec)
        {
            $gObj = new \snac\data\Place();
            $gObj->setDBInfo($rec['version'], $rec['id']);
            $metaObj = $this->buildMeta($rec['id'], $vhInfo['version']);
            $gObj->setSource($metaObj);

            /*
             * You might be looking for GeoTerm. We don't create GeoTerm objects because php Place is
             * denormalized compared to the database.
             */
            $geo = selectGeo($rec['geo_place_id']);
            $gObj->setLatitude($geo['latitude']);
            $gObj->setLongitude($geo['longitude']);
            $gObj->setAdministrationCode($geo['administrative_code']);
            $gObj->setCountryCode($geo['country_code']);
            $gObj->setName($geo['name']);
            $gObj->setGetNameId($geo['geoname_id']);

            $cObj->addPlace($gObj);
        }
    }

    /**
     * Return a snac meta object. Perhaps populateMeta() will replace this when we have a consistent API
     * for adding snac meta to all objects. See the discussion in populateSource(). That would be setSource() for all objects, or equivalent.
     *
     * Don't be confused by setSource() that uses a Source object and setSource() that uses a
     * SNACControlMetadata object.
     *
     * The convention for related things like date, place, and meta is args ($id, $version) so we're
     * following that.h
     *
     * @param integer $tid Table id, aka row id akd object id
     *
     * @param integer $version Constellation version number
     *
     */
    public function buildMeta($tid, $version)
    {
        /*
         * $gRows where g is for generic. As in "a generic object". Make this as idiomatic as possible.
         */
        if( $rec = $this->sql->selectMeta($tid, $version))
        {
            $gObj = new \snac\data\SNACControlMetadata();
            $gObj->setSubCitation($rec['sub_citation']);
            $gObj->setSourceData($rec['source_data']);
            $gObj->setDescriptiveRule($this->populateTerm($rec['rule_id']));
            $gObj->setNote($rec['note']);
            $gObj->setDBInfo($rec['version'], $rec['id']);
            /*
             * Prior to creating the Language object, language was strange and not fully functional. Now
             * language is a related record that links back here via our record id as a foreign key.
             */
            $this->populateLanguage($gObj);
            /*
             * populateSource() will call setCitation() for SNACControlMetadata objects
             */
            $this->populateSource($rec['id'], $gObj);
            return $gObj;
        }
        return null;
    }


    /**
     * Populate LegalStatus
     *
     * Populate the LegalStatus object(s), and add it/them to an existing Constellation object.
     *
     * Extends AbstracteTermData
     *
     * @param string[] $vhInfo associative list with keys 'version' and 'main_id'.
     *
     * @param $cObj snac\data\Constellation object, passed by reference, and changed in place
     *
     */
    private function populateLegalStatus($vhInfo, &$cObj)
    {
        /*
         * $gRows where g is for generic. As in "a generic object". Make this as idiomatic as possible.
         */
        $gRows = $this->sql->selectLegalStatus($vhInfo);
        foreach ($gRows as $rec)
        {
            $gObj = new \snac\data\LegalStatus();
            $gObj->setTerm($this->populateTerm($rec['term_id']));
            $gObj->setDBInfo($rec['version'], $rec['id']);
            /*
             * Must call $gOjb->setDBInfo() before calling populateDate()
             */
            $cObj->addLegalStatus($gObj);
        }
    }




    /**
     *
     * Populate the Subject object(s), and add it/them to an existing Constellation object.
     *
     * Extends AbstracteTermData
     *
     * @param string[] $vhInfo associative list with keys 'version' and 'main_id'.
     *
     * @param $cObj snac\data\Constellation object, passed by reference, and changed in place
     *
     */
    private function populateSubject($vhInfo, &$cObj)
    {
        /*
         * $gRows where g is for generic. As in "a generic object". Make this as idiomatic as possible.
         */
        $gRows = $this->sql->selectSubject($vhInfo);
        foreach ($gRows as $rec)
        {
            $gObj = new \snac\data\Subject();
            $gObj->setTerm($this->populateTerm($rec['term_id']));
            $gObj->setDBInfo($rec['version'], $rec['id']);
            /*
             * Must call $gOjb->setDBInfo() before calling populateDate()
             */
            $this->populateDate($gObj);
            $cObj->addSubject($gObj);
        }
    }


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
        $neRows = $this->sql->selectName($vhInfo);
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
            $cRows = $this->sql->selectContributor($neObj->getID(), $vhInfo['version']);
            foreach ($cRows as $contrib)
            {
                $ctObj = new \snac\data\Contributor();
                $ctObj->setType($this->populateTerm($contrib['name_type']));
                $ctObj->setName($contrib['short_name']);
                $ctObj->setDBInfo($contrib['version'], $contrib['id']);
                $neObj->addContributor($ctObj);
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
     *
     * @param $cObj snac\data\Constellation object, passed by reference, and changed in place
     */
    public function populateDate(&$cObj)
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

        foreach ($dateRows as $singleDate)
        {
            $dateObj = new \snac\data\SNACDate();
            $dateObj->setRange($singleDate['is_range']);
            $dateObj->setFromDate($singleDate['from_date'],
                                  $singleDate['from_date'],
                                  $this->populateTerm($dateRows['from_type']));
            $dateObj->setFromDateRange($singleDate['from_not_before'], $singleDate['from_not_after']);
            $dateObj->setToDate($singleDate['to_date'],
                                $singleDate['to_date'],
                                $this->populateTerm($dateRows['to_type']));
            $dateObj->setToDateRange($singleDate['to_not_before'], $singleDate['to_not_after']);
            $dateObj->setDBInfo($singleDate['version'], $singleDate['id']);

            $cObj->addDate($dateObj);
            if ($breakAfterOne)
            {
                break;
            }
        }
    }

    /**
     * Create Term
     *
     * Return a vocabulary term object selected from database using vocabulary id key. \src\snac\data\Term
     * which is used by many objects for controlled vocabulary "terms". We use "term" broadly in the sense of
     * an object that meets all needs of the the user interface.
     *
     * You might be searching for new Term(). This is the only place we create Terms here.
     *
     * @param integer $termID A unique integer record id from the database table vocabulary.
     *
     */
    private function populateTerm($termID)
    {
        $newObj = new \snac\data\Term();
        $row = $this->sql->selectTerm($termID);
        $newObj->setID($row['id']);
        $newObj->setType($row['type']); // Was setDataType() but this is a vocaulary type. See Term.php.
        $newObj->setTerm($row['value']);
        $newObj->setURI($row['uri']);
        $newObj->setDescription($row['description']);
        return $newObj;
    }

    /**
     * Select convention declaration from the db, and build an appropriate object which is added to
     * Constellation.
     *
     * Extends AbstractTextData.
     *
     * Note: $cObj passed by reference and changed in place.
     *
     * @param integer[] $vhInfo list with keys version, main_id.
     *
     * @param $cObj snac\data\Constellation object, passed by reference, and changed in place
     *
     */
    public function populateConventionDeclaration($vhInfo, &$cObj)
    {
        $rows = $this->sql->selectConventionDeclaration($vhInfo);
        foreach ($rows as $item)
        {
            $newObj = new \snac\data\ConventionDeclaration();
            $newObj->setText($item['text']);
            $newObj->setDBInfo($item['version'], $item['id']);
            $cObj->addConventionDeclaration($newObj);
        }
    }

    /**
     * Insert convention declaration
     *
     * Extends AbstractTextData.
     *
     * Note: $cObj passed by reference and changed in place.
     *
     * @param integer[] $vhInfo list with keys version, main_id.
     *
     * @param $cObj snac\data\Constellation object
     *
     */
    public function saveConventionDeclaration($vhInfo, $cObj)
    {
        if ($gList = $cObj->getConventionDeclarations())
        {
            foreach ($gList as $term)
            {
                $this->sql->insertConventionDeclaration($vhInfo,
                                            $term->getID(),
                                            $term->getText());
            }
        }
    }


    /**
     * Select StructureOrGenealogy from database, create object, add the object to Constellation. Support
     * multiples per Constalltion.
     *
     * Extends AbstractTextData.
     *
     * Note: $cObj passed by reference and changed in place.
     *
     * @param integer[] $vhInfo list with keys version, main_id.
     *
     * @param $cObj snac\data\Constellation object, passed by reference, and changed in place
     */
    public function populateStructureOrGenealogy($vhInfo, &$cObj)
    {
        $rows = $this->sql->selectStructureOrGenealogy($vhInfo);
        foreach ($rows as $item)
        {
            $newObj = new \snac\data\StructureOrGenealogy();
            $newObj->setText($item['text']);
            $newObj->setDBInfo($item['version'], $item['id']);
            $this->populateDate($newObj);
            $cObj->addStructureOrGenealogy($newObj);
        }
    }


    /**
     * Save StructureOrGenealogy to database
     *
     * Extends AbstractTextData.
     *
     * @param integer[] $vhInfo list with keys version, main_id.
     *
     * @param $cObj snac\data\Constellation object
     */
    public function saveStructureOrGenealogy($vhInfo, $cObj)
    {
        if ($gList = $cObj->getStructureOrGenealogies())
        {
            foreach ($gList as $item)
            {
                $this->sql->insertStructureOrGenealogy($vhInfo,
                                           $item->getID(),
                                           $item->getText());
            }
        }
    }

    /**
     * Select GeneralContext from database, create object, add the object to Constellation. Support multiples
     * per constellation.
     *
     * Extends AbstractTextData
     *
     * Note: $cObj passed by reference and changed in place.
     *
     * @param integer[] $vhInfo list with keys version, main_id.
     *
     * @param $cObj snac\data\Constellation object, passed by reference, and changed in place
     */
    public function populateGeneralContext($vhInfo, &$cObj)
    {
        $rows = $this->sql->selectGeneralContext($vhInfo);
        foreach ($rows as $item)
        {
            $newObj = new \snac\data\GeneralContext();
            $newObj->setText($item['term']);
            $newObj->setDBInfo($item['version'], $item['id']);
            $this->populateDate($newObj);
            $cObj->addGeneralContext($newObj);
        }
    }

    /**
     * Save GeneralContext to database
     *
     * Extends AbstractTextData
     *
     * @param integer[] $vhInfo list with keys version, main_id.
     *
     * @param $cObj snac\data\Constellation object
     */
    public function saveGeneralContext($vhInfo, $cObj)
    {
        if ($gList = $cObj->getGeneralContexts())
        {
            foreach ($gList as $item)
            {
                $this->sql->insertGeneralContext($vhInfo,
                                                 $item->getID(),
                                                 $item->getText());
            }
        }
    }


    /**
     * Select nationality from database
     *
     * Create object, add the object to Constellation. Support multiples per constellation.
     *
     * Note: $cObj passed by reference and changed in place.
     *
     * @param integer[] $vhInfo list with keys version, main_id.
     *
     * @param $cObj snac\data\Constellation object, passed by reference, and changed in place
     */
    public function populateNationality($vhInfo, &$cObj)
    {
        $rows = $this->sql->selectNationality($vhInfo);
        foreach ($rows as $item)
        {
            $newObj = new \snac\data\Nationality();
            $newObj->setTerm($this->populateTerm($item['term_id']));
            $newObj->setDBInfo($item['version'], $item['id']);
            $this->populateDate($newObj);
            $cObj->addNationality($newObj);
        }
    }

    /**
     * Save nationality to database
     *
     * @param integer[] $vhInfo list with keys version, main_id.
     *
     * @param $cObj snac\data\Constellation object
     */
    public function saveNationality($vhInfo, $cObj)
    {
        if ($gList = $cObj->getNationalities())
        {
            foreach ($gList as $item)
            {
                $this->sql->insertNationality($vhInfo,
                                  $item->getID(),
                                  $this->thingID($item->getTerm()));
            }
        }
    }

    /*
     * Select language from the database, create a language object, add the language to the object referenced
     * by $cObj.
     *
     * We have two term ids, language_id and script_id, so they need unique names (keys) and not the usual
     * "term_id".
     *
     * Note: $cObj passed by reference and changed in place.
     *
     * @param integer[] $vhInfo list with keys version, main_id.
     *
     * @param $cObj snac\data\Constellation object, passed by reference, and changed in place
     *
     */
    public function populateLanguage(&$cObj)
    {
        $rows = $this->sql->selectLanguage($cObj->getID(), $cObj->getVersion());
        foreach ($rows as $item)
        {
            $newObj = new \snac\data\Language();
            $newObj->setLanguage($this->populateTerm($item['language_id']));
            $newObj->setScript($this->populateTerm($item['script_id']));
            $newObj->setVocabularySource($item['vocabulary_source']);
            $newObj->setNote($item['note']);
            $newObj->setDBInfo($item['version'], $item['id']);
            $class = get_class($cObj);
            // SNACControlMetadata
            if ($class == 'snac\data\SNACControlMetadata' ||
                $class == 'snac\data\Source' ||
                $class == 'snac\data\BiogHist')
            {
                $cObj->setLanguage($newObj);
            }
            else if ($class == 'snac\data\Constellation')
            {
                $cObj->addLanguage($newObj);
            }
        }
    }

    /**
     * Create a source object from the database
     *
     * Select source from the database, create a source object and return it. The api isn't consisten with how
     * Source objects are added to other objects, so we're best off to build and return. This is different
     * than the populate* functions that rely on a consistent api to add theirself to the parent object.
     *
     * This is a bit exciting because Constellation will have a list of Source, but SNACControlMetadata only
     * has a single Source.
     *
     * Two options for setCitaion()
     * 1) call a function to build a Source object, call setCitation()
     *  $sourceArrayOrSingle = $this->buildSource($cObj);
     *  $gObj->setCitation($sourceOjb)
     *
     * 2) Call populateSource(), which is smart enough to know that SNACControlMetadata uses setCitation()
     * for its Source object and Constellation uses addSource().
     *
     * Option 2 is better because Constellation needs an array and SNACControlMetadata needs a single
     * Source object. It would be very odd for a function to return an array sometimes, and a single
     * object other times. The workaround for that is two functions, which is awkard.
     *
     * Best to just take our medicine and encapsulate the complexity inside here populateSource().
     *
     * @param integer[] $vhInfo list with keys version, main_id.
     *
     * @param $cObj snac\data\Constellation object, passed by reference, and changed in place
     *
     */
    public function populateSource(&$cObj)
    {
        $rows = $this->sql->selectSource($cObj->getID(), $cObj->getVersion());
        foreach ($rows as $rec)
        {
            $newObj = new \snac\data\Source();
            $newObj->setText($rec['text']);
            $newObj->setNote($rec['note']);
            $newObj->setURI($rec['uri']);
            $newObj->setType($this->populateTerm($rec['type_id']));
            $newObj->setDBInfo($rec['version'], $rec['id']);
            /*
             * setLanguage() is a Language object.
             */
            $this->populateLanguage($newObj);

            $class = get_class($cObj);
            if ($class == 'snac\data\Constellation')
            {
                $cObj->addSource($newObj);
            }
            else if ($class == 'snac\data\SNACControlMetadata')
            {
                $cObj->setCitation($newObj);
                // There is only one Source in the citation, so best that we break now.
                break;
            }
            else
            {
                $msg = sprintf("Cannot add Source to class: %s\n", $class);
                die($msg);
            }
        }
    }


    /**
     * Select mandate from database, create object, add the object to Constellation. Support multiples
     * per constellation.
     *
     * Extends AbstractTextData
     *
     * Note: $cObj passed by reference and changed in place.
     *
     * @param integer[] $vhInfo list with keys version, main_id.
     *
     * @param $cObj snac\data\Constellation object, passed by reference, and changed in place
     */
    public function populateMandate($vhInfo, &$cObj)
    {
        $rows = $this->sql->selectMandate($vhInfo);
        foreach ($rows as $item)
        {
            $newObj = new \snac\data\Mandate();
            $newObj->setText($item['text']);
            $newObj->setDBInfo($item['version'], $item['id']);
            // Dunno where date came from. Class mandate seems to have no dates (does not setMaxDate)
            // $this->populateDate($newObj);
            $cObj->addMandate($newObj);
        }
    }

    /**
     * Save mandate to database
     *
     * Extends AbstractTextData
     *
     * @param integer[] $vhInfo list with keys version, main_id.
     *
     * @param $cObj snac\data\Constellation object
     */
    public function saveMandate($vhInfo, $cObj)
    {
        if ($gList = $cObj->getMandates())
        {
            foreach ($gList as $term)
            {
                $mid = $this->sql->insertMandate($vhInfo,
                                                 $term->getID(),
                                                 $term->getText());
            }
        }
    }

    /**
     * Select gender from database, create object, add the object to Constellation. Support multiples
     * per constellation.
     *
     * Note: $cObj passed by reference and changed in place.
     *
     * @param integer[] $vhInfo list with keys version, main_id.
     *
     * @param $cObj snac\data\Constellation object, passed by reference, and changed in place
     */
    public function populateGender($vhInfo, &$cObj)
    {
        $rows = $this->sql->selectGender($vhInfo);
        foreach ($rows as $item)
        {
            $newObj = new \snac\data\Gender();
            $newObj->setTerm($this->populateTerm($item['term_id']));
            $newObj->setDBInfo($item['version'], $item['id']);
            $this->populateDate($newObj);
            $cObj->addGender($newObj);
        }
    }

    /**
     * Select GeneralContext from database, create object, add the object to Constellation. Support multiples
     * per constellation.  Get BiogHist from database, create relevant object and add to the constellation
     * object passed as an argument.
     *
     * Note: $cObj passed by reference and changed in place.
     *
     * @param integer[] $vhInfo list with keys version, main_id.
     *
     * @param $cObj snac\data\Constellation object, passed by reference, and changed in place
     */
    public function populateBiogHist($vhInfo, &$cObj)
    {
        $rows = $this->sql->selectBiogHist($vhInfo);
        foreach ($rows as $item)
        {
            $newObj = new \snac\data\BiogHist();
            $newObj->setText($item['text']);
            $newObj->setDBInfo($item['version'], $item['id']);
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
            $occObj->setTerm($this->populateTerm($oneOcc['occupation_id']));
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
     * | php                                    | sql              |
     * |----------------------------------------+------------------|
     * | setDBInfo                              | id               |
     * | setDBInfo                              | version          |
     * | setDBInfo                              | main_id          |
     * | setTargetConstellation                 | related_id       |
     * | setTargetArkID                         | related_ark      |
     * | setTargetEntityType  was setTargetType | role             |
     * | setType                                | arcrole          |
     * | setCPFRelationType                     | relation_type    |
     * | setContent                             | relation_entry   |
     * | setDates                               | date             |
     * | setNote                                | descriptive_note |
     *
     * cpfRelation/@type cpfRelation@xlink:type
     *
     * php: $altType setAltType() getAltType()
     *
     * The only value this ever has is "simple". Daniel says not to save it, and implicitly hard code when
     * serializing export.
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
            /*
             * setsourceConstellation() is parent::getID()
             * setSourceArkID() is parent::getARK()
             *
             * Unclear why those methods (and their properties) exist, but fill them in regardless.
             */
            $relatedObj->setSourceConstellation($cObj->getID());
            $relatedObj->setSourceArkID($cObj->getARK());

            $relatedObj->setTargetConstellation($oneRel['related_id']);
            $relatedObj->setTargetArkID($oneRel['related_ark']);
            $relatedObj->setTargetEntityType($this->populateTerm($oneRel['role']));
            $relatedObj->setType($this->populateTerm($oneRel['arcrole']));
            /*
             * Not using setAltType(). It is never used. See ConstellationRelation.php
             */
            $relatedObj->setCPFRelationType($this->populateTerm($oneRel['relation_type']));
            $relatedObj->setContent($oneRel['relation_entry']);
            $relatedObj->setNote($oneRel['descriptive_note']);
            $relatedObj->setDBInfo($oneRel['version'], $oneRel['id']);

            /*
             * Deprecated
             * $relatedObj->setDates($oneRel['date']);
             */
            $this->populateDate($relatedObj);

            $cObj->addRelation($relatedObj);
        }
    }


    /**
     * Populate the ResourceRelation
     *
     * Populate object(s), and add it/them to an existing Constellation object.
     *
     * | php                  | sql                      | CPF                                       |
     * |----------------------+--------------------------+-------------------------------------------|
     * | setDBInfo            | id                       |                                           |
     * | setDBInfo            | version                  |                                           |
     * | setDBInfo            | main_id                  |                                           |
     * | setDocumentType      | role                     | resourceRelation/@role                    |
     * | setRelationEntryType | relation_entry_type      | resourceRelation/relationEntry/@localType |
     * | setLinkType          | always "simple", ignored | resourceRelation@xlink:type               |
     * | setLink              | href                     | resourceRelation/@href                    |
     * | setRole              | arcrole                  | resourceRelation/@arcrole                 |
     * | setContent           | relation_entry           | resourceRelation/resourceEntry            |
     * | setSource            | object_xml_wrap          | resourceRelation/objectXMLWrap            |
     * | setNote              | descriptive_note         | resourceRelation/descriptiveNote          |
     *
     * @param string[] $vhInfo associative list with keys 'version' and 'main_id'.
     * @param $cObj snac\data\Constellation object, passed by reference, and changed in place
     *
     */
    public function populateResourceRelation($vhInfo, &$cObj)
    {
        $rrRows = $this->sql->selectResourceRelation($vhInfo);
        foreach ($rrRows as $oneRes)
        {
            $rrObj = new \snac\data\ResourceRelation();
            $rrObj->setDocumentType($this->populateTerm($oneRes['role']));
            $rrObj->setRelationEntryType($oneRes['relation_entry_type']);
            /*
             * setLinkType() Not used. Always "simple" See ResourceRelation.php
             */
            $rrObj->setLink($oneRes['href']);
            $rrObj->setRole($this->populateTerm($oneRes['arcrole']));
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
            $fObj->setTerm($this->populateTerm($oneFunc['function_id']));
            $fObj->setVocabularySource($oneFunc['vocabulary_source']);
            $fObj->setNote($oneFunc['note']);
            $fObj->setDBInfo($oneFunc['version'], $oneFunc['id']);

            /*
             * Must call $fOjb->setDBInfo() before calling populateDate()
             */
            $fDate = populateDate($fObj);
            $cObj->addFunction($fObj);
        }
    }

    /**
     * Write a PHP Constellation object to the database.
     *
     * This is a new constellation, and will get new version and main_id values. Calls saveConstellation() to
     * call a sql function to do the actual writing.
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
     * Save place object
     *
     * Save a list of places to place_link, including meta data.
     *
     * The only way to know the related table is for it to be passed in via $relatedTable.
     *
     * @param string[] $vhInfo Array with keys 'version', 'main_id' for this constellation.
     *
     * @param snac\data\AbstractData Object $id An object that might have a place, and that extends
     * AbstractData.
     *
     * @param string $relatedTable Name of the related table for this place.
     *
     */
    private function savePlace($vhInfo, $id, $relatedTable)
    {
        if ($placeList = $id->getPlaces())
        {
            foreach($placeList as $gObj)
            {
                $pid = $this->sql->insertPlace($vhInfo,
                                               $gObj->getID(),
                                               $this->db->boolToPg($gObj->getConfirmed()),
                                               $gObj->getOriginal(),
                                               $this->thingID($gObj->getGeoTerm()),
                                               $relatedTable,
                                               $id->GetID());
                if ($metaObjList = $gObj->getSNACControlMetadata())
                {
                    $this->saveMeta($vhInfo, $metaObjList, 'place_link', $pid);
                }
            }
        }
    }

    /**
     *
     */
    private function saveMeta($vhInfo, $metaObjList, $fkTable, $fkID)
    {
        if (! $metaObjList)
        {
            return;
        }
        /*
         * Citation is a Source object. Source objects are like dates: each one is specific to the
         * related record. Source is not a controlled vocabulary. Therefore, like date, Source has
         * an fk back to the original table.
         *
         * Note: this depends on an existing Source, DescriptiveRule, and Language, each in its
         * appropriate table in the database. Or if not existing they can be null.
         */
        foreach ($metaObjList as $metaObj)
        {
            $metaID = $this->sql->insertMeta($vhInfo,
                                             $metaObj->getID(),
                                             $metaObj->getSubCitation(),
                                             $metaObj->getSourceData(),
                                             $this->thingID($metaObj->getDescriptiveRule()),
                                             $metaObj->getNote(),
                                             $fkTable,
                                             $fkID);
            if ($lang = $metaObj->getLanguage())
            {
                $this->sql->insertLanguage($vhInfo,
                                           $lang->getID(),
                                           $this->thingID($lang->getLanguage()),
                                           $this->thingID($lang->getScript()),
                                           $lang->getVocabularySource(),
                                           $lang->getNote(),
                                           'scm',
                                           $metaID);
            }
            $citeID = null;
            if ($cite = $metaObj->getCitation())
            {
                $this->saveSource($vhInfo, $cite, 'scm', $metaID);
            }
        }
    }

    /**
     * Save a Source
     *
     * Source objects are written to table source, and their related language (if one exists) is written to
     * table Language with a reverse foreign key as usual. Related on source.id=language.fk_id.
     *
     *
     * 'type' is always simple, and Daniel says we can ignore it. It was used in EAC-CPF just to quiet
     * validation.
     *
     * Source is first order data. It is a non-authority description of a source. Each source is not a
     * shared authority and is singular to the record to which it is attached. That is: each Source is
     * related back to a record. There can be multiple sources all related back to a single record, as
     * is the case here in Constellation (nrd).
     *
     * @param Object $gObj The object containing this source
     *
     * @param string $fkTable The name of the containing object's table.
     *
     * @param integer $fkID The record id of the containing table.
     *
     */
    private function saveSource($vhInfo, $gObj, $fkTable, $fkID)
    {
        $genericRecordID = $this->sql->insertSource($vhInfo,
                                                    $gObj->getID(),
                                                    $gObj->getText(),
                                                    $gObj->getNote(),
                                                    $gObj->getURI(),
                                                    $this->thingID($gObj->getType()),
                                                    $fkTable,
                                                    $fkID);
        /*
         * Source only has a single language.
         */
        if ($lang = $gObj->getLanguage())
        {
            $this->sql->insertLanguage($vhInfo,
                                       $lang->getID(),
                                       $this->thingID($lang->getLanguage()),
                                       $this->thingID($lang->getScript()),
                                       $lang->getVocabularySource(),
                                       $lang->getNote(),
                                       'source',
                                       $genericRecordID);
         }
    }


    /**
     * Private function. Update a php constellation that is already in the database. This is called from
     * insertConstellation() or updateConstellation().
     *
     * The id->getID() has been populated by the calling code, whether this is new or exists in the
     * database. This is due to constellation id values coming out of table version_history, unlike all other
     * tables. For this reason, insertNrd() does not return the nrd.id value.
     *
     * nrd (ark, entityType), gender, date, language, bioghist, otherrecordid, nameentry, source, legalstatus, occupation,
     * function, subject, relation, resourceRelation
     *
     * ?? conventionDeclaration, place, nationality, generalContext, structureOrGenealogy,
     * mandate
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

        $this->savePlace($vhInfo, $id, 'nrd');
        $this->saveConventionDeclaration($vhInfo, $id);
        $this->saveNationality($vhInfo, $id);
        $this->saveGeneralContext($vhInfo, $id);
        $this->saveStructureOrGenealogy($vhInfo, $id);
        $this->saveMandate($vhInfo, $id);

        foreach ($id->getGenders() as $fdata)
        {
            $this->sql->insertGender($vhInfo,
                                     $fdata->getID(),
                                     $this->thingID($fdata->getTerm()));
        }

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

        /*
         * Constellation getLanguage() returns a list of Language objects. That's very reasonable in this
         * context.
         */
        foreach ($id->getLanguage() as $lang)
        {
            $this->sql->insertLanguage($vhInfo,
                                       $lang->getID(),
                                       $this->thingID($lang->getLanguage()),
                                       $this->thingID($lang->getScript()),
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
                                      $this->thingID($otherID->getType()),
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
            $this->saveSource($vhInfo, $fdata, 'nrd', $vhInfo['main_id']);
        }

        foreach ($id->getLegalStatuses() as $fdata)
        {
            $this->sql->insertLegalStatus($vhInfo,
                                          $fdata->getID(),
                                          $this->thingID($fdata->getTerm()));
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
                                                  $this->thingID($fdata->getTerm()),
                                                  $fdata->getVocabularySource(),
                                                  $fdata->getNote());
            foreach ($fdata->getDateList() as $date)
            {
                $date_fk = $this->sql->insertDate($vhInfo,
                                                  $date->getID(),
                                                  $this->db->boolToPg($date->getIsRange()),
                                                  $date->getFromDate(),
                                                  $this->thingID($date->getFromType()),
                                                  $this->db->boolToPg($date->getFromBC()),
                                                  $date->getFromRange()['notBefore'],
                                                  $date->getFromRange()['notAfter'],
                                                  $date->getToDate(),
                                                  $this->thingID($date->getToType()),
                                                  $this->db->boolToPg($date->getToBC()),
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
         * prototype: insertFunction($vhInfo, $id, $type, $vocabularySource, $note, $term)
         *
         * Example files: /data/extract/anf/FRAN_NP_050744.xml
         *
         */

        foreach ($id->getFunctions() as $fdata)
        {
            $funID = $this->sql->insertFunction($vhInfo,
                                                $fdata->getID(), // record id
                                                $this->thingID($fdata->getType()), // function type, aka localType, Term object
                                                $fdata->getVocabularySource(),
                                                $fdata->getNote(),
                                                $this->thingID($fdata->getTerm())); // function term id aka vocabulary.id, Term object
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
         * Save subject term
         *
         * getID() is the subject object record id.
         *
         * $this->thingID($term->getTerm()) more robust form of $term->getTerm()->getID() is the vocabulary id
         * of the Term object inside subject.
         */
        foreach ($id->getSubjects() as $term)
        {
            $this->sql->insertSubject($vhInfo,
                                      $term->getID(),
                                      $this->thingID($term->getTerm()));
        }


        /*
          ConstellationRelation

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

          getRelations() returns \snac\data\ConstellationRelation[]
          $fdata is \snac\data\ConstellationRelation
        */

        foreach ($id->getRelations() as $fdata)
        {
            /*
             * altType is cpfRelationType, at least in the CPF.
             *
             * Don't save the source info, because we are the source and have already saved the source data as
             * part of ourself.
             */
            $cpfRelTypeID = null;
            if ($cr = $fdata->getcpfRelationType())
            {
                $cpfRelTypeID = $cr->getID();
            }
            $relID = $this->sql->insertRelation($vhInfo,
                                                $fdata->getTargetConstellation(),
                                                $fdata->getTargetArkID(),
                                                $this->thingID($fdata->getTargetEntityType()),
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

          | placeholder | php                 | what, CPF                                        | sql                  |
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
                                               $fdata->getDocumentType()->getID(), // xlink:role
                                               $fdata->getEntryType()== null ? null : $fdata->getEntryType()->getID(), // relationEntry@localType
                                               $fdata->getLink(), // xlink:href
                                               $fdata->getRole()->getID(), // xlink:arcrole
                                               $fdata->getContent(), // relationEntry
                                               $fdata->getSource(), // objectXMLWrap
                                               $fdata->getNote(), // descriptiveNote
                                               $fdata->getID());
        }

        return $vhInfo;
    } // end saveConstellation


    public function searchVocabulary($type, $query) {

        return $this->sql->searchVocabulary($type, $query);
    }
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

        if ($lang = $biogHist->getLanguage())
        {
            $this->sql->insertLanguage($vhInfo,
                                       $lang->getID(),
                                       $this->thingID($lang->getLanguage()),
                                       $this->thingID($lang->getScript()),
                                       $lang->getVocabularySource(),
                                       $lang->getNote(),
                                       'biog_hist',
                                       $bid);
        }
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
    public function saveName($vhInfo, $ndata)
    {
        $nameID = $this->sql->insertName($vhInfo,
                                         $ndata->getOriginal(),
                                         $ndata->getPreferenceScore(),
                                         $ndata->getID());

        if ($contribList = $ndata->getContributors())
        {
            foreach($contribList as $cb)
            {
                $this->sql->insertContributor($vhInfo,
                                              $ndata->getID(),
                                              $cb->getName(),
                                              $this->thingID($cb->getType()));
            }
        }

        if ($lang = $ndata->getLanguage())
        {
            $this->sql->insertLanguage($vhInfo,
                                       $lang->getID(),
                                       $this->thingID($lang->getLanguage()),
                                       $this->thingID($lang->getScript()),
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
