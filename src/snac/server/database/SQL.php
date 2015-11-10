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
    // Copy global $db into local $sdb because I don't like local and globals vars of the same name.

    private $sdb; 
    public function __construct($db)
    {
        $this->sdb = $db;
    }

    function getAppUserID($userid)
    {
        // select id from appuser where userid=$userid
        $sdb->prepare('query', 'select id from appuser where userid=$1');
        $cursor = $sdb->execute('query', array($userid));
        $row = $db->fetchrow($cursor);
        return $row['id'];
    }

    public function insertVersionHistory($userid, $role, $icstatus, $msg)
    {
        // insert into version_history (default, default, $user_id, $role_id, default, $icstatus, false, $msg);
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

