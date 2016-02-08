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
     * Select records from table source.
     *
     * @param integer $fkID A foreign key to record in another table.
     *
     * @param integer $version The constellation version. For edits this is max version of the
     * constellation. For published, this is the published constellation version.
     *
     * @return string[] A list of location fields as list with keys matching the database field names:
     * version, main_id, id, text, note, uri, type_id, language_id.
     * 
     */
    public function selectSource($fkID, $version)
    {
        $qq = 'select_source';
        $this->sdb->prepare($qq, 
                            'select aa.version, aa.main_id, aa.id, aa.text, aa.note, aa.uri, aa.type_id, aa.language_id
                            from source as aa,
                            (select fk_id,max(version) as version from source where fk_id=$1 and version<=$2 group by fk_id) as bb
                            where not is_deleted and aa.fk_id=bb.fk_id and aa.version=bb.version');
        $result = $this->sdb->execute($qq, array($fkID, $version));
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }
        $this->sdb->deallocate($qq);
        return $all;
    }


    /**
     * Insert a record into table source.
     *
     * Language related is a Language object, and is saved in table language. It is related where
     * source.id=language.fk_id. There is not language_id in table source, and there should not be.
     *
     * $typeID is different. It is a vocabulary id, from a PHP Term object. Therefore we only save the id. The
     * Term object's instance is really a single row in table vocabulary.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param integer $id Record id
     *
     * @param string $text Text of this source.
     *
     * @param string $note Note about this source.
     *
     * @param string $uri URI of this source
     *
     * @param integer $typeID Vocabulary fk of the type
     *
     * @param integer $fkID Foreign key of the table related to this source.
     *
     * @param string $fkTable Name of the related table.
     * 
     * @return integer The id value of this record. Sources have a language, so we need to return the $id
     * which is used by language as a foreign key.
     * 
     */
    public function insertSource($vhInfo, $id, $text, $note, $uri, $typeID, $fkTable, $fkID)
    {
        if (! $id)
        {
            $id = $this->selectID();
        }
        $qq = 'insert_source';
        $this->sdb->prepare($qq, 
                            'insert into source 
                            (version, main_id, id, text, note, uri, type_id, fk_table, fk_id)
                            values 
                            ($1, $2, $3, $4, $5, $6, $7, $8, $9)');
        $this->sdb->execute($qq,
                            array($vhInfo['version'],
                                  $vhInfo['main_id'],
                                  $id,
                                  $text,
                                  $note,
                                  $uri, 
                                  $typeID,
                                  $fkTable,
                                  $fkID));
        $this->sdb->deallocate($qq);
        return $id;
    }


    /**
     * Insert a biogHist. If the $id arg is null, get a new id. Always return $id.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param int $id Record id.
     *
     * @param string $text Text of the biogHist.
     *
     */
    public function insertBiogHist($vhInfo, $id, $text)
    {
        if (! $id)
        {
            $id = $this->selectID();
        }
        $qq = 'insert_bioghist';
        $this->sdb->prepare($qq, 
                            'insert into biog_hist
                            (version, main_id, id, text)
                            values 
                            ($1, $2, $3, $4)');
        $this->sdb->execute($qq,
                            array($vhInfo['version'],
                                  $vhInfo['main_id'],
                                  $id,
                                  $text));
        $this->sdb->deallocate($qq);
        return $id;
    }


    /**
     * Insert a constellation occupation. If the $id arg is null, get a new id. Always return $id.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param int $id Record id occupation.id
     *
     * @param int $termID Vocabulary term foreign key id. Managed via Term objects in the calling code.
     *
     * @param string $vocabularySource Not currently saved. As far as we know, these are specific to
     * AnF. These probably should be somehow cross-walked to the SNAC vocabularies.
     *
     * @param string $note A note about the occupation.
     *
     */ 
    public function insertOccupation($vhInfo, $id, $termID, $vocabularySource, $note)
    {
        if (! $id)
        {
            $id = $this->selectID();
        }
        $qq = 'insert_occupation';
        $this->sdb->prepare($qq, 
                            'insert into occupation
                            (version, main_id, id, occupation_id, vocabulary_source, note)
                            values 
                            ($1, $2, $3, $4, $5, $6)');
        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['main_id'],
                                            $id, 
                                            $termID,
                                            $vocabularySource,
                                            $note));
        $this->sdb->deallocate($qq);
        return $id;
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
     * @param string $fk_table The name of the table to which this date and $fk_id apply.
     *
     * @param integer $fk_id The id of the record to which this date applies.
     *
     * @return integer date_range record id, in case some other code is interested in what record id was
     * inserted.
     *
     */
    public function insertDate($vhInfo,
                               $id, 
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
        if (! $id)
        {
            $id = $this->selectID();
        }
        $qq = 'insert_date';
        $this->sdb->prepare($qq, 
                            'insert into date_range
                            (version, main_id, id, is_range, from_date, from_type, from_bc, from_not_before, from_not_after,
                            to_date, to_type, to_bc, to_not_before, to_not_after, original, fk_table, fk_id)
                            values
                            ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17)');

       $result = $this->sdb->execute($qq,
                                     array($vhInfo['version'], 
                                           $vhInfo['main_id'],
                                           $id,
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
       return $id;
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
                            aa.from_type,aa.to_type
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
     * Note: This always gets the max version (most recent) for a given fk_id. Published records (older than
     * an edit) will show the edit (more recent) date, which is a known bug, and on the todo list for a fix.
     *
     * Select a place. This relies on table.id==fk_id where $tid is a foreign key of the record to which this
     * place applies. We do not know or care what the other record is.
     * 
     * @param integer $tid A foreign key to record in another table.
     *
     * @param integer $version The constellation version. For edits this is max version of the
     * constellation. For published, this is the published constellation version.
     *
     * @return string[] A list of fields/value as list keys matching the database field names: id,
     * version, main_id, confirmed, geo_place_id, fk_table, fk_id, from_type, to_type
     */
    public function selectPlace($tid, $version)
    {
        $qq = 'select_place';
        $this->sdb->prepare($qq, 
                            'select 
                            aa.id, aa.version, aa.main_id, aa.confirmed, aa.geo_place_id, fk_table, aa.fk_id,
                            aa.from_type, aa.to_type
                            from place as aa,
                            (select fk_id,max(version) as version from place where fk_id=$1 and version<=$2 group by fk_id) as bb
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
     * Insert into place_link. 
     *
     * @param integer $id The id
     *
     * @param string $confirmed Boolean confirmed by human
     *
     * @param string $geo_place_id The geo_place_id
     *
     * @param string $fk_id The fk_id of the related table.
     *
     * @param string $fk_table The fk_table name
     *
     * @return integer $id The id of what we (might) have inserted.
     * 
     */
    public function insertPlace($vhInfo, $id, $confirmed, $original,  $geo_place_id,  $fk_table, $fk_id)
    {
        if (! $id)
        {
            $id = $this->selectID();
        }
        $qq = 'insert_place';
        $this->sdb->prepare($qq, 
                            'insert into place_link
                            (version, main_id, id, confirmed, original, geo_place_id,  fk_id, fk_table)
                            values 
                            ($1, $2, $3, $4, $5, $6, $7, $8)');

        $result = $this->sdb->execute($qq,
                                      array($vhInfo['main_id'],
                                            $vhInfo['version'],
                                            $id,
                                            $confirmed,
                                            $original,
                                            $geo_place_id,
                                            $fk_id,
                                            $fk_table));
        $this->sdb->deallocate($qq);
        return $id;
    }


    /** 
     * Note: This always gets the max version (most recent) for a given fk_id. Published records (older than
     * an edit) will show the edit (more recent) record, which is a known bug, and on the todo list for a fix.
     *
     * Select a meta data record. We expect only one record, and will only return one (or zero). The query
     * relies on table.id==fk_id where $tid is a foreign key of the record to which this applies. We do not
     * know or care what the other record is.
     * 
     * @param integer $tid A foreign key to record in another table.
     *
     * @param integer $version The constellation version. For edits this is max version of the
     * constellation. For published, this is the published constellation version.
     *
     * @return string[] A list of fields/value as list keys matching the database field names: id,
     * version, main_id, citation_id, sub_citation, source_data, rule_id,
     * language_id, note. I don't think calling code has any use for fk_id, so we don't return it.
     */
    public function selectMeta($tid, $version)
    {
        $qq = 'select_place';
        $this->sdb->prepare($qq, 
                            'select 
                            aa.id, aa.version, aa.main_id, aa.citation_id, aa.sub_citation, aa.source_data, 
                            aa.rule_id, aa.language_id, aa.note
                            from scm as aa,
                            (select fk_id,max(version) as version from scm where fk_id=$1 and version<=$2 group by fk_id) as bb
                            where not is_deleted and aa.fk_id=bb.fk_id and aa.version=bb.version');

        $result = $this->sdb->execute($qq, array($did, $version));
        $row = $this->sdb->fetchrow($result);
        $this->sdb->deallocate($qq);
        return $row;
    }

    /** 
     * Insert meta record
     *
     * Inset meta related to the $fk_id. Table scm, php object SNACControlMetadata.
     *
     * Note: we do not use citation_id because citations are source, and source is not a controlled
     * vocabulary. Source is like date. Each source is indivualized for the record it relates to. To get the
     * citation, conceptuall select from source where scm.id==source.fk_id.
     *
     * Note: We do not save language_id because languages are objects, not a single vocabulary term like
     * $ruleID. The language is related back to this table where scm.id=language.fk_id and
     * scm.version=language.version. (Or something like that with version. It might be complicated.)
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param integer $id Record id of this table.
     *
     * @param string $subCitation
     *
     * @param string $sourceData
     *
     * @param integer $ruleID fk to vocaulary.id
     *
     * @param integer $languageID fk to vocaulary.id
     *
     * @param string $note
     *
     * @param integer $fkID fk to the relate table
     *
     * @param string $fkTable name of the related table 
     *
     */
    public function insertMeta($vhInfo, $id, $subCitation, $sourceData,
                               $ruleID, $note, $fkTable, $fkID)
    {
        if (! $id)
        {
            $id = $this->selectID();
        }
        $qq = 'select_place';
        $this->sdb->prepare($qq, 
                            'insert into scm 
                            (version, main_id, id, sub_citation, source_data, 
                            rule_id, note, fk_id, fk_table)
                            values ($1, $2, $3, $4, $5, $6, $7, $8, $9)');
        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'], 
                                            $vhInfo['main_id'],
                                            $id,
                                            $subCitation,
                                            $sourceData,
                                            $ruleID,
                                            $note,
                                            $fkID,
                                            $fkTable));
        $row = $this->sdb->fetchrow($result);
        $this->sdb->deallocate($qq);
        return $id;
    }

    /**
     * Get a geo_place record.
     * 
     * @param integer $gid A geo_place.id value.
     *
     * @return string[] A list of fields/value as list keys matching the database field names: latitude,
     * longitude, administrative_code, country_code, name, geoname_id.
     */
    public function selectGeo($gid)
    {
        $qq = 'select_geo_place';
        $this->sdb->prepare($qq, 'select * from geo_place where id=$1');
        $result = $this->sdb->execute($qq, array($gid));
        $row = $this->sdb->fetchrow($result);
        $this->sdb->deallocate($qq);
        return $row;
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
     * Insert an ID from records that were merged into this constellation. For the sake of convention, we put
     * the SQL columns in the same order as the function args.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param int $id The id of this record, otherid.id
     *
     * @param string $text The text of the SameAs object. 
     *
     * @param integer $typeID Vocabulary id foreign key for the type of this otherID. Probably the ids for
     * MergedRecord, viafID. From the SameAs object.
     *
     * @param string $uri The URI of the other record, probably the SNAC ARK as a URI/URL. From the SameAs
     * object.
     *
     */ 
    public function insertOtherID($vhInfo, $id, $text, $typeID, $uri)
    {
        if (! $id)
        {
            $id = $this->selectID();
        }
        $qq = 'insert_other_id';
        $this->sdb->prepare($qq,
                            'insert into otherid
                            (version, main_id, id, text, type, uri)
                            values
                            ($1, $2, $3, $4, $5, $6)');
        
        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['main_id'],
                                            $id,
                                            $text,
                                            $typeID,
                                            $uri));
        $this->sdb->deallocate($qq);
        return $id;
    }
    
    /** 
     * Insert (or update) a name into the database. Language and contributors are related table on name.id,
     * and the calling code is smart enough to call those insert functions for this name's language and
     * contributors. Our concern here is tightly focused on writing to table name. Nothing else.
     * 
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param string $original The original name string
     *
     * @param float $preference_score The preference score for ranking this as the preferred name. This
     * concept may not work in a culturally diverse environment
     *
     * @param integer $nameID A table id. If null we assume this is a new record an mint a new record version
     * from selectID().
     *
     */
    public function insertName($vhInfo, 
                               $original,
                               $preferenceScore,
                               $nameID)
    {
        if (! $nameID)
        {
            $nameID = $this->selectID();
        }
        $qq_1 = 'insert_name';
        $this->sdb->prepare($qq_1,
                            'insert into name
                            (version, main_id, original, preference_score, id)
                            values
                            ($1, $2, $3, $4, $5)');
        $result = $this->sdb->execute($qq_1,
                                      array($vhInfo['version'],
                                            $vhInfo['main_id'],
                                            $original,
                                            $preferenceScore,
                                            $nameID));
        $this->sdb->deallocate($qq_1);
        return $nameID;
    }
    
    /**
     * Insert a contributor record, related to name where contributor.name_id=name.id. This is a one-sided fk
     * relationship also used for date and language.
     *
     * Old: Contributor has issues. See comments in schema.sql. This will work for now.  Need to fix insert
     * name_contributor to keep the existing id values. Also, do not update if not changed. Implies a
     * name_contributor object with a $operation like everything else.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param integer $nameID Record id of related name
     *
     * @param string $name Name of the contributor
     *
     * @param integer $typeID Vocabulary fk id of the type of this contributor.
     *
     */
    public function insertContributor($vhInfo, $nameID, $name, $typeID)
    {
        $qq_2 = 'insert_contributor';
        $this->sdb->prepare($qq_2,
                            'insert into name_contributor
                            (version, main_id, name_id, short_name, name_type)
                            values
                            ($1, $2, $3, $4, $5)');
        $this->sdb->execute($qq_2,
                            array($vhInfo['version'],
                                  $vhInfo['main_id'],
                                  $nameID,
                                  $name,
                                  $typeID));
        $this->sdb->deallocate($qq_2);
    }
    
    
    /**
     * Insert into table function. The SQL returns the inserted id which is used when inserting a date into
     * table date_range. Function uses the same vocabulary terms as occupation.
     *
     * If the $id arg is null, get a new id. Always return $id.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param 
     *
     */
    public function insertFunction($vhInfo, $id, $type, $vocabularySource, $note, $term)
    {
        if (! $id)
        {
            $id = $this->selectID();
        }
        $qq = 'insert_function';
        $this->sdb->prepare($qq,
                            'insert into function
                            (version, main_id, id, function_type, vocabulary_source, note, function_id)
                            values
                            ($1, $2, $3, $4, $5, $6, $7)');
        $eArgs = array($vhInfo['version'],
                       $vhInfo['main_id'],
                       $id,
                       $type,
                       $vocabularySource,
                       $note,
                       $term);
        $result = $this->sdb->execute($qq, $eArgs);
        $id = $this->sdb->fetchrow($result)['id'];
        $this->sdb->deallocate($qq);
        return $id;
    }

    public function insertLanguage($vhInfo, $id, $languageID, $scriptID, $vocabularySource, $note, $fkTable, $fkID)
    {
        if (! $id)
        {
            $id = $this->selectID();
        }
        $qq = 'insert_language';
        $this->sdb->prepare($qq,
                            'insert into language
                            (version, main_id, id, language_id, script_id, vocabulary_source, note, fk_table, fk_id)
                            values
                            ($1, $2, $3, $4, $5, $6, $7, $8, $9)');
        $eArgs = array($vhInfo['version'],
                       $vhInfo['main_id'],
                       $id,
                       $languageID,
                       $scriptID,
                       $vocabularySource,
                       $note,
                       $fkTable,
                       $fkID);
        $result = $this->sdb->execute($qq, $eArgs);
        $this->sdb->deallocate($qq);
        return $id;
    }

    /**
     * Select for Language object.
     *
     * This is not language controlled vocabulary. That is in the vocabulary table. This table links vocab id
     * (language, script) to another table. Language objects are denormalized views of link and vocab tables.
     * 
     * Note: This always gets the max version (most recent) for a given fkID. (Really? What is $version?)
     * Published records (older than a specific edit) might show the edit (more recent) language. This is
     * untested.  fix.
     *
     * Select fields for a language object knowing a fkID value of the related table. This relies on the
     * language.fk_id==orig_table.id. $fkID is a foreign key of the record to which this language
     * applies. This (mostly) does not know or care what the other record is. Note that for the
     * "foreign-key-across-all-tables" to work, all the tables must use the same sequence (that is: id_seq).
     *
     * @param integer $fkID A foreign key to record in another table.
     *
     * @param integer $version The constellation version. For edits this is max version of the
     * constellation. For published, this is the published constellation version.
     *
     * @return string[] A list of location fields as list with keys matching the database field names.
     */ 
    public function selectLanguage($fkID, $version)
    {
        $qq = 'select_language';
        $this->sdb->prepare($qq,
                            'select aa.version, aa.main_id, aa.id, aa.language_id, aa.script_id, aa.vocabulary_source, aa.note
                            from language as aa,
                            (select fk_id,max(version) as version from language where fk_id=$1 and version<=$2 group by fk_id) as bb
                            where not is_deleted and aa.fk_id=bb.fk_id and aa.version=bb.version');
        $result = $this->sdb->execute($qq, array($fkID, $version));
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }
        $this->sdb->deallocate($qq);
        return $all;
    }




    
    /**
     * Insert into table subject. Data is currently only a string from the Constellation. If $id is null, get
     * a new record id. Always return $id.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param integer $termID Vocabulary foreign key for the term.
     *
     * @return no return value.
     * 
     */
    public function insertSubject($vhInfo, $id, $termID)
    {
        if (! $id)
        {
            $id = $this->selectID();
        }
        $qq = 'insert_subject';
        $this->sdb->prepare($qq,
                            'insert into subject
                            (version, main_id, id, term_id)
                            values
                            ($1, $2, $3, $4)');
        
        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['main_id'],
                                            $id,
                                            $termID));
        $this->sdb->deallocate($qq);
        return $id;
    }

    /**
     * Insert into table nationality. Data is currently only a string from the Constellation. If $id is null, get
     * a new record id. Always return $id.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param integer $termID Vocabulary foreign key for the term.
     *
     * @return no return value.
     * 
     */
    public function insertNationality($vhInfo, $id, $termID)
    {
        if (! $id)
        {
            $id = $this->selectID();
        }
        $qq = 'insert_nationality';
        $this->sdb->prepare($qq,
                            'insert into nationality
                            (version, main_id, id, term_id)
                            values
                            ($1, $2, $3, $4)');
        
        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['main_id'],
                                            $id,
                                            $termID));
        $this->sdb->deallocate($qq);
        return $id;
    }

    /**
     * Insert into table gender. Data is currently only a string from the Constellation. If $id is null, get
     * a new record id. Always return $id.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param integer $termID Vocabulary foreign key for the term.
     *
     * @return no return value.
     * 
     */
    public function insertGender($vhInfo, $id, $termID)
    {
        if (! $id)
        {
            $id = $this->selectID();
        }
        $qq = 'insert_gender';
        $this->sdb->prepare($qq,
                            'insert into gender
                            (version, main_id, id, term_id)
                            values
                            ($1, $2, $3, $4)');
        
        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['main_id'],
                                            $id,
                                            $termID));
        $this->sdb->deallocate($qq);
        return $id;
    }

    /**
     * Select text records. Several tables have identical structure so don't copy/paste, just call
     * this. DBUtils has code to turn the return values into objects in a Constellation object.
     *
     * Solve the multi-version problem by joining to a subquery.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @return string[][] Return list of an associative list with keys: id, version, main_id,
     * term_id. There may be multiple rows returned.
     * 
     */
    protected function selectTextCore($vhInfo, $table)
    {
        $approved_table = array('convention_declaration' => 1,
                                'structure_genealogy' => 1,
                                'general_context' => 1,
                                'mandate' => 1);
        if (! isset($approved_table[$table]))
        {
            /*
             * Trying something not approved is fatal. 
             */ 
            die("Tried to select on non-approved table: $table\n");
        }
        $qq = "select_$table";
        /*
         * String interpolation would require escaping $1 and $2 so just use sprintf() which always works.
         */ 
        $this->sdb->prepare($qq, 
                            sprintf(
                                'select
                                aa.id, aa.version, aa.main_id, aa.text
                                from %s aa,
                                (select id, max(version) as version from %s where version<=$1 and main_id=$2 group by id) as bb
                                where not aa.is_deleted and
                                aa.id=bb.id
                                and aa.version=bb.version', $table, $table));
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
     * Insert text records. Several tables have identical structure so don't copy/paste, just call
     * this. DBUtils has code to turn the return values into objects in a Constellation object.
     *
     * Solve the multi-version problem by joining to a subquery.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param integer $id Record id
     *
     * @param string $text Text value we're saving
     *
     * @param string $table One of the approved tables to which this data is being written. These tables are
     * identical except for the name, so this core code saves duplication. See also selectTextCore().
     *
     * @return string[][] Return list of an associative list with keys: id, version, main_id,
     * term_id. There may be multiple rows returned.
     * 
     */
    protected function insertTextCore($vhInfo, $id, $text, $table)
    {
        $approved_table = array('convention_declaration' => 1,
                                'structure_genealogy' => 1,
                                'general_context' => 1,
                                'mandate' => 1);
        if (! isset($approved_table[$table]))
        {
            /*
             * Trying something not approved is fatal. 
             */
            die("Tried to insert on non-approved table: $table\n");
        }
        $qq = "select_$table";
        $this->sdb->prepare($qq, 
                            sprintf(
                                'insert into %s
                                (version, main_id, id, text)
                                values
                                ($1, $2, $3, $4)', $table));
        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['main_id'],
                                            $id,
                                            $term));
        $this->sdb->deallocate($qq);
        return $id;
    }

    /**
     * Insert into table convention_declaration. Data is currently only a string from the Constellation. If
     * $id is null, get a new record id. Always return $id.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param string $text Text value we're saving.
     *
     * @return no return value.
     * 
     */
    public function insertConventionDeclaration($vhInfo, $id, $text)
    {
        return $this->insertTextCore($vhInfo, $id, $text, 'convention_declaration');
    }

        /**
     * Insert into table mandate. Data is currently only a string from the Constellation. If
     * $id is null, get a new record id. Always return $id.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param string $text Text value we're saving.
     *
     * @return no return value.
     * 
     */
    public function insertMandate($vhInfo, $id, $text)
    {
        return $this->insertTextCore($vhInfo, $id, $text, 'mandate');
    }

    /**
     * Insert into table structure_genealogy. Data is currently only a string from the Constellation. If $id
     * is null, get a new record id. Always return $id.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param string $text Text value we're saving.
     *
     * @return no return value.
     * 
     */
    public function insertStructureOrGenealogy($vhInfo, $id, $text)
    {
        return $this->insertTextCore($vhInfo, $id, $text, 'structure_genealogy');
    }

    /**
     * Insert into table structure_genealogy. Data is currently only a string from the Constellation. If $id
     * is null, get a new record id. Always return $id.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param string $text Text value we're saving.
     *
     * @return no return value.
     * 
     */
    public function insertGeneralContext($vhInfo, $id, $text)
    {
        return $this->insertTextCore($vhInfo, $id, $text, 'general_context');
    }

    /**
     *
     * Select StructureOrGenealogy records. DBUtils has code to turn the return values into objects in a Constellation object.
     *
     * Solve the multi-version problem by joining to a subquery.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @return string[][] Return list of an associative list with keys: id, version, main_id,
     * term_id. There may be multiple rows returned.
     * 
     */
    public function selectStructureOrGenealogy($vhInfo)
    {
        return $this->selectTextCore($vhInfo, 'structure_genealogy');
    }
    /**
     * Select mandate records. DBUtils has code to turn the return values into objects in a Constellation object.
     *
     * Solve the multi-version problem by joining to a subquery.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @return string[][] Return list of an associative list with keys: id, version, main_id,
     * term_id. There may be multiple rows returned.
     * 
     */
    public function selectMandate($vhInfo)
    {
        return $this->selectTextCore($vhInfo, 'mandate');
    }

    /**
     * Select GeneralContext records. DBUtils has code to turn the return values into objects in a
     * Constellation object.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @return string[][] Return list of an associative list with keys: id, version, main_id,
     * term_id. There may be multiple rows returned.
     * 
     */
    public function selectGeneralContext($vhInfo)
    {
        return $this->selectTextCore($vhInfo, 'general_context');
    }

    /**
     * Select conventionDeclaration records. DBUtils has code to turn the return values into objects in a
     * Constellation object.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @return string[][] Return list of an associative list with keys: id, version, main_id,
     * term_id. There may be multiple rows returned.
     * 
     */
    public function selectConventionDeclaration($vhInfo)
    {
        return $this->selectTextCore($vhInfo, 'convention_declaration');
    }

    /**
     * Insert a related identity aka table related_identity, aka constellation relation, aka cpf relation, aka
     * ConstellationRelation object. We first insert into related_identity saving the inserted record
     * id.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     * 
     * @param integer $targetID The constellation id of the related entity (aka the relation)
     * 
     * @param string $targetArkID The ARK of the related entity
     * 
     * @param string $targetEntityType The entity type of the target relation (aka the other entity aka the related entity)
     * 
     * @param $type Traditionally the xlink:arcrole of the relation (aka relation type, a controlled vocabulary)
     * 
     * @param $relationType The CPF relation type of this relationship, originally only used by AnF
     * cpfRelation@cpfRelationType. Probably xlink:arcrole should be used instead of this. The two seem
     * related and/or redundant.
     * 
     * @param $content Content of this relation
     * 
     * @param $note A note, perhaps a descriptive note about the relationship
     *
     * @return no return value.
     * 
     */
    public function insertRelation($vhInfo, 
                                   $targetID,
                                   $targetArkID,
                                   $targetEntityTypeID,
                                   $type,
                                   $relationType,
                                   $content,
                                   $note,
                                   $id)
    {
        if (! $id)
        {
            // If this is a new record, get a record id, else use the previously assigned id
            $id = $this->selectID();
        }
        $qq = 'insert_relation'; // aka insert_related_identity
        $this->sdb->prepare($qq,
                            'insert into related_identity
                            (version, main_id, related_id, related_ark, role, arcrole, 
                            relation_type, relation_entry, descriptive_note, id)
                            values
                            ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)');

        // Combine vhInfo and the remaining args into a big array for execute().
        $execList = array($vhInfo['version'], 
                          $vhInfo['main_id'],
                          $targetID,
                          $targetArkID,
                          $targetEntityTypeID,
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
     * @param integer $relationEntryType Vocab id value of the relation entry type, aka documentType aka xlink:role
     * @param integer $entryType Vocab id value of entry type aka relationEntry@localType
     * @param string $href A URI  aka xlink:href
     * @param integer $arcRole Vocabulary id value of the arc role aka xlink:arcrole
     * @param string $relationEntry Often the name of the relation aka relationEntry
     * @param string $objectXMLWrap Optional extra data, often an XML fragment aka objectXMLWrap
     * @param string $note A note aka descriptiveNote
     * @param integer $id The database record id 
     *
     * @return integer $id The record id, which might be new if this is the first insert for this resource relation.
     * 
     */
    public function insertResourceRelation($vhInfo,
                                           $relationEntryType, // aka documentType aka xlink:role
                                           $entryType, // relationEntry@localType
                                           $href,  // xlink:href
                                           $arcRole,  // xlink:arcrole
                                           $relationEntry, // relationEntry
                                           $objectXMLWrap, // objectXMLWrap
                                           $note, // descriptiveNote
                                           $id)
    {
        if (! $id)
        {
            $id = $this->selectID();
        }
        $qq = 'insert_resource_relation';
        $this->sdb->prepare($qq,
                            'insert into related_resource
                            (version, main_id, id, role, relation_entry_type, href, arcrole, relation_entry, 
                            object_xml_wrap, descriptive_note)
                            values
                            ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)');
        /* 
         * Combine vhInfo and the remaining args into a big array for execute().
         */
        $execList = array($vhInfo['version'], // 1
                          $vhInfo['main_id'], // 2
                          $id, // 3
                          $relationEntryType, // 4
                          $entryType, // 5
                          $href, // 6
                          $arcRole, // 7
                          $relationEntry, // 8
                          $objectXMLWrap, // 9
                          $note); // 10
        $this->sdb->execute($qq, $execList);
        $this->sdb->deallocate($qq);
        return $id;
    }

    /**
     * Get a single vocabulary record
     *
     * @param integer $termID The record id of a vocabulary term
     *
     * @return string[] A list with keys: id, type, value, uri, description
     *
     */
    public function selectTerm($termID)
    {
        $qq = 'sc';
        $this->sdb->prepare($qq, 
                            'select
                            id, type, value, uri, description
                            from vocabulary where $1=id');
        $result = $this->sdb->execute($qq, array($termID));
        $row = $this->sdb->fetchrow($result);
        $this->sdb->deallocate($qq);
        return $row;
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
     * This table has biogHist records (possibly multiple per constellation), and serves as a record for the
     * biogHist to relate to language via foreign key biog_hist.id. Note that language (like date) relies on
     * the fk from the original table to reside in the language table. Calling code will use a second function
     * to retrieve the langauge of each biogHist, that is: the language related to the biogHist.
     *
     * Note: Even though there is only one biogHist in a given language, eventually there may be multiple
     * translations, each in a different language. We just return all biogHist records that we find and let
     * the calling code figure it out. As of Jan 28 2016, Constellation.php defines private var $biogHists as
     * \snac\data\BiogHist[]. The Constellation is already supporting a list of biogHist.
     * 
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @return string[] A list of associative list with keys: version, main_id, id, text.
     * 
     */
    public function selectBiogHist($vhInfo)
    {
        $qq = 'sbh';
        $this->sdb->prepare($qq, 
                            'select
                            aa.version, aa.main_id, aa.id, aa.text
                            from biog_hist as aa,
                            (select main_id, max(version) as version from biog_hist where version<=$1 and main_id=$2 group by main_id) as bb
                            where not aa.is_deleted and
                            aa.main_id=bb.main_id
                            and aa.version=bb.version');
        /* 
         * Always use key names explicitly when going from associative context to flat indexed list context.
         */
        $result = $this->sdb->execute($qq, 
                                      array($vhInfo['version'],
                                            $vhInfo['main_id']));
        $rowList = array();
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }
        $this->sdb->deallocate($qq);
        return $rowList;
    }


    /** 
     *
     * Select flat list of distinct id values meeting the version and main_id constraint. Specifically a
     * helper function for selectOtherID(). This deals with the possibility that a given otherid.id may
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
     * Jan 28 2016 The query use to say "... and main_id=$2 and id=$3');" which is odd. We never constrain on
     * table.id that way. This appears to be old and incorrect code.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @return string[] Return an associative ist of otherid rows with keys: id, version, main_id, text, uri,
     * type, link_type. otherid.type is an integer fk id from vocabulary, not that we need to concern
     * ourselves with that here.
     * 
     */
    public function selectOtherID($vhInfo)
    {
        $matchingIDs = $this->matchORID($vhInfo);

        $qq = 'sorid';
        $this->sdb->prepare($qq, 
                            'select
                            id, version, main_id, text, uri, type
                            from otherid
                            where
                            version=(select max(version) from otherid where version<=$1)
                            and main_id=$2');

        $all = array();
        foreach ($matchingIDs as $orid)
        {
            $result = $this->sdb->execute($qq, array($vhInfo['version'], $vhInfo['main_id']));
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
                            aa.id, aa.version, aa.main_id, aa.subject_id
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
     * Select gender records. DBUtils has code to turn the return values into objects in a Constellation object.
     *
     * Solve the multi-version problem by joining to a subquery.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @return string[][] Return list of an associative list with keys: id, version, main_id,
     * term_id. There may be multiple rows returned.
     * 
     */
    public function selectGender($vhInfo)
    {
        $qq = 'select_gender';
        $this->sdb->prepare($qq, 
                            'select
                            aa.id, aa.version, aa.main_id, aa.term_id
                            from gender aa,
                            (select id, max(version) as version from gender where version<=$1 and main_id=$2 group by id) as bb
                            where not aa.is_deleted and
                            aa.id=bb.id
                            and aa.version=bb.version');
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
     * Select nationality records. DBUtils has code to turn the return values into objects in a Constellation
     * object.
     *
     * Solve the multi-version problem by joining to a subquery.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @return string[][] Return list of an associative list with keys: id, version, main_id,
     * term_id. There may be multiple rows returned.
     * 
     */
    public function selectNationality($vhInfo)
    {
        $qq = 'select_gender';
        $this->sdb->prepare($qq, 
                            'select
                            aa.id, aa.version, aa.main_id, aa.term_id
                            from nationality aa,
                            (select id, max(version) as version from nationality where version<=$1 and main_id=$2 group by id) as bb
                            where not aa.is_deleted and
                            aa.id=bb.id
                            and aa.version=bb.version');
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
                            aa.id, aa.version, aa.main_id, aa.note, aa.vocabulary_source, aa.occupation_id
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
                            aa.role,
                            aa.arcrole
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
    public function selectResourceRelation($vhInfo)
    {
        $qq = 'select_related_resource';
        $this->sdb->prepare($qq,
                            'select
                            aa.id, aa.version, aa.main_id,
                            aa.relation_entry_type, aa.href, aa.relation_entry, aa.object_xml_wrap, aa.descriptive_note,
                            aa.role,
                            aa.arcrole
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
        $qq = 'select_function';
        $this->sdb->prepare($qq,
                            'select
                            aa.id, aa.version, aa.main_id, aa.function_type, aa.vocabulary_source, aa.note,
                            aa.function_id
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
      * Language is a related table where language.fk_id=name.id. Contributor is a related table where
      * contributor.name_id=name.id. See selectLanguage(), selectContributor() both called in DBUtil.
      *
      * This code only knows about selecting from table name. DBUtil knows that to build a constellation it is
      * necessary to also get information related to name.
      *
      * @param string[] $vhInfo with keys version, main_id.
      *
      * @return string[][] Return a list of lists. The inner list has keys: id, version, main_id, original,
      * preference_score. 
      */
    public function selectName($vhInfo)
    {
        $qq_1 = 'selname';
        $this->sdb->prepare($qq_1,
                            'select
                            aa.is_deleted,aa.id,aa.version, aa.main_id, aa.original, aa.preference_score
                            from name as aa,
                            (select id,max(version) as version from name where version<=$1 and main_id=$2 group by id) as bb
                            where
                            aa.id = bb.id and not aa.is_deleted and 
                            aa.version = bb.version order by preference_score,id');
        
        $name_result = $this->sdb->execute($qq_1,
                                           array($vhInfo['version'],
                                                 $vhInfo['main_id']));
        $all = array();
        while($name_row = $this->sdb->fetchrow($name_result))
        {
            array_push($all, $name_row);
        }
        $this->sdb->deallocate($qq_1);
        return $all;
    }

    // Contributor has issues. See comments in schema.sql. This will work for now.
    // Get each name, and for each name get each contributor. 

    /*
     * Select contributor of a specific name, where name_contributor.name_id=name.id.
     *
     * @param integer $nameID The foreign key record id from name.id
     *
     * @param integer $version The version number.
     *
     * @return string[] List of list, one inner list per contributor keys: id, version, main_id, type, name, name_id
     * 
     * The new query is based on selectDate.
     *
     * This is the old query. Unclear what the intent was, but it looks like it would heap all the
     * contributors together, as opposed to per-name contributors which is what I recollect that we want.
     *
     * 'select 
     * aa.id,aa.version, aa.main_id, aa.name_id, aa.short_name,aa.name_type
     * from  name_contributor as aa,
     * (select id, max(version) as version from name_contributor where version<=$1 and main_id=$2 group by id) as bb
     * where not aa.is_deleted and
     * aa.id=bb.id
     * and aa.version=bb.version
     * and aa.name_id=$3');
     *
     */  
    public function selectContributor($nameID, $version)
    {
        $qq_2 = 'selcontributor';
        $this->sdb->prepare($qq_2,
                            'select 
                            aa.id, aa.version, aa.main_id, aa.short_name, aa.name_type, aa.name_id
                            from name_contributor as aa,
                            (select name_id,max(version) as version from name_contributor where name_id=$1 and version<=$2 group by name_id) as bb
                            where not is_deleted and aa.name_id=bb.name_id and aa.version=bb.version');
        $contributor_result = $this->sdb->execute($qq_2, array($nameID, $version));
        $all = array();
        while($contributor_row = $this->sdb->fetchrow($contributor_result))
        {
            array_push($all, $contributor_row);
        }
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
            $nRow = $this->selectName(array('version' => $row['version'],
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

    /**
     * Search Vocabulary
     *
     * This method allows searching the vocabulary table for a given type and value
     *
     * @param string $term The "type" term for what type of vocabulary to search
     * @param string $query The string to search through the vocabulary
     */
    public function searchVocabulary($term, $query)
    {
        $result = $this->sdb->query('select id,value 
                                    from vocabulary 
                                    where type=$1 and value ilike $2 order by value asc limit 100;',
                                    array($term, "%".$query."%"));
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }
        return $all;
        
    }
    
    /**
     * Select all vocabulary from the database.  This returns the vocabulary in a
     * 2D array, with keys:
     *  * id
     *  * type
     *  * value
     *
     * @return string[][] Multi-dimensional array of vocabulary terms
     */
    public function selectAllVocabulary() {
        $selectSQL = "select id, type, value, uri, description from vocabulary;";
        $result = $this->sdb->query($selectSQL, array());
        $allVocab = array();
        while ($row = $this->sdb->fetchrow($result)) {
            array_push($allVocab, $row);
        }
        return $allVocab;
    }

}

