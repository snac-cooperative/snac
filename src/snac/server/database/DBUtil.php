<?php
  /**
   * High level database abstraction layer for constellations.
   *
   * License:
   *
   * @author Tom Laudeman
   * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
   * @copyright 2015 the Rector and Visitors of the University of Virginia, and
   *            the Regents of the University of California
   */

namespace snac\server\database;
use \snac\server\validation\ValidationEngine as ValidationEngine;
use snac\server\validation\validators\IDValidator;
use \snac\server\validation\validators\HasOperationValidator;


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
 * Functions saveFoo() are broad wrappers that traverse objects and save to the database via more granular
 * functions.
 *
 * Need: high level "populate", "build", "read" equivalent to saveFoo() like readFoo().
 *
 * Need: lockConstellation()
 *
 * We need a way to select the unlocked, published version. Probably best to get the version number of the
 * published, and call existing functions with the appropriate version number. 
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
     */
    private $canDelete = null;

    // private $ourVersion = null;
    // private $haveVersion = false;

    /**
     * Database connector object
     *
     * @var \snac\server\database\DatabaseConnector object.
     */
    private $db = null;

    /**
     * Constellation status
     *
     * These are the valid values for constellation status. These are used in the code, so an enumerated type
     * in the database was both irritating to maintain, added complexity, and was in the wrong place because
     * the values are needed here in the code, not over in the database.
     *
     * Add new status values to this variable. PHP allows list keys with no values, but I like the explicit
     * value, so these all have value 1. There are other ways to initialize the list, but this method is very clear.
     *
     * published: the published public constellation (proposed, not implemented)
     *
     * being edited: locked for edit, viewable only by the locker, and maybe special admins (proposed, not implemented)
     *
     * deleted: only admins can see this (implemented) See the new SQL() call above. This valued 'deleted' is pass to class SQL's constructor.
     *
     * needs review: awaiting review, proposed, probably will change
     *
     * bulk ingest: bulk inserted, might become directly published
     *
     * rejected: an uploaded record that fails integrity checks. The system will not allow the constellation
     * to be sent for review, nor can the constellation be published. Presumably, rejected records can be
     * change to 'locked editing', or transfered to another user with or without a status change.
     *
     * currently editing: Added Mar 29 2016 in order to deal with an edit happening right now, in this session
     * (that is: login session, aka in the web browser). Using this solves the problem of session locking,
     * even when there are multiple sessions. Using constellation status this way saves us having to (try) to
     * manage a session-to-constellation link.
     */
    private $statusList = array('published' => 1,
                                'needs review' => 1,
                                'rejected' => 1,
                                'locked editing' => 1,
                                'bulk ingest' => 1,
                                'deleted' =>1,
                                'currently editing' => 1,
                                'ingest cpf' => 1);

    /**
     * Check status values
     *
     * Validate status values. This is an evolving concept, so just return true or false right now.
     *
     * 'published', 'needs review', 'rejected', 'being edited', 'bulk ingest', 'deleted'
     *
     * @param string $status A status value
     * @return boolean Returns true if the $status is a valid status, else returns false.
     */ 
    private function statusOK($status)
    {
        if (isset($this->statusList[$status]))
        {
            return true;
        }
        return false;
    }
    
    /**
     * @var \Monolog\Logger $logger the logger for this server
     *
     * See enableLogging() in this file.
     */
    private $logger = null;


    /** 
     * Constructor
     *
     * The constructor for the DBUtil class.
     */
    public function __construct()
    {
        $this->db = new \snac\server\database\DatabaseConnector();

        /*
         * See private var $statusList. Passing the value of deleted to the SQL constructor is a valiant, but
         * probably pointless, attempt to use the deleted status symbolically, instead of being tightly
         * coupled with the string's value. In reality, I think it only makes matters more complex. If the
         * value changed (very unlikely) the "fix" cwould be a simple search and replace.
         */
        
        $this->sql = new SQL($this->db, 'deleted');

        /*
         * Mar 4 2016 Here's a little suprise: we don't have an object for name component. How this will work
         * is not determined, so I guess we're ignoring it for now. The topic came up when filling in the canDelete array.
         *
         * (Not only do we not have a name component object, we don't have any working code that deals with
         * name components, so there isn't an issue here. When we parse names into components, we will deal
         * with all this. We do have a SQL table name_component, but it is not used, yet.)
         * 
         * 'snac\data\Foo' => 'name_component',
         *
         */ 

        /*
         * This is a list of php class and SQL table, but only classes which supported by setDeleted(). All
         * the save* and populate* functions are unique and essentially hard coded. However, setDeleted() and
         * clearDeleted() are generalized so they use this to figure out what table is associated with a given
         * class. See prepOperation(), setDeleted(), and clearDeleted().
         *
         * Table nrd and the constellation have a different mechanism, so they are not listed here.
         *
         * What about table otherid? Oddly, we can't delete otherid records, and that seems wrong.
         * 
         */ 
        $this->canDelete = array('snac\data\BiogHist' => 'biog_hist',
                                 'snac\data\ConventionDeclaration' => 'convention_declaration',
                                 'snac\data\SNACDate' => 'date_range',
                                 'snac\data\SNACFunction' => 'function',
                                 'snac\data\Gender' => 'gender',
                                 'snac\data\GeneralContext' => 'general_context',
                                 'snac\data\Language' => 'language',
                                 'snac\data\LegalStatus' => 'legal_status',
                                 'snac\data\Mandate' => 'mandate',
                                 'snac\data\NameEntry' => 'name',
                                 'snac\data\Contributor' => 'name_contributor',
                                 'snac\data\Nationality' => 'nationality',
                                 'snac\data\Occupation' => 'occupation',
                                 'snac\data\SameAs' => 'otherid',
                                 'snac\data\Place' => 'place_link',
                                 'snac\data\ConstellationRelation' => 'related_identity',
                                 'snac\data\ResourceRelation' => 'related_resource',
                                 'snac\data\SNACControlMetadata' => 'scm',
                                 'snac\data\StructureOrGenealogy' => 'structure_genealogy',
                                 'snac\data\Source' => 'source',
                                 'snac\data\Subject' => 'subject');
    }

    /**
     * Enable logging
     *
     * Call this to enabled loggin for objects of this class. For various reasons, logging is not enabled by default.
     *
     * Check that we don't have a logger before creating a new one. This can be called as often as one wants
     * with no problems.
     */ 
    private function enableLogging()
    {
        global $log;
        if (! $this->logger)
        {
            // create a log channel
            $this->logger = new \Monolog\Logger('DBUtil');
            $this->logger->pushHandler($log);
        }
    }

    /**
     * Wrap logging
     *
     * When logging is disabled, we don't want to call the logger because we don't want to generate errors. We
     * also don't want logs to just magically start up. Doing logging should be very intentional, especially
     * in a low level class like SQL. Call enableLogging() before calling logDebug().
     *
     * @param string $msg The logging messages
     *
     * @param string[] $debugArray An associative list of keys and values to send to the logger.
     */
    private function logDebug($msg, $debugArray=array())
    {
        if ($this->logger)
        {
            $this->logger->addDebug($msg, $debugArray);
        }
    }
    

    /**
     * Table name for a given class.
     *
     * This does two things:
     *
     * 1) return the SQL table for a class
     *
     * 2) return null if the class in question can't be deleted
     *
     * @param object $cObj Some object that we think has an associated SQL table.
     */
    private function deleteOK($cObj)
    {
        if (isset($this->canDelete[get_class($cObj)]))
        {
            return $this->canDelete[get_class($cObj)];
        }
        return null;
    }

    /**
     * Test for delete operation
     *
     * This is a wrapper to deal with delete, and call setDeleted() if necessary. Returns true if not deleted
     * and it is ok to proceed with an insert or update.
     *
     * Note the setOperation() at the end right before return. It is best that we not return from the middle
     * of this function.
     *
     * This is where operation is cleared during write. Also see saveNrd() where operation for the constellation is cleared.
     *
     * @param integer[] $vhInfo Associative list with keys 'ic_id', 'version'.
     * @param object $cObj An object that supports getOperation() and getID().
     *
     * @return boolean true if not delete and ok to proceed
     */ 
    private function prepOperation($vhInfo, $cObj)
    {
        $theOp = $cObj->getOperation();
        $result = false;
        if ($theOp == \snac\data\AbstractData::$OPERATION_DELETE)
        {
            $this->setDeleted($vhInfo, $cObj);
            $result = false;
        }
        elseif (! $theOp)
        {
            /* 
             * if (! $cObj->getID())
             * {
             *     /\*
             *      * If we have no ID then this must be an insert, so return true now.  This is really just a
             *      * case during testing prior to all objects explicitly getting an operation. Once every
             *      * operation is set, this branch should never run.
             *      *\/ 
             *     $result = true;
             * }
             * else
             * {
             */
            /*
             * Apr 6 2016. The code above that allowed insert when no op and no id is wrong. The rule is: no
             * operation is nothing gets done. There's no being nice. Actually, it is nice to not do things
             * when no operation because the UI can be a bit more lax about things like empty objects.
             * 
             * Mar 8 2014. With a null operation, we do nothing, and by returning false we prevent the calling
             * code from doing anything as well.
             *
             * This prevents nameEntry with no operation from updating itself when its child contributor has
             * an operation. In some cases the other code will not send objects that have no operation, but
             * that doesn't save us any work here because we always have to test the operation.
             *
             * If the no-op objects really were simply not in the constellation, then all inserts and updates
             * would be identical. No-op objects are sometimes present, and thus this distinction for no
             * operation.
             *
             * Top level code will have already minted a new version number, and since all true updates and
             * inserts are equivalent at the low level, the only thing we need to do here is prevent
             * unnecessary updates on no-op objects.
             *
             */
            $result = false;
            /* } */
        }
        else
        {
            $result = true;
        }
        return $result;
    }

    /**
     * Read published by ARK
     *
     * Read a published constellation by ARK from the database.
     *
     * @param string $arkID An ARK
     * @param boolean $summary optional Optional arg if true then return summary constellation.
     *
     * @return \snac\data\Constellation|boolean A PHP constellation object or false if none found.
     *
     */
    public function readPublishedConstellationByARK($arkID, $summary=false)
    {
        $mainID = $this->sql->selectMainID($arkID);
        if ($mainID)
        {
            $version = $this->sql->selectCurrentVersionByStatus($mainID, 'published');
            if ($version)
            {
                $cObj = $this->readConstellation($mainID, $version, $summary);
                return $cObj;
            }
        }
        return false;
    }


    /**
     * Read published by ID
     *
     * Read a published constellation by constellation ID (aka ic_id, mainID) from the database.
     *
     * @param integer $mainID A constellation id
     * @param boolean $summary optional Optional arg if true then return summary constellation.
     *
     * @return \snac\data\Constellation A PHP constellation object.
     */ 
    public function readPublishedConstellationByID($mainID, $summary=false)
    {
        $version = $this->sql->selectCurrentVersionByStatus($mainID, 'published');
        if ($version)
        {
            $cObj = $this->readConstellation($mainID, $version, $summary);
            return $cObj;
        }
        // Need to throw an exception as well? Or do we? It is possible that higher level code is rather brute
        // force asking for a published constellation. Returning false means the request didn't work.
        $this->enableLogging();
        $this->logDebug(sprintf("Warning: cannot get constellation id: $mainID (This is expected for test testFullCPFWithEditList)"));
        return false;
    }


    /**
     * List ic_id, version by status
     *
     * Build a list of ic_id,version. If locked, than select by $user.
     *
     * The public API is listConstellationsWithStatus(), if you want a list of constellations with a
     * status such as 'locked editing'.
     *
     * @param \snac\data\User $user The user's to get the list for
     * @param string $status option An optional status value.
     * 
     * @return integer[] A list with keys 'ic_id', 'version'.
     */ 
    private function editList($user, $status='locked editing')
    {
        if ($user == null || $user->getUserID() == null) {
            return false;
        }
        
        $vhList = $this->sql->selectEditList($user->getUserID(), $status);
        if ($vhList)
        {
            return $vhList;
        }
        return false;
    }
    
    /**
     * Most recent constellation list by status current user only
     *
     * List constellations that meet all these criteria: 1) most recent,  2) current user, 3) given status
     *
     * This function returns valid, partial, summary constellations. The last arg to readConstellation() is
     * $summary and we pass true.
     *
     * Status defaults to 'locked editing'. The default is: user has the constellation locked for edit. Note:
     * 'locked editing' and 'currently editing' are different with different meanings.
     *
     * The constellations returned will always be owned by the current user, and will be the most recent
     * version, period. The returned constellations will be the absolutely most recent version for that
     * constellation. This will not return any constellation for which the most recent version does not match
     * status and user.
     *
     * Mar 29 2016 Robbie suggests we only return partial, summary constellations here with enough data to build
     * UI. Partial means: table nrd and table name_entry. We tried returning the full constellations, but that
     * was simply too much data to send to the web browser. The return values here are valid constellations
     * and can be treated as normal constellations which keeps all the code consistent. However, by only
     * containing a fraction of the data, the returned list is manageable.
     *
     * Was named listConstellationsLockedToUser().
     *
     * Defaults are: \snac\Config::$SQL_LIMIT (probably 42), \snac\Config::$SQL_OFFSET (probably 0).

     * Note about default paramter values: Unfortunately, we have to accept null and default to null since php
     * cannot default to a constant or variable in a function signature. Also, php does not allow optional
     * parameters except the last parameter, so we have to accept null for $limit regardless.
     *
     * From the php manual: "The default value must be a constant expression, not (for example) a variable, a
     * class member or a function call."
     * 
     * @param \snac\data\User $user The user to get the list of constellationsf or
     *
     * @param string optional $status A single status for the list of constellations. Not implemented, but
     * planned to support status values in addition to 'locked editing'
     *
     * @param integer $limit optional Limit to the number of records. Not optional here. Must be -1 for all, or an
     * integer . Default to the config when missing. 
     *
     * @param integer $offset optional An offset to jump into the list of records in the database. Optional defaults to
     * a config value. Must be -1 for all, or an integer. Default to the config when missing.
     *
     * @return \snac\data\Constellation[] A list of PHP constellation object (which might be summary objects),
     * or an empty array when there are no constellations.
     */
    public function listConstellationsWithStatusForUser($user,
                                                        $status='locked editing',
                                                        $limit=null,
                                                        $offset=null)
    {
        if ($user == null || $user->getUserID() == null) {
            return false;
        }
        
        if ($limit==null || ! is_int($limit))
        {
            $limit = \snac\Config::$SQL_LIMIT;
        }
        if ($offset == null || ! is_int($offset))
        {
            $offset = \snac\Config::$SQL_OFFSET;
        }
        $infoList = $this->sql->selectEditList($user->getUserID(), $status, $limit, $offset);
        if ($infoList)
        {
            $constellationList = array();
            foreach ($infoList as $idVer)
            {
                $cObj = $this->readConstellation($idVer['ic_id'], $idVer['version'], true);
                array_push($constellationList, $cObj);
            }
            return $constellationList;
        }
        return array();
    }
    
    /**
     * List constellations most recent by status for any user
     *
     * Return a list of valid (but partial, summary) constellations for a single status, but for any user, and the most
     * recent version.
     *
     * List constellations that meet all these criteria: 1) most recent, 2) given status. User is ignored,
     * thus constellations owned by any user are returned.
     *
     * This will return the most recent version for the status. For reasons of sanity and
     * safety, status defaults to 'published'. This function will handle any status, including various locks,
     * deleted, embargoed. The given status is returned for any user, and always the most recent version.
     *
     * There is no question of this honoring deleted. If you ask for 'published' and the most recent is
     * 'published' then you get published. Deleted, or any other status does not come into the argument,
     * because we must always match "most recent". In other words, if you ask for publshed and the most recent
     * is not published, you won't get that constellation. There is no question of status when the status is
     * not the requested status. This is a bit odd because nearly everything else in DBUtil fills some other
     * need and therefore behaves otherwise.
     *
     * Note about default paramter values: Unfortunately, we have to accept null and default to null since php
     * cannot default to a constant or variable in a function signature. Also, php does not allow optional
     * parameters except the last parameter, so we have to accept null for $limit regardless.
     *
     * @param string $status optional Status defaults to 'published'.
     * 
     * @param integer $limit optional Limit to the number of records. Not optional here. Must be -1 for all, or an
     * integer . Default to the config when missing.
     * 
     * @param integer $offset optional An offset to jump into the list of records in the database. Optional defaults to
     * a config value. Must be -1 for all, or an integer. Default to the config when missing. 
     *
     * @return \snac\data\Constellation[] A list of PHP constellation object, or false when there are no constellations.
     */
    public function listConstellationsWithStatusForAny($status='published',
                                                       $limit=null,
                                                       $offset=null)
    {
        if ($limit==null || ! is_int($limit))
        {
            $limit = \snac\Config::$SQL_LIMIT;
        }
        if ($offset == null || ! is_int($offset))
        {
            $offset = \snac\Config::$SQL_OFFSET;
        }
        $infoList = $this->sql->selectListByStatus($status, $limit, $offset);
        if ($infoList)
        {
            $constellationList = array();
            foreach ($infoList as $idVer)
            {
                $cObj = $this->readConstellation($idVer['ic_id'], $idVer['version'], true);
                array_push($constellationList, $cObj);
            }
            return $constellationList;
        }
        return false;
    }

    /**
     * Return version list
     *
     * List all version numbers for the given $mainID. This is a utility function which may eventually become
     * private if some broader public function takes over its purpose.
     *
     * @param integer $mainID Constellation ID
     *
     * @return integer[] List of version integers.
     * 
     */ 
    public function allVersion($mainID)
    {
        return null;
    }


    /**
     * Safely call object getID method
     *
     * Call this so we don't have to sprinkle ternary ops throughout our code. The alternative to using this
     * is for every call to getID() from a Language, Term, or Source to be made in the same ternary that is
     * inside this.  Works for any class that has a getID() method. Intended to use with Language, Term,
     * Source,
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
     * Fill in a constellation.
     *
     * Call all necessary functions to essentially traverse the constellation, getting all data from the
     * database. 
     *
     * Calling overview: readConstellation() calls selectConstellation() which calls many populate*() functions each of
     * which call one or more select*() SQL functions.
     *
     * This function serves two purposes. First, it simplfies the API so that all outside code sees
     * something high level. Second, it wraps all the mid-level functions that know something about structure
     * of objects. Each populate*() function knows how to populate its own object. selectConstellation() only
     * knows the list of populate*() functions to call. By factoring the code to have 4 levels of functions,
     * we avoid massive copy/paste. Each of the 4 levels does some bookkeeping appropriate to its level, so
     * this architecture is not merely to make the code more legible.
     *
     * Must call populateNrd() first so that constellation version and id are set.  Most functions use the
     * $vhInfo arg, but populateDate(), populateConstellationSource(), and populateLanguage() rely on the
     * internals of the constellation object.
     * 
     * We always need nrd data for ARK and entity type, and we always need the name. The constellation
     * object has mainID and version. That is enough data to render the user interface.
     *
     * Use readConstellation() as the public API. This is private.
     *
     * @param integer[] $vhInfo An associative list with keys 'version', 'ic_id'. Values are integers.
     * @param boolean $summary optional Optional arg if true then return summary constellation.
     *
     * @return \snac\data\Constellation A PHP constellation object.
     * 
     */
    private function selectConstellation($vhInfo, $summary=false)
    {
        $tableName = 'version_history';
        $cObj = new \snac\data\Constellation();
        $this->populateNrd($vhInfo, $cObj);
        $this->populateNameEntry($vhInfo, $cObj);
        /*
         * If any true value in $summary, only return a summary (partial) constellation constising of data
         * from nrd and name_entry. Yes, we are returning from the middle of the function when doing a
         * summary.
         */ 
        if ($summary)
        {
            return $cObj;
        }
        /*
         * Constellation SCM aka populateMeta() call moved here from populateNrd() because it is *not* the scm
         * for table nrd.
         */ 
        $this->populateMeta($vhInfo, $cObj, $tableName);
        $this->populateBiogHist($vhInfo, $cObj);
        $this->populateDate($vhInfo, $cObj, $tableName); // "Constellation Date" in SQL these dates are linked to table nrd.
        $this->populateSourceConstellation($vhInfo, $cObj); // "Constellation Source" in the order of statements here
        $this->populateConventionDeclaration($vhInfo, $cObj);
        $this->populateFunction($vhInfo, $cObj);
        $this->populateGender($vhInfo, $cObj);
        $this->populateGeneralContext($vhInfo, $cObj);
        $this->populateLanguage($vhInfo, $cObj, $cObj->getID(), $tableName); // Constellation->getID() returns ic_id aka nrd.ic_id
        $this->populateLegalStatus($vhInfo, $cObj);
        $this->populateMandate($vhInfo, $cObj);
        $this->populateNationality($vhInfo, $cObj);
        $this->populateOccupation($vhInfo, $cObj);
        $this->populateOtherRecordID($vhInfo, $cObj);
        $this->populatePlace($vhInfo, $cObj, $cObj->getID(), 'version_history'); // Constellation->getID() returns ic_id aka nrd.ic_id
        $this->populateStructureOrGenealogy($vhInfo, $cObj);
        $this->populateSubject($vhInfo, $cObj);
        $this->populateRelation($vhInfo, $cObj); // aka cpfRelation
        $this->populateResourceRelation($vhInfo, $cObj); // resourceRelation
        /* 
         * todo: maintenanceEvents and maintenanceStatus added to version history and managed from there.
         */
        return $cObj;
    } // end selectConstellation

    /**
     * Populate Constellation properties
     * 
     * Populate the Constellation's 1:1 properties. An existing (empty) constellation is changed in place.
     *
     * Get a constellation from the database
     *
     * Select a given constellation from the database based on version and ic_id.
     * Create an empty constellation by calling the constructor with no args. Then used the setters to add
     * individual properties of the class(es).
     *
     * | php                                                    | sql                    |
     * |--------------------------------------------------------+------------------------|
     * | setArkID                                               | ark_id                 |
     * | setEntityType                                          | entity_type            |
     * |                                                        |                        |
     *
     * @param integer[] $vhInfo associative list with keys 'version' and 'ic_id'. The version and ic_id
     * you want. Note that constellation component version numbers are the max() <= version requested.
     * ic_id is the unique id across all tables in this constellation. This is not the nrd.id, but is
     * version_history.ic_id which is also nrd.ic_id, etc.
     *
     * @param $cObj \snac\data\Constellation object, passed by reference, and changed in place
     *
     */ 
    private function populateNrd($vhInfo, $cObj)
    {
        $row = $this->sql->selectNrd($vhInfo);
        $cObj->setArkID($row['ark_id']);
        $cObj->setEntityType($this->populateTerm($row['entity_type']));
        $cObj->setID($vhInfo['ic_id']); // constellation ID, $row['ic_id'] has the same value.
        $cObj->setVersion($vhInfo['version']);
    }

    /**
     * Populate OtherRecordID
     * 
     * Populate the OtherRecordID object(s), and add it/them to an existing Constellation object.
     *
     * OtherRecordID is an array of SameAs \snac\data\SameAs[]
     *
     * Other record id can be found in the SameAs class.
     *
     * Here $otherID is a SameAs object. SameAs->setType() is a Term object and thus it takes populateTerm()
     * as an argument. SameAs->setURI() takes a string. Term->setTerm() takes a string. SameAs->setText()
     * takes a string.
     *
     * @param integer[] $vhInfo associative list with keys 'version' and 'ic_id'.
     *
     * @param $cObj \snac\data\Constellation object, passed by reference, and changed in place
     *
     */ 
    private function populateOtherRecordID($vhInfo, $cObj)
    {
        $oridRows = $this->sql->selectOtherID($vhInfo); 
        foreach ($oridRows as $rec)
        {
            $gObj = new \snac\data\SameAs();
            $gObj->setText($rec['text']); // the text of this sameAs or otherRecordID
            $gObj->setURI($rec['uri']); // the URI of this sameAs or otherRecordID
            $gObj->setType($this->populateTerm($rec['type'])); // \snac\data\Term Type of this sameAs or otherRecordID
            $gObj->setDBInfo($rec['version'], $rec['id']);
            $this->populateMeta($vhInfo, $gObj, 'otherid');
            $cObj->addOtherRecordID($gObj);
        }
    }

    /**
     * Populate Place object
     * 
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
     * @param integer[] $vhInfo associative list with keys 'version', 'ic_id'.
     *
     * @param \snac\data\Constellation $cObj Constellation object, passed by reference as is the default in
     * php, and changed in place
     *
     * @param integer $fkID An integer foreign key of the table that has a place.
     *
     * @param string $fkTable Table name of the foreign (related) table.
     */
    private function populatePlace($vhInfo, $cObj, $fkID, $fkTable)
    {
        /*
         * $gRows where g is for generic. As in "a generic object". Make this as idiomatic as possible.
         */
        $tableName = 'place_link';
        $gRows = $this->sql->selectPlace($fkID, $vhInfo['version'], $fkTable);
        foreach ($gRows as $rec)
        {
            $gObj = new \snac\data\Place();
            $gObj->setOriginal($rec['original']);
            $gObj->setType($this->populateTerm($rec['type']));
            $gObj->setRole($this->populateTerm($rec['role']));
            $gObj->setGeoTerm($this->buildGeoTerm($rec['geo_place_id']));
            $gObj->setScore($rec['score']);
            $gObj->setConfirmed($this->db->pgToBool($rec['confirmed']));
            $gObj->setNote($rec['note']);
            $gObj->setDBInfo($rec['version'], $rec['id']);
            $this->populateMeta($vhInfo, $gObj, $tableName);
            /*
             * Feb 11 2016 At some point, probably in the last few days, setSource() disappeared from class
             * Place. This is probably due to all AbstractData getting SNACControlMetadata (SCM) properties.
             * 
             * $metaObj = $this->buildMeta($rec['id'], $vhInfo['version']);
             * $gObj->setSource($metaObj);
             *
             * A whole raft of place related properties have been moved from Place to GeoTerm.
             */
            $this->populateDate($vhInfo, $gObj, $tableName);
            $cObj->addPlace($gObj);
        }
    }

    /**
     * Populate the SNACControlMetadata (SCM)
     *
     * Read the SCM from the database and add it to the object in $cObj.
     *
     * Don't be confused by setSource() that uses a Source object and setSource() that uses a
     * SNACControlMetadata object.
     *
     * The convention for related things like date, place, and meta is args ($id, $version) so we're
     * following that.
     *
     * @param integer[] $vhInfo associative list with keys 'version', 'ic_id'.
     *
     * @param integer $tid Table id, aka row id akd object id
     *
     * @param integer $version Constellation version number
     *
     * @param string $fkTable Name of the related table aka foreign table aka  
     */
    private function populateMeta($vhInfo, $cObj, $fkTable)
    {
        /*
         * $gRows where g is for generic. As in "a generic object". Make this as idiomatic as possible.
         * I'm pretty sure that first arg is an $fkID.
         */
        if ( $recList = $this->sql->selectMeta($cObj->getID(), $vhInfo['version'], $fkTable))
        {
            foreach($recList as $rec)
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
                $this->populateLanguage($vhInfo, $gObj, $rec['id'], 'scm');
                /*
                 * populateSourceByID() will call setCitation() for SNACControlMetadata objects and
                 * addSource() for Constellation object. SCM has only a single source, so it calls
                 * populateSourceByID().
                 */ 
                $this->populateSourceByID($vhInfo, $gObj, $rec['citation_id']);
                $cObj->addSNACControlMetadata($gObj);
            }
        }
    }
    

    /**
     * Populate LegalStatus
     *
     * Populate the LegalStatus object(s), and add it/them to an existing Constellation object.
     *
     * LegalStatus Extends AbstracteTermData
     *
     * @param integer[] $vhInfo associative list with keys 'version' and 'ic_id'.
     *
     * @param $cObj \snac\data\Constellation object, passed by reference, and changed in place
     *
     */
    private function populateLegalStatus($vhInfo, $cObj)
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
            $this->populateMeta($vhInfo, $gObj, 'legal_status');
            $cObj->addLegalStatus($gObj);
        }
    }


    /**
     * Populate the Subject object(s)
     *
     * Select subjects from db, create objects, add them to an existing Constellation.
     *
     * Extends AbstracteTermData
     *
     * @param integer[] $vhInfo associative list with keys 'version' and 'ic_id'.
     *
     * @param $cObj \snac\data\Constellation object, passed by reference, and changed in place
     *
     */
    private function populateSubject($vhInfo, $cObj)
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
            $this->populateMeta($vhInfo, $gObj, 'subject');
            $cObj->addSubject($gObj);
        }
    }


    /** 
     * Populate nameEntry objects
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
     * @param integer[] $vhInfo associative list with keys 'version', 'ic_id'.
     * @param $cObj \snac\data\Constellation object, passed by reference, and changed in place
     *
     */
    private function populateNameEntry($vhInfo, $cObj)
    {
        $tableName = 'name';
        $neRows = $this->sql->selectName($vhInfo);
        foreach ($neRows as $oneName)
        {
            $neObj = new \snac\data\NameEntry();
            $neObj->setOriginal($oneName['original']);
            $neObj->setPreferenceScore($oneName['preference_score']);
            $neObj->setDBInfo($oneName['version'], $oneName['id']); 
            /*
             * Contributor
             * 
             * This line works because $oneName['id'] == $neObj->getID() after calling setDBInfo(). Both are
             * record id, not constellation id. Both are non-null when reading from the database.
             */ 
            $cRows = $this->sql->selectContributor($neObj->getID(), $vhInfo['version']);
            foreach ($cRows as $contrib)
            {
                $ctObj = new \snac\data\Contributor();
                $ctObj->setType($this->populateTerm($contrib['name_type']));
                $ctObj->setRule($this->populateTerm($contrib['rule']));
                $ctObj->setName($contrib['short_name']);
                $ctObj->setDBInfo($contrib['version'], $contrib['id']);
                $neObj->addContributor($ctObj);
            }

            /*
             * Component
             */
            $componentRows = $this->sql->selectComponent($neObj->getID(), $vhInfo['version']);
            foreach ($componentRows as $cp)
            {
                /* 
                 * | class         | json key        | php property                        | getter     | setter      | SQL field   |
                 * |---------------+-----------------+-------------------------------------+------------+-------------+-------------|
                 * | NameComponent | "text"          | $this->text                         | getText()  | setText()   | nc_value    |
                 * | NameComponent | "order"         | $this->order                        | getOrder() | setOrder()  | c_order     |
                 * | NameComponent | "type"          | $this->type                         | getType()  | setType()   | nc_label    |
                 * | AbstractData  | 'id', 'version' | $this->getID(), $this->getVersion() |            | setDBInfo() | version, id |
                 */
                $cpObj = new \snac\data\NameComponent();
                $cpObj->setText($cp['nc_value']);
                $cpObj->setOrder($cp['c_order']);
                $cpObj->setType($this->populateTerm($cp['nc_label']));
                $cpObj->setDBInfo($cp['version'], $cp['id']);
                $neObj->addComponent($cpObj);
            }
            $this->populateMeta($vhInfo, $neObj, $tableName);
            $this->populateLanguage($vhInfo, $neObj, $oneName['id'], $tableName);
            $this->populateDate($vhInfo, $neObj, $tableName);
            $cObj->addNameEntry($neObj);
        }
    }

    /**
     * Populate dates
     * 
     * Select date range(s) from db, foreach create SNACDate object, add to the object $cObj, which may be any
     * kind of object that extends AbstractData.
     *
     * Currently, we call insertDate() for: nrd, occupation, function, relation,
     *
     * Note: $cObj passed by reference and changed in place.
     *
     * @param integer[] $vhInfo associative list with keys 'version', 'ic_id'.
     *
     * @param object $cObj \snac\data\Constellation object or other object with related date.
     * 
     * @param string $fkTable The related table name.
     */
    private function populateDate($vhInfo, $cObj, $fkTable)
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
        $dateRows = $this->sql->selectDate($cObj->getID(), $vhInfo['version'], $fkTable);

        foreach ($dateRows as $singleDate)
        {
            $dateObj = new \snac\data\SNACDate();
            $dateObj->setRange($this->db->pgToBool($singleDate['is_range']));
            $dateObj->setFromDate($singleDate['from_original'],
                                  $singleDate['from_date'],
                                  $this->populateTerm($singleDate['from_type']));
            $dateObj->setFromBC($this->db->pgToBool($singleDate['from_bc']));
            $dateObj->setFromDateRange($singleDate['from_not_before'], $singleDate['from_not_after']);
            $dateObj->setToDate($singleDate['to_original'],
                                $singleDate['to_date'],
                                $this->populateTerm($singleDate['to_type']));
            $dateObj->setToBC($this->db->pgToBool($singleDate['to_bc']));
            $dateObj->setToDateRange($singleDate['to_not_before'], $singleDate['to_not_after']);
            $dateObj->setNote($singleDate['descriptive_note']);
            $dateObj->setDBInfo($singleDate['version'], $singleDate['id']);
            $this->populateMeta($vhInfo, $dateObj, 'date_range');

            $cObj->addDate($dateObj);
            if ($breakAfterOne)
            {
                break;
            }
        }
    }

    /**
     * Populate Term
     *
     * Return a vocabulary term object selected from database using vocabulary id key. \src\snac\data\Term
     * which is used by many objects for controlled vocabulary "terms". We use "term" broadly in the sense of
     * an object that meets all needs of the the user interface.
     *
     * Most of the populate* functions build an object and add it to another existing object. This returns the
     * object, so it might better be called buildTerm() since we have already used that nameing convention.
     *
     * You might be searching for new Term(). This is the only place we create Terms here.
     *
     * @param integer $termID A unique integer record id from the database table vocabulary.
     *
     */
    private function populateTerm($termID)
    {
        $row = $this->sql->selectTerm($termID);
        if ($row == null || empty($row))
            return null;
        $newObj = new \snac\data\Term();
        $newObj->setID($row['id']);
        $newObj->setType($row['type']); // Was setDataType() but this is a vocaulary type. See Term.php.
        $newObj->setTerm($row['value']);
        $newObj->setURI($row['uri']);
        $newObj->setDescription($row['description']);
        /*
         * Class Term has no SNACControlMetadata
         */ 
        return $newObj;
    }

    /**
     * Build a GeoTerm
     *
     * Return a GeoTerm object selected from database. Outside code can (and will, sometimes) call this, but
     * primarily this is used to build GeoTerm objects as part of Place in a Constellation.
     *
     * @param integer $termID A unique integer record id from the database table geo_place.
     *
     * @return \snac\data\GeoTerm $gObj A GeoTerm object.
     */ 
    public function buildGeoTerm($termID)
    {
        $rec = $this->sql->selectGeoTerm($termID);
        if ($rec == null || empty($rec)) 
            return null;
        $gObj = new \snac\data\GeoTerm();
        $gObj->setID($rec['id']);
        $gObj->setURI($rec['uri']);
        $gObj->setName($rec['name']);
        $gObj->setLatitude($rec['latitude']);
        $gObj->setLongitude($rec['longitude']);
        $gObj->setAdministrationCode($rec['admin_code']);
        $gObj->setCountryCode($rec['country_code']);
        /*
         * Class GeoTerm has no SNACControlMetadata
         */ 
        return $gObj;
    }

    /**
     * Save a GeoTerm
     *
     * Insert a GeoTerm object into the database. This is a public function that outside code is expected to
     * call.
     *
     * @param \snac\data\GeoTerm $term A GeoTerm object
     *
     * @param integer $version A version number, defaults to 1
     *
     * The ID may be null or the empty string in which case the database will assign a new value.
     */ 
    public function saveGeoTerm(\snac\data\GeoTerm $term, $version)
    {
        if (! $version)
        {
            $version = 1;
        }
        $id = insertGeo($term->getID(),
                        $version,
                        $term->getURI(),
                        $term->getName(),
                        $term->getLatitude(),
                        $term->getLongitude(),
                        $term->getAdministrationCode(),
                        $term->getCountryCode());
        return $id;
    }



    /**
     * Select (populate) ConventionDeclaration
     *
     * Build an appropriate object which is added to Constellation.
     *
     * Extends AbstractTextData.
     *
     * Note: $cObj passed by reference and changed in place.
     *
     * @param integer[] $vhInfo list with keys version, ic_id.
     *
     * @param $cObj \snac\data\Constellation object, passed by reference, and changed in place
     *
     */
    private function populateConventionDeclaration($vhInfo, $cObj)
    {
        $rows = $this->sql->selectConventionDeclaration($vhInfo);
        foreach ($rows as $item)
        {
            $newObj = new \snac\data\ConventionDeclaration();
            $newObj->setText($item['text']);
            $newObj->setDBInfo($item['version'], $item['id']);
            $this->populateMeta($vhInfo, $newObj, 'convention_declaration');
            $cObj->addConventionDeclaration($newObj);
        }
    }


    /**
     * Save StructureOrGenealogy to database
     *
     * Extends AbstractTextData.
     *
     * @param integer[] $vhInfo list with keys version, ic_id.
     *
     * @param $cObj \snac\data\Constellation object
     */ 
    private function saveStructureOrGenealogy($vhInfo, $cObj)
    {
        if ($gList = $cObj->getStructureOrGenealogies())
        {
            foreach ($gList as $item)
            {
                $rid = $item->getID();
                if ($this->prepOperation($vhInfo, $item))
                {
                    $rid = $this->sql->insertStructureOrGenealogy($vhInfo,
                                                                  $item->getID(),
                                                                  $item->getText());
                    $item->setID($rid);
                    $item->setVersion($vhInfo['version']);
                }
                $this->saveMeta($vhInfo, $item, 'structure_genealogy', $rid);
            }
        }
    }

    /**
     * Select StructureOrGenealogy from database
     *
     * Create object, add the object to Constellation
     *
     * Extends AbstractTextData.
     *
     * @param integer[] $vhInfo list with keys version, ic_id.
     * 
     * @param $cObj \snac\data\Constellation object
     */ 
    private function populateStructureOrGenealogy($vhInfo, $cObj)
    {
        $rows = $this->sql->selectStructureOrGenealogy($vhInfo);
        foreach ($rows as $item)
        {
            $newObj = new \snac\data\StructureOrGenealogy();
            $newObj->setText($item['text']);
            $newObj->setDBInfo($item['version'], $item['id']);
            $this->populateMeta($vhInfo, $newObj, 'structure_genealogy');
            $cObj->addStructureOrGenealogy($newObj);
        }
    }


    /**
     * Select GeneralContext from database
     *
     * Create object, add the object to Constellation. Support multiples per constellation.
     *
     * Extends AbstractTextData
     *
     * Note: $cObj passed by reference and changed in place.
     *
     * @param integer[] $vhInfo list with keys version, ic_id.
     *
     * @param $cObj \snac\data\Constellation object, passed by reference, and changed in place
     */
    private function populateGeneralContext($vhInfo, $cObj)
    {
        $rows = $this->sql->selectGeneralContext($vhInfo);
        foreach ($rows as $item)
        {
            $newObj = new \snac\data\GeneralContext();
            $newObj->setText($item['text']);
            $newObj->setDBInfo($item['version'], $item['id']);
            $this->populateMeta($vhInfo, $newObj, 'general_context');
            $cObj->addGeneralContext($newObj);
        }
    }

    /**
     * Save GeneralContext to database
     *
     * Extends AbstractTextData
     *
     * @param integer[] $vhInfo list with keys version, ic_id.
     *
     * @param $cObj \snac\data\Constellation object
     */
    private function saveGeneralContext($vhInfo, $cObj)
    {
        if ($gList = $cObj->getGeneralContexts())
        {
            foreach ($gList as $item)
            {
                $rid = $item->getID();
                if ($this->prepOperation($vhInfo, $item))
                {
                    $rid = $this->sql->insertGeneralContext($vhInfo,
                                                            $item->getID(),
                                                            $item->getText());
                    $item->setID($rid);
                    $item->setVersion($vhInfo['version']);
                }
                $this->saveMeta($vhInfo, $item, 'general_context', $rid);
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
     * @param integer[] $vhInfo list with keys version, ic_id.
     *
     * @param $cObj \snac\data\Constellation object, passed by reference, and changed in place
     */
    private function populateNationality($vhInfo, $cObj)
    {
        $rows = $this->sql->selectNationality($vhInfo);
        foreach ($rows as $item)
        {
            $newObj = new \snac\data\Nationality();
            $newObj->setTerm($this->populateTerm($item['term_id']));
            $newObj->setDBInfo($item['version'], $item['id']);
            $this->populateMeta($vhInfo, $newObj, 'nationality');
            $cObj->addNationality($newObj);
        }
    }

    /**
     * Save nationality to database
     *
     * @param integer[] $vhInfo list with keys version, ic_id.
     *
     * @param $cObj \snac\data\Constellation object
     */
    private function saveNationality($vhInfo, $cObj)
    {
        if ($gList = $cObj->getNationalities())
        {
            foreach ($gList as $item)
            {
                $rid = $item->getID();
                if ($this->prepOperation($vhInfo, $item))
                {
                    $rid = $this->sql->insertNationality($vhInfo,
                                                         $item->getID(),
                                                         $this->thingID($item->getTerm()));
                    $item->setID($rid);
                    $item->setVersion($vhInfo['version']);
                }
                $this->saveMeta($vhInfo, $item, 'nationality', $rid);
            }
        }
    }

    /**
     * Select language from the database, create a language object, add the language to the object referenced
     * by $cObj.
     *
     * We have two term ids, language_id and script_id, so they need unique names (keys) and not the usual
     * "term_id".
     *
     * Note: $cObj passed by reference and changed in place.
     * 
     * @param integer[] $vhInfo associative list with keys 'version', 'ic_id'.
     *
     * @param object $cObj An object. May be: \snac\data\Constellation, snac\data\SNACControlMetadata,
     * snac\data\Source, snac\data\BiogHist. Passed by reference, and changed in place
     *
     * @param string $fkTable Table name of the related table.
     *
     */
    private function populateLanguage($vhInfo, $cObj, $fkID, $fkTable)
    {
        /* 
         * This reflects reality table.id=language.fk_id. Do not use the $cObj->getID(). The calling code
         * knows which ID to use. In one case the calling code does pass $cObj->getID() for $fkID, but we
         * can't know that down here, so we must use the $fkID param.
         */
        $rows = $this->sql->selectLanguage($fkID, $vhInfo['version'], $fkTable);

        foreach ($rows as $item)
        {
            $newObj = new \snac\data\Language();
            $newObj->setLanguage($this->populateTerm($item['language_id']));
            $newObj->setScript($this->populateTerm($item['script_id']));
            $newObj->setVocabularySource($item['vocabulary_source']);
            $newObj->setNote($item['note']);
            $newObj->setDBInfo($item['version'], $item['id']);
            $this->populateMeta($vhInfo, $newObj, 'language');
            $class = get_class($cObj);
            /*
             * Class specific method for setting/adding a language.
             */
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
     * Populate Constellation source list
     *
     * Source is first-order data, especially as viewed by the Constellation. However, Constellation sources
     * are also "linked" by some SCMs, when an SCM has a citation.
     * 
     * This is sort of a wrapper function which is necessary because Constellation objects have multiple
     * source objects in a list. This function is called from selectConstellation() and is specific to the
     * Constellation object.  Thus, this code adds each source to the Constellation. The helper function is
     * populateSourceByID() which can only do a single Source per call.
     *
     * We get a list of Source records from selectSourceIDList() (thus the "List" in the name).  SCM also has
     * a single source, and therefore SCM directly calls populateSourceByID().
     *
     * Constellation version aka version_history.id is always the "newest". If only SCM changed, then
     * constellation version would be the same as the SCM version. Thus we should use $vhInfo['verion'].
     *
     * If something else changed, then the Constellation version is newer than the SCM version, and we
     * should still use the constellation version (because always use the newest version available).
     *
     * @param integer[] $vhInfo associative list with keys 'version', 'ic_id'.
     *
     * @param $cObj \snac\data\Constellation object, passed by reference (as is the default in php for objects
     * as parameters), and changed in place
     */
    private function populateSourceConstellation($vhInfo, $cObj)
    {
        // Constellation version aka version_history.id is always the "newest". See note above.
        $rows = $this->sql->selectSourceIDList($cObj->getID(), $vhInfo['version']);

        foreach ($rows as $rec)
        {
            $this->populateSourceByID($vhInfo, $cObj, $rec['id']);
        }
    }

    
    /**
     * Populate one source object 
     *
     * This adds a single source to Constellation or SCM. The calling code will loop for Constellation,
     * possibly adding multple Sources to the Constellation.
     *
     * Select a source from the database based on Source id (not constellation id), create a source object and
     * add it to the passed in $cObj. $cObj can be either a Constellation or SCM. This function is called in a
     * loop for Constellation, but only called once for SCM. For the Constellation-loop call see
     * populateSourceConstellation().
     *
     * Note that Constellation will have a list (array) of Source via addSource(), but SNACControlMetadata
     * only has a single Source via setCitation(). The code below checks the get_class() of $cObj to know
     * which method to call.
     *
     * Constellation version aka version_history.id is always the "newest". If only SCM changed, then
     * constellation version would be the same as the SCM version. Thus we should use $vhInfo['verion'].
     *
     * If something else changed, then the Constellation version is newer than the SCM version, and we
     * should still use the constellation version (because always use the newest version available).
     *
     * Note that source is first-order data, especially as viewed by the Constellation. However, these
     * first-order sources may also be "linked" by some SCMs, when an SCM has a citation.
     *
     * apr 4 remove             $newObj->setType($this->populateTerm($rec['type_id']));
     *
     * @param integer[] $vhInfo associative list with keys 'version', 'ic_id'.
     *
     * @param object $cObj Either a \snac\data\Constellation Constellation object, or a
     * \snac\data\SNACControlMetadata object. (In PHP all objects are passed by reference as is the default in
     * php for objects as parameters, and changed in place.)
     *
     * @param integer $sourceID Source record id. This is a source.id value, and is different than foreign
     * keys in some other uses of $fkID in other functions.
     */
    private function populateSourceByID($vhInfo, $cObj, $sourceID)
    {
        $tableName = 'source';
        // Constellation version aka version_history.id is always the "newest". See note above.
        $rows = $this->sql->selectSourceByID($sourceID, $vhInfo['version']);
        foreach ($rows as $rec)
        {
            $newObj = new \snac\data\Source();
            $newObj->setDisplayName($rec['display_name']);
            $newObj->setText($rec['text']);
            $newObj->setNote($rec['note']);
            $newObj->setURI($rec['uri']);
            $newObj->setDBInfo($rec['version'], $rec['id']);

            // Set the type to "simple", the default required by Daniel
            $type = null;
            $types = $this->searchVocabulary("source_type", "simple");
            if (count($types) == 1) {
                $type = $this->populateTerm($types[0]["id"]);
            }
            $newObj->setType($type);

            $this->populateMeta($vhInfo, $newObj, $tableName);
            /*
             * setLanguage() is a Language object.
             */
            $this->populateLanguage($vhInfo, $newObj, $rec['id'], $tableName);

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
                $this->enableLogging();
                $this->logDebug($msg);
                throw new \snac\exceptions\SNACDatabaseException($msg);
            }
        }
    }

    /**
     * Save nrd
     *
     * Unlike other insert functions, insertNrd() does not return the id value. The id for nrd is the
     * constellation id, aka $vhInfo['ic_id'] aka ic_id aka version_history.ic_id, and as always,
     * $id->getID() once the Constellation has been saved to the database. The $vhInfo arg is created by
     * accessing the database, so it is guaranteed to be "new" or at least, up-to-date.
     *
     * The entityType may be null because toArray() can't tell the differnce between an empty class and a
     * non-empty class, leading to empty classes littering the JSON with empty json. To avoid that, we use
     * null for an empty class, and test with the ternary operator.
     *
     * @param integer[] $vhInfo list with keys version, ic_id.
     * 
     * @param $cObj \snac\data\Constellation object
     */
    private function saveNrd($vhInfo, $cObj)
    {
        $theOp = $cObj->getOperation();
        $theID = $cObj->getID();
        if (! $theOp && ! $theID)
        {
            /* 
             * If no operation and no id, this must be new (no id) so force the operation to be insert.
             * I think the old code intended to do this, but was simply wrong and could fail to write nrd.
             */
            $theOp = \snac\data\AbstractData::$OPERATION_INSERT;
        }

        if ($theOp == \snac\data\AbstractData::$OPERATION_UPDATE || 
            $theOp == \snac\data\AbstractData::$OPERATION_INSERT)
        {
            /*
             * Table nrd is special, and the identifier is ic_id.
             */ 
            $this->sql->insertNrd($vhInfo,
                                  $cObj->getArk(),
                                  $this->thingID($cObj->getEntityType()),
                                  $cObj->getID());
        }
        /*
         * else...
         * 
         * Any other operation, especially delete makes no sense for nrd.
         * If we get into this else, something has probably gone wrong.
         */
    }

    /**
     * Select mandate from database
     *
     * Create object, add the object to Constellation. Support multiples per constellation.
     *
     * Extends AbstractTextData
     *
     * Note: $cObj passed by reference and changed in place.
     *
     * @param integer[] $vhInfo list with keys version, ic_id.
     *
     * @param $cObj \snac\data\Constellation object, passed by reference, and changed in place
     */
    private function populateMandate($vhInfo, $cObj)
    {
        $rows = $this->sql->selectMandate($vhInfo);
        foreach ($rows as $item)
        {
            $newObj = new \snac\data\Mandate();
            $newObj->setText($item['text']);
            $newObj->setDBInfo($item['version'], $item['id']);
            $this->populateMeta($vhInfo, $newObj, 'mandate');
            $cObj->addMandate($newObj);
        }
    }

    /**
     * Save mandate to database
     *
     * Extends AbstractTextData
     *
     * @param integer[] $vhInfo list with keys version, ic_id.
     *
     * @param $cObj \snac\data\Constellation object
     */
    private function saveMandate($vhInfo, $cObj)
    {
        if ($gList = $cObj->getMandates())
        {
            foreach ($gList as $term)
            {
                $rid = $term->getID();
                if ($this->prepOperation($vhInfo, $term))
                {
                    $rid = $this->sql->insertMandate($vhInfo,
                                                     $term->getID(),
                                                     $term->getText());
                    $term->setID($rid);
                    $term->setVersion($vhInfo['version']);
                }
                $this->saveMeta($vhInfo, $term, 'mandate', $rid);
            }
        }
    }

    /**
     * Save conventionDeclaration to database
     *
     * Extends AbstractTextData
     * 
     * @param integer[] $vhInfo list with keys version, ic_id.
     * 
     * @param $cObj \snac\data\Constellation object
     */
    private function saveConventionDeclaration($vhInfo, $cObj)
    {
        if ($gList = $cObj->getConventionDeclarations())
        {
            foreach ($gList as $term)
            {
                $rid = $term->getID();
                if ($this->prepOperation($vhInfo, $term))
                {
                    $rid = $this->sql->insertConventionDeclaration($vhInfo,
                                                                   $term->getID(),
                                                                   $term->getText());
                    $term->setID($rid);
                    $term->setVersion($vhInfo['version']);
                }
                $this->saveMeta($vhInfo, $term, 'convention_declaration', $rid);
            }
        }
    }

    /**
     * Save gender data
     *
     * @param integer[] $vhInfo list with keys version, ic_id.
     * 
     * @param $cObj \snac\data\Constellation object
     */
    private function saveGender($vhInfo, $cObj)
    {
        foreach ($cObj->getGenders() as $fdata)
        {
            $rid = $fdata->getID();
            if ($this->prepOperation($vhInfo, $fdata))
            {
                $rid = $this->sql->insertGender($vhInfo,
                                                $fdata->getID(),
                                                $this->thingID($fdata->getTerm()));
                $fdata->setID($rid);
                $fdata->setVersion($vhInfo['version']);
            }
            $this->saveMeta($vhInfo, $fdata, 'gender', $rid);
        }
    }

    /**
     * Save date list of constellation
     *
     * The related table is 'version_history'. Very early on, we thought of nrd as the root of the
     * constellation, but that is inaccruate.
     *
     * @param integer[] $vhInfo list with keys version, ic_id.
     * 
     * @param $cObj \snac\data\Constellation object
     */
    private function saveConstellationDate($vhInfo, $cObj)
    {
        foreach ($cObj->getDateList() as $date)
        {
            $this->saveDate($vhInfo, $date, 'version_history', $vhInfo['ic_id']);
            /*
             * We don't saveMeta() after save functions, only after insert functions. saveDate() calls
             * saveMeta() internally.
             */ 
        }
    }

    /**
     * Save date object to database
     * 
     * Save a date to the database, relating it to the table and foreign key id in $tableName and $tableID.
     *
     * $date is a SNACDate object.
     * getFromType() must be a Term object
     * getToType() must be a Term object
     *
     * @param integer[] $vhInfo list with keys version, ic_id.
     *
     * @param \snac\data\SNACDate $date A single date object
     *
     * @param string $tableName Name of the related table
     *
     * @param integer $tableID Record id of the related table
     *
     * What does it mean to have a date with no fromType? Could be an unparseable date, I guess.
     */
    private function saveDate($vhInfo, $date, $tableName,  $tableID)
    {
        $rid = $date->getID();
        if ($this->prepOperation($vhInfo, $date))
        {
            $rid = $this->sql->insertDate($vhInfo,
                                          $date->getID(),
                                          $this->db->boolToPg($date->getIsRange()),
                                          $date->getFromDate(),
                                          $this->thingID($date->getFromType()),
                                          $this->db->boolToPg($date->getFromBc()),
                                          $date->getFromRange()['notBefore'],
                                          $date->getFromRange()['notAfter'],
                                          $date->getFromDateOriginal(),
                                          $date->getToDate(),
                                          $this->thingID($date->getToType()),
                                          $this->db->boolToPg($date->getToBc()),
                                          $date->getToRange()['notBefore'],
                                          $date->getToRange()['notAfter'],
                                          $date->getToDateOriginal(),
                                          $date->getNote(),
                                          $tableName,
                                          $tableID);
            $date->setID($rid);
            $date->setVersion($vhInfo['version']);
        }
        /*
         * We decided that DBUtil doesn't know (much) about dates as first order data, so write the SCM if
         * there is any. If no SCM, nothing will happen in saveMeta().
         */
        $this->saveMeta($vhInfo, $date, 'date_range', $rid);
    }


    /**
     * Save language
     *
     * Constellation getLanguage() returns a list of Language objects. That's very reasonable in this
     * context.
     *
     * Typical confusion over table.ic_id and table.id. What is necessary here is the table.id values which
     * is not part of the constellation. It is managed here in DBUtil via a return value from an insert
     * function, and passed to saveLanguage() as $fkID.
     * 
     * Old (wrong) $vhInfo['ic_id'] New: $fkID
     *
     * @param integer[] $vhInfo list with keys version, ic_id.
     * 
     * @param \snac\data\Constellation $cObj \snac\data\Constellation object
     *
     * @param string $table Table name of the related table
     *
     * @param integer $fkID Foreign key row id aka table.id from the related table.
     */
    private function saveLanguage($vhInfo, $cObj, $table, $fkID)
    {
        /*
         * Classes are not consistent in whether language is returned as a list or scalar, so we need to
         * change them all to a list. If only one language, then we make a list of one element. If we didn't
         * do this, we would have to copy/paste the insertLanguage() call or otherwise wrap it. Class
         * Constellation already has a wrapper function getLanguage() which calls the "read" function
         * getLanguagesUsed(). That wrapper function was created so this code didn't have to do that.
         */ 
        $langList = array();
        $scalarOrList = $cObj->getLanguage();
        if (! $scalarOrList)
        {
            // Be lazy and return from the middle of the function if there is no language info.
            return;
        }
        elseif (is_object($scalarOrList))
        {
            array_push($langList, $scalarOrList);
        }
        else
        {
            $langList = $scalarOrList;
        }
        
        foreach ($langList as $lang)
        {
            $rid = $lang->getID();
            if ($this->prepOperation($vhInfo, $lang))
            {
                $rid = $this->sql->insertLanguage($vhInfo,
                                                  $lang->getID(),
                                                  $this->thingID($lang->getLanguage()),
                                                  $this->thingID($lang->getScript()),
                                                  $lang->getVocabularySource(),
                                                  $lang->getNote(),
                                                  $table,
                                                  $fkID);
                $lang->setID($rid);
                $lang->setVersion($vhInfo['version']);
            }
            /*
             * Try saving meta data, even though some language objects are not first order data and have no
             * meta data. If there is no meta data, nothing will happen.
             */ 
            $this->saveMeta($vhInfo, $lang, 'language', $rid);
        }
    }

    /**
     * Save otherRecordID
     *
     * Other record id can be found in the SameAs class.
     *
     * Here $otherID is a SameAs object. SameAs->getType() is a Term object. SameAs->getURI() is a string.
     * Term->getTerm() is a string. SameAs->getText() is a string.
     * 
     * @param integer[] $vhInfo list with keys version, ic_id.
     * 
     * @param $cObj \snac\data\Constellation object
     */
    private function saveOtherRecordID($vhInfo, $cObj)
    {
        foreach ($cObj->getOtherRecordIDs() as $otherID)
        {
            $rid = $otherID->getID();
            if ($this->prepOperation($vhInfo, $otherID))
            {
                $rid = $this->sql->insertOtherID($vhInfo,
                                                 $otherID->getID(),
                                                 $otherID->getText(),
                                                 $this->thingID($otherID->getType()),
                                                 $otherID->getURI());
                $otherID->setID($rid);
                $otherID->setVersion($vhInfo['version']);
            }
            $this->saveMeta($vhInfo, $otherID, 'otherid', $rid);
        }
    }

    /**
     * Save Source of constellation
     *
     * @param integer[] $vhInfo list with keys version, ic_id.
     * 
     * @param $cObj \snac\data\Constellation object
     */
    private function saveConstellationSource($vhInfo, $cObj)
    {
        
        throw new \snac\exceptions\SNACDatabaseException("DBUtil saveConstellationSource() no longer used. See saveSource()");
        return;
        foreach ($cObj->getSources() as $fdata)
        {
            $this->saveSource($vhInfo, $fdata);
            /*
             * No saveMeta() here, because saveSource() calls saveMeta() internally. This particular Source
             * may be first order data, but that is not a concern of DBUtil.
             */ 
        }
    }

    /**
     * Save legalStatus
     *
     * @param integer[] $vhInfo list with keys version, ic_id.
     * 
     * @param $cObj \snac\data\Constellation object
     */
    private function saveLegalStatus($vhInfo, $cObj)
    {
        foreach ($cObj->getLegalStatuses() as $fdata)
        {
            $rid = $fdata->getID();
            if ($this->prepOperation($vhInfo, $fdata))
            {
                $rid = $this->sql->insertLegalStatus($vhInfo,
                                                     $fdata->getID(),
                                                     $this->thingID($fdata->getTerm()));
                $fdata->setID($rid);
                $fdata->setVersion($vhInfo['version']);
            }
            $this->saveMeta($vhInfo, $fdata, 'legal_status', $rid);
        }
    }

    /**
     * Save Occupation
     *
     * Insert an occupation. If this is a new occupation, or a new constellation we will get a new
     * occupation id which we save in $occID and use for the related dates.
     *
     * fdata is foreach data. Just a notation that the generic variable is for local use in this loop. 
     *
     * @param integer[] $vhInfo list with keys version, ic_id.
     * 
     * @param $cObj \snac\data\Constellation object
     */
    private function saveOccupation($vhInfo, $cObj)
    {
        foreach ($cObj->getOccupations() as $fdata)
        {
            $occID = $fdata->getID();
            if ($this->prepOperation($vhInfo, $fdata))
            {
                $occID = $this->sql->insertOccupation($vhInfo,
                                                      $fdata->getID(),
                                                      $this->thingID($fdata->getTerm()),
                                                      $fdata->getVocabularySource(),
                                                      $fdata->getNote());
                $fdata->setID($occID);
                $fdata->setVersion($vhInfo['version']);
            }
            $this->saveMeta($vhInfo, $fdata, 'occupation', $occID);
            foreach ($fdata->getDateList() as $date)
            {
                $this->saveDate($vhInfo, $date, 'occupation', $occID);
            }
        }
    }

    /**
     * Save Function
     *
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
     *
     * @param integer[] $vhInfo list with keys version, ic_id.
     * 
     * @param $cObj \snac\data\Constellation object
     */
    private function saveFunction($vhInfo, $cObj)
    {
        foreach ($cObj->getFunctions() as $fdata)
        {
            $funID = $fdata->getID();
            if ($this->prepOperation($vhInfo, $fdata))
            {
                $funID = $this->sql->insertFunction($vhInfo,
                                                    $fdata->getID(), // record id
                                                    $this->thingID($fdata->getType()), // function type, aka localType, Term object
                                                    $fdata->getVocabularySource(),
                                                    $fdata->getNote(),
                                                    $this->thingID($fdata->getTerm())); // function term id aka vocabulary.id, Term object
                $fdata->setID($funID);
                $fdata->setVersion($vhInfo['version']);
            }
            $this->saveMeta($vhInfo, $fdata, 'function', $funID);
            /*
             * getDateList() always returns a list of SNACDate objects. If no dates then list is empty, but it
             * is still a list that we can foreach on without testing for null and count>0. All of which
             * should go without saying.
             */ 
            foreach ($fdata->getDateList() as $date)
            {
                $this->saveDate($vhInfo, $date, 'function', $funID);
            }
        }
    }


    /**
     * Save subject
     *
     * Save subject term
     *
     * getID() is the subject object record id.
     *
     * $this->thingID($term->getTerm()) more robust form of $term->getTerm()->getID() is the vocabulary id
     * of the Term object inside subject.
     *
     * @param integer[] $vhInfo list with keys version, ic_id.
     * 
     * @param $cObj \snac\data\Constellation object
     */
    private function saveSubject($vhInfo, $cObj)
    {
        foreach ($cObj->getSubjects() as $term)
        {
            $rid = $term->getID();
            if ($this->prepOperation($vhInfo, $term))
            {
                $rid = $this->sql->insertSubject($vhInfo, 
                                                 $term->getID(),
                                                 $this->thingID($term->getTerm())); 
                $term->setID($rid);
                $term->setVersion($vhInfo['version']);
            }
            $this->saveMeta($vhInfo, $term, 'subject', $rid);
        }
        
    }
    
    
    /**
     * Save Relation aka  ConstellationRelation
     *
     * "ConstellationRelation" has had many names: cpfRelation related_resource, relation,
     * related_identity. We're attempting to make that more consistent, although the class is
     * ConstellationRelation and the SQL table is related_identity.
     * 
     * ignored: we know our own id value: sourceConstellation, // id fk
     * ignored: we know our own ark: sourceArkID,  // ark why are we repeating this?
     * ignored: always 'simple', altType, cpfRelation@xlink:type vocab source_type, .type
     * 
     * | placeholder | php                 | what                                                       | sql               |
     * |-------------+---------------------+------------------------------------------------------------+-------------------|
     * |           1 | $vhInfo['version']  |                                                            | version           |
     * |           2 | $vhInfo['ic_id']  |                                                            | ic_id           |
     * |           3 | targetConstellation | id fk to version_history                                   | .related_id       |
     * |           4 | targetArkID         | ark                                                        | .related_ark      |
     * |           5 | targetEntityType    | cpfRelation@xlink:role, vocab entity_type, Term object     | .role             |
     * |           6 | type                | cpfRelation@xlink:arcrole vocab relation_type, Term object | .arcrole          |
     * |           7 | cpfRelationType     | AnF only, so far                                           | .relation_type    |
     * |           8 | content             | cpfRelation/relationEntry, usually a name                  | .relation_entry   |
     * |           9 | dates               | cpfRelation/date (or dateRange)                            | .date             |
     * |          10 | note                | cpfRelation/descriptiveNote                                | .descriptive_note |
     * 
     * New convention: when there are dates, make them the second arg. Final arg is a list of all the
     * scalar values that will eventually be passed to execute() in the SQL function. This convention
     * is already in use in a couple of places, but needs to be done for some existing functions.
     * 
     * Ignore ConstellationRelation->$altType. It was always "simple".
     * 
     * altType is cpfRelationType, at least in the CPF.
     *
     * Don't save the source info, because we are the source and have already saved the source data as
     * part of ourself.
     *
     * getRelations() returns \snac\data\ConstellationRelation[]
     * $fdata is \snac\data\ConstellationRelation
     *
     * @param integer[] $vhInfo list with keys version, ic_id.
     * 
     * @param $cObj \snac\data\Constellation object
     */
    private function saveRelation($vhInfo, $cObj)
    {
        foreach ($cObj->getRelations() as $fdata)
        {
            $relID = $fdata->getID();
            if ($this->prepOperation($vhInfo, $fdata))
            {
                $relID = $this->sql->insertRelation($vhInfo,
                                                    $fdata->getTargetConstellation(),
                                                    $fdata->getTargetArkID(),
                                                    $this->thingID($fdata->getTargetEntityType()),
                                                    $this->thingID($fdata->getType()),
                                                    $this->thingID($fdata->getcpfRelationType()), // $cpfRelTypeID,
                                                    $fdata->getContent(),
                                                    $fdata->getNote(),
                                                    $fdata->getID());
                $fdata->setID($relID);
                $fdata->setVersion($vhInfo['version']);
                
                // Be nice and fill in the source Constellation
                $fdata->setSourceConstellation($vhInfo['ic_id']);
                $fdata->setSourceArkID($cObj->getArk());
            }
            $this->saveMeta($vhInfo, $fdata, 'related_identity', $relID);
            foreach ($fdata->getDateList() as $date)
            {
                $this->saveDate($vhInfo, $date, 'related_identity', $relID);
            }
        }
    }

    /**
     * Save resourceRelation
     * 
     * ignored: $this->linkType, @xlink:type always 'simple', vocab source_type, .type
     * 
     * | placeholder | php                 | what, CPF                                        | sql                  |
     * |-------------+---------------------+--------------------------------------------------+----------------------|
     * |           1 | $vhInfo['version']  |                                                  | .version             |
     * |           2 | $vhInfo['ic_id']  |                                                  | .ic_id             |
     * |           3 | documentType        | @xlink:role id fk to vocab document_type         | .role                |
     * |           4 | entryType           | relationEntry@localType, AnF, always 'archival'? | .relation_entry_type |
     * |           5 | link                | @xlink:href                                      | .href                |
     * |           6 | role                | @xlink:arcrole vocab document_role               | .arcrole             |
     * |           7 | content             | relationEntry, usually a name                    | .relation_entry      |
     * |           8 | source              | objectXMLWrap                                    | .object_xml_wrap     |
     * |           9 | note                | descriptiveNote                                  | .descriptive_note    |
     * 
     * Final arg is a list of all the scalar values that will eventually be passed to execute() in the SQL
     * function. This convention is already in use in a couple of places, but needs to be done for some
     * existing functions.  
     *
     * @param integer[] $vhInfo list with keys version, ic_id.
     * 
     * @param $cObj \snac\data\Constellation object
     */
    private function saveResourceRelation($vhInfo, $cObj)
    {
        foreach ($cObj->getResourceRelations() as $fdata)
        {
            $rid = $fdata->getID();
            if ($this->prepOperation($vhInfo, $fdata))
            {
                $rid = $this->sql->insertResourceRelation($vhInfo,
                                                          $this->thingID($fdata->getDocumentType()), // xlink:role
                                                          $this->thingID($fdata->getEntryType()), // relationEntry@localType
                                                          $fdata->getLink(), // xlink:href
                                                          $this->thingID($fdata->getRole()), // xlink:arcrole
                                                          $fdata->getContent(), // relationEntry
                                                          $fdata->getSource(), // objectXMLWrap
                                                          $fdata->getNote(), // descriptiveNote
                                                          $fdata->getID());
                $fdata->setID($rid);
                $fdata->setVersion($vhInfo['version']);
            }
            $this->saveMeta($vhInfo, $fdata, 'related_resource', $rid);
        }
    }

    /**
     * Select gender from database
     *
     * Create object, add the object to Constellation. Support multiples per constellation.
     *
     * Note: $cObj passed by reference and changed in place.
     *
     * @param integer[] $vhInfo list with keys version, ic_id.
     * 
     * @param $cObj \snac\data\Constellation object, passed by reference, and changed in place
     */
    private function populateGender($vhInfo, $cObj)
    {
        $rows = $this->sql->selectGender($vhInfo);
        foreach ($rows as $item)
        {
            $newObj = new \snac\data\Gender();
            $newObj->setTerm($this->populateTerm($item['term_id']));
            $newObj->setDBInfo($item['version'], $item['id']);
            $this->populateMeta($vhInfo, $newObj, 'gender');
            $cObj->addGender($newObj);
        }
    }

    /**
     * Select GeneralContext from database
     *
     * Create object, add the object to Constellation. Support multiples per constellation.  Get BiogHist from
     * database, create relevant object and add to the constellation object passed as an argument.
     * 
     * Note: $cObj passed by reference and changed in place.
     *
     * @param integer[] $vhInfo list with keys version, ic_id.
     * 
     * @param $cObj \snac\data\Constellation object, passed by reference, and changed in place
     */
    private function populateBiogHist($vhInfo, $cObj)
    {
        $tableName = 'biog_hist';
        $rows = $this->sql->selectBiogHist($vhInfo);
        foreach ($rows as $item)
        {
            $newObj = new \snac\data\BiogHist();
            $newObj->setText($item['text']);
            $newObj->setDBInfo($item['version'], $item['id']);
            $this->populateMeta($vhInfo, $newObj, $tableName);
            $this->populateLanguage($vhInfo, $newObj, $item['id'], $tableName);
            $cObj->addBiogHist($newObj);
        }
    }


    /**
     * Get Occupation from the db
     *
     * Populate occupation object(s), add to Constellation object passed by
     * reference.
     *
     * | php                 | sql               |
     * |---------------------+-------------------|
     * | setDBInfo           | id                |
     * | setDBInfo           | version           |
     * | setDBInfo           | ic_id             |
     * | setTerm             | occupation_id     |
     * | setNote             | note              |
     * | setVocabularySource | vocabulary_source |
     *
     * @param integer[] $vhInfo associative list with keys 'version' and 'ic_id'.
     * @param $cObj \snac\data\Constellation object, passed by reference, and changed in place
     * 
     */
    private function populateOccupation($vhInfo, $cObj)
    {
        $tableName = 'occupation';
        $occRows = $this->sql->selectOccupation($vhInfo);
        foreach ($occRows as $oneOcc)
        {
            $occObj = new \snac\data\Occupation();
            $occObj->setTerm($this->populateTerm($oneOcc['occupation_id']));
            $occObj->setVocabularySource($oneOcc['vocabulary_source']);
            $occObj->setNote($oneOcc['note']);
            $occObj->setDBInfo($oneOcc['version'], $oneOcc['id']);
            $this->populateMeta($vhInfo, $occObj, $tableName);
            $this->populateDate($vhInfo, $occObj, $tableName);
            $cObj->addOccupation($occObj);
        }
    }

    /**
     * Populate relation object(s)
     *
     * Select from db then add to existing Constellation object.
     *
     * test with: scripts/get_constellation_demo.php 2 10
     *
     *
     * | php                                    | sql              |
     * |----------------------------------------+------------------|
     * | setDBInfo                              | id               |
     * | setDBInfo                              | version          |
     * | setDBInfo                              | ic_id          |
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
     * Note:
     * setsourceConstellation() is parent::getID()
     * setSourceArkID() is parent::getARK()
     *
     * Unclear why those methods (and their properties) exist, but fill them in regardless.
     *
     * php: $altType setAltType() getAltType()
     *
     * The only value this ever has is "simple". Daniel says not to save it, and implicitly hard code when
     * serializing export.
     *
     * @param integer[] $vhInfo associative list with keys 'version' and 'ic_id'.
     * @param $cObj \snac\data\Constellation object, passed by reference, and changed in place
     */
    private function populateRelation($vhInfo, $cObj)
    {
        $tableName = 'related_identity';
        $relRows = $this->sql->selectRelation($vhInfo);
        foreach ($relRows as $oneRel)
        {
            $relatedObj = new \snac\data\ConstellationRelation();
            $relatedObj->setSourceConstellation($cObj->getID());
            $relatedObj->setSourceArkID($cObj->getARK());
            $relatedObj->setTargetConstellation($oneRel['related_id']);
            $relatedObj->setTargetArkID($oneRel['related_ark']);
            $relatedObj->setTargetEntityType($this->populateTerm($oneRel['role']));
            $relatedObj->setType($this->populateTerm($oneRel['arcrole']));
            /* Not using setAltType(). It is never used. See ConstellationRelation.php */ 
            $relatedObj->setCPFRelationType($this->populateTerm($oneRel['relation_type']));
            $relatedObj->setContent($oneRel['relation_entry']);
            $relatedObj->setNote($oneRel['descriptive_note']);
            $relatedObj->setDBInfo($oneRel['version'], $oneRel['id']);
            $this->populateMeta($vhInfo, $relatedObj, $tableName);
            $this->populateDate($vhInfo, $relatedObj, $tableName);
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
     * | setDBInfo            | ic_id                  |                                           |
     * | setDocumentType      | role                     | resourceRelation/@role                    |
     * | setRelationEntryType | relation_entry_type      | resourceRelation/relationEntry/@localType |
     * | setLinkType          | always "simple", ignored | resourceRelation@xlink:type               |
     * | setLink              | href                     | resourceRelation/@href                    |
     * | setRole              | arcrole                  | resourceRelation/@arcrole                 |
     * | setContent           | relation_entry           | resourceRelation/resourceEntry            |
     * | setSource            | object_xml_wrap          | resourceRelation/objectXMLWrap            |
     * | setNote              | descriptive_note         | resourceRelation/descriptiveNote          |
     *
     * @param integer[] $vhInfo associative list with keys 'version' and 'ic_id'.
     * @param $cObj \snac\data\Constellation object, passed by reference, and changed in place
     *
     */
    private function populateResourceRelation($vhInfo, $cObj)
    {
        $rrRows = $this->sql->selectResourceRelation($vhInfo);
        foreach ($rrRows as $oneRes)
        {
            $rrObj = new \snac\data\ResourceRelation();
            $rrObj->setDocumentType($this->populateTerm($oneRes['role']));
            $rrObj->setRelationEntryType($oneRes['relation_entry_type']);
            /* setLinkType() Not used. Always "simple" See ResourceRelation.php */ 
            $rrObj->setLink($oneRes['href']);
            $rrObj->setRole($this->populateTerm($oneRes['arcrole']));
            $rrObj->setContent($oneRes['relation_entry']);
            $rrObj->setSource($oneRes['object_xml_wrap']);
            $rrObj->setNote($oneRes['descriptive_note']);
            $rrObj->setDBInfo($oneRes['version'], $oneRes['id']);
            $this->populateMeta($vhInfo, $rrObj, 'related_resource' );
            $cObj->addResourceRelation($rrObj);
        }
    }


    /**
     * Populate the SNACFunction object(s)
     *
     * Select, create object, then add to an existing Constellation object.
     *
     * @param integer[] $vhInfo associative list with keys 'version' and 'ic_id'.
     * @param $cObj \snac\data\Constellation object, passed by reference, and changed in place
     *
     */
    private function populateFunction($vhInfo, $cObj)
    {
        $tableName = 'function';
        $funcRows = $this->sql->selectFunction($vhInfo);
        foreach ($funcRows as $oneFunc)
        {
            $fObj = new \snac\data\SNACFunction();
            $fObj->setType($oneFunc['function_type']);
            $fObj->setTerm($this->populateTerm($oneFunc['function_id']));
            $fObj->setVocabularySource($oneFunc['vocabulary_source']);
            $fObj->setNote($oneFunc['note']);
            $fObj->setDBInfo($oneFunc['version'], $oneFunc['id']);
            $this->populateMeta($vhInfo, $fObj, $tableName);

            /*
             * Must call $fOjb->setDBInfo() before calling populateDate()
             */
            $this->populateDate($vhInfo, $fObj, $tableName);
            $cObj->addFunction($fObj);
        }
    }


    /**
     * Read the status of a constellation.
     *
     * Read the status of a constellation, with optional version. If version is not supplied, then the most
     * recent version is used.
     *
     * @param integer $mainID The constellation ID
     *
     * @param integer $version optional The version number or if empty, return the status for the most recent
     * version.
     *
     * @return string status. Return the version_history.status value.
     */
    public function readConstellationStatus($mainID, $version=null)
    {
        if (! $version)
        {
            $version = $this->sql->selectCurrentVersion($mainID);
        }
        if ($version)
        {
            $status = $this->sql->selectStatus($mainID, $version);
            if ($status)
            {
                return $status;
            }
        }
        return false;
    }

    /**
     * Modify constellation status
     *
     * Write a new version history record, updating the constellation status.
     *
     * always increment version
     * can write status 'deleted' which deletes the constellation
     * can update is_deleted and status='deleted' records (if update where is_deleted, probably best to set is_deleted to 'f')
     * returns false if $id not found
     * return false for any failure
     * return new version on success
     * write optional note if supplied
     *
     * @param \snac\data\User $user The user to perform the write status
     * 
     * @param integer $mainID A constellation ID
     *
     * @param string $status The new status value. 'deleted' is allowed, and will cause the constellation to
     * be deleted. The status must be one of the known values.
     *
     * @param string $note optional text note to write to the version_history table.
     *
     * @return integer|boolean Returns the new version number on success or false on failure.
     *
     */ 
    public function writeConstellationStatus($user, $mainID, $status, $note="")
    {
        if ($user == null || $user->getUserID() == null) {
            return false;
        }
        
        if (! $mainID)
        {
            return false;
        }
        /*
         * Apr 4 2016 Rather than delete via setOperation() set to delete then callign writeConstellation(),
         * allow setting the constellation status to deleted. A quick scan of the code doesn't reveal any
         * problems with this approach. So, the following if statement is commented out.
         */ 
        /* 
         * if ($status == 'deleted')
         * {
         *     return false;
         * }
         */
        if ($this->statusOK($status))
        {
            if (! $note)
            {
                $note = "";
            }
            $oldVersion = $this->sql->selectCurrentVersion($mainID);
            if (! $oldVersion)
            {
                return false;
            }

            // Right now, we're passing null as the role ID.  We may change this to a role from the user object
            $vhInfo = $this->sql->insertVersionHistory($mainID, $user->getUserID(), null, $status, $note);
            return $vhInfo['version'];
        }
        else
        {
            $this->enableLogging();
            $this->logDebug("DBUtil.php Error: bad status $status\n");
        }
        return false;
    }


    /**
     * Write a constellation to the database. 
     *
     * Both insert and update are "write". Insert is "do not yet have a version number." Update is "have
     * version number."
     *
     * We get a new version number for every write.
     *
     * The returned constellation is what was passed in, but with any null id and version filled. So, if a
     * constellation with only a new inserted name is written to the db, that is what is returned: an empty
     * constellation with nothing but a name. This was decided on Mar 3 2016 after much discussion.
     *
     * The web UI will send a partial constellation with appropriate operation set. Only the modified parts of
     * the constellation are send from the web UI to the server.
     *
     * There is only one instance where we mint a new constellation id: Constellation insert.
     *
     * When doing component insert for an existing constellation, all new components use the constellation
     * ID, thus no new ID is minted.
     *
     * We assume that something will happen so we always mint a new version number, as well as writing
     * $status and $note to the version_history.
     *
     * As of php 5 objects are passed by reference. It is therefore redundant for a function prototype to say
     * foo($cObj). It is necessary to clone() the object if you want to mess with it and not have it changed
     * in place.
     *
     * Mar 9 2016 There is no $status arg. When creating the new version, keep the existing status. If the
     * status needs to change, do that with writeConstellationStatus().
     * 
     * Version, status, and note are used only for this write. If at some future time you create private
     * vars for version, ic_id, status, and note here in DBUtil, then you must clear the ic_id, version,
     * status, and note before returning. Always set all version info explicitly.
     *
     * What won't happen here is two records edited simultaneously being saved. We assume that is
     * impossible. And if it were possible, both updates would (logically?) have the same status, and share
     * the same note.
     *
     * Even on bulk ingest version numbers are not reused for constellations ingested in the same
     * "transaction". A new version_history record is created for each write. It is (sort of) a
     * coincidence that status and note are the same in one or more version_history records.
     *
     * A single version_history record does (and must) apply to all new/modified components of a single
     * constellation.
     *
     * If $mainID is null, insertVersionHistory() is smart enough to mint a new one.
     *
     * A reminder: the structure of $vhInfo is array('version' => 123, 'ic_id' => 456);
     * 
     * @param \snac\data\User $user The User to perform the write
     *
     * @param \snac\data\Constellation $argObj A constellation object
     *
     * @param string $note Human written note from the person who edit this data. This is a version commit
     * message.
     *
     * @param string $statusArg optional Status value, most likely "ingest cpf" that means we are creating a
     * new record and need to capture maintenance info. The default is 'locked editing' which makes sense
     * since we can't write unless we are editing. Or maybe 'currently editing'.
     *
     * @return \snac\data\Constellation|boolean the original constellation object modified to include id and version, or
     * false if the user could not perform the action
     *
     */
    public function writeConstellation($user, $argObj, $note, $statusArg='locked editing')
    {
        /*
         * We can initialize $status to either $defaultStatus or $statusArg. I'm not sure it makes much
         * difference. We do use $defaultStatus later to set the status after creating a version_history
         * record for 'ingest cpf'.
         */  
        $defaultStatus = 'locked editing'; // Don't change unless you understand how it is used below.
        $status = $defaultStatus;
        if ($user == null || $user->getUserID() == null) {
            $this->enableLogging();
            $this->logDebug("dbutil user or userid is null");
            return false;
        }
        
        $cObj = clone($argObj);
        $mainID = null;
        $op = $cObj->getOperation();
        if ($op == \snac\data\AbstractData::$OPERATION_UPDATE)
        {
            /*
             * Update uses the existing constellation ID.
             */ 
            $mainID = $cObj->getID();
            $status = $this->readConstellationStatus($mainID);
        }
        elseif ($op == \snac\data\AbstractData::$OPERATION_DELETE)
        {
            /*
             * Delete uses the existing constellation ID
             */ 
            $status = 'deleted';
            $mainID = $cObj->getID();
        }
        elseif ($op == \snac\data\AbstractData::$OPERATION_INSERT)
        {
            /*
             * Insert requires a new ID. Passing a null mainID (aka ic_id) to insertVersionHistory() will
             * cause a new mainID to be minted.
             *
             */ 
            $mainID = null;
        }
        elseif ($op == null)
        {
            $mainID = $cObj->getID();
            if ($mainID)
            {
                /*
                 * This must be an update. That is: an existing constellation with no change at the top, but some
                 * operation(s) inside. Since the constellation exists, we assume the ID is good, and there's no
                 * need to mint a new ID.
                 *
                 * Question: why isn't this simply part of the update branch above?
                 *
                 * A new constellation must have operation insert, and is handled above.
                 */
                $status = $this->readConstellationStatus($mainID);
            }
            else
            {
                /*
                 * I guess this is an insert. We don't have a mainID, so this must be a new constellation.
                 *
                 */ 
            }
        }
        else
        {
            $json = $cObj->toJSON();
            $this->enableLogging();
            $opErrorMsg = sprintf("Error: Bad operation: $op\n%s", $json);
            $this->logDebug($opErrorMsg);
            throw new \snac\exceptions\SNACDatabaseException($opErrorMsg);
        }

        /*
         * Validation. If we have a mainID then add the IDValidator. Always add the HasOperationValidator.
         * Make sure 
         */  
        $ve = new ValidationEngine();        
        $hasOperationValidator = new HasOperationValidator();
        $ve->addValidator($hasOperationValidator);
        if ($mainID)
        {
            $idValidator = new IDValidator();
            $ve->addValidator($idValidator);
        }        
        if (!$ve->validateConstellation($cObj))
        {
            // problem
            $this->enableLogging();
            $this->logDebug(sprintf("Error: Validation failed: %s", $ve->getErrors()));
        }
        if (! $status)
        {
            $this->enableLogging();
            $msg = sprintf("Error: writeConstellation() cannot determine version status.\n");
            $msg .= sprintf("operation: %s mainID: %s\n",
                            $op, $mainID);
            $this->logDebug($msg);
        }
        
        /*
         * On insert, the constellation may have a status property 'ingest cpf' which signals we are creating
         * a new record. We create a special ingest version where the note is the maintenance info as json. Do
         * not use var $vhInfo which will be initialized below. Do initialize or update $mainID.
         *
         * This only runs when $statusArg is 'ingest cpf' and the operation is insert.
         *
         * After writing a version_history record, change status to 'locked editing'. 
         */ 
        if ($op == \snac\data\AbstractData::$OPERATION_INSERT && $statusArg == 'ingest cpf')
        {
            $maintNote = $this->maintenanceNote($cObj);
            $vhInfoIngest = $this->sql->insertVersionHistory($mainID, $user->getUserID(), null, $statusArg, $maintNote);
            $mainID = $vhInfoIngest['ic_id'];
            $status = $defaultStatus;
            $cObj->setStatus($status);
        }

        // Right now, we're passing null as the role ID.  We may change this to a role from the user object
        $vhInfo = $this->sql->insertVersionHistory($mainID, $user->getUserID(), null, $status, $note);

        /*
         * $cObj is passed by reference, and changed in place.
         *
         * The only changes to $cObj are adding id and version as necessary, and setting operation to null.
         */
        $this->coreWrite($vhInfo, $cObj);
        return $cObj;
    }

    /**
     * Middle layer write constellation to db (new)
     *
     * We already have a version and ic_id, but must write a version_history record. We got the new version
     * from selectNewVersion() and the new ic_id from selectNewID().
     *
     * @param int[] $vhInfo A list with keys 'ic_id' and 'version'
     *
     * @param \snac\data\Constellation $cObj A constellation object. Remember that php objects are passed by
     * reference.
     *
     * No return value. $cObj is passed by reference, and is changed in place by the save functions, as
     * necessary to update/populate id and version.
     * 
     */ 
    private function coreWrite($vhInfo, $cObj)
    {
        /*
         * Always update the constellation ID and version, even when not inserting into table nrd. The
         * constellation ID and version are more properly connected to table version_history, but due to
         * historical baggage we tend to conflate nrd and constellation.
         *
         * May 6 2016: Call the setters early, and then call the getters instead of simply using $vhInfo. It
         * doesn't make a functional difference, but it clarifies our intention.
         */ 
        $cObj->setID($vhInfo['ic_id']);
        $cObj->setVersion($vhInfo['version']);

        $this->saveMeta($vhInfo, $cObj, 'version_history', $vhInfo['ic_id']);
        $this->saveBiogHist($vhInfo, $cObj);
        $this->saveConstellationDate($vhInfo, $cObj);
        $this->saveSource($vhInfo, $cObj); // Source objects are only per constellation. Other uses of source are by foreign key.
        $this->saveConventionDeclaration($vhInfo, $cObj);
        $this->saveFunction($vhInfo, $cObj);
        $this->saveGender($vhInfo, $cObj);
        $this->saveGeneralContext($vhInfo, $cObj);
        $this->saveLegalStatus($vhInfo, $cObj);
        $this->saveLanguage($vhInfo, $cObj, 'version_history', $vhInfo['ic_id']);
        $this->saveMandate($vhInfo, $cObj);
        $this->saveName($vhInfo, $cObj);
        $this->saveNationality($vhInfo, $cObj);
        $this->saveNrd($vhInfo, $cObj);
        $this->saveOccupation($vhInfo, $cObj);
        $this->saveOtherRecordID($vhInfo, $cObj);
        $this->savePlace($vhInfo, $cObj, 'version_history', $vhInfo['ic_id']);
        $this->saveStructureOrGenealogy($vhInfo, $cObj);
        $this->saveSubject($vhInfo, $cObj);
        $this->saveRelation($vhInfo, $cObj); // aka cpfRelation, constellationRelation, related_identity
        $this->saveResourceRelation($vhInfo, $cObj);
    }

    /**
     * Read a constellation from the database.
     *
     * Read constellation ID $mainID from the database. 
     *
     * This the public exposed interface function to read constellation data from the db, and handles a bit of
     * bookkeeping. It might be possible to move the logic here into selectConstellation(), but I can't see a
     * compelling reason to do that. The word "select" is reserved for SQL functions, so selectConstellation()
     * really should be renamed, and we really do not want a public API function with "select" in the name.
     *
     * If we need to do any read related bookkeeping, do it here, and not in the lower level code.
     *
     * @param integer $mainID A constellation ID number
     *
     * @param integer $version optional An optional version number. When not supplied this function will look
     * up the most recent version, regardless of status.
     *
     * @param boolean $summary Any true value means return a summary constellation. Defaults to false which is
     * return the full constellation.
     *
     * @return \snac\data\Constellation or boolean If successful, return a constellation, else if not successful return false.
     *
     */
    public function readConstellation($mainID, $version=null, $summary=false)
    {
        if (! $mainID)
        {
            return false;
        }
        if (! $version)
        {
            $version = $this->sql->selectCurrentVersion($mainID);
        }
        if (! $version)
        {
            return false;
        }
        $vhInfo = array('version' => $version,
                        'ic_id' => $mainID);
        $cObj = $this->selectConstellation($vhInfo, $summary);
        if ($cObj)
        {
            /*
             * If you wanted to fill in Constellation->status aka setStatus() this would be the place to
             * readConstellationStatus() and setStatus(). Status is intentionally not populated because the
             * server may change it on the fly.
             */
            return $cObj;
        }
        return false;
    }

    /**
     * Generate a Maintenance Note
     *
     * Given a Constellation object, this method generates a note (for storing in a text field) that 
     * contains the maintenance information from the constellation.
     * 
     * @param \snac\data\Constellation $cObj A constellation object
     * @return string The JSON-encoded note containing maintenance data
     */
    private function maintenanceNote($cObj)
    {
        $saveArray = array("maintenanceStatus" => $cObj->getMaintenanceStatus() == null ? null : $cObj->getMaintenanceStatus()->toArray(),
                           "maintenanceAgency" => $cObj->getMaintenanceAgency(),
                           "maintenanceEvents" => array());
        foreach ($cObj->getMaintenanceEvents() as $i => $v)
            $saveArray["maintenanceEvents"][$i] = $v->toArray();
        
        $json = json_encode($saveArray, JSON_PRETTY_PRINT);
        return $json;
    }

    /**
     * Save place object
     *
     * Save a list of places to place_link, including meta data.
     *
     * The only way to know the related table is for it to be passed in via $relatedTable.
     *
     * @param integer[] $vhInfo Array with keys 'version', 'ic_id' for this constellation.
     *
     * @param \snac\data\AbstractData Object $id An object that might have a place, and that extends
     * AbstractData.
     *
     * @param string $relatedTable Name of the related table for this place.
     * @param integer $fkID Foreign key row id aka table.id from the related table.
     *
     */
    private function savePlace($vhInfo, $cObj, $relatedTable, $fkID)
    {
        if ($placeList = $cObj->getPlaces())
        {
            foreach($placeList as $gObj)
            {
                $pid = $gObj->getID();
                if ($this->prepOperation($vhInfo, $gObj))
                {
                    $pid = $this->sql->insertPlace($vhInfo,
                                                   $gObj->getID(),
                                                   $this->db->boolToPg($gObj->getConfirmed()),
                                                   $gObj->getOriginal(),
                                                   $this->thingID($gObj->getGeoTerm()),
                                                   $this->thingID($gObj->getType()),
                                                   $this->thingID($gObj->getRole()),
                                                   $gObj->getNote(),
                                                   $gObj->getScore(),
                                                   $relatedTable,
                                                   $fkID);
                    $gObj->setID($pid);
                    $gObj->setVersion($vhInfo['version']);
                }
                $this->saveMeta($vhInfo, $gObj, 'place_link', $pid);
                if ($dObj = $gObj->getDateList())
                {
                    /*
                     * The docs for getDateList() imply that in some circumstances it does not return a list,
                     * not even an empty list. We have to check.
                     */ 
                    if (is_array($dObj))
                    {
                        foreach ($dObj as $date)
                        {
                            $this->saveDate($vhInfo, $date, 'place_link', $pid);
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Save SNACControlMetadata to database
     *
     * Might have been called saveSCM().
     *
     * Save the metadata to table scm in the database. Saved record is related to table $fkTable, and record id $fkID.
     *
     * Citation is a Source object. Source objects are like dates: each one is specific to the
     * related record. Source is not a controlled vocabulary. Therefore, like date, Source has
     * an fk back to the original table.
     *
     * Note: this depends on an existing Source, DescriptiveRule, and Language, each in its
     * appropriate table in the database. Or if not existing they can be null.
     *
     * @param integer[] $vhInfo Array with keys 'version', 'ic_id' for this constellation.
     *
     * @param \snac\data\SNACControlMetadata[] $metaObjList List of SNAC control meta data
     *
     * @param string $fkTable Name of the table to which this meta data relates
     *
     * @param integer $fkID Record id aka table.id of the record to which this meta data relates.
     *
     */ 
    private function saveMeta($vhInfo, $gObj, $fkTable, $fkID)
    {
        if (! $metaObjList = $gObj->getSNACControlMetadata())
        {
            return;
        }
        foreach ($metaObjList as $metaObj)
        {
            $metaID = $metaObj->getID();
            if ($this->prepOperation($vhInfo, $metaObj))
            {
                $citationID = null;
                if ($metaObj->getCitation())
                {
                    $citationID = $metaObj->getCitation()->getID();
                }
                $metaID = $this->sql->insertMeta($vhInfo,
                                                 $metaObj->getID(),
                                                 $citationID,
                                                 $metaObj->getSubCitation(),
                                                 $metaObj->getSourceData(),
                                                 $this->thingID($metaObj->getDescriptiveRule()),
                                                 $metaObj->getNote(),
                                                 $fkTable,
                                                 $fkID);
                $metaObj->setID($metaID);
                $metaObj->setVersion($vhInfo['version']);
            }
            $this->saveLanguage($vhInfo, $metaObj, 'scm', $metaID);
            /*
             * Citation has become a Source and has an ID to a Source table record. No need to save
             * separately.
             */ 
        }
    }

    /**
     * Save a Source link. Not used.
     *
     * Source objects exist in their own table, so when any part of a constellation needs a source, it links
     * to it. This writes those links. These links have version control. There may be SCM for these links.
     *
     * @param integer[] $vhInfo Array with keys 'version', 'ic_id' for this constellation.
     *
     * @param \snac\data\Source $gObj The Source object
     *
     * @param string $fkTable The name of the containing object's table. This will be 'version_history' for
     * constellation source. Table 'nrd' is a data table, not the root of the constellation.
     *
     * @param integer $fkID The record id of the containing table.
     *
     */
    /* 
     * private function saveSourceLink($vhInfo, $gObj, $fkTable, $fkID)
     * {
     *     die("DBUtil saveSourceLink Not used. Source links are single-sided relations foreign key relations to table source.\n");
     *     $genericRecordID = $gObj->getID();
     *     if ($this->prepOperation($vhInfo, $gObj))
     *     {
     *         $genericRecordID = $this->sql->insertSourceLink($vhInfo,
     *                                                         $gObj->getID(),
     *                                                         $fkTable,
     *                                                         $fkID);
     *         $gObj->setID($genericRecordID);
     *         $gObj->setVersion($vhInfo['version']);
     *     }
     *     $this->saveMeta($vhInfo, $gObj, 'source_link', $genericRecordID);
     * }
     */

    /**
     * Write constellation sources to the database
     *
     * Foreach over a list of all Source objects in a constellation and the sources to the db. Source objects
     * are written to table source, and their related language (if one exists) is written to table Language
     * with a reverse foreign key as usual. Source and language are related on source.id=language.fk_id.
     *
     * Constellation sources can each have an SCM. 
     *
     * Any part of a constellation that needs source will link to the source by source->getID().
     * 
     * 'type' is always simple, and Daniel says we can ignore it. It was used in EAC-CPF just to quiet
     * validation.
     *
     * @param integer[] $vhInfo list with keys version, ic_id.
     *
     * @param \snac\data\Constellation $cObj The constellation object
     */
    private function saveSource($vhInfo, $cObj)
    {
        // G for generic in $gObj
        foreach ($cObj->getSources() as $gObj)
        {
            $genericRecordID = $gObj->getID();
            if ($this->prepOperation($vhInfo, $gObj))
            {
                $genericRecordID = $this->sql->insertSource($vhInfo,
                                                            $gObj->getID(),
                                                            $gObj->getDisplayName(),
                                                            $gObj->getText(),
                                                            $gObj->getNote(),
                                                            $gObj->getURI());
                $gObj->setID($genericRecordID);
                $gObj->setVersion($vhInfo['version']);
            }
            $this->saveMeta($vhInfo, $gObj, 'source', $genericRecordID);
            $this->saveLanguage($vhInfo, $gObj, 'source', $genericRecordID);
        }
    }

    /**
     * Search Vocabulary
     * 
     * Searches the vocabulary and returns an array of id, value pairs.
     * 
     * @param string $type vocabulary type
     *
     * @param string $query search string
     *
     * @param integer $entityTypeID The vocabulary.id of one of the 3 entity type records. Used for selecting
     * name component vocabulary sensitive to context of entity type.
     * 
     * @return string[][] list of results
     */
    public function searchVocabulary($type, $query, $entityTypeID=null) {

        if ($type == 'geo_place') {
            $results = $this->sql->searchPlaceVocabulary($query);
            $retVal = array();
            foreach ($results as $res) {
                $item = array();
                $item["id"] = $res["id"];
                $item["value"] = $res["name"] . " (" . $res["admin_code"] . ", " . $res["country_code"] . ")";
                array_push($retVal, $item);
            }
            return $retVal;
        }
        return $this->sql->searchVocabulary($type, $query, $entityTypeID);
    }
    

    /**
     * Get a Place Vocabulary Term by URI
     *
     * Looks up the given URI and returns the associated geoplace Term.
     * 
     * @param string $uri search uri
     * @return \snac\data\GeoTerm the corresponding geoterm, or null
     */
    public function getPlaceByURI($uri) {
    
        $data = $this->sql->getPlaceByURI($uri);
        
        if ($data == null || empty($data)) return null;
        
        $place = new \snac\data\GeoTerm();
        $place->setAdministrationCode($data["admin_code"]);
        $place->setCountryCode($data["country_code"]);
        $place->setID($data["id"]);
        $place->setLatitude($data["latitude"]);
        $place->setLongitude($data["longitude"]);
        $place->setName($data["name"]);
        $place->setURI($data["uri"]);
        
        return $place;
    }
    
    /**
     * Save the biogHist
     *
     * Constellation biogHist is currently a list, although the expectation is that it only has a single
     * element.
     *
     * biogHist language, and biogHist date(s?). This is a private function that exists to
     * keep the code organized. It is probably only called from saveConstellation().
     *
     * @param array[] $vhInfo Associative list with keys version, ic_id
     *
     * @param \snac\data\BiogHist A single BiogHist object.
     */ 
    private function saveBiogHist($vhInfo, $cObj)
    {
        $tableName = 'biog_hist';
        foreach ($cObj->getBiogHistList() as $biogHist)
        {
            $bid = $biogHist->getID();
            if ($this->prepOperation($vhInfo, $biogHist))
            {
                $bid = $this->sql->insertBiogHist($vhInfo,
                                                  $biogHist->getID(),
                                                  $biogHist->getText());
                $biogHist->setID($bid);
                $biogHist->setVersion($vhInfo['version']);
            }
            $this->saveMeta($vhInfo, $biogHist, $tableName, $bid);
            if ($lang = $biogHist->getLanguage())
            {
                $this->saveLanguage($vhInfo, $biogHist, $tableName, $bid);
            }
        }
    }

    /**
     * Save a name
     *
     * Once we have AbstractData->$operation implemented, make this method private, and fix DBUtilTest to use
     * setOperation() to update only the name of a constellation. In the meantime, saveName() needs to be public.
     *
     * In the declarative sense "name" is all name data, here a list of name objects, as well as related
     * contributor data, language data, date data.
     *
     * This exists primarily to make the code here in DBUtil more legible.
     *
     * Note about \snac\data\Language objects. This is the Language of the entry. Language object's
     * getLanguage() returns a Term object. Language getScript() returns a Term object for the script. The
     * database only uses the id of each Term.
     *
     * Constellation name entry data is already an array of name entry data. 
     * getUseDates() returns SNACDate[] (An array of SNACDate objects.)
     *
     * When saving a name, the database assigns it a new id, and returns that id. We must be sure to use
     * $nameID for related dates, etc.
     *
     * @param integer[] $vhInfo associative list with keys 'version', 'ic_id'.
     *
     * @param \snac\data\NameEntry Name entry object
     *
     */
    public function saveName($vhInfo, $cObj)
    {
        foreach ($cObj->getNameEntries() as $ndata)
        {
            $nameID = $ndata->getID();
            if ($this->prepOperation($vhInfo, $ndata))
            {
                $nameID = $this->sql->insertName($vhInfo, 
                                                 $ndata->getOriginal(),
                                                 $ndata->getPreferenceScore(),
                                                 $ndata->getID());
                $ndata->setID($nameID);
                $ndata->setVersion($vhInfo['version']);
            }
            $this->saveMeta($vhInfo, $ndata, 'name', $nameID);
            /*
             * Inline the code that would be saveComponent() because it is only used here. 
             */ 
            if ($componentList = $ndata->getComponents())
            {
                foreach($componentList as $cp)
                {
                    if ($this->prepOperation($vhInfo, $cp))
                    {
                        $rid = $this->sql->insertComponent($vhInfo,
                                                           $cp->getID(),
                                                           $nameID,
                                                           $cp->getText(),
                                                           $this->thingID($cp->getType()),
                                                           $cp->getOrder());
                        $cp->setID($rid);
                        $cp->setVersion($vhInfo['version']);
                    }
                }
            }
            if ($contribList = $ndata->getContributors())
            {
                /*
                 * $ndata->getID() is null for inserted name. $nameID is walways non-null.
                 * 
                 * $nameID and $ndata->getID() will be the same for a name that is being updated. getID() will
                 * be null for inserted names since there's no id until after insert. $nameID will always be
                 * non-null.
                 *
                 * Both ids are the record id, not the constellation id.
                 *
                 */ 
                foreach($contribList as $cb)
                {
                    // Why initialize $rid? if(true) $rid will be set and used.
                    $rid = $cb->getID();
                    if ($this->prepOperation($vhInfo, $cb))
                    {
                        $rid = $this->sql->insertContributor($vhInfo,
                                                             $cb->getID(),
                                                             $nameID,
                                                             $cb->getName(),
                                                             $this->thingID($cb->getType()),
                                                             $this->thingID($cb->getRule()));
                        $cb->setID($rid);
                        $cb->setVersion($vhInfo['version']);
                    }
                }
            }
            $this->saveLanguage($vhInfo, $ndata, 'name', $nameID);
            $dateList = $ndata->getDateList();
            foreach ($ndata->getDateList() as $date)
            {
                $this->saveDate($vhInfo, $date, 'name', $nameID);
            }
        
        }
    }


    /**
     * Delete a single record of a single table.
     *
     * Public for testing until we implement "operation". When we implement operations via
     * AbstractData::setOperation() this will become private.
     *
     * Pass a single record object $cObj. The other code here just gets all the records (keeping their id
     * values) and throws them into an Constellation object. Delete is different and delete has single-record
     * granularity.
     *
     * By calling deleteOK() as we use the associative list $canDelete to associate each class with a table.
     *
     * Name is special because a constellation must have at least one name. Everything else can be zero per constellation.
     *
     * @param integer[] $vhInfo Associative list with keys 'ic_id', 'version'. These are the new version of the
     * delete, and the constellation ic_id.
     *
     * @param \snac\data\Constellation $cObj An object to be deleted. This is any non-Constellation
     * object. Constellation delete is special and handled elsewhere (or at least that is the plan.)
     *
     * @return string Non-null is success, null is failure. On succeess returns the deleted row id, which
     * should be the same as $id.
     *
     */
    private function setDeleted($vhInfo, $cObj)
    {
        /*
         * If this object is associated with a table that allows delete, then deleteOK() will return a
         * non-null $table, else it returns null and the if() will fail.
         */
        $table = null;
        if ($table = $this->deleteOK($cObj))
        {
            $snCount = $this->sql->siblingNameCount($cObj->getID());
            if (($table == 'name') && ($snCount <= 1))
            {
                $this->enableLogging();
                $this->logDebug(sprintf("DBUtil.php Error: Cannot delete the only name for id: %s count: %s\n",
                                        $cObj->getID(),
                                        $this->sql->siblingNameCount($cObj->getID())));
                return false;
            }
            $this->sql->sqlSetDeleted($table, $cObj->getID(), $vhInfo['version']);
            $postNCount = $this->sql->siblingNameCount($cObj->getID());
            return true;
        }
        else
        {
            // Warn the user and write into the log.
            $this->enableLogging();
            $this->logDebug(sprintf("DBUtil.php Error: Cannot set deleted on class: %s table: $table json: %s\n", 
                                    get_class($cObj),
                                    $cObj->toJSON()));
            return false;
        }
    }

    /**
     * Undelete a record.
     *
     * @param \snac\data\User $user The user performing the undelete
     *
     * @param integer $roleID The current integer role.id value of the user. Comes from role.id and table appuser_role_link.
     * 
     * @param string $icstatus Status of this record. Pass a null if unchanged. Lower level code will preserved the existing setting.
     *
     * @param string $note A user-created note for what was done to the constellation. A check-in note.
     *
     * @param integer $ic_id The constellation id.
     *
     * @param string $table Name of the table we are deleting from.
     *
     * @param integer $id The record id of the record being deleted. Corresponds to table.id.
     *
     * @return string Non-null is success, null is failure. On succeess returns the deleted row id, which
     * should be the same as $id.
     *
     */
    public function clearDeleted($user, $roleID, $icstatus, $note, $ic_id, $table, $id)
    {
        if ($user == null || $user->getUserID() == null) {
            return null;
        }
        
        if (! isset($this->canDelete[$table]))
        {
            // Warn the user and write into the log.
            $this->enableLogging();
            $this->logDebug(sprintf("Cannot clear deleted on table: $table"));
            return null;
        }
        $newVersion = $this->sql->updateVersionHistory($user->getUserID(), $roleID, $icstatus, $note, $ic_id);
        $this->sql->sqlClearDeleted($table, $id, $newVersion);
        return $newVersion;
    }

}
