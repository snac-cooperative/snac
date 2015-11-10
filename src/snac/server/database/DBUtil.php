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
        $sql = new SQL($db);
    }
    
    // is there another word for "insert"? SQL uses insert, but this is higher level than the SQL class.
    // $id is a Constellation object

    public function insertConstellation($id)
    {
        // This is proabably a good place to start using named args to methods, esp in class SQL.
        
        // vh_info: version_history.id, version, main_id, ark_id?
        $vh_info = $sql->insertVersionHistory();

        $sql->insertNrd($vh_info, $id->getARK(), $id->getEntityType(), $id->getBiogHist);

        foreach ($id->getOtherRecordID() as $otherID)
        {
            $sql->insertOtherID($vh_info, $id->getOtherID());
        }
        
        foreach ($id->getNameEntries() as $nameEntry)
        {
            $name_id = $sql->insertName($vh_info, 
                                        $nameEntry->getOriginal(), 
                                        $nameEntry->getPreferenceScore(),
                                        $nameEntry->getContributors(), // list of type/contributor values
                                        $nameEntry->getLanguage(),
                                        $nameEntry->getScriptCode(),
                                        $nameEntry->getUseDates());
        }
    }
}
