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
     * Deleted status string
     *
     * Value of the string for deleted records. This is passed to the constructor because it seems likely to
     * change.
     *
     */  
    private $deleted = null;

    /**
     * The constructor
     *
     * Makes the outside $db a local variable. I did this out of a general sense of avoiding
     * globals, but I'm unclear if this is really any better than using a globale $db variable. $db is
     * critical to the application, and is read-only after intialization. Breaking it or changing it in any
     * way will break everything, whether global or not. Passing it in here to get local scope doesn't meet
     * any clear need.
     *
     * @param \snac\server\database\DatabaseConnector $db A working, initialized DatabaseConnector object.
     *
     * @param string $deletedValue optional Optional param in case the value of status deleted ever changes
     * its string representation. In hindsight not necessary and just added complexity. Oh well.
     */
    public function __construct($db, $deletedValue='deleted')
    {
        $this->sdb = $db;
        $this->deleted = $deletedValue;
    }

    /**
     * Insert a new user aka appuser
     *
     * Insert a user into the db, returning the new record id. Field userid is not currently used.
     *
     * @param string $firstName The first name
     * @param string $lastName The last name
     * @param string $fullName The full name
     * @param string $avatar The avatar
     * @param string $avatarSmall The small avatar
     * @param string $avatarLarge The large avatar
     * @param string $email The email address
     * @return integer Record row id, unique, from sequence id_seq.
     */ 
    public function insertUser($firstName, $lastName, $fullName, $avatar, $avatarSmall, $avatarLarge, $email)
    {
        $result = $this->sdb->query(
            'insert into appuser (first, last, fullname, avatar, avatar_small, avatar_large, email)
            values ($1, $2, $3, $4, $5, $6, $7)
            returning id',
            array($firstName, $lastName, $fullName, $avatar, $avatarSmall, $avatarLarge, $email));
        $row = $this->sdb->fetchrow($result);
        return $row['id'];
    }

    /**
     * Really delete a user
     *
     * Used for testing only. Normal users are inactivated.
     *
     * @param integer $appUserID The user id to delete.
     */
    public function deleteUser($appUserID)
    {
        $result = $this->sdb->query(
            'delete from appuser where id=$1',
            array($appUserID));
    }

    /**
     * Really delete a role
     *
     * Used for testing only, maybe. In any case, deleting a role should be rare. To make this a little safer
     * it only deletes if the role is not in use.
     *
     * @param string[] $role A list with keys: id, label, description
     */
    public function deleteRole($role)
    {
        $result = $this->sdb->query(
            'delete from role where id=$1 and id not in (select distinct(rid) from appuser_role_link)',
            array($role['id']));
    }


    /**
     * Update password for an existing user.
     *
     * We assume the user exists. This will silently fail for non-existing user, although the calling code
     * won't be able to get a $appUserID for a non-existent user, so that's not a problem.
     *
     * @param integer $appUserID The numeric user id
     *
     * @param string $passwd An encrypted password
     *
     */
    public function updatePassword($appUserID, $passwd)
    {
        $this->sdb->query(
            'update appuser set password=$1 where id=$2',
            array($passwd, $appUserID));
    }
    
    /**
     * Check if a password matches
     *
     * Select a record with matching appUserID and password. Essentially, if the password it not a match, it
     * will return a null.
     *
     * @param integer $appUserID The numeric user id
     *
     * @param string $passwd An encrypted password
     *
     * @return integer User id
     */
    public function selectMatchingPassword($appUserID, $passwd)
    {
        $result = $this->sdb->query(
            'select id from  appuser where password=$1 and id=$2',
            array($passwd, $appUserID));
        $row = $this->sdb->fetchrow($result);
        return $row['id'];
    }

    /**
     * Return the id of a session by token.
     *
     * If the session exists, return the record.
     *
     * @param string $accessToken A session token
     *
     * @return string[] The session record as a list with keys appuser_fk, access_token, expires.
     *
     */
    public function selectSession($accessToken)
    {
        $this->sdb->query(
            'select id from session where access_token=$1',
            array($accessToken));
        $row = $this->sdb->fetchrow($result);
        return $row;
    }


    /**
     * Update a session expiration timestamp
     *
     * @param string $accessToken A session token
     *
     * @param string $expire A session expiration timestamp
     *
     */
    public function updateSession($accessToken, $expire)
    {
        $this->sdb->query(
            'update session set expire=$1 where access_token=$2',
            array($expire, $accessToken));
    }

    /**
     * Create a new session
     *
     * Create a new session for a user.
     *
     * @param integer $appUserID The user id
     * 
     * @param string $accessToken A session token
     *
     * @param string $expire A session expiration timestamp
     *
     */
    public function insertSession($appUserID, $accessToken, $expire)
    {
        $this->sdb->query(
            'insert into session (appuser_fk, access_token expire) values ($1, $2, $3)',
            array($appUserID, $accessToken, $expire));
    }

    
    /**
     * Check that a session is active
     *
     * I'm sure there are Postgres docs for extract(), epoch from, at time zone 'utc', but this is a nice example.
     * 
     * http://stackoverflow.com/questions/16609724/using-current-time-in-utc-as-default-value-in-postgresql
     *
     * @param integer $appUserID The user id
     * 
     * @param string $accessToken A session token
     *
     * @return boolean true for active, false for inactive or not found.
     */
    public function selectActive($appUserID, $accessToken)
    {
        $result = $this->sdb->query(
            'select count(*) from session where appuser_fk=$1 and access_token=$2 and $expire >= extract(epoch from now() at time zone \'utc\')',
            array($appUserID, $accessToken));
        $row = $this->sdb->fetchrow($result);
        if ($row['count'] == 1)
        {
            return true;
        }
        return false;
    }

    /**
     * Clear all user sessions
     *
     * @param integer $appUserID The user id
     * 
     * @param string $accessToken A session token
     *
     * @return boolean true for active, false for inactive or not found.
     */
    public function deleteAllSessions($appUserID)
    {
        $result = $this->sdb->query(
            'delete from session where appuser_fk=$1',
            array($appUserID));
    }



    /**
     * Insert a new user aka appuser
     *
     * Insert a user into the db, returning the new record id. Field userid is not currently used.
     *
     * @param integer $uid The row id aka user id (but not from field userid) 
     * @param string $firstName The first name
     * @param string $lastName The last name
     * @param string $fullName The full name
     * @param string $avatar The avatar
     * @param string $avatarSmall The small avatar
     * @param string $avatarLarge The large avatar
     * @param string $email The email address
     */ 
    public function updateUser($uid, $firstName, $lastName, $fullName, $avatar, $avatarSmall, $avatarLarge, $email)
    {
        $this->sdb->query(
            'update appuser set first=$2, last=$3, fullname=$4, avatar=$5, avatar_small=$6, avatar_large=$7, email=$8
            where appuser.id=$1',
            array($uid, $firstName, $lastName, $fullName, $avatar, $avatarSmall, $avatarLarge, $email));
    }

    /**
     * Get user id from email
     *
     * @param string $email Email address
     * @return integer User id which is appuser.id, aka row id. We aren't cuurrently using appuser.userid.
     */ 
    public function selectUserByEmail($email)
    {
        $result = $this->sdb->query("select id from appuser where email=$1",
                                    array($email));
        $row = $this->sdb->fetchrow($result);
        return $row['id'];
    }

    /**
     * Select user record from database
     *
     * @param integer $uid User id, aka appuser.id aka row id.
     * @return string[] Array with keys: id, first, last, fullname, avatar, avatar_small, avatar_large, email
     */ 
    public function selectUserByid($uid)
    {
        $result = $this->sdb->query("select * from appuser where appuser.id=$1",
                                    array($uid));
        $row = $this->sdb->fetchrow($result);
        return $row;
    }

    /**
     * Disable/enable user account
     *
     * Set active to true or false, depending on $value.
     *
     * @param integer $uid User id, aka appuser.id aka row id.
     *
     * @param string $value A Postgres compatible value, 't' or 'f'. Get this value by calling boolToPg() with
     * true or false in the calling code.
     * 
     */ 
    public function updateActive($uid, $value)
    {
        $this->sdb->query("update appuser set active=$2 where appuser.id=$1",
                          array($uid, $value));
    }

    /**
     * Add a role to a user
     *
     * Link a role to a user. 
     *
     * @param integer $uid User id, aka appuser.id aka row id.
     * @param integer $newRoleID A rold id
     */ 
    public function insertRoleLink($uid, $newRoleID)
    {
        $this->sdb->query("insert into appuser_role_link (uid, rid) values ($1, $2)",
                          array($uid, $newRoleID));
    }

    /**
     * Insert a new role.
     *
     * Insert a new role and return the role's id.
     *
     * @param string $label Role label
     *
     * @param string $description Role description
     *
     * @return integer Role id
     */
    public function insertRole($label, $description)
    {
        $result = $this->sdb->query("insert into role (label, description) values ($1, $2) returning id, label, description",
                          array($label, $description));
        $row = $this->sdb->fetchrow($result);
        return $row;
    }

    /**
     * Delete a role from a user
     *
     * Deleted a link role.
     *
     * @param integer $uid User id, aka appuser.id aka row id.
     * @param integer $roleID A rold id
     */ 
    public function deleteRoleLink($uid, $roleID)
    {
        $this->sdb->query("delete from appuser_role_link where uid=$1 and rid=$2",
                          array($uid, $roleID));
    }

    /**
     * Select all role records
     *
     * @return string[][] Return list of list with keys: id, label, description.
     */ 
    public function selectRole()
    {
        $result = $this->sdb->query("select * from role order by label asc",
                                    array());
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }
        return $all;
    }

    /**
     * Select user role records
     *
     * @return string[][] Return list of list with keys: id, label, description.
     */ 
    public function selectUserRole($appUserID)
    {
        $result = $this->sdb->query("select role.* from role,appuser_role_link
                                    where appuser_role_link.uid=$1 and role.id=rid order by label asc",
                                    array($appUserID));
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }
        return $all;
    }




    /**
     * Current version by mainID
     *
     * The max, that is: current version for mainID regardless of status. This will return max for deleted as
     * well as all other status values. It is important that this return status on deleted records because
     * this function is crucial in getting the version number of records, deleted and otherwise. Ignores
     * status, and will return records which are deleted, or have any status.
     *
     * aka selectMaxVersion selectMostRecentVersion selectCurrentVersion
     *
     * @param integer $mainID The constellation ID
     * 
     * @return integer Version number from version_history.id returned as 'version', as is our convention.
     *
     */
    public function selectCurrentVersion($mainID)
    {
        $result = $this->sdb->query(
            'select max(id) as version 
            from version_history
            where version_history.main_id=$1',
            array($mainID));
        $row = $this->sdb->fetchrow($result);
        return $row['version'];
    }

    /**
     * Status by mainID and version number
     *
     * Get the version_history status.
     *
     * @param integer $mainID The constellation ID
     *
     * @param integer $version A specific version number. We assume that you have called some function to get
     * a specific version number. Null is not ok, and guesses are not ok. This will not select for <$version.
     *
     * @return string The status string of that version for the given mainID.
     */ 
    public function selectStatus($mainID, $version)
    {
        $result = $this->sdb->query(
            'select status from version_history where main_id=$1 and id=$2',
            array($mainID, $version));
        $row = $this->sdb->fetchrow($result);
        return $row['status'];
    }

    /**
     * Select by status and most recent, user only
     *
     * Get a list of mainID, version for all records when the most recent matches the given status and user.
     * This will only return most recent records, and only if the status and user match. A note about
     * checking for deleted: All status values are exclusive, so if a record's most recent status is 'locked
     * editing' then it cannot not possibly also be deleted. If you ask for 'locked editing' and that is most
     * recent, and for this user, then you will get that record.
     *
     * This will not return records which are not most recent. After that, any record must match status and user.
     *
     * @param integer $appUserID User numeric ID. The most recent record must match this user id, and must
     * match $status.
     *
     * @param string $status optional The status we want, defaults to 'locked editing'. Any status is
     * supported, but records are returned only if the most recent is this status, and matches $appUserID.
     *
     * @param integer $limit Limit to the number of records. Not optional here. Must be -1 for all, or a
     * number. The higher level calling code has a default from the config.
     *
     * @param integer $offset An offset to jump into the list of records in the database. Not optional
     * here. Must be -1 for all, or a number. The higher level calling code has a default from the config.
     *
     * @return string[] Associative list with keys 'version', 'main_id'. Values are integers.
     */ 
    public function selectEditList($appUserID, $status = 'locked editing', $limit, $offset)
    {
        if ($status != 'locked editing' &&
            $status != 'currently editing')
        {
            return array();
        }
        $limitStr = '';
        $offsetStr = '';
        $limitStr = $this->doLOValue('limit', $limit);
        $offsetStr = $this->doLOValue('offset', $offset);

        $queryString = sprintf(
            'select aa.id as version, aa.main_id
            from version_history as aa,
            (select max(bb.id) as id,bb.main_id from version_history as bb group by bb.main_id) as cc
            where
            aa.main_id=cc.main_id and
            aa.id=cc.id and
            aa.user_id=$1 and
            aa.status = $2 %s %s', $limitStr, $offsetStr);
        $result = $this->sdb->query($queryString,
                                    array($appUserID, $status));
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            $mainID = $row['main_id'];
            $version = $row['version'];
            /*
             * I think the query above works, returning the max() only when that version also has the required
             * status. However, the check below will confirm that the returned version really is the max.
             */ 
            $maxVersion = $this->selectCurrentVersion($mainID);
            if ($maxVersion == $version)
            {
                array_push($all, $row);
            }
        }
        return $all;
    }

    /**
     * Handle the strings for limit and offset
     *
     * This the logical implementation details in building limit and offset SQL statements. The defaults are
     * at a higher level, so checking here is merely to catch errors. If -1, do all, that is no limit or
     * offset statement. If an integer, use that as the limit or offset. If null then use the default, but
     * null should never happen because the default in the calling code is an integer constant from the
     * config.
     *
     * Default in the calling code are: \snac\Config::$SQL_LIMIT, \snac\Config::$SQL_OFFSET.
     *
     * Always return the string padded with leading and trailing spaces. It is safer to have extra spaces.
     *
     * @param string $str Either 'limit' or 'offset'
     *
     * @param integer $value An integer or -1. We accept null, but only as an error which results in using the
     * constants.
     *
     * @return string An empty string, or a limit or offset SQL string. 
     */ 
    private function doLOValue($str, $value)
    {
        if ($value < 0 || ($str != 'limit' && $str != 'offset'))
        {
            /*
             * -1 or any negative value is "all". Negative values don't get beyond the first if statement.
             */
            return '';
        }
        elseif ($value == null || ! is_int($value))
        {
            /*
             * Null or weird stuff use the default. This seems the safe option.  This should never happen, but
             * if it does, we have backstopped the user doing something non-sensical.
             */ 
            if ($str == 'limit')
            {
                return " $str " . \snac\Config::$SQL_LIMIT . " ";
            }
            elseif ($str == 'offset')
            {
                return " $str " . \snac\Config::$SQL_OFFSET . " ";
            }
            else
            {
                /*
                 * If the $str is unknown, there's no limit or offset string we can create, so just return an
                 * empty string. The $str is checked in the first if statement, so we should never get down
                 * here, which makes this a belt and suspenders situation.
                 */ 
                return '';
            }
        }
        else
        {
            /*
             * Else we have a nice integer, so we use that.
             */ 
            return " $str $value ";
        }
    }

    /**
     * Select a list by status
     *
     * Select a list of mainID, version for a given status, and most recent. User is ignored therefore we get
     * records from any user. That is: not constrained by user. Optional args for limit and offset allow
     * returning partial lists.
     *
     * We are dynamically building the query string. Use sprintf() in order to get $limiStr and $offsetStr
     * into the query as necessary. Note the care we take in checking that $limit and $offset are
     * integers. Building query strings dynamically is one way that sql injection attacks sneak into code. We
     * are validating for ints and building the string only from trusted data.
     *
     * Use single quotes in $queryStr below. We don't want $1 to be interpolated. Testing php behavior reveals
     * that $1 never interpolates, but heaven only knows if that is in the php language spec. If you want, it
     * also seems fine to escape with \ when using double quotes.
     *
     * @param string $status optional Status defaults to 'published'.
     * 
     * @param integer $limit Limit to the number of records. Not optional here. Must be -1 for all, or a
     * number. The higher level calling code has a default from the config.
     *
     * @param integer $offset An offset to jump into the list of records in the database. Not optional
     * here. Must be -1 for all, or a number. The higher level calling code has a default from the config.
     *
     * @return string[] Associative list with keys 'version', 'main_id'. Values are integers.
     */ 
    public function selectListByStatus($status = 'published', $limit, $offset)
    {
        $limitStr = '';
        $offsetStr = '';
        $limitStr = $this->doLOValue('limit', $limit);
        $offsetStr = $this->doLOValue('offset', $offset);
        $queryString = sprintf(
            'select aa.id as version, aa.main_id
            from version_history as aa,
            (select max(bb.id) as id,bb.main_id from version_history as bb group by bb.main_id) as cc
            where
            aa.main_id=cc.main_id and
            aa.id=cc.id and
            aa.status = $1
            order by aa.id desc %s %s', $limitStr, $offsetStr);
        
        $result = $this->sdb->query($queryString,
                                    array($status));
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            $mainID = $row['main_id'];
            $version = $row['version'];
            /*
             * I think the query above works, returning the max() only when that version also has the required
             * status. However, the check below will confirm that the returned version really is the max.
             */ 
            $maxVersion = $this->selectCurrentVersion($mainID);
            if ($maxVersion == $version)
            {
                array_push($all, $row);
            }
        }
        return $all;
    }
        

    /**
     * Mint a new record id.
     *
     * We always insert a new record, even on update. However, new objects do not have a
     * record id, so we create a table.id from the main sequence id_seq. This is just a centralized place to
     * do that.
     *
     * Also, when inserting a constellation we need a new id, and those ids are generated from the same
     * sequence, so this is used there as well.
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
     * select mainID by arkID
     *
     * nrd.main_id is the constellation id.
     *
     * nrd.id is a typical row id (aka record id)
     *
     * Constellation->getID() gets the main_id aka constellation id
     *
     * non-constellation->getID() gets the row id. Non-constellation objects get the main_id from the
     * constellation, and it is not stored in these objects themselves. I mention this (again) because it
     * (again) caused confusion in the SQL below (now fixed).
     *
     * @param string $arkID The ARK id of a constellation
     *
     * @return integer The constellation ID aka mainID akd main_id aka version_history.main_id.
     */
    public function selectMainID($arkID)
    {
        $result = $this->sdb->query(
            'select nrd.main_id
            from version_history, nrd
            where
            nrd.ark_id=$1
            and version_history.main_id=nrd.main_id',
            array($arkID));
        $row = $this->sdb->fetchrow($result);
        return $row['main_id'];
    }


    /**
     * Select records from table source by foreign key
     *
     * @param integer $fkID A foreign key to record in another table.
     *
     * @param integer $version The constellation version. For edits this is max version of the
     * constellation. For published, this is the published constellation version.
     *
     * @return string[] A list of records (list of lists) with inner keys matching the database field names:
     * version, main_id, id, text, note, uri, language_id.
     *
     */
    public function selectSource($fkID, $version)
    {
        $qq = 'select_source';
        $this->sdb->prepare($qq,
                            'select aa.version, aa.main_id, aa.id, aa.text, aa.note, aa.uri, aa.language_id, aa.display_name
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
     * Select all source id only by constellation ID
     *
     * Select only source.id values for a given constellation ID. Use this to get constellation source id
     * values, which higher level code uses to call populateSourceByID(). If you want full source record data,
     * then you should use selectSourceByID().
     *
     * @param integer $mainID A foreign key to record in another table.
     *
     * @param integer $version The constellation version. For edits this is max version of the
     * constellation. For published, this is the published constellation version.
     *
     * @return string[] A list of list (records) with key 'id'
     *
     */
    public function selectSourceIDList($mainID, $version)
    {
        $qq = 'select_source_id_list';
        $this->sdb->prepare($qq,
                            'select aa.id
                            from source as aa,
                            (select id,max(version) as version from source where main_id=$1 and version<=$2 group by id) as bb
                            where not is_deleted and aa.id=bb.id and aa.version=bb.version');
        $result = $this->sdb->execute($qq, array($mainID, $version));
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }
        $this->sdb->deallocate($qq);
        return $all;
    }


    /**
     * Select full source records from table source by source id
     *
     * Select source where the most recent version <= $version for source id $sourceID and not deleted.
     *
     * @param integer $fkID A foreign key to record in another table.
     *
     * @param integer $version The constellation version. For edits this is max version of the
     * constellation. For published, this is the published constellation version.
     *
     * @return string[] A single source record keys matching the database field names:
     * version, main_id, id, text, note, uri, language_id.
     *
     */
    public function selectSourceByID($sourceID, $version)
    {
        $qq = 'select_source_by_id';
        $this->sdb->prepare($qq,
                            'select aa.version, aa.main_id, aa.id, aa.text, aa.note, aa.uri, aa.language_id, aa.display_name
                            from source as aa,
                            (select id,max(version) as version from source where id=$1 and version<=$2 group by id) as bb
                            where not is_deleted and aa.id=bb.id and aa.version=bb.version');
        $result = $this->sdb->execute($qq, array($sourceID, $version));
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
     * Write a source objec to the database. These are per-constellation so they have main_id and no foreign
     * keys. These are linked to other tables by putting a source.id foreign key in that related table. 
     * Language related is a Language object, and is saved in table language. It is related where
     * source.id=language.fk_id. There is no language_id in table source, and there should not be. However, a
     * lanugage may link to this source record via source.id. See DBUtil writeSource().
     * The "type" field was always "simple" and is no longer used.
     * 
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param integer $id Record id
     *
     * @param string $displayName The name of the source to display in the UI
     *
     * @param string $text Text of this source.
     *
     * @param string $note Note about this source.
     *
     * @param string $uri URI of this source
     *
     * @return integer The id value of this record. Sources have a language, so we need to return the $id
     * which is used by language as a foreign key.
     *
     */
    public function insertSource($vhInfo, $id, $displayName, $text, $note, $uri)
    {
        if (! $id)
        {
            $id = $this->selectID();
        }
        $qq = 'insert_source';
        $this->sdb->prepare($qq,
                            'insert into source
                            (version, main_id, id, display_name, text, note, uri)
                            values
                            ($1, $2, $3, $4, $5, $6, $7)');
        $this->sdb->execute($qq,
                            array($vhInfo['version'],
                                  $vhInfo['main_id'],
                                  $id,
                                  $displayName,
                                  $text,
                                  $note,
                                  $uri));
        $this->sdb->deallocate($qq);
        return $id;
    }


    /**
     * Insert a biogHist.
     *
     * If the $id arg is null, get a new id. Always return $id.
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
     * Select the id and role for a given appuser.
     *
     * Maybe this should be called selectAppUserInfo() in keeping
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
    public function selectAppUserInfo($userString)
    {
        $qq = 'get_app_user_info';
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
        $result = $this->sdb->execute($qq, array($userString));
        $row = $this->sdb->fetchrow($result);
        $this->sdb->deallocate($qq);
        return array($row['id'], $row['role']);
    }

    /**
     * Insert a version_history record.
     *
     * This always increments the version_history.id which is the version number. An old comment said: "That
     * needs to not be incremented in some cases." That is certainly not possible now. Our rule is: always
     * increment version on any database operation.
     *
     * The $mainID aka main_id may not be minted. If we have an existing $mainID we do not create a new
     * one. This would be the case for update and delete.
     *
     * @param integer $mainID Constellation id
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
    public function insertVersionHistory($mainID, $userid, $role, $status, $note)
    {
        if (! $mainID)
        {
            $mainID = $this->selectID();
        }
        $qq = 'insert_version_history';
        // We need version_history.id and version_history.main_id returned.
        $this->sdb->prepare($qq, 
                            'insert into version_history 
                            (main_id, user_id, role_id, status, is_current, note)
                            values
                            ($1, $2, $3, $4, $5, $6)
                            returning id as version');

        $result = $this->sdb->execute($qq, array($mainID, $userid, $role, $status, true, $note));
        $row = $this->sdb->fetchrow($result);
        $vhInfo['version'] = $row['version'];
        $vhInfo['main_id'] = $mainID;
        $this->sdb->deallocate($qq);
        return $vhInfo;
    }


    /**
     * New insert into version_history
     *
     * We already know the version_history.id aka version, and main_id, so we are not relying on the default
     * values. This all happens because we are using the same main_id across an entire constellation. And we
     * might be using the same version across many Constellations and other inserts all in this current
     * session.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param integer $appUserID User id
     *
     * @param integer $roleID Role id
     *
     * @param string $status Constellation status
     *
     * @param string $note Note for this version
     *
     * @return string[] $vhInfo associative list with keys: version, main_id
     *
     */
    public function insertIntoVH($vhInfo, $appUserID, $roleID, $status, $note)
    {
        $qq = 'insert_into_version_history';
        $this->sdb->prepare($qq, 
                            'insert into version_history 
                            (id, main_id, user_id, role_id, status, is_current, note)
                            values 
                            ($1, $2, $3, $4, $5, $6, $7)
                            returning id as version, main_id;');

        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'], $vhInfo['main_id'], $appUserID, $roleID, $status, true, $note));
        $vhInfo = $this->sdb->fetchrow($result);
        $this->sdb->deallocate($qq);
        return $vhInfo;
    }

    /**
     * Update a version_history record
     *
     * Get a new version but keeping the existing main_id. This also uses DatabaseConnector->query() in an
     * attempt to be more efficient, or perhaps just less verbose.
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
     * @param integer $main_id Constellation id
     *
     * @return string[] $vhInfo An assoc list with keys 'version', 'main_id'. Early on, version_history.id was
     * returned as 'id', but all the code knows that as the version number, so this code plays nice by
     * returning it as 'version'. Note the "returning ..." part of the query.
     *
     */
    public function updateVersionHistory($appUserID, $roleID, $status, $note, $main_id)
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
                                    array($main_id, $appUserID, $roleID, $status, true, $note));
        $row = $this->sdb->fetchrow($result);
        return $row['version'];
    }


    /**
     * Insert date
     *
     * SNACDate.php has fromDateOriginal and toDateOriginal, but the CPF lacks date components, and the
     * database "original" is only the single original string.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     * @param integer $id Record id. If null a new one will be minted.
     * @param integer $isRange Boolean if this is a date range
     * @param string $fromDate The from date
     * @param string $fromType, Type of from date, fk to vocabulary.id
     * @param integer $fromBC Boolean if this is a BC date
     * @param string $fromNotBefore Not before this date
     * @param string $fromNotAfter Not after this date
     * @param string $fromOriginal What we got from the CPF
     * @param string $toDate The to date
     * @param integer $toType Type of the date, fk to vocabulary.id
     * @param integer $toBC Boolean, true if BC
     * @param string $toNotBefore Not before this date
     * @param string $toNotAfter Not after this date
     * @param string $toOriginal What we got from the CPF
     * @param string $fk_table The name of the table to which this date and $fk_id apply.
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
                               $fromOriginal,
                               $toDate,
                               $toType, // fk to vocabulary
                               $toBC,
                               $toNotBefore,
                               $toNotAfter,
                               $toOriginal, 
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
                            (version, main_id, id, is_range, 
                            from_date, from_type, from_bc, from_not_before, from_not_after, from_original,
                            to_date, to_type, to_bc, to_not_before, to_not_after, to_original, fk_table, fk_id)
                            values
                            ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17, $18)');
        
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
                                            $fromOriginal,
                                            $toDate,
                                            $toType,
                                            $toBC,
                                            $toNotBefore, 
                                            $toNotAfter,
                                            $toOriginal, 
                                            $fk_table,
                                            $fk_id));

        $row = $this->sdb->fetchrow($result);
        $this->sdb->deallocate($qq);
        return $id;
    }


    /**
     * Select list of dates
     *
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
                            aa.id, aa.version, aa.main_id, aa.is_range, 
                            aa.from_date, aa.from_bc, aa.from_not_before, aa.from_not_after, aa.from_original,
                            aa.to_date, aa.to_bc, aa.to_not_before, aa.to_not_after, aa.to_original, aa.fk_table, aa.fk_id,
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
     * Select list of place_link
     *
     * Note: This always gets the max version (most recent) for a given fk_id. Published records (older than
     * an edit) will show the edit (more recent) date, which is a known bug, and on the todo list for a fix.
     *
     * Select a place. This relies on table.id==fk_id where $tid is a foreign key of the record to which this
     * place applies. We do not know or care what the other record is.
     *
     * @param integer $tid A foreign key to record in the other table.
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
                            aa.id, aa.version, aa.main_id, aa.confirmed, aa.original, 
                            aa.geo_place_id, aa.type, aa.role, aa.note, aa.score, aa.fk_table, aa.fk_id
                            from place_link as aa,
                            (select fk_id,max(version) as version from place_link where fk_id=$1 and version<=$2 group by fk_id) as bb
                            where not is_deleted and aa.fk_id=bb.fk_id and aa.version=bb.version');

        $result = $this->sdb->execute($qq, array($tid, $version));
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
     * @param string[] $vhInfo associative list with keys: version, main_id
     * 
     * @param integer $id The id
     *
     * @param string $confirmed Boolean confirmed by human
     *
     * @param string $original The original string
     *
     * @param string $geo_place_id The geo_place_id
     *
     * @param integer $typeID Vocabulary ID of the place@localType
     *
     * @param integer $roleID Vocabulary ID of the role
     *
     * @param string $note A note
     *
     * @param float $score The geoname matching score
     *
     * @param string $fk_id The fk_id of the related table.
     *
     * @param string $fk_table The fk_table name
     *
     * @return integer $id The id of what we (might) have inserted.
     *
     */
    public function insertPlace($vhInfo, 
                                $id,
                                $confirmed,
                                $original,
                                $geo_place_id,
                                $typeID,
                                $roleID,
                                $note,
                                $score,
                                $fk_table,
                                $fk_id)
    {
        if (! $id)
        {
            $id = $this->selectID();
        }
        $qq = 'insert_place';
        $this->sdb->prepare($qq,
                            'insert into place_link
                            (version, main_id, id, confirmed, original, geo_place_id, type, role, note, score,  fk_id, fk_table)
                            values 
                            ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12)');

        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['main_id'],
                                            $id,
                                            $confirmed,
                                            $original,
                                            $geo_place_id,
                                            $typeID,
                                            $roleID,
                                            $note,
                                            $score,
                                            $fk_id,
                                            $fk_table));
        $this->sdb->deallocate($qq);
        return $id;
    }


    /**
     * Select a snac control meta data record
     *
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
     * @return string[][] A list of lists of fields/value as list keys matching the database field names: id,
     * version, main_id, citation_id, sub_citation, source_data, rule_id, language_id, note. I don't think
     * calling code has any use for fk_id, so we don't return it.
     */
    public function selectMeta($tid, $version)
    {
        $qq = 'select_meta';
        $this->sdb->prepare($qq, 
                            'select 
                            aa.id, aa.version, aa.main_id, aa.citation_id, aa.sub_citation, aa.source_data, 
                            aa.rule_id, aa.note
                            from scm as aa,
                            (select fk_id,max(version) as version from scm where fk_id=$1 and version<=$2 group by fk_id) as bb
                            where not is_deleted and aa.fk_id=bb.fk_id and aa.version=bb.version');

        $result = $this->sdb->execute($qq, array($tid, $version));
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }
        $this->sdb->deallocate($qq);
        return $all;
    }

    /**
     * Insert meta record
     *
     * Inset meta related to the $fk_id. Table scm, php object SNACControlMetadata.
     *
     * Note: we do not use citation_id because citations are source, and source is not a controlled
     * vocabulary. Source is like date. Each source is indivualized for the record it relates to. To get the
     * citation, conceptual select from source where scm.id==source.fk_id.
     *
     * Note: We do not save language_id because languages are objects, not a single vocabulary term like
     * $ruleID. The language is related back to this table where scm.id=language.fk_id and
     * scm.version=language.version. (Or something like that with version. It might be complicated.)
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param integer $id Record id of this table.
     *
     * @param integer $citationID Foreign key to table source.id
     *
     * @param string $subCitation A sub citation
     *
     * @param string $sourceData The text of the source
     *
     * @param integer $ruleID fk to vocaulary.id
     *
     * @param string $note A note about this meta data
     *
     * @param string $fkTable name of the related table
     *
     * @param integer $fkID fk to the relate table
     *
     * @return integer Return the record id
     */
    public function insertMeta($vhInfo, $id, $citationID, $subCitation, $sourceData,
                               $ruleID, $note, $fkTable, $fkID)
    {
        if (! $id)
        {
            $id = $this->selectID();
        }
        $qq = 'insert_meta';
        $this->sdb->prepare($qq,
                            'insert into scm
                            (version, main_id, id, citation_id, sub_citation, source_data,
                            rule_id, note, fk_id, fk_table)
                            values ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)');
        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['main_id'],
                                            $id,
                                            $citationID,
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
     * Get a geo_place record
     *
     * Also known as GeoTerm
     *
     * @param integer $gid A geo_place.id value.
     *
     * @return string[] A list of fields/value as list keys matching the database field names: id, uri,
     * latitude, longitude, admin_code, country_code, name.
     */
    public function selectGeoTerm($gid)
    {
        $qq = 'select_geo_place';
        $this->sdb->prepare($qq, 'select * from geo_place where id=$1');
        $result = $this->sdb->execute($qq, array($gid));
        $row = $this->sdb->fetchrow($result);
        $this->sdb->deallocate($qq);
        return $row;
    }

    /**
     * Insert geo_place
     *
     * Also known as GeoTerm
     * 
     * @param integer $id The id. If null, the system will assign a new id.
     *
     * @param string $version The version.
     *
     * @param string $uri The uri.
     *
     * @param string $name The name.
     *
     * @param string $latitude  The latitude maybe a string in php, but really a number(10,7)
     *
     * @param string $longitude  The longitude maybe a string in php, but really a number(10,7)
     *
     * @param string $admin_code  The admin_code 
     *
     * @param string $country_code  The country_code 
     *
     * @return integer $id Existing or new record id 
     */
    public function insertGeo($id, $version, $uri, $name, $latitude, $longitude, $admin_code, $country_code)
    {
        if (! $id)
        {
            $id = $this->selectID();
        }
        $qq = 'insert_geo_place';
        $this->sdb->prepare($qq,
                            'insert into geo_place
                            (id, version, uri, name, latitude, longitude, admin_code, country_code)
                            values
                            ($1, $2, $3, $4, $5, $6, $7, $8)');
        $result = $this->sdb->execute($qq,
                                      array($id,
                                            $version,
                                            $uri,
                                            $name,
                                            $latitude,
                                            $longitude,
                                            $admin_code,
                                            $country_code));
        $this->sdb->deallocate($qq);
        return $id;
    }


    /**
     * Insert nrd record
     *
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
     * Insert otherID record
     *
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
     * Insert (or update) a name
     *
     * Language and contributors are related table on name.id, and the calling code is smart enough to call
     * those insert functions for this name's language and contributors. Our concern here is tightly focused
     * on writing to table name. Nothing else.
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
     * Insert a contributor record
     *
     * Related to name where contributor.name_id=name.id. This is a one-sided fk relationship also used for
     * date and language.
     *
     * Old: Contributor has issues. See comments in schema.sql. This will work for now.  Need to fix insert
     * name_contributor to keep the existing id values. Also, do not update if not changed. Implies a
     * name_contributor object with a $operation like everything else.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param integer $id Record id if this contributor. If null one will be minted. The id (existing or new) is always returned.
     *
     * @param integer $nameID Record id of related name
     *
     * @param string $name Name of the contributor
     *
     * @param integer $typeID Vocabulary fk id of the type of this contributor.
     * 
     * @return integer $id Return the existing id, or the newly minted id. 
     */
    public function insertContributor($vhInfo, $id, $nameID, $name, $typeID)
    {
        if ($nameID == null)
        {
            /*
             * This did happen, but once we have good tests it should never happen again. Perhaps better to
             * add a "not null" to the db schema.
             */ 
            printf("SQL.php Fatal: \$nameID must not be null\n");
            exit();
        }
        if (! $id)
        {
            $id = $this->selectID();
        }
        $qq_2 = 'insert_contributor';
        $this->sdb->prepare($qq_2,
                            'insert into name_contributor
                            (version, main_id, id, name_id, short_name, name_type)
                            values
                            ($1, $2, $3, $4, $5, $6)');
        $this->sdb->execute($qq_2,
                            array($vhInfo['version'],
                                  $vhInfo['main_id'],
                                  $id,
                                  $nameID,
                                  $name,
                                  $typeID));
        $this->sdb->deallocate($qq_2);
        return $id;
    }

    /**
     * Insert a function record
     *
     * The SQL returns the inserted id which is used when inserting a date into table date_range. Function
     * uses the same vocabulary terms as occupation.
     *
     * If the $id arg is null, get a new id. Always return $id.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param integer $id Record id
     *
     * @param integer $type Function type controlled vocab term id
     *
     * @param string $vocabularySource The vocabulary source
     *
     * @param string $note Note for this function
     *
     * @param integer $term Function term controlled vocab id
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

    /**
     * Insert a Language link record
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param integer $id Record id of this languae link
     *
     * @param integer $languageID Language controlled vocab id
     *
     * @param integer $scriptID Scrip controlled vocab id
     *
     * @param string $vocabularySource Vocabulary source of this language link
     *
     * @param string $note A note
     *
     * @param string $fkTable Related table name
     *
     * @param integer $fkID Related table record id, foreign key
     *
     * @return integer $id The record id of this record.
     */
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
     * Insert into table subject.
     * Data is currently only a string from the Constellation. If $id is null, get
     * a new record id. Always return $id.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param integer $id Record id
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
     * Insert into table nationality.
     *
     * Data is currently only a string from the Constellation. If $id is null, get
     * a new record id. Always return $id.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param integer $id Record id
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
     * Insert into table gender.
     *
     * Data is currently only a string from the Constellation. If $id is null, get
     * a new record id. Always return $id.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param integer $id Record id
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
     * @param string[] $vhInfo associative list with keys: version, main_id.
     *
     * @param string $table The table this text is related to.
     *
     * @return string[][] Return list of an associative list with keys: id, version, main_id,
     * text. There may be multiple rows returned.
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
     * Insert text records.
     *
     * Generic core function supports insert into identically structured tables. Several tables have identical
     * structure so don't copy/paste, just call this. DBUtils has code to turn the return values into objects
     * in a Constellation object.
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
             * Trying something not approved is fatal.  Add an exception for this.
             */
            die("Tried to insert on non-approved table: $table\n");
        }
        if (! $id)
        {
            $id = $this->selectID();
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
                                            $text));
        $this->sdb->deallocate($qq);
        return $id;
    }

    /**
     * Insert into table convention_declaration.
     *
     * Data is currently only a string from the Constellation. If
     * $id is null, get a new record id. Always return $id.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param integer $id Record id
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
     * Insert into table mandate.
     *
     * Data is currently only a string from the Constellation. If
     * $id is null, get a new record id. Always return $id.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param integer $id Record id
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
     * Insert into table structure_genealogy.
     *
     * Data is currently only a string from the Constellation. If $id
     * is null, get a new record id. Always return $id.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param integer $id Record id
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
     * Insert into table structure_genealogy.
     *
     * Data is currently only a string from the Constellation. If $id
     * is null, get a new record id. Always return $id.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param integer $id Record id
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
     * text. There may be multiple rows returned.
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
     * text. There may be multiple rows returned.
     * 
     */
    public function selectMandate($vhInfo)
    {
        return $this->selectTextCore($vhInfo, 'mandate');
    }

    /**
     * Select GeneralContext records.
     *
     * DBUtils has code to turn the return values into objects in a
     * Constellation object.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @return string[][] Return list of an associative list with keys: id, version, main_id,
     * text. There may be multiple rows returned.
     * 
     */
    public function selectGeneralContext($vhInfo)
    {
        return $this->selectTextCore($vhInfo, 'general_context');
    }

    /**
     * Select conventionDeclaration records.
     *
     * DBUtils has code to turn the return values into objects in a
     * Constellation object.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @return string[][] Return list of an associative list with keys: id, version, main_id,
     * text. There may be multiple rows returned.
     * 
     */
    public function selectConventionDeclaration($vhInfo)
    {
        return $this->selectTextCore($vhInfo, 'convention_declaration');
    }

    /**
     * Insert a related identity
     *
     * related identity is also known as table related_identity, aka constellation relation, aka cpf relation,
     * aka ConstellationRelation object. We first insert into related_identity saving the inserted record id.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param integer $targetID The constellation id of the related entity (aka the relation)
     *
     * @param string $targetArkID The ARK of the related entity
     *
     * @param string $targetEntityType The entity type of the target relation (aka the other entity aka the related entity)
     *
     * @param integer $type A foreign key id of entityType, traditionally the xlink:arcrole of the relation
     * (aka relation type, a controlled vocabulary)
     *
     * @param integer $relationType A foreign key id. The CPF relation type of this relationship, originally
     * only used by AnF cpfRelation@cpfRelationType. Probably xlink:arcrole should be used instead of
     * this. The two seem related and/or redundant.
     *
     * @param string $content Content of this relation
     *
     * @param string $note A note, perhaps a descriptive note about the relationship
     *
     * @param integer $id The record (related_identity.id) id
     *
     * @return integer the record (related_identity.id) id
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
        
        return $id;
    }

    /**
     * Insert into table related_resource
     *
     * Use data from php ResourceRelation object. It is assumed that the calling code in DBUtils knows the php
     * to sql fields. Note keys in $argList have a fixed order.
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
     * Select from table nrd
     *
     * Table nrd has 1:1 fields for the constellation. We keep 1:1 fields here, although table version_history
     * is the center of everything. A class DBUtils method also called selectConstellation() knows all the SQL
     * methods to call to get the full constellation.
     *
     * It is intentional that the fields are not retrieved in any particular order because the row will be
     * saved as an associative list. That allows us to write the sql query in a more legible format.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @return string[] An associative list with keys: version, main_id, ark_id, entity_type.
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
     * Select biogHist
     *
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
            array_push($rowList, $row);
        }
        $this->sdb->deallocate($qq);
        return $rowList;
    }


    /**
     * Helper for selectOtherID()
     *
     * Mar 1 2016: The comment below is incomplete because we have lots of cases where there could be multiple
     * versions. All queries deal with multiple version by using a subquery. This function is probably
     * redundant.
     *
     * Select flat list of distinct id values meeting the version and main_id constraint. Specifically a
     * helper function for selectOtherID(). This deals with the possibility that a given otherid.id may
     * have several versions while other otherid.id values are different (and single) versions.
     *
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
     * select other IDs
     *
     * These were originally ID values of merged records. DBUtils has code that adds an otherRecordID to a
     * Constellation object.
     *
     * Mar 25 2016: The fix described below only worked in certain cases. It is unclear why the subquery was
     * not just turned into a join like all the other tables. Fixed and this query works in at least one case
     * where the original failed. It failed when the versions were not in order.
     *
     * I just noticed that otherid doesn't have is_deleted. There is a historical reason for that, but I
     * suspect history needs to be updated. Unless there is some really good reason otherid will never be
     * deleted. Or even edited? 
     * 
     * Mar 1 2016: Legacy code here did not used to have the subquery constraining the version. As a result,
     * that old code used matchORID() above and a foreach loop as well as a constraint in the query here. That
     * was all fairly odd, but worked. This code now follows our idiom for main_id and version constraint via
     * a subquery. As far as I can tell from the full CPF test, this works. I have diff'd the parse and
     * database versions, and the otherRecordID JSON looks correct.
     * 
     * select
     * id, version, main_id, text, uri, type
     * from otherid
     * where
     * version=(select max(version) from otherid where version<=$1)
     * and main_id=$2 order by id
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
        $qq = 'sorid';
        $this->sdb->prepare($qq,
                            'select
                            aa.id, aa.version, aa.main_id, aa.text, aa.uri, aa.type
                            from otherid as aa,
                            (select id,max(version) as version from otherid where version<=$1 and main_id=$2 group by id) as bb
                            where
                            aa.id = bb.id and 
                            aa.version = bb.version order by id asc');

        $all = array();
        $result = $this->sdb->execute($qq, array($vhInfo['version'], $vhInfo['main_id']));
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }

        $this->sdb->deallocate($qq);
        return $all;
    }


    /**
     * Select subjects.
     *
     * DBUtils has code to turn the return values into subjects in a Constellation object.
     *
     * Solve the multi-version problem by joining to a subquery.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @return string[][] Return list of an associative list with keys: id, version, main_id,
     * term_id.
     *
     * There may be multiple rows returned, which is perhaps sort of obvious because the return value is a
     * list of list.
     *
     */
    public function selectSubject($vhInfo)
    {
        $qq = 'ssubj';
        $this->sdb->prepare($qq,
                            'select
                            aa.id, aa.version, aa.main_id, aa.term_id
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
     * Insert legalStatus.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param integer $termID Vocabulary foreign key for the term.
     *
     * @param integer $id Record id from this object and table.
     *
     * @return no return value.
     *
     */
    public function insertLegalStatus($vhInfo, $id, $termID)
    {
        if (! $id)
        {
            $id = $this->selectID();
        }
        $qq = 'insert_subject';
        $this->sdb->prepare($qq,
                            'insert into legal_status
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
     * Select legalStatus.
     *
     * Like subject, these are directly linked to Constellation only, and not to any other tables. Therefore
     * we only need version and main_id.
     *
     * DBUtils has code to turn the returned values into legalStatus in a Constellation object.
     *
     * Solve the multi-version problem by joining to a subquery.
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @return string[][] Return list of an associative list with keys: id, version, main_id,
     * term_id. There may be multiple records returned.
     *
     */
    public function selectLegalStatus($vhInfo)
    {
        $qq = 'ssubj';
        $this->sdb->prepare($qq,
                            'select
                            aa.id, aa.version, aa.main_id, aa.term_id
                            from legal_status aa,
                            (select id, max(version) as version from legal_status where version<=$1 and main_id=$2 group by id) as bb
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
     * Select gender records.
     *
     * DBUtils has code to turn the return values into objects in a Constellation object.
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
     * Select nationality records.
     *
     * DBUtils has code to turn the return values into objects in a Constellation object.
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
     * Select occupation
     *
     * Return a list of lists. Code in DBUtils foreach's over the outer list, turning each inner list into an
     * Occupation object.
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
     * Select a related identity
     *
     * Related identity aka cpf relation. Code in DBUtils turns the returned array into a
     * ConstellationRelation object.
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
     * select related archival resource records
     *
     * Where $vhInfo 'version' and 'main_id'. Code in DBUtils knows how to turn the return value into a pgp
     * ResourceRelation object.
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
     * Select all function records
     *
     * Constrain on version and main_id. Code in DBUtils turns the return value into a SNACFunction object.
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
      * Select all names
      *
      * Constrain on version and main_id. Code in DBUtils turns each returned list into a NameEntry
      * object. Order the returned records by preference_score descending so that preferred names are at the
      * beginning of the returned list. For ties, we also order by id, just so we'll be consistent. The
      * consistency may help testing more than UI related issues (where names should consistently appear in
      * the same order each time the record is viewed.)
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
                            aa.version = bb.version order by preference_score desc,id asc');
        
        $name_result = $this->sdb->execute($qq_1,
                                           array($vhInfo['version'],
                                                 $vhInfo['main_id']));
        $all = array();
        while($name_row = $this->sdb->fetchrow($name_result))
        {
            /* 
             * printf("\nsn: id: %s version: %s main_id: %s original: %s is_deleted: %s\n",
             *        $name_row['id'],
             *        $name_row['version'],
             *        $name_row['main_id'],
             *        $name_row['original'],
             *        $name_row['is_deleted']);
             */
            array_push($all, $name_row);
        }
        $this->sdb->deallocate($qq_1);
        return $all;
    }

    // Contributor has issues. See comments in schema.sql. This will work for now.
    // Get each name, and for each name get each contributor.

    /**
     * Select contributor of a specific name, where name_contributor.name_id=name.id.
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
     * @param integer $nameID The foreign key record id from name.id
     *
     * @param integer $version The version number.
     *
     * @return string[] List of list, one inner list per contributor keys: id, version, main_id, type, name, name_id
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
     * Return a test constellation
     *
     * This is used for testing. Not really random. Get a record that has a date_range record. The query
     * doesn't need to say date_range.fk_id since fk_is is unique to date_range, but it makes the join
     * criteria somewhat more obvious.
     *
     * Note: Must select max(version_history.id) as version. The max() version is the Constellation version.
     *
     * Mar 4 2016: Changed "nrd.id=date_range.fk_id" to "nrd.main_id=date_range.fk_id" because getID of
     * nrd is main_id not id as with other tables and other objects. We changed this a while back, but
     * (oddly?) this didn't break until today.
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
                            nrd.main_id=date_range.fk_id and
                            nrd.main_id=version_history.main_id
                            and not date_range.is_deleted 
                            and version_history.status <> $1
                            group by version_history.main_id
                            order by version_history.main_id
                            limit 1');

        $result = $this->sdb->execute($qq, array($this->deleted));
        $row = $this->sdb->fetchrow($result);
        $this->sdb->deallocate($qq);
        return array($row['version'], $row['main_id']);
    }


    /**
     * Most recent version by status
     *
     * Helper function to return the most recent status version for a given main_id. If the status is anything
     * except deleted, then the version number must be greater than any existing deleted version. If the
     * deleted version is greater, then whatever status we were asked for was deleted.
     *
     * This can deal with situations where a record was deleted, then undeleted, but never published after
     * undelete. There is no current published for that record.
     *
     * v1:published
     * v2:deleted
     * v3:undelete 
     * v4:locked editing
     *
     * @param integer $mainID id value matching version_history.main_id.
     *
     * @param string $status Constellation status we need
     *
     * @return integer Version number from version_history.id returned as 'version', as is our convention.
     *
     */
    public function selectCurrentVersionByStatus($mainID, $status)
    {
        $deletedVersion = null;
        if ($status != $this->deleted)
        {
            /*
             * We need to know the version of a deleted constellation. If the most recent version is deleted,
             * we return false.
             *
             * In other words, we only return a version number if the most recent version is not deleted. The
             * tricky bit is that we are constrained by status, so we might not have been asked to return the
             * most recent version (absolute) but the most recent version with a given status. This is what
             * happens when we are asked for the most recent published version, and the absolute most recent
             * is locked editing.
             *
             * This is fine, and exactly what we planned for. A search of published sees the most recent
             * published, if the constellation has not been deleted more recently.
             *
             * This points up a wrinkle in our current implementation where all versions are in the same
             * table. We have to check for more recent deleted (and eventually embargo) because there are
             * multiple copies of the records. While copying data to edit and publish tables seems clumsy, it
             * may be a simpler and more efficient implementation than we are current using.
             */ 
            $deletedVersion = $this->selectCurrentVersionByStatus($mainID, $this->deleted);
        }
        $result = $this->sdb->query(
            'select max(id) as version
            from version_history
            where 
            version_history.main_id=$1 and status=$2',
            array($mainID, $status));

        $row = $this->sdb->fetchrow($result);
        $version = $row['version'];

        if ($version && (! $deletedVersion || $version > $deletedVersion))
        {
            return $version;
        }
        return false;
    }


    /**
     * Return the lowest main_id
     *
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
     * constellation id, so we call selectCurrentVersion() to get the current version for that
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
                            (select count(distinct(aa.id)),aa.main_id from name as aa
                            where aa.id not in (select id from name where is_deleted) group by main_id order by main_id) as zz
                            where
                            vh.main_id=zz.main_id and
                            vh.status <> $1 and 
                            zz.count>1 group by vh.main_id limit 1');

        $result = $this->sdb->execute($qq, array($this->deleted));
        $row = $this->sdb->fetchrow($result);

        $version = $this->selectCurrentVersion($row['main_id']);

        $this->sdb->deallocate($qq);
        return array('version' => $version,
                     'main_id' => $row['main_id']);
    }


    /**
     * Count vocabulary rows
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
     * Get a set of 100 records
     *
     * Only return data (version, main_id) that might be necessary for display
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
        $sql =
            'select max(id) as version,main_id
            from version_history
            where version_history.status <> $1
            group by main_id order by main_id limit 100';

        $result = $this->sdb->query($sql, array($this->deleted));
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            $nRow = $this->selectName(array('version' => $row['version'],
                                            'main_id' => $row['main_id']));
            if (count($nRow) == 0)
            {
                // Yikes, cannot have a constellation with zero names.
                printf("\nError: SQL.php No names for version: %s main_id: %s\n", $row['version'], $row['main_id']);
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

    /**
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

    /**
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
     * Core function for delete
     *
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
        // printf("\ntable: $table id: $id newVersion: $newVersion\n");
        $selectSQL =
                   "select aa.* from $table as aa,
                   (select id, max(version) as version from $table where version<=$1 and id=$2 group by id) as bb
                   where aa.version=bb.version and aa.id=bb.id";

        $result = $this->sdb->query($selectSQL, array($newVersion, $id));
        $xx = 0;
        $row = $this->sdb->fetchrow($result);
        if ($secondRow = $this->sdb->fetchrow($result))
        {
            /* This is a crude way to test for multiple rows.
             * 
             * This happened when some inserts during testing went wrong. Might be something to test for,
             * and/or add a primary key constraint. There can be only one main_id for a given id.
             */  
            printf("Error: sqlSetDeleted() selects multiple rows: %s for table: $table id: $id newVersion: $newVersion \n",
                   count($row));
            return;
        }

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
        // printf("del SQL: $updateSQL array: %s\n", var_export($row, 1));
        $newResult = $this->sdb->query($updateSQL, array_values($row));
    }

    /**
     * Count sibling name records
     *
     * Counts only the most recent.
     *
     * @param integer $recID The record id
     *
     * @return integer The count of sibling records matching $recID
     */
    public function siblingNameCount($recID)
    {
        /*
         * Get the main_id for $recID
         */ 
        $result = $this->sdb->query(
            "select aa.main_id from name as aa,
            (select id, main_id, max(version) as version from name group by id,main_id) as bb
            where aa.id=bb.id and not aa.is_deleted and aa.version=bb.version and aa.main_id=bb.main_id and aa.id=$1",
            array($recID));

        $row = $this->sdb->fetchrow($result);
        $mainID = $row['main_id'];

        /*
         * Use the main_id to find not is_deleted sibling names.
         */ 
        $result = $this->sdb->query(
            "select count(*) as count from name as aa,
            (select id, max(version) as version from name where main_id=$1 group by id) as bb
            where aa.id=bb.id and not aa.is_deleted and aa.version=bb.version",
            array($mainID));

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
     * Count names, current version, not deleted, for a single constellation.
     *
     * This is used to check if we are allowed to delete a name, because we must not delete the only name for
     * a constellation.
     *
     * Note that Postgres names the column from the count() function 'count', so we do not need to alias the
     * column. I used the explicit alias just to make intent clear.
     *
     * @param $mainID Integer constellation id usually from version_history.main_id.
     *
     * @return interger Number of names meeting the criteria. Zero if no names or if the query fails.
     *
     */
    public function parentCountNames($mainID)
    {
        $selectSQL =
            "select count(*) as count from name as aa,
            (select id, main_id, max(version) as version from name group by id,main_id) as bb
            where aa.id=bb.id and not aa.is_deleted and aa.version=bb.version and aa.main_id=bb.main_id and aa.main_id=$1";

        $result = $this->sdb->query($selectSQL, array($mainID));
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
     * Get Place By URI
     *
     * This method searches the database for a place URI and returns the entry
     *
     * @param string $term The "type" term for what type of vocabulary to search
     * @param string $query The string to search through the vocabulary
     */
    public function getPlaceByURI($uri) 
    {
        $result = $this->sdb->query('select *
                                    from geo_place
                                    where uri = $1 limit 1;',
                                    array($uri));

        while($row = $this->sdb->fetchrow($result))
        {
            return $row;
        }
        return null;

    }

    /**
     * Search Place Vocabulary
     *
     * This method allows searching the geo_place table for a given type and value
     *
     * @param string $query The string to search through the vocabulary
     */
    public function searchPlaceVocabulary($query)
    {
        $result = $this->sdb->query('select *
                                    from geo_place
                                    where name ilike $1 order by name asc limit 100;',
                array("%".$query."%"));
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }
        return $all;
    
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
     * Select all vocabulary from the database.
     *
     * This returns the vocabulary in a
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
