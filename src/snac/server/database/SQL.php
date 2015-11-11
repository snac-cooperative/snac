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

    function getAppUserInfo($userid)
    {
        // select id from appuser where userid=$userid
        $this->sdb->prepare('query', 
                            'select appuser.id as id,role.id as role from appuser, appuser_role_link, role
                            where 
                            appuser.userid=$1
                            and appuser.id=appuser_role_link.uid
                            and role.id = appuser_role_link.rid
                            and appuser_role_link.is_primary=true');
    
        // $result behaves a bit like a cursor. Php docs say the data is in memory, and that a cursor is not
        // used.
        $result = $this->sdb->execute('query', array($userid));
        $row = $this->sdb->fetchrow($result);
        $this->sdb->deallocate('query');
        return array($row['id'], $row['role']);
    }

    public function insertVersionHistory($userid, $role, $status, $note)
    {
        // We need version_history.id and version_history.main_id returned.
        $this->sdb->prepare('query', 
                            'insert into version_history 
                            (user_id, role_id, status, is_current, note)
                            values 
                            ($1, $2, $3, $4, $5)
                            returning id, main_id;');
        $result = $this->sdb->execute('query', array($userid, $role, $status, true, $note));
        printf("vh execute result:\n%s\n", var_export($result, 1));
        $vh_info = $this->sdb->fetchrow($result);
        $this->sdb->deallocate('query');
        return $vh_info;
    }
    
    public function insertNrd($vh_info, $ark, $entityType, $biogHist)
    {

    }

    public function insertOtherID($vh_info, $type, $href)
    {
        /* 
         * insert into otherid
         * (version, main_id, other_id, link_type)
         * values
         * ($version, $main_id, $otherid, select id from vocabulary where type='record_type' and value='MergedRecord');
         */
    }

    public function insertName($vh_info, $original, $preferenceScore, $contributors, $language, $scriptCode, $useDates)
    {
        
    }
    
}

