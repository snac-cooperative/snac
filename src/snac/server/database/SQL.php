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
                            ($1, $2, (select id from vocabulary where type=\'occupation\' and value=$3), $4)
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
        $qq = 'insert_date';
        $this->sdb->prepare($qq, 
                            'insert into date_range
                            (version, main_id, is_range, from_date, from_type, from_bc, from_not_before, from_not_after,
                            to_date, to_type, to_bc, to_not_before, to_not_after, original, fk_table, fk_id)
                            values
                            ($1, $2, $3, $4, 
                            (select id from vocabulary where type=\'date_type\' and value=$5),
                            $6, $7, $8, $9,
                            (select id from vocabulary where type=\'date_type\' and value=$10),
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
                            (select id from vocabulary where type=\'entity_type\' and value=$4),
                            $5,
                            (select id from vocabulary where type=\'nationality\' and value=$6),
                            (select id from vocabulary where type=\'gender\' and value=$7),
                            $8, $9, $10, $11, $12,
                            (select id from vocabulary where type=\'language_code\' and value=$13),
                            $14,
                            (select id from vocabulary where type=\'script_code\' and value=$15))
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
                            ($1, $2, $3, (select id from vocabulary where type=\'record_type\' and value=$4))');
        
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
                            (select id from vocabulary where type=\'language\' and value=$5),
                            (select id from vocabulary where type=\'scriptCode\' and value=$6))
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
                            (select id from vocabulary where type=\'name_type\' and value=$5))');

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
                            ($1, $2, (select id from vocabulary where type=\'occupation\' and value=$3), $4)');
        
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
                            ($1, $2, (select id from vocabulary where type=\'subject\' and value=$3))');
        
        $result = $this->sdb->execute($qq,
                                      array($vh_info['id'],
                                            $vh_info['main_id'],
                                            $term));
        $this->sdb->deallocate($qq);
    }

    public function insertRelation($vh_info, $arg_list)
    {
        $qq = 'insert_subject';
        $this->sdb->prepare($qq,
                            'insert into subject
                            (version, main_id, subject_id)
                            values
                            ($1, $2, (select id from vocabulary where type=\'subject\' and value=$3))');
        
        $result = $this->sdb->execute($qq,
                                      array($vh_info['id'],
                                            $vh_info['main_id'],
                                            $term));
        $this->sdb->deallocate($qq);
    }



}

