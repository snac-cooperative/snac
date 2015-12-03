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

  // namespace is confusing. Are they path relative? Are they arbitrary? How much of the leading directory
  // tree can be left out of the namespace? I just based this file's namespace on the parser example below.

  // namespace snac\util;
  //       src/snac/util/EACCPFParser.php

namespace snac\server\database;

/**
 * Low level SQL methods. These methods include SQL queries. This is the only place in the code where SQL is
 * allowed (by convention, of course). Ideally, there minimal non-SQL php here. Interact with the database,
 * and nothing more. Send the data up to higher level classes for everything else.
 *
 * @author Tom Laudeman
 *        
 */


class SQL
{

    /*
     * The constructor makes the outside $db a local variable. I did this out of a general sense of avoiding
     * globals, but I'm unclear if this is really any better than using a globale $db variable. $db is
     * critical to the application, and is read-only after intialization. Breaking it or changing it in any
     * way will break everything, whether global or not. Passing it in here to get local scope doesn't meet
     * any clear need.
     *
     * Interesting that $this->sdb simply auto-vivifies a private variable.
     *
     * @param DatabaseConnector $db A working, initialized DatabaseConnector object.
     *
     * 
    */

    public function __construct($db)
    {
        $this->sdb = $db;
    }

    /*
     * Insert a record into table source.
     *
     * @param 
     *
     * 
     */

    public function insertSource($vhInfo, $href)
    {
        $qq = 'insert_source';
        $this->sdb->prepare($qq, 
                            'insert into source 
                            (version, main_id, href)
                            values 
                            ($1, $2, $3)');
        $this->sdb->execute($qq,
                            array($vhInfo['id'],
                                  $vhInfo['main_id'],
                                  $href));
        $this->sdb->deallocate($qq);
    }

    public function insertOccupation($vhInfo, $term, $vocabularySource, $dates, $note)
    {
        $qq = 'insert_occupation';
        $this->sdb->prepare($qq, 
                            'insert into occupation
                            (version, main_id, occupation_id, note)
                            values 
                            ($1, $2, (select id from vocabulary where type=\'occupation\' and value=regexp_replace($3, \'^.*#\', \'\')), $4)
                            returning id');
        $result = $this->sdb->execute($qq,
                                      array($vhInfo['id'],
                                            $vhInfo['main_id'],
                                            $term,
                                            $note));
        $id = $this->sdb->fetchrow($result)['id'];
        $this->sdb->deallocate($qq);
        foreach ($dates as $single_date)
        {
            $date_fk = $this->insertDate($vhInfo, $single_date, 'occupation', $id);
        }
    }



    function getAppUserInfo($userid)
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
    
    public function insertVersionHistory($userid, $role, $status, $note)
    {
        $qq = 'insert_version_history';
        // We need version_history.id and version_history.main_id returned.
        $this->sdb->prepare('insert_version_history', 
                            'insert into version_history 
                            (user_id, role_id, status, is_current, note)
                            values 
                            ($1, $2, $3, $4, $5)
                            returning id, main_id;');

        $result = $this->sdb->execute('insert_version_history', array($userid, $role, $status, true, $note));
        $vhInfo = $this->sdb->fetchrow($result);
        $this->sdb->deallocate('insert_version_history');
        return $vhInfo;
    }

    /* 
     * SNACDate.php has fromDateOriginal and toDateOriginal, but the CPF lacks date components, and the
     * database "original" is only the single original string.
     *
     * Need to add later:
     * 
     *  $date->getMissingFrom(),
     *  $date->getMissingTo(),
     *  $date->getToPresent(),
     *
     *
     */
    public function insertDate($vhInfo, $date, $fk_table, $fk_id)
    {
        printf("from_type: %s to_type: %s\n%s\n",
               $date->getFromType(),
               $date->getToType(),
               $date->toJSON());

        $qq = 'insert_date';
        $this->sdb->prepare($qq, 
                            'insert into date_range
                            (version, main_id, is_range, from_date, from_type, from_bc, from_not_before, from_not_after,
                            to_date, to_type, to_bc, to_not_before, to_not_after, original, fk_table, fk_id)
                            values
                            ($1, $2, $3, $4, 
                            (select id from vocabulary where type=\'date_type\' and value=regexp_replace($5, \'^.*#\', \'\')),
                            $6, $7, $8, $9,
                            (select id from vocabulary where type=\'date_type\' and value=regexp_replace($10, \'^.*#\', \'\')),
                            $11, $12, $13, $14, $15, $16)
                            returning id');

       $result = $this->sdb->execute($qq,
                                     array($vhInfo['id'], 
                                           $vhInfo['main_id'],
                                           $this->sdb->boolToPg($date->getIsRange()),
                                           $date->getFromDate(),
                                           $date->getFromType(),
                                           $this->sdb->boolToPg($date->getFromBc()),
                                           $date->getFromRange()['notBefore'],
                                           $date->getFromRange()['notAfter'],
                                           $date->getToDate(),
                                           $date->getToType(),
                                           $this->sdb->boolToPg($date->getToBc()),
                                           $date->getToRange()['notBefore'],
                                           $date->getToRange()['notAfter'],
                                           $date->getFromDateOriginal() . ' - ' . $date->getToDateOriginal(),
                                           $fk_table,
                                           $fk_id));

       $row = $this->sdb->fetchrow($result);
       $this->sdb->deallocate($qq);
       return $row['id'];
    }


    // This date select relies on the date.id being in the original table.
    // 
    // The other date select function would be by original.id=date.fk_id. Maybe we only need by date.fk_id.

    public function selectDate($did)
    {
        $qq = 'select_date';
        $this->sdb->prepare($qq, 
                            'select 
                            id, version, main_id, is_range, from_date, from_bc, from_not_before, from_not_after,
                            to_date, to_bc, to_not_before, to_not_after, original, fk_table, fk_id,
                            (select value from vocabulary where id=from_type) as from_type,
                            (select value from vocabulary where id=to_type) as to_type
                            from date_range where fk_id=$1');


        $result = $this->sdb->execute($qq, array($did));
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }

        $this->sdb->deallocate($qq);
        return $all;
    }

    
    // biogHist is a string, not array. 

    //  language, languageCode, script, scriptCode

    public function insertNrd($vhInfo, $existDates, $arg_list)
    {
        $qq = 'insert_nrd';
        $this->sdb->prepare($qq, 
                            'insert into nrd
                            (version, main_id, ark_id, entity_type, biog_hist, nationality, 
                            gender, general_context, structure_or_genealogy, mandate, convention_declaration,
                            language, language_code, script, script_code)
                            values
                            ($1, $2, $3,
                            (select id from vocabulary where type=\'entity_type\' and value=regexp_replace($4, \'^.*#\', \'\')),
                            $5,
                            (select id from vocabulary where type=\'nationality\' and value=regexp_replace($6, \'^.*#\', \'\')),
                            (select id from vocabulary where type=\'gender\' and value=regexp_replace($7, \'^.*#\', \'\')),
                            $8, $9, $10, $11, $12,
                            (select id from vocabulary where type=\'language_code\' and value=regexp_replace($13, \'^.*#\', \'\')),
                            $14,
                            (select id from vocabulary where type=\'script_code\' and value=regexp_replace($15, \'^.*#\', \'\')))
                            returning id');
        
        // Combine vhInfo and the remaining args into a big array for execute().
        $execList = array($vhInfo['id'], $vhInfo['main_id']);

        foreach ($arg_list as $arg)
        {
            array_push($execList, $arg);
        }
                                                                
        $result = $this->sdb->execute($qq, $execList);

        $id = $this->sdb->fetchrow($result)['id'];
        $this->sdb->deallocate($qq);
        foreach ($existDates as $singleDate)
        {
            $date_fk = $this->insertDate($vhInfo, $singleDate, 'nrd', $id);
        }
    }

    public function insertOtherID($vhInfo, $type, $href)
    {
        $qq = 'insert_other_id';
        $this->sdb->prepare($qq,
                            'insert into otherid
                            (version, main_id, other_id, link_type)
                            values
                            ($1, $2, $3, (select id from vocabulary where type=\'record_type\' and value=regexp_replace($4, \'^.*#\', \'\')))');
        
        $result = $this->sdb->execute($qq,
                                      array($vhInfo['id'],
                                            $vhInfo['main_id'],
                                            $href,
                                            $type));
        $this->sdb->deallocate($qq);
    }
    
    // Need to return the name.id so we can used it as fk for inserting related records
    public function insertName($vhInfo, $original, $preferenceScore, $contributors, $language, $scriptCode, $useDates)
    {
        $qq_1 = 'insert_name';
        $qq_2 = 'insert_contributor';
        $this->sdb->prepare($qq_1,
                            'insert into name
                            (version, main_id, original, preference_score, language, script_code)
                            values
                            ($1, $2, $3, $4,
                            (select id from vocabulary where type=\'language\' and value=regexp_replace($5, \'^.*#\', \'\')),
                            (select id from vocabulary where type=\'scriptCode\' and value=regexp_replace($6, \'^.*#\', \'\')))
                            returning id');
        
        $result = $this->sdb->execute($qq_1,
                                      array($vhInfo['id'],
                                            $vhInfo['main_id'],
                                            $original,
                                            $preferenceScore,
                                            $language,
                                            $scriptCode));
        $row = $this->sdb->fetchrow($result);
        $name_id = $row['id'];

        // Contributor has issues. See comments in schema.sql. This will work for now.

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
                                array($vhInfo['id'],
                                      $vhInfo['main_id'],
                                      $name_id,
                                      $contrib['contributor'],
                                      $contrib['type']));
        }

        $this->sdb->deallocate($qq_1);
        $this->sdb->deallocate($qq_2);
    }
    
    
    /*
     * Insert into table function. The SQL returns the inserted id which is used when inserting a date into
     * table date_range. Function uses the same vocabulary terms as occupation.
     *
     * @param $vhInfo associative list with keys: version, main_id
     *
     * @param $argList list with keys:
     * 
     * @param $dates list of list of date keys suitable for insertDate()
     *
     * @return no return value.
     * 
     */
    public function insertFunction($vhInfo, $argList, $dates)
    {
        $qq = 'insert_function';
        $this->sdb->prepare($qq,
                            'insert into function
                            (version, main_id, function_type, vocabulary_source, note, function_id)
                            values
                            ($1, $2, $3, $4, $5,
                            (select id from vocabulary where type=\'occupation\' and value=regexp_replace($6, \'^.*#\', \'\')))
                            returning id');
        
        /* 
         * Initialize $eArgs with $vhInfo, then push the rest of the args onto the execute list. If you decide to
         * sanity check $argList, put the checks here, and if you want to change list element order, do that
         * here too. Keep in mind that our philosophy is for DBUtils to know about php objects and SQL fields,
         * but not how to do SQL queries. Class SQL is intentionally minimal, knowing only enough to do queries.
         */
        $eArgs = $vhInfo;
        foreach ($argList as $arg)
        {
            array_push($eArgs, $arg);
        }
        
        $result = $this->sdb->execute($qq, $eArgs);
        $id = $this->sdb->fetchrow($result)['id'];
        $this->sdb->deallocate($qq);

        /* 
         * This would more simpler and robust if foreach() silently ignored nulls and any non-array args. The
         * surrounding if() wouldn't be necessary. Oh well. Feel free to add more sanity checks to the if()
         * statement. Maybe gettype() array, and check get_class() on each array element. We need a date list
         * wrapper function that we can call everywhere.
         */
        if ($dates and count($dates)>=1)
        {
            foreach ($dates as $single_date)
            {
                $date_fk = $this->insertDate($vhInfo, $single_date, 'function', $id);
            }
        }
    }

    
    /*
     * Insert into table subject. Data is currently only a string from the Constellation.
     *
     * @param $vhInfo associative list with keys: version, main_id
     *
     * @param $term string that is a subject
     *
     * @return no return value.
     * 
     */
    public function insertSubject($vhInfo, $term)
    {
        $qq = 'insert_subject';
        $this->sdb->prepare($qq,
                            'insert into subject
                            (version, main_id, subject_id)
                            values
                            ($1, $2, (select id from vocabulary where type=\'subject\' and value=regexp_replace($3, \'^.*#\', \'\')))');
        
        $result = $this->sdb->execute($qq,
                                      array($vhInfo['id'],
                                            $vhInfo['main_id'],
                                            $term));
        $this->sdb->deallocate($qq);
    }

    /*
     * Insert a related identity aka table related_identity, aka constellation relation, aka cpf relation, aka
     * ConstellationRelation object. We first insert into related_identity saving the inserted record
     * id. After that, we insert date_range records with related via saved related_record.id. See insertDate().
     *
     * @param vhInfo associative list with keys: version, main_id
     * 
     * @param $dates list of list of date_range keys suitable to pass to insertDate(). Yes, list of list in order to support multiple dates.
     *
     * @param $argList list of keys: related_id, related_ark, role, arcrole, relation_type, relation_entry,
     * descriptive_note. We assume that DBUtils knows the php to sql field translation. Keys must occur in the noted order.
     *
     * @return no return value.
     * 
     */
    public function insertRelation($vhInfo, $dates, $argList)
    {
        $qq = 'insert_related_identity';
        $this->sdb->prepare($qq,
                            'insert into related_identity
                            (version, main_id, related_id, related_ark, role, arcrole, relation_type, relation_entry, descriptive_note)
                            values
                            ($1, $2, $3, $4,
                            (select id from vocabulary where type=\'entity_type\' and value=regexp_replace($5, \'^.*#\', \'\')),
                            (select id from vocabulary where type=\'relation_type\' and value=regexp_replace($6, \'^.*#\', \'\')),
                            $7, $8, $9)
                            returning id');

        // Combine vhInfo and the remaining args into a big array for execute(). Start by initializing the
        // first two elements of the array with id and main_id from vhInfo.
        $execList = array($vhInfo['id'], $vhInfo['main_id']);
        foreach ($argList as $arg)
        {
            array_push($execList, $arg);
        }
        
        $result = $this->sdb->execute($qq, $execList);
        $row = $this->sdb->fetchrow($result);

        // Nov 19 2015 Use a unique var name for the returned id. It is more typing, but should preclude using
        // some variable of the same name that happens to be in scope. It also makes the intention absolutely
        // clear. Might use $row['id'] in the one place below we need the variable. Hmmm.

        $relationId = $row['id'];
        $this->sdb->deallocate($qq);

        foreach ($dates as $singleDate)
        {
            $date_fk = $this->insertDate($vhInfo, $singleDate, 'related_identity', $relationId);
        }

    }

    /*
     * Insert into table related_resource using data from php ResourceRelation object. It is assumed that the
     * calling code in DBUtils knows the php to sql fields. Note keys in $argList have a fixed order.
     *
     * @param $vhInfo associative list with keys: version, main_id
     *
     * @param $argList list with keys: role, relation_entry_type, arcrole, relation_entry, object_xml_wrap,
     * decriptive_note. These elements must occur in this order, and they are not sanity checked. The foreach
     * before execute() simply copies all of them into a list to pass to execute(). If there is a problem, but
     * the sanity check in the foreach loop.
     *
     * @return no return values
     * 
     */
    public function insertResourceRelation($vhInfo, $argList)
    {
        $qq = 'insert_resource_relation';
        $this->sdb->prepare($qq,
                            'insert into related_resource
                            (version, main_id, role, relation_entry_type, href, arcrole, relation_entry, object_xml_wrap, descriptive_note)
                            values
                            ($1, $2,
                            (select id from vocabulary where type=\'document_type\' and value=regexp_replace($3, \'^.*#\', \'\')),
                            $4, $5,
                            (select id from vocabulary where type=\'document_role\' and value=regexp_replace($6, \'^.*#\', \'\')),
                            $7, $8, $9)');

        // Combine vhInfo and the remaining args into a big array for execute(). Start by initializing the
        // first two elements of the array with id and main_id from vhInfo.
        $execList = array($vhInfo['id'], $vhInfo['main_id']);
        foreach ($argList as $arg)
        {
            array_push($execList, $arg);
        }
        
        $this->sdb->execute($qq, $execList);
        $this->sdb->deallocate($qq);
    }


    /*
     *
     * Select from table nrd which is philosophically the central constellation table. In actual
     * implementation, table version_history is the center of everything.
     *
     * It is intentional that the fields are not retrieved in any particular order because the row will be
     * saved as an associative list. That allows us to write the sql query in a more legible format.
     *
     *
     * @param vhInfo associative list with keys: version, main_id
     *
     * @return  
     * 
     * 
     */
    public function selectConstellation($vhInfo) // $version, $main_id)
    {
        $qq = 'sc';
        $this->sdb->prepare($qq, 
                            'select
                            id,version,main_id,biog_hist,general_context,structure_or_genealogy, mandate, convention_declaration,
                            ark_id,language,script,
                            (select value from vocabulary where vocabulary.id=entity_type) as entity_type,
                            (select value from vocabulary where vocabulary.id=nationality) as nationality,
                            (select value from vocabulary where vocabulary.id=gender) as gender,
                            (select value from vocabulary where vocabulary.id=language_code) as language_code,
                            (select value from vocabulary where vocabulary.id=script_code) as script_code
                            from nrd
                            where
                            version=(select max(version) from nrd where version<=$1)
                            and main_id=$2');

        $result = $this->sdb->execute($qq, $vhInfo);
        $row = $this->sdb->fetchrow($result);
        $this->sdb->deallocate($qq);
        return $row;
    }

    /* 
     *
     * Select flat list of distinct id values meeting the version and main_id constraint. Specifically a
     * helper function for selectOtherRecordIDs(). This deals with the possibility that a given otherid.id may
     * have several versions while other otherid.id values are different (and single) versions.
     *
     * Nov 23 2015: I figured out how to do a multi-version join with sub query. See below in
     * selectSubjects(). Doing the multi-version sub query probably makes this function obsolete.
     * 
     * @param vhInfo associative list with keys: version, main_id
     *
     * @return string[] list of record id values meeting the version and main_id constriants.
     * 
     */
    public function matchORID($vhInfo) // $version, $main_id)
    {
        // $matchingIDs = matchORID($version, $main_id);

        $qq = 'morid';
        $this->sdb->prepare($qq, 
                            'select
                            distinct(id)
                            from otherid
                            where
                            version=(select max(version) from otherid where version<=$1 and main_id=$2)
                            and main_id=$2');

        $result = $this->sdb->execute($qq, $vhInfo); // array($version, $main_id));
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row['id']);
        }
        $this->sdb->deallocate($qq);
        return $all;
    }


    /* 
     * select other IDs which were originally ID values of merged records. DBUtils has code that adds an otherRecordID to a Constellation object.
     * 
     * Assume unique id in vocab, so don't need extra constraint type='record_type'
     * Nov 23 2015: see multi-version join with sub query below in selectSubjects()
     *
     * @param vhInfo associative list with keys: version, main_id
     *
     * @return a list of otherid rows
     * 
     */
    public function selectOtherRecordIDs($vhInfo)
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

    
    /*
     *
     * Select subjects. DBUtils has code to turn the return values into subjects in a Constellation object.
     *
     * Solve the multi-version problem by joining to a subquery.
     *
     * @param vhInfo associative list with keys: version, main_id
     *
     * @return list with keys: id, version, main_id, subject_id 
     * 
     */
    public function selectSubjects($vhInfo) // $version, $main_id)
    {
        $qq = 'ssubj';
        $this->sdb->prepare($qq, 
                            'select
                            subject.id, subject.version, subject.main_id,
                            (select value from vocabulary where id=subject_id) as subject_id
                            from subject,
                            (select id, max(version) as version from subject where version<=$1 and main_id=$2 group by id) as aa
                            where
                            subject.id=aa.id
                            and subject.version=aa.version');
        $all = array();
        $result = $this->sdb->execute($qq, $vhInfo);
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }
        $this->sdb->deallocate($qq);
        return $all;
    }


    /* 
     *
     * Select occupation, returning a list of lists. Code in DBUtils foreach's over the outer list, turning
     * each inner list into an Occupation object.
     *
     * Nov 24 2015 New convention: the table we're working on is 'aa', and the subquery is 'bb'. This makes
     * the query more of a standard template.  Assuming this works and is a good idea, we should port this to
     * all the other select queries.
     *
     * @param vhInfo associative list with keys: version, main_id
     *
     * @return list of lists. Inner list has keys: id, version, main_id, not, vocabulary_source, occupation_id
     * 
     */

    public function selectOccupations($vhInfo) // $version, $main_id)
    {
        $qq = 'socc';
        $this->sdb->prepare($qq, 
                            'select
                            aa.id, aa.version, aa.main_id, aa.note, aa.vocabulary_source,
                            (select value from vocabulary where id=aa.occupation_id) as occupation_id
                            from occupation as aa,
                            (select id, max(version) as version from occupation where version<=$1 and main_id=$2 group by id) as bb
                            where
                            aa.id=bb.id
                            and aa.version=bb.version');
        $all = array();
        $result = $this->sdb->execute($qq, $vhInfo); // array($version, $main_id));
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }
        $this->sdb->deallocate($qq);
        return $all;
    }

    /*
     * Select a related identity (akd cpf relation). Code in DBUtils turns the returned array into a ConstellationRelation object. 
     *
     * @param $vhInfo associative list with keys: 'version', 'main_id'
     *
     * @return list of lists. There may be multiple relations. Each relation has keys: id, version, main_id,
     * related_id, related_ark, relation_entry, descriptive_node, relation_type, role, arcrole, date. Date is
     * an associative list with keys from table date_range. See selectDate().
     * 
     */ 
    public function selectRelation($vhInfo) // $version, $main_id)
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
                            where
                            aa.id=bb.id
                            and aa.version=bb.version');

        $result = $this->sdb->execute($qq, $vhInfo); // array($version, $main_id));
        $all = array();
        while ($row = $this->sdb->fetchrow($result))
        {
            $relationId = $row['id'];
            $dateList = $this->selectDate($relationId);
            $row['date'] = array();
            if (count($dateList)>=1)
            {
                $row['date'] = $dateList[0];
            }
            if (count($dateList)>1)
            {
                printf("Warning: more than one date for a related identity. count: %s\n", count($dateList));
            }
            array_push($all, $row);
        }
        $this->sdb->deallocate($qq);
        return $all;
    }

    /*
     * select related archival resources given $vhInfo 'version' and 'main_id'. Code in DBUtils knows how to
     * turn the return value into a pgp ResourceRelation object.
     *
     * @param array $vhInfo 'version' and 'main_id'.
     *
     * @return array Keys: id, version, main_id, relation_entry_type, href, relation_entry, object_xml_wrap, descriptive_note, role, arcrole
     *
     */ 
    public function selectRelatedResources($vhInfo) // $version, $main_id)
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
                            where
                            aa.id=bb.id
                            and aa.version=bb.version');

        $result = $this->sdb->execute($qq, $vhInfo); // array($version, $main_id));
        $all = array();
        while ($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }
        $this->sdb->deallocate($qq);
        return $all;
    }

    /*
     * Select all functions for the given version and main_id. Code in DBUtils turns the return value into a
     * SNACFunction object.
     *
     * @param array $vhInfo list with keys 'version', 'main_id'
     *
     * @return array('id', 'version', 'main_id', 'function_type', 'note', 'date' => assoc array of date info from selectDate()
     *
     */ 
    public function selectFunctions($vhInfo) // $version, $main_id)
    {
        $qq = 'select_related_resource';
        $this->sdb->prepare($qq,
                            'select
                            aa.id, aa.version, aa.main_id, aa.function_type, aa.vocabulary_source, aa.note,
                            (select value from vocabulary where id=aa.function_id) as function_id
                            from function as aa,
                            (select id, max(version) as version from function where version<=$1 and main_id=$2 group by id) as bb
                            where
                            aa.id=bb.id
                            and aa.version=bb.version');

        $result = $this->sdb->execute($qq, $vhInfo); // array($version, $main_id));
        $all = array();
        while ($row = $this->sdb->fetchrow($result))
        {
            $dateList = $this->selectDate($row['id']);
            $row['date'] = array();
            if (count($dateList)>=1)
            {
                $row['date'] = $dateList[0];
            }
            if (count($dateList)>1)
            {
                printf("Warning: more than one date for a function. count: %s\n", count($dateList));
            }
            array_push($all, $row);
        }
        $this->sdb->deallocate($qq);
        return $all;
    }


     /* 
      * Select all names for the given version and main_id. Code in DBUtils turns each returned list into a
      * NameEntry object.
      *
      * @param array $vhInfo with keys version, main_id.
      *
      * @return array('original'=>"",
      * 		     'language'=>"",
      *              'script_code'=>"",
      *              'preference_score'=>"",
      *              'contributors' =>  array('name_type'=>"", 'short_name'=>""))
      */
    public function selectNameEntries($vhInfo) // $version, $main_id)
    {
        $qq_1 = 'selname';
        $qq_2 = 'selcontributor';
        $this->sdb->prepare($qq_1,
                            'select
                            name.id,name.version, main_id, original, preference_score,
                            (select value from vocabulary where id=language) as language,
                            (select value from vocabulary where id=script_code) as script_code
                            from name,
                            (select id, max(version) as version from name where version<=$1 and main_id=$2 group by id) as aa
                            where
                            name.id=aa.id
                            and name.version=aa.version');
        
        $this->sdb->prepare($qq_2,
                            'select 
                            name_contributor.id,name_contributor.version, main_id, name_id, short_name,
                            (select value from vocabulary where id=name_type) as name_type
                            from  name_contributor,
                            (select id, max(version) as version from name_contributor where version<=$1 and main_id=$2 group by id) as aa
                            where
                            name_contributor.id=aa.id
                            and name_contributor.version=aa.version
                            and name_contributor.name_id=$3');
        
        $name_result = $this->sdb->execute($qq_1, $vhInfo); // array($version,$main_id));
        
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



    /* 
     * This is used for testing. Not really random. Get a record that has a date_range record. The query
     * doesn't need to say date_range.fk_id since fk_is is unique to date_range, but it makes the join
     * criteria somewhat more obvious.
     * 
     * @return array(id, version, main_id) for a record that has a date_range
     */
    public function randomConstellationID()
    {
        $qq = 'rcid';
        $this->sdb->prepare($qq, 
                            'select nrd.id, version_history.id as version, version_history.main_id
                            from nrd,date_range, version_history
                            where
                            nrd.id=date_range.fk_id and
                            nrd.main_id=version_history.main_id and
                            nrd.version=version_history.id
                            limit 1');
    
        $result = $this->sdb->execute($qq, array());
        $row = $this->sdb->fetchrow($result);
        $this->sdb->deallocate($qq);
        return array($row['id'], $row['version'], $row['main_id']);
    }



}

