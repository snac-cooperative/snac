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
    public function __construct($db)
    {
        $this->sdb = $db;
    }

    public function insertSource($vh_info, $href)
    {
        $qq = 'insert_source';
        $this->sdb->prepare($qq, 
                            'insert into source 
                            (version, main_id, href)
                            values 
                            ($1, $2, $3)');
        $this->sdb->execute($qq,
                            array($vh_info['id'],
                                  $vh_info['main_id'],
                                  $href));
        $this->sdb->deallocate($qq);
    }

    public function insertOccupation($vh_info, $term, $vocabularySource, $dates, $note)
    {
        $qq = 'insert_occupation';
        $this->sdb->prepare($qq, 
                            'insert into occupation
                            (version, main_id, occupation_id, note)
                            values 
                            ($1, $2, (select id from vocabulary where type=\'occupation\' and value=regexp_replace($3, \'^.*#\', \'\')), $4)
                            returning id');
        $result = $this->sdb->execute($qq,
                                      array($vh_info['id'],
                                            $vh_info['main_id'],
                                            $term,
                                            $note));
        $id = $this->sdb->fetchrow($result)['id'];
        $this->sdb->deallocate($qq);
        foreach ($dates as $single_date)
        {
            $date_fk = $this->insertDate($vh_info, $single_date, 'occupation', $id);
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

        // I'm pretty sure php used to be able to var_export() on $result. No longer.

        /* 
         * var_dump($result);
         * printf("vh execute result:\n%s\n", var_export($result, true));
         * printf("json execute result:\n%s\n", json_encode($result, JSON_PRETTY_PRINT, 10));
         */

        $vh_info = $this->sdb->fetchrow($result);

        // printf("vh: \n%s\n", var_export($vh_info, 1));

        $this->sdb->deallocate('insert_version_history');
        return $vh_info;
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
    public function insertDate($vh_info, $date, $fk_table, $fk_id)
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
                                     array($vh_info['id'], 
                                           $vh_info['main_id'],
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

    public function insertNrd($vh_info, $existDates, $arg_list)
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
        
        // Combine vh_info and the remaining args into a big array for execute().
        $execList = array($vh_info['id'], $vh_info['main_id']);

        foreach ($arg_list as $arg)
        {
            array_push($execList, $arg);
        }
                                                                
        $result = $this->sdb->execute($qq, $execList);

        $id = $this->sdb->fetchrow($result)['id'];
        $this->sdb->deallocate($qq);
        foreach ($existDates as $singleDate)
        {
            $date_fk = $this->insertDate($vh_info, $singleDate, 'nrd', $id);
        }
    }

    public function insertOtherID($vh_info, $type, $href)
    {
        $qq = 'insert_other_id';
        $this->sdb->prepare($qq,
                            'insert into otherid
                            (version, main_id, other_id, link_type)
                            values
                            ($1, $2, $3, (select id from vocabulary where type=\'record_type\' and value=regexp_replace($4, \'^.*#\', \'\')))');
        
        $result = $this->sdb->execute($qq,
                                      array($vh_info['id'],
                                            $vh_info['main_id'],
                                            $href,
                                            $type));
        $this->sdb->deallocate($qq);
    }
    
    // Need to return the name.id so we can used it as fk for inserting related records
    public function insertName($vh_info, $original, $preferenceScore, $contributors, $language, $scriptCode, $useDates)
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
                                      array($vh_info['id'],
                                            $vh_info['main_id'],
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
                                array($vh_info['id'],
                                      $vh_info['main_id'],
                                      $name_id,
                                      $contrib['contributor'],
                                      $contrib['type']));
        }

        $this->sdb->deallocate($qq_1);
        $this->sdb->deallocate($qq_2);
    }
    
    
    // Function uses the same vocabulary terms as occupation.
    public function insertFunction($vh_info, $term, $vocabularySource, $dates, $note)
    {
        $qq = 'insert_function';
        $this->sdb->prepare($qq,
                            'insert into function
                            (version, main_id, function_id, note)
                            values
                            ($1, $2, (select id from vocabulary where type=\'occupation\' and value=regexp_replace($3, \'^.*#\', \'\')), $4)');
        
        $result = $this->sdb->execute($qq,
                                      array($vh_info['id'],
                                            $vh_info['main_id'],
                                            $term,
                                            $note));
        $id = $this->sdb->fetchrow($result)['id'];
        $this->sdb->deallocate($qq);
        foreach ($dates as $single_date)
        {
            $date_fk = $this->insertDate($vh_info, $single_date, 'function', $id);
        }
    }

    
    public function insertSubject($vh_info, $term)
    {
        $qq = 'insert_subject';
        $this->sdb->prepare($qq,
                            'insert into subject
                            (version, main_id, subject_id)
                            values
                            ($1, $2, (select id from vocabulary where type=\'subject\' and value=regexp_replace($3, \'^.*#\', \'\')))');
        
        $result = $this->sdb->execute($qq,
                                      array($vh_info['id'],
                                            $vh_info['main_id'],
                                            $term));
        $this->sdb->deallocate($qq);
    }

    public function insertRelation($vh_info, $dates, $argList)
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

        // Combine vh_info and the remaining args into a big array for execute(). Start by initializing the
        // first two elements of the array with id and main_id from vh_info.
        $execList = array($vh_info['id'], $vh_info['main_id']);
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
            $date_fk = $this->insertDate($vh_info, $singleDate, 'related_identity', $relationId);
        }

    }

    public function insertResourceRelation($vh_info, $argList)
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

        // Combine vh_info and the remaining args into a big array for execute(). Start by initializing the
        // first two elements of the array with id and main_id from vh_info.
        $execList = array($vh_info['id'], $vh_info['main_id']);
        foreach ($argList as $arg)
        {
            array_push($execList, $arg);
        }
        
        $this->sdb->execute($qq, $execList);
        $this->sdb->deallocate($qq);
    }


    /*
     *
     * Pull back the most recent version row from nrd (and eventually other tables?) using a known id.
     *
     * It is intentional that the fields are not retrieved in any particular order because the row will be
     * saved as an associative list. That allows us to write the sql query in a more legible format.
     * 
     */
    
    public function selectConstellation($version, $main_id)
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

        $result = $this->sdb->execute($qq, array($version, $main_id));
        $row = $this->sdb->fetchrow($result);
        $this->sdb->deallocate($qq);
        return $row;
    }

    // return flat list of distinct id values meeting the version and main_id constraint Specifically a helper
    // function fro selectOtherRecordIDs() This deals with the possibility that a given id may have several
    // versions while other id values are other (and single) versions.

    public function matchORID($version, $main_id)
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

        $result = $this->sdb->execute($qq, array($version, $main_id));
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            printf("matchORID: %s\n", $row['id']);
            array_push($all, $row['id']);
        }
        $this->sdb->deallocate($qq);
        return $all;
    }


    // return a list of otherid rows
    // Assume unique id in vocab, so don't need extra constraint type='record_type'
    public function selectOtherRecordIDs($version, $main_id)
    {
        $matchingIDs = $this->matchORID($version, $main_id);

        $qq = 'sorid';
        $this->sdb->prepare($qq, 
                            'select
                            id, version, main_id, other_id,
                            (select value from vocabulary where id=link_type) as link_type
                            from otherid
                            where
                            version=(select max(version) from otherid where version<=$1)
                            and main_id=$2 and id=$3');

        foreach ($matchingIDs as $orid)
        {
            $result = $this->sdb->execute($qq, array($version, $main_id, $orid));
            $all = array();
            while($row = $this->sdb->fetchrow($result))
            {
                array_push($all, $row);
            }
        }
        $this->sdb->deallocate($qq);
        return $all;
    }

    // For the purposes of testing, get a record that has a date_range record.

    // return array(id, version, main_id) for a record that has a date_range
    public function randomConstellationID()
    {
        $qq = 'rcid';
        $this->sdb->prepare($qq, 
                            'select nrd.id, version_history.id as version, version_history.main_id
                            from nrd,date_range, version_history
                            where nrd.id=fk_id and nrd.main_id=version_history.main_id and nrd.version=version_history.id limit 1');
    
        $result = $this->sdb->execute($qq, array());
        $row = $this->sdb->fetchrow($result);
        $this->sdb->deallocate($qq);
        return array($row['id'], $row['version'], $row['main_id']);
    }



}

