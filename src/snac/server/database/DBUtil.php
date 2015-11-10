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

  // namespace is confusing. Are they path relative? Are they arbitrary? How much of the leading directory
  // tree can be left out of the namespace? I just based this file's namespace on the parser example below.

  // namespace snac\util;
  //       src/snac/util/EACCPFParser.php

namespace snac\server\database;

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
    public function __construct($db) 
    {
        $db = new \snac\server\database\DatabaseConnector();
        $this->sql = new SQL($db);
        init_local_vars($sql);
    }
    
    // This needs to access some system-wide authentication and/or current user info. Hard coded for now.
    function getAppUserID($userid)
    {
        $appUserID = $this->sql->getAppUserID($userid);
    }
    
    // is there another word for "insert"? SQL uses insert, but this is higher level than the SQL class.
    // $id is a Constellation object
    
    // Put this in some util class.
    // None too efficient since it opens and closes the stream constantly.
    function quick_stderr ($message)
    {
        $stderr = fopen('php://stderr', 'w');
        fwrite($stderr,"  $message\n");
        fclose($stderr); 
    }

    public function insertConstellation($id)
    {
        // This is proabably a good place to start using named args to methods, esp in class SQL.

        // Move those sanity checks up here, and decide what kind of exception to throw, or message to log if
        // not fatal.
        
        // vh_info: version_history.id, version, main_id, ark_id?
        $vh_info = $this->sql->insertVersionHistory();

        $cdata = $id->toArray(false);
        if (count($cdata['biogHist']) > 1)
        {
            $msg = sprintf("Warning: multiple biogHist (%s)\n", count($cdata['biogHist']));
            quick_stderr($msg);
        }
        
        if ($otherID['type'] != 'MergedRecord')
        {
            $msg = sprintf("Warning: unexpected otherRecordID type: %s for ark: %s\n",
                           $otherID['type'],
                           $cdata['ark']);
            quick_stderr($msg);
        }

        $this->sql->insertNrd($vh_info, $cdata['ark'], $cdata['entityType'], $cdata['biogHist'][0]);

        foreach ($cdata['otherRecordIDs'] as $otherID)
        {
            $this->sql->insertOtherID($vh_info, $otherID['type'], $otherID['href']);
        }

        foreach ($cdata['nameEntries'] as $nameEntry)
        {
            $ndata = $nameEntry->toArray(false);
            $name_id = $this->sql->insertName($vh_info, 
                                        $ndata['original'],
                                        $ndata['preferenceScore'],
                                        $ndata['contributors'], // list of type/contributor values
                                        $ndata['language'],
                                        $ndata['scriptCode'],
                                        $ndata['useDates']);
        }
    }
}
