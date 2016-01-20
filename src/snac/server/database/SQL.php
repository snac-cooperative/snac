<?php

/**
 * EAC-CPF Parser File
 *
 * Contains the parser for EAC-CPF files into PHP Identity Constellation objects.
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
 * SQL Class
 *
 * Low level SQL methods. These methods include SQL queries. This is the only place in the code where SQL is
 * allowed (by convention, of course). Ideally, there minimal non-SQL php here. Interact with the database,
 * and nothing more. Send the data up to higher level classes for everything else.
 *
 * @author Tom Laudeman
 *        
 */
class SQL
{

    /**
     * SQL db object.
     *
     * @var \snac\server\database\DatabaseConnector A working, initialized DatabaseConnector object.
     * 
     */ 
    private $sdb = null;

    /**
     * The constructor makes the outside $db a local variable. I did this out of a general sense of avoiding
     * globals, but I'm unclear if this is really any better than using a globale $db variable. $db is
     * critical to the application, and is read-only after intialization. Breaking it or changing it in any
     * way will break everything, whether global or not. Passing it in here to get local scope doesn't meet
     * any clear need.
     *
     * @param DatabaseConnector $db A working, initialized DatabaseConnector object.
     *
     * 
     */
    public function __construct($db)
    {
        $this->sdb = $db;
    }

    /**
     * Mint a new record id. We always insert a new record, even on update. However, new objects do not have a
     * record id, so we create a table.id from the main sequence id_seq. This is just a centralized place to
     * do that. 
     *
     * @return integer A table id from sequence id_seq.
     *
     */ 
    private function selectID()
    {
        $result = $this->sdb->query('select nextval(\'id_seq\') as id',array());
        $row = $this->sdb->fetchrow($result);
        return $row['id'];
    }

    /**
     * Insert a record into table source.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param string $href A string that goes into source.href. Presumably this is a resolvable URI for the
     * source, but sources are not currenly well defined.
     *
     * @return No return value.
     * 
     */
    public function insertSource($vhInfo, $href, $id)
    {
        if (! $id)
        {
            $id = $this->selectID();
        }
        $qq = 'insert_source';
        $this->sdb->prepare($qq, 
                            'insert into source 
                            (version, main_id, href, id)
                            values 
                            ($1, $2, $3, $4)');
        $this->sdb->execute($qq,
                            array($vhInfo['version'],
                                  $vhInfo['main_id'],
                                  $href,
                                  $id));
        $this->sdb->deallocate($qq);
    }


    /**
     * Insert a constellation occupation.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param string $term Vocabulary term string which will be saved as a vocabulary.id foreign key. This
     * goes into occupation.occupation_id.
     *
     * @param string $vocabularySource Not currently saved. As far as we know, these are specific to
     * AnF. These probably should be somehow cross-walked to the SNAC vocabularies.
     *
     * @param string $note A note about the occupation.
     *
     * @param integer Record id value, aka table.id. If null due to a new object, we simply mint a new id.
     *
     *
     */ 
    public function insertOccupation($vhInfo, $term, $vocabularySource, $note, $id)
    {
        if (! $id)
        {
            $id = $this->selectID();
        }
        $qq = 'insert_occupation';
        $this->sdb->prepare($qq, 
                            'insert into occupation
                            (version, main_id, occupation_id, vocabulary_source, note)
                            values 
                            ($1, $2, (select id from vocabulary where type=\'occupation\' and value=regexp_replace($3, \'^.*#\', \'\')),
                            $4, $5, $6)');
        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['main_id'],
                                            $term,
                                            $vocabularySource,
                                            $note,
                                            $id));
        $this->sdb->deallocate($qq);
    }



    /**
     * Select the id and role for a given appuser. Maybe this should be called selectAppUserInfo() in keeping
     * with naming conventions for the other methods. Also the return values are in a flat array, and might
     * better be return in an assoc list where the keys are based on our usual conventions.
     *
     * @param string $userid A string value of the users id which is appuser.userid. Once again, the 'id' part
     * is misleading because this is a string identifier. We really need to go through everything and only use
     * 'id' where numeric ids are used. This param and field would better be called username.
     *
     * @return integer[] A flat list of the appuser.id and related role.id, both are numeric. 
     *
     */ 
    public function getAppUserInfo($userid)
    {
        $qq = 'get_app_user_info';
        // select id from appuser where userid=$userid
        $this->sdb->prepare($qq, 
                            'select appuser.id as id,role.id as role from appuser, appuser_role_link, role
                            where 
                            appuser.userid=$1
                            and appuser.id=appuser_role_link.uid
                            and role.id = appuser_role_link.rid
                            and appuser_role_link.is_primary=true');
    
        /* 
         * $result behaves a bit like a cursor. Php docs say the data is in memory, and that a cursor is not
         * used.
         */
        $result = $this->sdb->execute($qq, array($userid));
        $row = $this->sdb->fetchrow($result);
        $this->sdb->deallocate($qq);
        return array($row['id'], $row['role']);
    }
    
    /**
     * Insert a version_history record. Current this increments the id which is the version number. That needs
     * to not be incremented in some cases.
     *
     * @param integer $userid Foreign key to appuser.id, the current user's appuser id value.
     *
     * @param integer $role Foreign key to role.id, the role id value of the current user.
     *
     * @param string $status Status value from the enum icstatus. Using an enum from the db is a bit obscure
     * to all the php code, so maybe best to move icstatus to some util class and have a method to handle
     * these. Or a method that knows about the db class, but can hide the details from the application
     * code. Something.
     *
     * @param string $note A string the user enters to identify what changed in this version.
     *
     * @return string[] $vhInfo An assoc list with keys 'version', 'main_id'. Early on, version_history.id was
     * returned as 'id', but all the code knows that as the version number, so this code plays nice by
     * returning it as 'version'. Note the "returning ..." part of the query.
     *
     */
    public function insertVersionHistory($userid, $role, $status, $note)
    {
        $qq = 'insert_version_history';
        // We need version_history.id and version_history.main_id returned.
        $this->sdb->prepare('insert_version_history', 
                            'insert into version_history 
                            (user_id, role_id, status, is_current, note)
                            values 
                            ($1, $2, $3, $4, $5)
                            returning id as version, main_id;');

        $result = $this->sdb->execute('insert_version_history', array($userid, $role, $status, true, $note));
        $vhInfo = $this->sdb->fetchrow($result);
        $this->sdb->deallocate('insert_version_history');
        return $vhInfo;
    }

    /**
     * Update a version_history record by getting a new version but keeping the existing main_id. This also
     * uses DatabaseConnector->query() in an attempt to be more efficient, or perhaps just less verbose.
     *
     * @param integer $userid Foreign key to appuser.id, the current user's appuser id value.
     *
     * @param integer $role Foreign key to role.id, the role id value of the current user.
     *
     * @param string $status Status value from the enum icstatus. Using an enum from the db is a bit obscure
     * to all the php code, so maybe best to move icstatus to some util class and have a method to handle
     * these. Or a method that knows about the db class, but can hide the details from the application
     * code. Something.
     *
     * @param string $note A string the user enters to identify what changed in this version.
     *
     * @return string[] $vhInfo An assoc list with keys 'version', 'main_id'. Early on, version_history.id was
     * returned as 'id', but all the code knows that as the version number, so this code plays nice by
     * returning it as 'version'. Note the "returning ..." part of the query.
     *
     */
    public function updateVersionHistory($userid, $role, $status, $note, $main_id)
    {
        /*
         * Note: query() as opposed to prepare() and execute()
         * query() has two args:
         * 1) a string (sql query)
         * 2) an array of the vars that match the query placeholders
         * 
         */ 
        $result = $this->sdb->query('insert into version_history 
                                    (main_id, user_id, role_id, status, is_current, note)
                                    values 
                                    ($1, $2, $3, $4, $5, $6)
                                    returning id as version'
                                    ,
                                    array($main_id, $userid, $role, $status, true, $note));
        $row = $this->sdb->fetchrow($result);
        return $row['version'];
    }


    /** 
     * SNACDate.php has fromDateOriginal and toDateOriginal, but the CPF lacks date components, and the
     * database "original" is only the single original string.
     *
     * Need to add later:
     * 
     *  $date->getMissingFrom(),
     *  $date->getMissingTo(),
     *  $date->getToPresent(),
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param SNACDate $date Contrary to practice elsewhere, this code knows about the internal structure of
     * SNACDate. This arg needs to be moved up to DBUtils, and wrapped with a function to translate php
     * objects to sql tables. There shouldn't be any info about objects here, because this file is sql only.
     *
     * @param string $fk_table The name of the table to which this date and $fk_id apply.
     *
     * @param integer $fk_id The id of the record to which this date applies.
     *
     * @return integer date_range record id, in case some other code is interested in what record id was
     * inserted.
     *
     */

    public function insertDate($vhInfo,
                               $isRange,
                               $fromDate,
                               $fromType, // fk to vocabulary
                               $fromBC, 
                               $fromNotBefore,
                               $fromNotAfter, 
                               $toDate,
                               $toType, // fk to vocabulary
                               $toBC, 
                               $toNotBefore, 
                               $toNotAfter,
                               $original, 
                               $fk_table,
                               $fk_id)
    {
        $qq = 'insert_date';
        $this->sdb->prepare($qq, 
                            'insert into date_range
                            (version, main_id, is_range, from_date, from_type, from_bc, from_not_before, from_not_after,
                            to_date, to_type, to_bc, to_not_before, to_not_after, original, fk_table, fk_id)
                            values
                            ($1, $2, $3, $4, $5,
                            $6, $7, $8, $9, $10,
                            $11, $12, $13, $14, $15, $16)
                            returning id');

       $result = $this->sdb->execute($qq,
                                     array($vhInfo['version'], 
                                           $vhInfo['main_id'],
                                           $isRange,
                                           $fromDate,
                                           $fromType,
                                           $fromBC,
                                           $fromNotBefore,
                                           $fromNotAfter, 
                                           $toDate,
                                           $toType,
                                           $toBC,
                                           $toNotBefore, 
                                           $toNotAfter,
                                           $original, 
                                           $fk_table,
                                           $fk_id));

       $row = $this->sdb->fetchrow($result);
       $this->sdb->deallocate($qq);
       return $row['id'];
    }


    /** 
     * Note: This always gets the max version (most recent) for a given fk_id. Published records (older than
     * an edit) will show the edit (more recent) date, which is a known bug, and on the todo list for a fix.
     *
     * Select a date knowing a date id values. selectDate() relies on the date.id being in the original table,
     * thus $did is a foreign key of the record to which this date applies. selectDate() does not know or care
     * what the other record is.
     * 
     * The other date select function would be by original.id=date.fk_id. Maybe we only need by date.fk_id.
     *
     * @param integer $did A foreign key to record in another table.
     *
     * @param integer $version The constellation version. For edits this is max version of the
     * constellation. For published, this is the published constellation version.
     *
     * @return string[] A list of date_range fields/value as list keys matching the database field names.
     */
    public function selectDate($did, $version)
    {
        $qq = 'select_date';
        $this->sdb->prepare($qq, 
                            'select 
                            aa.id, aa.version, main_id, is_range, from_date, from_bc, from_not_before, from_not_after,
                            to_date, to_bc, to_not_before, to_not_after, original, fk_table, aa.fk_id,
                            (select value from vocabulary where id=from_type) as from_type,
                            (select value from vocabulary where id=to_type) as to_type
                            from date_range as aa,
                            (select fk_id,max(version) as version from date_range where fk_id=$1 and version<=$2 group by fk_id) as bb
                            where not is_deleted and aa.fk_id=bb.fk_id and aa.version=bb.version');

        $result = $this->sdb->execute($qq, array($did, $version));
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }

        $this->sdb->deallocate($qq);
        return $all;
    }

    
    /** 
     * Insert the non-repeating parts (non repeading data) of the constellation. No need to return a value as
     * the nrd row key is main_id,version which corresponds to the row key in all other tables being
     * id,version. Table nrd is the 1:1 table for the constellation, therefore it is logical (and consistent)
     * for it not to have a table.id field.
     * 
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param string $ark_id ARK string. There was a reason why I added _id to distinguish this from something
     * else with the naked name "ark".
     *
     * @param integer $entity_type A foreign key into table vocabulary, handled by Term related functions here and in
     * DBUtils.
     * 
     */
    public function insertNrd($vhInfo, $ark_id, $entity_type)
    {
        $qq = 'insert_nrd';
        $this->sdb->prepare($qq, 
                            'insert into nrd
                            (version, main_id, ark_id, entity_type)
                            values
                            ($1, $2, $3, $4)');
        $execList = array($vhInfo['version'], $vhInfo['main_id'], $ark_id, $entity_type);
        $result = $this->sdb->execute($qq, $execList);
        $this->sdb->deallocate($qq);
    }

    /**
     * Insert an ID from records that were merged into this constellation.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param string $type The vocabulary id for this string into field link_type.
     *
     * @param string $href This is the href for the persistent id of the other merged record, if it has
     * one. Or this might simply be some kind of ID string.
     *
     */ 
    public function insertOtherID($vhInfo, $type, $href, $id)
    {
        if (! $id)
        {
            $id = $this->selectID();
        }
        $qq = 'insert_other_id';
        $this->sdb->prepare($qq,
                            'insert into otherid
                            (version, main_id, other_id, id, link_type)
                            values
                            ($1, $2, $3, $4,
                            (select id from vocabulary where type=\'record_type\' and value=regexp_replace($5, \'^.*#\', \'\')))');
        
        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['main_id'],
                                            $href,
                                            $id,
                                            $type));
        $this->sdb->deallocate($qq);
    }
    
    /** 
     * Insert (or update) a name into the database. 
     * 
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param string $original The original name string
     *
     * @param float $preference_score The preference score for ranking this as the preferred name. This
     * concept may not work in a culturally diverse environment
     *
     * @param string[][] $contributors List of list with keys 'contributor', 'type'. Inserted into table
     * name_contributor.
     *
     * @param string $language The language of this name. This value will be retrieved from table vocabulary,
     * and the id saved in table name. This should be in the table vocabulary, however, instead of passing
     * strings back and forth, we really should be using id values from the database, so this param needs
     * work.
     *
     * @param string $scriptCode The script code of this name. Looked up from table vocabulary and the id saved in table name.
     *
     * @param integer $nameID A table id. If null we assume this is a new record an mint a new record version
     * from selectID().
     *
     */
    public function insertName($vhInfo, 
                               $original,
                               $preferenceScore,
                               $contributors,
                               $language,
                               $scriptCode,
                               $nameID)
    {
        if (! $nameID)
        {
            $nameID = $this->selectID();
        }
        $qq_1 = 'insert_name';
        $qq_2 = 'insert_contributor';

        $this->sdb->prepare($qq_1,
                            'insert into name
                            (version, main_id, original, preference_score, language, script_code, id)
                            values
                            ($1, $2, $3, $4,
                            (select id from vocabulary where type=\'language\' and value=regexp_replace($5, \'^.*#\', \'\')),
                            (select id from vocabulary where type=\'scriptCode\' and value=regexp_replace($6, \'^.*#\', \'\')),
                            $7)');
        
        $result = $this->sdb->execute($qq_1,
                                      array($vhInfo['version'],
                                            $vhInfo['main_id'],
                                            $original,
                                            $preferenceScore,
                                            $language,
                                            $scriptCode,
                                            $nameID));
        /* 
         * Contributor has issues. See comments in schema.sql. This will work for now.  Need to fix insert
         * name_contributor to keep the existing id values. Also, do not update if not changed. Implies a
         * name_contributor object with a $operation like everything else.
         *
         * So, this will move to its own function. 
         */

        $this->sdb->prepare($qq_2,
                            'insert into name_contributor
                            (version, main_id, name_id, short_name, name_type)
                            values
                            ($1, $2, $3, $4,
                            (select id from vocabulary where type=\'name_type\' and value=regexp_replace($5, \'^.*#\', \'\')))');

        // foreach over $contributors executing the insert query on each.
        foreach ($contributors as $contrib)
        {
            $this->sdb->execute($qq_2,
                                array($vhInfo['version'],
                                      $vhInfo['main_id'],
                                      $nameID,
                                      $contrib['contributor'],
                                      $contrib['type']));
        }

        $this->sdb->deallocate($qq_1);
        $this->sdb->deallocate($qq_2);
    }
    
    
    /**
     * Insert into table function. The SQL returns the inserted id which is used when inserting a date into
     * table date_range. Function uses the same vocabulary terms as occupation.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param 
     *
     */
    public function insertFunction($vhInfo, $type, $vocabularySource, $note, $term, $id)
    {
        if (! $id)
        {
            $id = $this->selectID();
        }
        $qq = 'insert_function';
        $this->sdb->prepare($qq,
                            'insert into function
                            (version, main_id, function_type, vocabulary_source, note, id, function_id)
                            values
                            ($1, $2, $3, $4, $5, $6, $7)');
        $eArgs = array($vhInfo['version'], $vhInfo['main_id'], $type, $vocabularySource, $note, $id, $term);
        $result = $this->sdb->execute($qq, $eArgs);
        $id = $this->sdb->fetchrow($result)['id'];
        $this->sdb->deallocate($qq);
    }

    
    /**
     * Insert into table subject. Data is currently only a string from the Constellation.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param $term string that is a subject
     *
     * @return no return value.
     * 
     */
    public function insertSubject($vhInfo, $term, $id)
    {
        if (! $id)
        {
            $id = $this->selectID();
        }
        $qq = 'insert_subject';
        $this->sdb->prepare($qq,
                            'insert into subject
                            (version, main_id, id, subject_id)
                            values
                            ($1, $2, $3, (select id from vocabulary where type=\'subject\' and value=regexp_replace($4, \'^.*#\', \'\')))');
        
        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['main_id'],
                                            $id,
                                            $term));
        $this->sdb->deallocate($qq);
    }

    /**
     * Insert a related identity aka table related_identity, aka constellation relation, aka cpf relation, aka
     * ConstellationRelation object. We first insert into related_identity saving the inserted record
     * id.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     * 
     * @param $argList Flat array of data suitable for execute(). We assume that DBUtils
     * knows the php to sql field translation.
     *
     * @return no return value.
     * 
     */
    public function insertRelation($vhInfo, 
                                   $targetID,
                                   $targetArkID,
                                   $targetEntityType,
                                   $type,
                                   $relationType,
                                   $content,
                                   $note,
                                   $id)
    {
        if (! $id)
        {
            $id = $this->selectID();
        }
        $qq = 'insert_related_identity';
        $this->sdb->prepare($qq,
                            'insert into related_identity
                            (version, main_id, related_id, related_ark, role, arcrole, 
                            relation_type, relation_entry, descriptive_note, id)
                            values
                            ($1, $2, $3, $4,
                            (select id from vocabulary where type=\'entity_type\' and value=regexp_replace($5, \'^.*#\', \'\')),
                            (select id from vocabulary where type=\'relation_type\' and value=regexp_replace($6, \'^.*#\', \'\')),
                            $7, $8, $9, $10)');

        // Combine vhInfo and the remaining args into a big array for execute().
        $execList = array($vhInfo['version'], 
                          $vhInfo['main_id'],
                          $targetID,
                          $targetArkID,
                          $targetEntityType,
                          $type,
                          $relationType,
                          $content,
                          $note,
                          $id);
        $result = $this->sdb->execute($qq, $execList);
        $row = $this->sdb->fetchrow($result);

        $this->sdb->deallocate($qq);
    }

    /**
     * Insert into table related_resource using data from php ResourceRelation object. It is assumed that the
     * calling code in DBUtils knows the php to sql fields. Note keys in $argList have a fixed order.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param string[] $argList A flat array. The foreach before execute() simply copies all of them into a
     * list to pass to execute(). We assume that DBUtils knows the order of data to send. If an order problem
     * develops, fix it in the calling code, not down here. The whole point of DBUtils is to know php and SQL
     * fields. The code down here only knows how to write database tables.
     *
     * @return no return values
     * 
     */
    public function insertResourceRelation($vhInfo,
                                           $relationEntryType, // 3
                                           $entryType, // 4
                                           $href, // 5
                                           $arcRole, // 6
                                           $relationEntry, // 7
                                           $objectXMLWrap, // 8
                                           $note, // 9
                                           $id) // 10
    {
        if (! $id)
        {
            $id = $this->selectID();
        }
        $qq = 'insert_resource_relation';
        $this->sdb->prepare($qq,
                            'insert into related_resource
                            (version, main_id, role, relation_entry_type, href, arcrole, relation_entry, 
                            object_xml_wrap, descriptive_note, id)
                            values
                            ($1, $2,
                            (select id from vocabulary where type=\'document_type\' and value=regexp_replace($3, \'^.*#\', \'\')),
                            $4, $5,
                            (select id from vocabulary where type=\'document_role\' and value=regexp_replace($6, \'^.*#\', \'\')),
                            $7, $8, $9, $10)');

        /* 
         * Combine vhInfo and the remaining args into a big array for execute().
         */
        $execList = array($vhInfo['version'], // 1
                          $vhInfo['main_id'], // 2
                          $relationEntryType, // 3
                          $entryType, // 4
                          $href, // 5
                          $arcRole, // 6
                          $relationEntry, // 7
                          $objectXMLWrap, // 8
                          $note, // 9
                          $id); // 10
        $this->sdb->execute($qq, $execList);
        $this->sdb->deallocate($qq);
    }


    /**
     *
     * Select from table nrd which has 1:1 fields for the constellation. We keep 1:1 fields here, although
     * table version_history is the center of everything. A class DBUtils method also called
     * selectConstellation() knows all the SQL methods to call to get the full constellation.
     *
     * It is intentional that the fields are not retrieved in any particular order because the row will be
     * saved as an associative list. That allows us to write the sql query in a more legible format.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @return string[] An associative list with keys: version, main_id, ark_id, entity_type.
     * 
     * 
     */
    public function selectNrd($vhInfo)
    {
        $qq = 'sc';
        $this->sdb->prepare($qq, 
                            'select
                            aa.version,aa.main_id,aa.ark_id,aa.entity_type
                            from nrd as aa,
                            (select main_id, max(version) as version from nrd where version<=$1 and main_id=$2 group by main_id) as bb
                            where not aa.is_deleted and
                            aa.main_id=bb.main_id
                            and aa.version=bb.version');
        /* 
         * Always use key names explicitly when going from associative context to flat indexed list context.
         */
        $result = $this->sdb->execute($qq, 
                                      array($vhInfo['version'],
                                            $vhInfo['main_id']));
        $row = $this->sdb->fetchrow($result);
        $this->sdb->deallocate($qq);
        return $row;
    }

    /** 
     *
     * Select flat list of distinct id values meeting the version and main_id constraint. Specifically a
     * helper function for selectOtherRecordID(). This deals with the possibility that a given otherid.id may
     * have several versions while other otherid.id values are different (and single) versions.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @return integer[] Return a list of record id values meeting the version and main_id constriants.
     * 
     */
    public function matchORID($vhInfo)
    {

        $qq = 'morid';
        $this->sdb->prepare($qq, 
                            'select
                            distinct(id)
                            from otherid
                            where
                            version=(select max(version) from otherid where version<=$1 and main_id=$2)
                            and main_id=$2');
        /* 
         * Always use key names explicitly when going from associative context to flat indexed list context.
         */
        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['main_id']));
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row['id']);
        }
        $this->sdb->deallocate($qq);
        return $all;
    }


    /** 
     * select other IDs which were originally ID values of merged records. DBUtils has code that 
     * adds an otherRecordID to a Constellation object.
     * 
     * Assume unique id in vocab, so don't need extra constraint type='record_type'
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @return string[] Return an associative ist of otherid rows with keys: id, version, main_id, other_id,
     * link_type.
     * 
     */
    public function selectOtherRecordID($vhInfo)
    {
        $matchingIDs = $this->matchORID($vhInfo);

        $qq = 'sorid';
        $this->sdb->prepare($qq, 
                            'select
                            id, version, main_id, other_id,
                            (select value from vocabulary where id=link_type) as link_type
                            from otherid
                            where
                            version=(select max(version) from otherid where version<=$1)
                            and main_id=$2 and id=$3');

        $all = array();
        foreach ($matchingIDs as $orid)
        {
            $result = $this->sdb->execute($qq, array($vhInfo['version'], $vhInfo['main_id'], $orid));
            while($row = $this->sdb->fetchrow($result))
            {
                array_push($all, $row);
            }
        }
        $this->sdb->deallocate($qq);
        return $all;
    }

    
    /**
     *
     * Select subjects. DBUtils has code to turn the return values into subjects in a Constellation object.
     *
     * Solve the multi-version problem by joining to a subquery.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @return string[][] Return list of an associative list with keys: id, version, main_id,
     * subject_id. There may be multiple subjects returned.
     * 
     */
    public function selectSubject($vhInfo)
    {
        $qq = 'ssubj';
        $this->sdb->prepare($qq, 
                            'select
                            aa.id, aa.version, aa.main_id,
                            (select value from vocabulary where id=subject_id) as subject_id
                            from subject aa,
                            (select id, max(version) as version from subject where version<=$1 and main_id=$2 group by id) as bb
                            where not aa.is_deleted and
                            aa.id=bb.id
                            and aa.version=bb.version');
        /* 
         * Always use key names explicitly when going from associative context to flat indexed list context.
         */
        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['main_id']));
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }
        $this->sdb->deallocate($qq);
        return $all;
    }


    /** 
     *
     * Select occupation, returning a list of lists. Code in DBUtils foreach's over the outer list, turning
     * each inner list into an Occupation object.
     *
     * Nov 24 2015 New convention: the table we're working on is 'aa', and the subquery is 'bb'. This makes
     * the query more of a standard template.  Assuming this works and is a good idea, we should port this to
     * all the other select queries.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @return string[][] Return a list of lists. Inner list has keys: id, version, main_id, note, vocabulary_source, occupation_id, date
     * 
     */
    public function selectOccupation($vhInfo)
    {
        $qq = 'socc';
        $this->sdb->prepare($qq, 
                            'select
                            aa.id, aa.version, aa.main_id, aa.note, aa.vocabulary_source,
                            (select value from vocabulary where id=aa.occupation_id) as occupation_id
                            from occupation as aa,
                            (select id, max(version) as version from occupation where version<=$1 and main_id=$2 group by id) as bb
                            where not aa.is_deleted and
                            aa.id=bb.id
                            and aa.version=bb.version');
        /* 
         * Always use key names explicitly when going from associative context to flat indexed list context.
         */
        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['main_id']));
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            $rid = $row['id'];
            $dateList = $this->selectDate($rid, $vhInfo['version']);
            $row['date'] = array();
            if (count($dateList)>=1)
            {
                $row['date'] = $dateList[0];
            }
            if (count($dateList)>1)
            {
                // TODO Throw an exception or write a log message. Or maybe this will never, ever happen. John
                // Prine says: "Stop wishing for bad luck and knocking on wood"
            }
            array_push($all, $row);
        }
        $this->sdb->deallocate($qq);
        return $all;
    }

    /**
     * Select a related identity (aka cpf relation). Code in DBUtils turns the returned array into a ConstellationRelation object. 
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @return string[][] Return a list of lists. There may be multiple relations. Each relation has keys: id,
     * version, main_id, related_id, related_ark, relation_entry, descriptive_node, relation_type, role,
     * arcrole, date. Date is an associative list with keys from table date_range. See selectDate().
     * 
     */ 
    public function selectRelation($vhInfo)
    {
        $qq = 'selectrelatedidentity';
        $this->sdb->prepare($qq,
                            'select
                            aa.id, aa.version, aa.main_id, aa.related_id, aa.related_ark,
                            aa.relation_entry, aa.descriptive_note, aa.relation_type,
                            (select value from vocabulary where id=aa.role) as role,
                            (select value from vocabulary where id=aa.arcrole) as arcrole
                            from related_identity as aa,
                            (select id, max(version) as version from related_identity where version<=$1 and main_id=$2 group by id) as bb
                            where not aa.is_deleted and
                            aa.id=bb.id
                            and aa.version=bb.version');

        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['main_id']));
        $all = array();
        while ($row = $this->sdb->fetchrow($result))
        {
            $relationId = $row['id'];
            $dateList = $this->selectDate($relationId, $vhInfo['version']);
            $row['date'] = array();
            if (count($dateList)>=1)
            {
                $row['date'] = $dateList[0];
            }
            if (count($dateList)>1)
            {
                //TODO Throw warning or log 
            }
            array_push($all, $row);
        }
        $this->sdb->deallocate($qq);
        return $all;
    }

    /**
     * select related archival resource records given $vhInfo 'version' and 'main_id'. Code in DBUtils knows how to
     * turn the return value into a pgp ResourceRelation object.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @return string[][] Return a list of lists. Inner list keys: id, version, main_id, relation_entry_type,
     * href, relation_entry, object_xml_wrap, descriptive_note, role, arcrole
     *
     */ 
    public function selectRelatedResource($vhInfo)
    {
        $qq = 'select_related_resource';
        $this->sdb->prepare($qq,
                            'select
                            aa.id, aa.version, aa.main_id,
                            aa.relation_entry_type, aa.href, aa.relation_entry, aa.object_xml_wrap, aa.descriptive_note,
                            (select value from vocabulary where id=aa.role) as role,
                            (select value from vocabulary where id=aa.arcrole) as arcrole
                            from related_resource as aa,
                            (select id, max(version) as version from related_resource where version<=$1 and main_id=$2 group by id) as bb
                            where not aa.is_deleted and
                            aa.id=bb.id
                            and aa.version=bb.version');

        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['main_id']));
        $all = array();
        while ($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }
        $this->sdb->deallocate($qq);
        return $all;
    }

    /**
     * Select all function records for the given version and main_id. Code in DBUtils turns the return value into a
     * SNACFunction object.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @return string[][] Return a list of list. The inner list has keys: id, version, main_id, function_type,
     * note, date. Key date is also a list assoc array of date info from selectDate().
     *
     */ 
    public function selectFunction($vhInfo)
    {
        $qq = 'select_related_resource';
        $this->sdb->prepare($qq,
                            'select
                            aa.id, aa.version, aa.main_id, aa.function_type, aa.vocabulary_source, aa.note,
                            (select value from vocabulary where id=aa.function_id) as function_id
                            from function as aa,
                            (select id, max(version) as version from function where version<=$1 and main_id=$2 group by id) as bb
                            where not aa.is_deleted and
                            aa.id=bb.id
                            and aa.version=bb.version');

        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['main_id']));
        $all = array();
        while ($row = $this->sdb->fetchrow($result))
        {
            $dateList = $this->selectDate($row['id'], $vhInfo['version']);
            $row['date'] = array();
            if (count($dateList)>=1)
            {
                $row['date'] = $dateList[0];
            }
            if (count($dateList)>1)
            {
                // TODO: Throw a warning or log
            }
            array_push($all, $row);
        }
        $this->sdb->deallocate($qq);
        return $all;
    }


     /** 
      * Select all names for the given version and main_id. Code in DBUtils turns each returned list into a
      * NameEntry object. Order the returned records by preference_score descending so that preferred names
      * are at the beginning of the returned list. For ties, we also order by id, just so we'll be
      * consistent. The consistency may help testing more than UI related issues (where names should
      * consistently appear in the same order each time the record is viewed.)
      *
      * @param string[] $vhInfo with keys version, main_id.
      *
      * @return string[][] Return a list of lists. The inner list has keys: id, version, main_id, original,
      * preference_score, language, script_code, contributors. Key contributors is a list with keys: id,
      * version, main_id, name_id, short_name, name_type.
      */
    public function selectNameEntry($vhInfo)
    {
        $qq_1 = 'selname';
        $qq_2 = 'selcontributor';
        $this->sdb->prepare($qq_1,
                            'select
                            aa.is_deleted,aa.id,aa.version, aa.main_id, aa.original, aa.preference_score,
                            (select value from vocabulary where id=aa.language) as language,
                            (select value from vocabulary where id=aa.script_code) as script_code
                            from name as aa,
                            (select id,max(version) as version from name where version<=$1 and main_id=$2 group by id) as bb
                            where
                            aa.id = bb.id and not aa.is_deleted and 
                            aa.version = bb.version order by preference_score,id');
        
        $this->sdb->prepare($qq_2,
                            'select 
                            aa.id,aa.version, main_id, name_id, short_name,
                            (select value from vocabulary where id=name_type) as name_type
                            from  name_contributor as aa,
                            (select id, max(version) as version from name_contributor where version<=$1 and main_id=$2 group by id) as bb
                            where not aa.is_deleted and
                            aa.id=bb.id
                            and aa.version=bb.version
                            and aa.name_id=$3');
        
        $name_result = $this->sdb->execute($qq_1,
                                           array($vhInfo['version'],
                                                 $vhInfo['main_id']));
        // Contributor has issues. See comments in schema.sql. This will work for now.
        // Get each name, and for each name get each contributor.
        $all = array();
        while($name_row = $this->sdb->fetchrow($name_result))
        {
            $name_row['contributors'] = array();
            $contributor_result = $this->sdb->execute($qq_2,
                                                      array($vhInfo['version'],
                                                            $vhInfo['main_id'],
                                                            $name_row['id']));
            $name_row['contributors'] = array();
            while($contributor_row = $this->sdb->fetchrow($contributor_result))
            {
                array_push($name_row['contributors'], $contributor_row);
            }
            array_push($all, $name_row);
        }
        $this->sdb->deallocate($qq_1);
        $this->sdb->deallocate($qq_2);
        return $all;
    }



    /** 
     * This is used for testing. Not really random. Get a record that has a date_range record. The query
     * doesn't need to say date_range.fk_id since fk_is is unique to date_range, but it makes the join
     * criteria somewhat more obvious.
     *
     * Note: Must select max(version_history.id) as version. The max() version is the Constellation version.
     * 
     * @return string[] Return a flat array. This seems like a function that should return an associative
     * list. Currently, is only called in one place. 
     */
    public function randomConstellationID()
    {
        $qq = 'rcid';
        $this->sdb->prepare($qq, 
                            'select max(version_history.id) as version, version_history.main_id
                            from nrd,date_range, version_history
                            where
                            nrd.id=date_range.fk_id and
                            nrd.main_id=version_history.main_id
                            and not date_range.is_deleted
                            group by version_history.main_id
                            order by version_history.main_id
                            limit 1');
    
        $result = $this->sdb->execute($qq, array());
        $row = $this->sdb->fetchrow($result);
        $this->sdb->deallocate($qq);
        return array($row['version'], $row['main_id']);
    }
    
    /**
     * Helper function to return the most recent version for a given main_id.
     *
     * @param integer $mainID id value matching version_history.main_id.
     *
     * @return integer Version number from version_history.id returned as 'version', as is our convention.
     *
     */

    public function sqlCurrentVersion($mainID)
    {
        $result = $this->sdb->query('select max(id) as version from version_history where main_id=$1',
                                    array($mainID));
        $row = $this->sdb->fetchrow($result);
        return $row['version'];
    }

    /**
     * Return the lowest main_id for a multi-name constellation with 2 or more non-deleted names. Returns a
     * version and main_id for the constelletion to which this name belongs.
     *
     * Note: When getting a version number, we must always look for max(version_history.id) as version to be
     * sure we have "the" constellation version number.
     *
     * Note: Use boolean "is true" short syntax "is_deleted" in the sql because it is unambiguous, and it
     * saves escaping \'t\' which is necessary in the verbose syntax.
     *
     * This is a helper/convenience function for testing purposes only.
     *
     * @return integer[] Returns a vhInfo associateve list of integers with key names 'version' and
     * 'main_id'. The main_id is from table 'name' for the multi-alt string name. That main_id is a
     * constellation id, so we call sqlCurrentVersion() to get the current version for that
     * constellation. This allows us to return a conventional vhInfo associative list which is conventient
     * return value. (Convenient, in that we do extra work so the calling code is simpler.)
     * 
     * 
     */
    public function sqlMultiNameConstellationID()
    {
        $qq = 'mncid';
        $this->sdb->prepare($qq, 
                            'select max(vh.id) as version, vh.main_id 
                            from version_history as vh,
                            (select count(aa.id),aa.main_id from name as aa
                            where aa.id not in (select id from name where is_deleted) group by main_id order by main_id) as zz
                            where 
                            vh.main_id=zz.main_id and 
                            zz.count>1 group by vh.main_id limit 1');
    
        $result = $this->sdb->execute($qq, array());
        $row = $this->sdb->fetchrow($result);

        $version = $this->sqlCurrentVersion($row['main_id']);

        $this->sdb->deallocate($qq);
        return array('version' => $version, 
                     'main_id' => $row['main_id']);
    }


    /**
     *
     * Small utility function to count rows in table vocabulary. Currently only used in DBUtilTest.php
     *
     * @return int Count of number of rows in table vocabulary.
     */
    public function countVocabulary()
    {
        /*
         * Note: query() as opposed to prepare() and execute()
         * query() has two args:
         * 1) a string (sql query)
         * 2) an array of the vars that match the query placeholders, empty here because there are no placeholders.
         */ 
        $result = $this->sdb->query('select count(*) as count from vocabulary',
                                    array());
        $row = $this->sdb->fetchrow($result);
        return $row['count'];
    }

    /**
     * Get a set of 100 records, but only return data (version, main_id) that might be necessary for display
     * in the dashboard. Version and main_id are sufficient to call selectConstellation(). The name is added
     * on the off chance that this could be used in some UI that needed a name displayed for the
     * constellation.
     *
     * Note: query() as opposed to prepare() and execute()
     *
     * @return string[] A list of 100 lists. Inner list keys are: 'version', 'main_id', 'formatted_name'. At
     * this time 'formatted_name' is from table name.original
     */ 
    public function selectDemoRecs()
    {
        $qq =
            'select max(id) as version,main_id 
            from version_history 
            group by main_id order by main_id limit 100';

        $result = $this->sdb->query($qq, array());
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            $nRow = $this->selectNameEntry(array('version' => $row['version'],
                                                 'main_id' => $row['main_id']));
            if (count($nRow) == 0)
            {
                // Yikes, cannot have a constellation with zero names.
                printf("No names for version: %s main_id: %s\n", $row['version'], $row['main_id']);
            }

            /* 
             * For now just use the first name returned, whatever that is. selectNameEntry() sorts by
             * preference_score, but that score might not be accurate for all contexts.
             */
            $row['formatted_name'] = $nRow[0]['original'];
            array_push($all, $row);
        }
        return $all;
    }

    /*
     * Update a record in table $table to have is_deleted set to true, for the most recent version. "Most
     * recent" is the version prior to $newVersion, because $newVersion has not yet been saved in the
     * database. The query retains the usual subquery $version<=$1 even though in this case we have a new
     * version so < would also work.
     *
     * Note that $table is a direct string interpolation. It is vital to avoiding sql injection attacks that
     * calling code only allow valid table names. There are limits to what placeholders can do, and
     * placeholders are limited to being column values.
     *
     * Note that the method below makes no assumptions about field order. Even associative list key order is
     * not assumed to be unchanging.
     *
     * Don't constrain the query to not aa.is_deleted. First, it won't matter to delete the record
     * again. Second, all the other code checks is_deleted, so the other code won't even show the user a
     * record that has already been marked as deleted. Therefore (in theory) here we will never be asked to
     * delete an is_deleted record.
     *
     * The unique primary key for a table is id,version. Field main_id is the relational grouping field,
     * and used by higher level code to build the constellation, but by and large main_id is not used for
     * record updates, so the code below makes no explicit mention of main_id.
     * 
     * @param string $table A valid table name, created from internal data only, since there is a risk here of
     * SQL injection attack.
     *
     * @param integer $id The id (table.id) of the record we are deleting. This field is not unique, so we
     * must constrain by id,version as is conventional practice.
     *
     * @param integer $newVersion The max version of the record to delete. We delete the record with the matching
     * table.id and the version <= to $newVersion as is conventional practice.
     *
     */
    public function sqlSetDeleted($table, $id, $newVersion)
    {
        $this->sqlCoreDeleted($table, $id, $newVersion, 'set');
    }

        /*
     * Update a record in table $table to have is_deleted set to true, for the most recent version. "Most
     * recent" is the version prior to $newVersion, because $newVersion has not yet been saved in the
     * database. The query retains the usual subquery $version<=$1 even though in this case we have a new
     * version so < would also work.
     *
     * Note that $table is a direct string interpolation. It is vital to avoiding sql injection attacks that
     * calling code only allow valid table names. There are limits to what placeholders can do, and
     * placeholders are limited to being column values.
     *
     * Note that the method below makes no assumptions about field order. Even associative list key order is
     * not assumed to be unchanging.
     *
     * Don't constrain the query to not aa.is_deleted. First, it won't matter to delete the record
     * again. Second, all the other code checks is_deleted, so the other code won't even show the user a
     * record that has already been marked as deleted. Therefore (in theory) here we will never be asked to
     * delete an is_deleted record.
     *
     * The unique primary key for a table is id,version. Field main_id is the relational grouping field,
     * and used by higher level code to build the constellation, but by and large main_id is not used for
     * record updates, so the code below makes no explicit mention of main_id.
     * 
     * @param string $table A valid table name, created from internal data only, since there is a risk here of
     * SQL injection attack.
     *
     * @param integer $id The id (table.id) of the record we are deleting. This field is not unique, so we
     * must constrain by id,version as is conventional practice.
     *
     * @param integer $newVersion The max version of the record to delete. We delete the record with the matching
     * table.id and the version <= to $newVersion as is conventional practice.
     *
     */
    public function sqlClearDeleted($table, $id, $newVersion)
    {
        $this->sqlCoreDeleted($table, $id, $newVersion, 'clear');
    }

    /**
     * Do the real work for set/clear is_deleted. The only difference between setting and clearing is the
     * value put into is_deleted, so it makes sense to have one function doing both.
     *
     * @param string $table A valid table name, created from internal data only, since there is a risk here of
     * SQL injection attack.
     *
     * @param integer $id The id (table.id) of the record we are deleting. This field is not unique, so we
     * must constrain by id,version as is conventional practice.
     *
     * @param integer $newVersion The max version of the record to delete. We delete the record with the matching
     * table.id and the version <= to $newVersion as is conventional practice.
     * 
     * @param string $operation Either 'set', or 'clear'. Set changes is_delete to 't'. Clear changes is_deleted to 'f'.
     *
     */
    public function sqlCoreDeleted($table, $id, $newVersion, $operation)
    {
        $selectSQL =
                   "select aa.* from $table as aa,
                   (select id, max(version) as version from $table where version<=$1 and id=$2 group by id) as bb
                   where aa.version=bb.version and aa.id=bb.id";

        $result = $this->sdb->query($selectSQL, array($newVersion, $id));
        $row = $this->sdb->fetchrow($result);

        if (count($row) == 0) 
        {
            /*
             * This should never happen. Calling code has already checked for one of these records, and
             * wouldn't be calling us if there wasn't something to operate on. Still, when called with wrong
             * arguments (an upstream bug), this has happened.
             */ 
            printf("Error: sqlSetDeleted() fails to select a row for table: $table id: $id newVersion: $newVersion\n");
            return;
        }
        // Default to clearing, that is un-delete.
        $row['is_deleted'] = 'f';
        if ($operation == 'set')
        {
            $row['is_deleted'] = 't';
        }
        $row['version'] = $newVersion;
    
        /* 
         * Dynamically build an insert statement "column string" and matching "place holder string". We could
         * assume the order or columns and keys would be invariant, as is defined in SQL and php. However, it
         * is not too outlandish to think that something will break one of those two foundational language
         * definitions.
         *
         * Use the "tween idiom" to handle the comma separators. By prefixing the tween, we don't need to
         * clean trailing tween/separator from the strings after the loop. The tween string starts empty, and
         * is set at the end of the loop. The tween is always set at the end of the loop, which is a trifle
         * brute force, but se assume that setting the tween is less CPU than checking to see if the tween is
         * empty. In other words, there is no point in optimizing the loop below.
         */

        $columnString = '';
        $placeHolderString = '';
        $xx = 1;     // Counting numbers start at 1, and place holders start with $1 (indexes start at zero)
        $tween = ''; // Tweens always start empty.
        foreach ($row as $key => $value)
        {
            $columnString .="$tween$key";
            $placeHolderString .= "$tween\$$xx";
            $xx++;
            $tween = ", ";
        }
        $updateSQL = "insert into $table ($columnString) values ($placeHolderString) returning id";
        $newResult = $this->sdb->query($updateSQL, array_values($row));
    }

    /**
     * Count names, current version, not deleted, for a single constellation.
     *
     * This is used to check if we are allowed to delete a name, because we must not delete the only name for
     * a constellation.
     *
     * Note that Postgres names the column from the count() function 'count', so we do not need to alias the
     * column. I used the explicit alias just to make intent clear.
     *
     * @param $main_id Integer constellation id usually from version_history.main_id.
     *
     * @return interger Number of names meeting the criteria. Zero if no names or if the query fails. 
     * 
     */
    public function CountNames($main_id)
    {
        $selectSQL =
            "select count(*) as count from name as aa,
            (select id, main_id, max(version) as version from name group by id,main_id) as bb
            where aa.id=bb.id and not aa.is_deleted and aa.version=bb.version and aa.main_id=bb.main_id and aa.main_id=$1";
        
        $result = $this->sdb->query($selectSQL, array($main_id));
        $row = $this->sdb->fetchrow($result);
        if ($row and isset($row['count']))
        {
            return $row['count'];
        }
        else
        {
            return 0;
        }
    }


}

