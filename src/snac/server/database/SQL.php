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
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

namespace snac\server\database;

use snac\exceptions\SNACDatabaseException;

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
     * @var \Monolog\Logger $logger the logger for this server
     */
    private $logger;


    /**
     * The constructor
     *
     * Makes the outside $db a local variable. I did this out of a general sense of avoiding
     * globals, but I'm unclear if this is really any better than using a globale $db variable. $db is
     * critical to the application, and is read-only after intialization. Breaking it or changing it in any
     * way will break everything, whether global or not. Passing it in here to get local scope doesn't meet
     * any clear need.
     *
     * The constructor does not enable logging for performance reasons. Use the function enableLogging() below
     * to enable it on an as-needed basis.
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
        $this->enableLogging();
    }


    /**
     * Get the DB Connector object
     *
     * Utility function to return the Database connector for this SQL object.
     *
     * @return \snac\server\database\DatabaseConnector The database connector for this SQL object
     */
    public function connectorObj()
    {
        return $this->sdb;
    }

    /**
     * Enable logging
     *
     * For various reasons, logging is not enabled by default. Call this to enabled it for objects of this class.
     *
     * Check that we don't have a logger before creating a new one. This can be called as often as one wants
     * with no problems.
     */
    public function enableLogging()
    {
        global $log;
        if (! $this->logger)
        {
            // create a log channel
            $this->logger = new \Monolog\Logger('SQL');
            $this->logger->pushHandler($log);
        }
    }

    /**
     * Wrap logging
     *
     * When logging is disabled, we don't want to call the logger because we don't want to generate errors. We
     * also don't want logs to just magically start up. Doing logging should be very intentional, especially
     * in a low level class like SQL. Call enableLogging() before calling logDebug().
     *
     * @param string $msg The logging messages
     *
     * @param string[] $debugArray An associative list of keys and values to send to the logger.
     */
    private function logDebug($msg, $debugArray)
    {
        if ($this->logger)
        {
            $this->logger->addDebug($msg, $debugArray);
        }
    }


    /**
     * Insert a new user aka appuser
     *
     * Insert a user into the db, returning the new record id. Field userid is not currently used.
     *
     * @param string $userName The username, unique, we initially are using email address
     * @param string $firstName The first name
     * @param string $lastName The last name
     * @param string $fullName The full name
     * @param string $avatar The avatar
     * @param string $avatarSmall The small avatar
     * @param string $avatarLarge The large avatar
     * @param string $email The email address, not unique
     * @param string $workEmail Work email address
     * @param string $workPhone Work phone number
     * @param integer $affiliationID Foreign key to ic_id of the SNAC constellation for affiliated institution
     * @param string $preferredRules Preferred descriptive name rules
     * @param boolean $active Is the user active
     * @return integer Record row id, unique, from sequence id_seq.
     */
    public function insertUser($userName,
                               $firstName,
                               $lastName,
                               $fullName,
                               $avatar,
                               $avatarSmall,
                               $avatarLarge,
                               $email,
                               $workEmail,
                               $workPhone,
                               $affiliationID,
                               $preferredRules,
                               $active)
    {
        $result = $this->sdb->query(
            'insert into appuser
            (username, first, last, fullname, avatar, avatar_small, avatar_large,
            email, work_email, work_phone, affiliation, preferred_rules, active)
            values ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13)
            returning id',
            array($userName,
                  $firstName,
                  $lastName,
                  $fullName,
                  $avatar,
                  $avatarSmall,
                  $avatarLarge,
                  $email,
                  $workEmail,
                  $workPhone,
                  $affiliationID,
                  $preferredRules,
                  $this->sdb->boolToPg($active)));
        $row = $this->sdb->fetchrow($result);
        return $row['id'];
    }

    /**
     * Really delete a user
     *
     * Used for testing only. Normal users are inactivated.
     *
     * Delete the user, and delete user role links.
     *
     * @param integer $appUserID The user id to delete.
     */
    public function deleteUser($appUserID)
    {
        $this->sdb->query('delete from appuser where id=$1', array($appUserID));
        $this->sdb->query('delete from appuser_role_link where uid=$1', array($appUserID));
    }

    /**
     * Really delete a role
     *
     * Used for testing only, maybe. In any case, deleting a role should be rare. To make this a little safer
     * it only deletes if the role is not in use.
     *
     * Before deleting a role, remove all privilege links for it. Otherwise it appears to the privilege code
     * that the privileges are still in use.
     *
     * What about either deleting the role from all users? Or not allowing the role to be deleted if still
     * used by some user?
     *
     * @throws \snac\exceptions\SNACDatabaseException
     *
     * @param integer $roleID An role id
     */
    public function deleteRole($roleID)
    {
        $result = $this->sdb->query(
            'select appuser.username from appuser,appuser_role_link as arl where  arl.rid=$1 and arl.uid=appuser.id',
            array($roleID));
        $usernames = "";
        while($row = $this->sdb->fetchrow($result))
        {
            $usernames .= $row['username'] . " ";
        }
        if ($usernames)
        {
            throw new \snac\exceptions\SNACDatabaseException("Tried to delete role still used by users: $usernames");
        }
        else
        {
            $this->sdb->query(
                'delete from privilege_role_link where rid=$1',
                array($roleID));
            $this->sdb->query(
                'delete from role where id=$1 and id not in (select distinct(rid) from appuser_role_link)',
                array($roleID));
        }
    }

    /**
     * Really delete a privilege
     *
     * Used for testing only, maybe. In any case, deleting a privilege should be rare. To make this a little safer
     * it only deletes if the privilege is not in use.
     *
     * @throws \snac\exceptions\SNACDatabaseException
     *
     * @param integer $privilegeID An privilege id
     */
    public function deletePrivilege($privilegeID)
    {
        $result = $this->sdb->query(
            'select role.label from role,privilege_role_link as prl where prl.pid=$1 and prl.rid=role.id',
            array($privilegeID));
        $labels = "";
        while($row = $this->sdb->fetchrow($result))
        {
            $labels .= $row['label'] . " ";
        }
        if ($labels)
        {
            throw new \snac\exceptions\SNACDatabaseException("Tried to delete privilege still used by roles: $labels");
        }
        else
        {
            $this->sdb->query(
                'delete from privilege where id=$1 and id not in (select distinct(pid) from privilege_role_link)',
                array($privilegeID));
        }
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
     * If the session exists for this user, return the record.
     *
     * @param integer $userID A user id
     *
     * @param string $accessToken A session token
     *
     * @return string[] The session record as a list with keys appuser_fk, access_token, expires.
     *
     */
    public function selectSession($userID, $accessToken)
    {
        $result = $this->sdb->query(
            'select * from session where appuser_fk=$1 and access_token=$2',
            array($userID, $accessToken));
        $row = $this->sdb->fetchrow($result);
        return $row;
    }


    /**
     * Update a session expiration timestamp
     *
     * @param string $accessToken A session token
     *
     * @param string $expires A session expiration timestamp
     *
     */
    public function updateSession($accessToken, $expires)
    {
        $this->sdb->query(
            'update session set expires=$1 where access_token=$2',
            array($expires, $accessToken));
    }

    /**
     * Update a session expiration timestamp
     *
     * @param int $userID The User's ID to update
     * @param string $accessToken A session token
     * @param string $extend An amount of time by which to extend the session
     * @return boolean true on success, false otherwise
     */
    public function updateByExtendingSession($userID, $accessToken, $extend)
    {
        $result = $this->sdb->query(
            'update session set expires=expires+$1 where access_token=$2 and appuser_fk=$3 returning appuser_fk',
            array($extend, $accessToken, $userID));
        $row = $this->sdb->fetchrow($result);
        if ($row && array_key_exists('appuser_fk', $row))
        {
            return true;
        }
        return false;
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
     * @param string $expires A session expiration timestamp
     *
     */
    public function insertSession($appUserID, $accessToken, $expires)
    {
        $result = $this->sdb->query(
            'insert into session (appuser_fk, access_token, expires) values ($1, $2, $3) returning appuser_fk',
            array($appUserID, $accessToken, $expires));
        $row = $this->sdb->fetchrow($result);
        if ($row && array_key_exists('appuser_fk', $row))
        {
            return true;
        }
        return false;
    }


    /**
     * Check that a session is active
     *
     * I'm sure there are Postgres docs for extract(), epoch from, at time zone 'utc', but this is a nice example.
     *
     * https://stackoverflow.com/questions/16609724/using-current-time-in-utc-as-default-value-in-postgresql
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
            "select count(*) from session where appuser_fk=$1 and access_token=$2 and expires >= extract(epoch from now() at time zone 'utc')",
            array($appUserID, $accessToken));
        $row = $this->sdb->fetchrow($result);
        if ($row['count'] == 1)
        {
            return true;
        }
        return false;
    }

    /**
     * Clear a session
     *
     * @param string $accessToken A session token
     */
    public function deleteSession($accessToken)
    {
        $result = $this->sdb->query(
            'delete from session where access_token=$1',
            array($accessToken));
    }

    /**
     * Clear all user sessions
     *
     * @param integer $appUserID The user id
     */
    public function deleteAllSession($appUserID)
    {
        $result = $this->sdb->query(
            'delete from session where appuser_fk=$1',
            array($appUserID));
    }



    /**
     * Insert a new user aka appuser
     *
     * Insert a user into the db, returning the new record id.
     *
     * By checking the returned id we at least know a record was updated in the database.
     *
     * @param integer $uid The row id aka user id aka numeric user id
     * @param string $firstName The first name
     * @param string $lastName The last name
     * @param string $fullName The full name
     * @param string $avatar The avatar
     * @param string $avatarSmall The small avatar
     * @param string $avatarLarge The large avatar
     * @param string $email The email address
     * @param string $userName The user name
     * @param string $workEmail Work email
     * @param string $workPhone Work phone
     * @param integer $affiliationID Constellation ID aka ic_id integer version_history.id
     * @param string $preferredRules Preferred name descriptive rules.
     * @param boolean $active Is the user active
     * @return boolean Whether the SQL statement succeeded
     */
    public function updateUser($uid, $firstName, $lastName, $fullName, $avatar, $avatarSmall, $avatarLarge, $email, $userName,
                               $workEmail, $workPhone, $affiliationID, $preferredRules, $active)
    {
        $result = $this->sdb->query(
            'update appuser set first=$2, last=$3, fullname=$4, avatar=$5, avatar_small=$6,
            avatar_large=$7, email=$8, userName=$9, work_email=$10, work_phone=$11, affiliation=$12,
            preferred_rules=$13, active=$14
            where appuser.id=$1 returning id',
            array($uid,
                  $firstName,
                  $lastName,
                  $fullName,
                  $avatar,
                  $avatarSmall,
                  $avatarLarge,
                  $email,
                  $userName,
                  $workEmail,
                  $workPhone,
                  $affiliationID,
                  $preferredRules,
                  $this->sdb->boolToPg($active)));
        $row = $this->sdb->fetchrow($result);
        if ($row && array_key_exists('id', $row))
        {
            return true;
        }
        return false;
    }

    /**
     * Get user id email
     *
     * Return the first user record based on email. Email is not unique, so there may be multiple users with
     * the same email address. This simply returns the first appuser record found. See the commentary with
     * function readUser() in DBUser.php
     *
     * @param string $email Email to look up for the associated User
     *
     * @return string[] A list with keys: id, active, username, email, first, last, fullname, avatar,
     * avatar_small, avatar_large, affiliation. Or false is returned.
     */
    public function selectUserByEmail($email)
    {
        $result = $this->sdb->query("select id from appuser where lower(email) = lower($1) limit 1",
                                    array($email));
        $row = $this->sdb->fetchrow($result);
        if ($row && array_key_exists('id', $row))
        {
            /*
             * Call selectUserByID() to avoid all copy/paste code.
             */
            $rec = $this->selectUserByID($row['id']);
            return $rec;
        }
        return false;
    }


    /**
     * Get user id from user name
     *
     * Return user record based on username aka user name aka userName. Field password is not returned
     *
     * @param string $userName User name, a unique string, probably the user email
     *
     * @return string[] A list with keys: id, active, username, email, first, last, fullname, avatar, avatar_small, avatar_large
     */
    public function selectUserByUserName($userName)
    {
        $result = $this->sdb->query("select id from appuser where lower(username) = lower($1)",
                                    array($userName));
        $row = $this->sdb->fetchrow($result);
        if ($row && array_key_exists('id', $row))
        {
            /*
             * Call selectUserByID() to avoid all copy/paste code.
             */
            $rec = $this->selectUserByID($row['id']);
            return $rec;
        }
        return false;
    }

    /**
     * Select user record from database
     *
     * @param integer $uid User id, aka appuser.id aka row id.
     *
     * @return string[] Array with keys: id, first, last, fullname, avatar, avatar_small, avatar_large, email, work_email,
     * work_phone, affiliation, preferred_rules
     */
    public function selectUserByID($uid)
    {
        $result = $this->sdb->query(
            "select id,active,username,email,first,last,
            fullname,avatar,avatar_small,avatar_large,work_email, work_phone, affiliation,preferred_rules
            from appuser where appuser.id=$1",
            array($uid));
        $row = $this->sdb->fetchrow($result);
        if ($row && array_key_exists('active', $row))
        {
            $row['active'] = $this->sdb->pgToBool($row['active']);
            return $row;
        }
        return false;
    }

    /**
     * Select all user row id values from database
     *
     * If we have a non-null, non-empty $affiliationID then constrain the query to only return users
     * associated with that institution.
     *
     * Paremeters are not optional. The calling code needs to pass both params, and must use some logic to
     * determine what param values need to be passed.
     *
     * @param boolean $everyone True to return all users active and inactive. False to return active only.
     *
     * @param integer $affiliationID If non-null (true in any php sense of true), constrain on this
     * affiliation ID.
     *
     * @return integer[] Array of row id integer values.
     */
    public function selectAllUserIDList($everyone, $affiliationID)
    {
        /*
         * $vars are interpolated in "" strings. $1 is not interpolated.
         *
         * Yes, we are building a sql query via string interpolation. We are not using any outside data, so
         * there's no risk of sql injection.
         *
         * $affiliation really needs to be a greater than zero integer, so we could test for is_int() && >
         * zero which might be better (more specific).
         */
        $activeClause = "";
        if ($affiliationID)
        {
            if (! $everyone)
            {
                $activeClause = " active and ";
            }
            $query = "select id from appuser where $activeClause affiliation=$1";
            $result = $this->sdb->query($query, array($affiliationID));
        }
        else
        {
            if (! $everyone)
            {
                $activeClause = " where active";
            }
            $query = "select id from appuser $activeClause";
            $result = $this->sdb->query($query, array());
        }
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row['id']);
        }
        return $all;
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
     * @param integer $newRoleID A role id
     */
    public function insertRoleLink($uid, $newRoleID)
    {
        $this->sdb->query("insert into appuser_role_link (uid, rid) values ($1, $2)",
                          array($uid, $newRoleID));
    }


    /**
     * Add a privilege to a role
     *
     * Link a privilege to a role.
     *
     * @param integer $rID Role id, aka role.id aka row id.
     * @param integer $pID A privilege id.
     */
    public function insertPrivilegeRoleLink($rID, $pID)
    {
        try {
        $this->sdb->query("insert into privilege_role_link (rid, pid) values ($1, $2)",
                          array($rID, $pID));
        }
        catch ( \snac\exceptions\SNACDatabaseException $e) {
            // This should only happen if privilege_role_link already exists
        }
    }



    /**
     * Delete a privilege from a role
     *
     * @param integer $rID Role id, aka role.id aka row id.
     * @param integer $pID A privilege id.
     */
    public function deletePrivilegeRoleLink($rID, $pID)
    {
        $this->sdb->query("delete from privilege_role_link where rid=$1 and pid=$2",
                          array($rID, $pID));
    }


    /**
     * Add a role by label
     *
     * Use "returning rid" to detect if the query succeeded. If it fails, no rid will be returned.
     *
     * This is a conditional insert, and it relies on the values coming from a select statement. The select
     * supplying the values has a combination of hard coded $1 and query derived values. Selects which supply
     * values may have a where clause and when there are no records supplied by the select, nothing is
     * inserted.
     *
     * The "id not in..." prevents adding the same role twice.
     *
     * @param integer $uid User id, aka appuser.id aka row id.
     * @param string $roleLabel A role label
     * @return boolean True if successful, false otherwise.
     */
    public function insertRoleByLabel($uid, $roleLabel)
    {
        $qq =
            "insert into appuser_role_link (uid, rid) select $1, (select id from role where label=$2)
            where
            (select id from role where label=$2 and id not in (select rid from appuser_role_link)) is not null
            returning rid";

        $result = $this->sdb->query($qq, array($uid, $roleLabel));
        $row = $this->sdb->fetchrow($result);
        if ($row['rid'])
        {
            return true;
        }
        return false;
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
     * @return integer The inserted row id.
     */
    public function insertRole($label, $description)
    {
        $result = $this->sdb->query("insert into role (label, description) values ($1, $2) returning id",
                          array($label, $description));
        $row = $this->sdb->fetchrow($result);
        return $row['id'];
    }

    /**
     * Update a role.
     *
     * @param integer $rid Role row id
     *
     * @param string $label Role label
     *
     * @param string $description Role description
     */
    public function updateRole($rid, $label, $description)
    {
        $result = $this->sdb->query("update role set label=$2, description=$3 where id=$1",
                                    array($rid, $label, $description));
        $row = $this->sdb->fetchrow($result);
    }



    /**
     * Select a privilege record
     *
     * Get all fields of a single privilege record matching id $pid.
     *
     * @param integer $pid Privilege ID value.
     *
     * @return string[] All fields of a single privilege record.
     */
    public function selectPrivilege($pid)
    {
        $result = $this->sdb->query("select * from privilege where id=$1",
                                    array($pid));
        $row = $this->sdb->fetchrow($result);
        return $row;
    }

    /**
     * Return a list of privilege id values for a role
     *
     * @param integer $rid A role id value
     *
     * @return integer[] List of related privilege ID values.
     */
    public function selectRolePrivilegeList($rid)
    {
        $result = $this->sdb->query("select pid from privilege_role_link where rid=$1", array($rid));
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row['pid']);
        }
        return $all;
    }

    /**
     * Select IDs of all privilege records
     *
     * Return a list privilege IDs
     *
     * @return integer[] List of strings for each privileges. We expect the calling code in DBUser.php to send
     * each element of the list to populatePrivilege().
     */
    public function selectAllPrivilegeIDs()
    {
        $result = $this->sdb->query("select id from privilege order by label", array());
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row['id']);
        }
        return $all;
    }



    /**
     * Insert a new privilege.
     *
     * Insert a new privilege and return the privilege's id.
     *
     * @param string $label Privilege label
     *
     * @param string $description Privilege description
     *
     * @return integer Privilege id
     */
    public function insertPrivilege($label, $description)
    {
        $result = $this->sdb->query("insert into privilege (label, description) values ($1, $2) returning id",
                          array($label, $description));
        $row = $this->sdb->fetchrow($result);
        return $row['id'];
    }


    /**
     * Update a privilege.
     *
     * @param integer $pid Privilege record id
     *
     * @param string $label Privilege label
     *
     * @param string $description Privilege description
     */
    public function updatePrivilege($pid, $label, $description)
    {
        $result = $this->sdb->query("update privilege set label=%2, description=%3 where id=$1",
                          array($pid, $label, $description));
        $row = $this->sdb->fetchrow($result);
    }



    /**
     * Delete a role from a user
     *
     * Deleted a link role.
     *
     * @param integer $uid User id, aka appuser.id aka row id.
     * @param integer $roleID A role id
     */
    public function deleteRoleLink($uid, $roleID)
    {
        $this->sdb->query("delete from appuser_role_link where uid=$1 and rid=$2",
                          array($uid, $roleID));
    }

    /**
     * Select one role record
     *
     * Get all fields of a single role. Also, get a list of related pids, returning it as a simple array.
     *
     * If we really always (and only?) call selectRolePrivilegeList() from here, and nowhere else, could we do
     * a join and gain some efficiency? Calling code would have to be modified.
     *
     * @param int $rid Role ID to look up
     * @return string[] Return associative array of role information. Also includes key 'pid_list' which is a
     * list of privilege ids for the given role.
     */
    public function selectRole($rid)
    {
        $result = $this->sdb->query("select * from role where id=$1",
                                    array($rid));
        $row = $this->sdb->fetchrow($result);
        $row['pid_list'] = $this->selectRolePrivilegeList($row['id']);
        return $row;
    }

    /**
     * Select all role record IDs
     *
     * Get all the IDs of all roles.
     *
     * @return integer[] Return list of ID values. The higher level calling code is expected to send each ID
     * to populateRole().
     */
    public function selectAllRoleIDs()
    {
        $result = $this->sdb->query("select id from role order by label asc", array());
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row['id']);
        }
        return $all;
    }

    /**
     * Select user role record IDs
     *
     * Select all the IDs of roles for a single user. Higher level code will use each id to call
     * populateRole().
     *
     * @param int $appUserID The numeric ID for the user for whom to list roles.
     *
     * @return integer[] Return list of ID values. We expect the higher level calling code to pass each ID to
     * populateRole().
     */
    public function selectUserRoleIDs($appUserID)
    {
        $result = $this->sdb->query("select role.id from role,appuser_role_link
                                    where appuser_role_link.uid=$1 and role.id=rid order by label asc",
                                    array($appUserID));
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row['id']);
        }
        return $all;
    }


    /**
     * Select user API key data
     *
     * Selects all API key data for the given user id
     *
     * @param int $appUserID The numeric ID for the user
     *
     * @return string[][] A 2-d associative array of the user's API-key data out of Postgres
     */
    public function selectUserKeys($appUserID)
    {
        $result = $this->sdb->query("select * from api_keys
                                    where uid=$1 order by generated asc",
                                    array($appUserID));
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }
        return $all;
    }

    /**
     * Save User Key
     *
     * Save the given API key with provided label for the given user.  This method will
     * also have Postgres auto-generate an expiration time and unique database ID for the
     * key.  The key is stored using PHP's built-in password hashing scheme, which is
     * cryptographically secure; the key is unrecoverable from the database since this method
     * is a one-way hash.
     *
     * @param int $appUserID The numeric ID for the user
     * @param string $key The clear-text key to save (encrypted)
     * @param string $label The label for the key (stored in clear text)
     *
     * @return string[] All data for the inserted key as an associative array (includes expires and generated time)
     */
    public function saveUserKey($appUserID, $key, $label)
    {
        // encrypt the key in the database
        $encrypt = password_hash($key, PASSWORD_DEFAULT);
        if ($encrypt === false)
            return null;

        $result = $this->sdb->query("insert into api_keys (uid, label, key) values ($1, $2, $3)
                                        returning *;", [$appUserID, $label, $encrypt]);

        // Return only the data returned (one row);
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            $all = $row;
        }
        return $all;
    }

    /**
     * Revoke User Key
     *
     * Deletes the key in the database associtated with the given user id and label.
     *
     * @param int $appUserID The numeric ID for the user
     * @param string $label The label for the key
     *
     * @return boolean True if successfully removed, false otherwise
     */
    public function revokeUserKey($appUserID, $label)
    {
        // Check to see if the key exists for the user first
        $result = $this->sdb->query("select id from api_keys where uid=$1 and label=$2;", [$appUserID, $label]);

        // Return only the data returned (one row);
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            $all = $row;
        }

        // If key exists for the user, then delete it
        if (!empty($all) && isset($all["id"])) {
            $result = $this->sdb->query("delete from api_keys where id=$1 returning *;", [$all["id"]]);
            // Return only the data returned (one row);
            $check = array();
            while($row = $this->sdb->fetchrow($result))
            {
                $check = $row;
            }

            // Sanity check: did we actually delete something?
            if (empty($all))
                return false;
            return true;
        }

        return false;
    }

    /**
     * Select Key Data by Key
     *
     * This method is used for authentication purposes, when we have the clear-text key
     * (which can NOT be used to index the database) and the label (which can be used
     * to index the database).  This method gets all keys matching the provided label (which
     * may be multiple).  Then, for each key, it uses PHP's built-in cryptographically
     * secure password verifier to see if the encrypted key in the database matches the
     * provided clear-text key.  If the keys match, it returns that row's data (including
     * userID, expires, and generated times).
     *
     * WARNING: No part of this method should be logged on production! It contains clear-text
     * API keys.
     *
     * @param string $key The CLEAR-TEXT API Key
     * @param string $label The label for the API Key
     * @return string[]|null The associated data for the key (userid, expires, generated) or null if not found
     */
    public function selectAPIKeyByKey($key, $label)
    {
        $result = $this->sdb->query("select * from api_keys where label = $1;", [$label]);

        while($row = $this->sdb->fetchrow($result))
        {
            if (password_verify($key, $row["key"]))
                return $row;
        }
        return null;
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
     * @return integer Version number from version_history.version
     *
     */
    public function selectCurrentVersion($mainID)
    {
        $result = $this->sdb->query(
                                    'select max(version) as version
                                    from version_history
                                    where version_history.id=$1',
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
                                    'select status from version_history where id=$1 and version=$2',
                                    array($mainID, $version));
        $row = $this->sdb->fetchrow($result);
        return $row['status'];
    }

    /**
     * User for Constellation by mainID and version number
     *
     * Get the version_history user id editing this constellation.
     *
     * @param integer $mainID The constellation ID
     *
     * @param integer $version A specific version number. We assume that you have called some function to get
     * a specific version number. Null is not ok, and guesses are not ok. This will not select for <$version.
     *
     * @return int The user id who most recently touched this constellation
     */
    public function selectCurrentUserForConstellation($mainID, $version)
    {
        $result = $this->sdb->query(
                                    'select user_id from version_history where id=$1 and version=$2',
                                    array($mainID, $version));
        $row = $this->sdb->fetchrow($result);
        return $row['user_id'];
    }


    /**
     * Version History Log Note by mainID and version number
     *
     * Get the version_history log note.
     *
     * @param integer $mainID The constellation ID
     *
     * @param integer $version A specific version number. We assume that you have called some function to get
     * a specific version number. Null is not ok, and guesses are not ok. This will not select for <$version.
     *
     * @return string The log note string of that version for the given mainID.
     */
    public function selectCurrentNoteForConstellation($mainID, $version)
    {
        $result = $this->sdb->query(
                                    'select note from version_history where id=$1 and version=$2',
                                    array($mainID, $version));
        $row = $this->sdb->fetchrow($result);
        return $row['note'];
    }


    /**
     * Select Constellations User Recently Edited
     *
     * Gets a list of the version history id, version that have been recently edited
     * by the user (even if they have been published by someone else later.
     *
     * @param integer $appUserID The user ID to query
     * @param integer $limit The maximum number of results to return
     * @param integer $offset Where in the list of results to start
     * @return string[][] The list of ic_id and version numbers recently edited
     */
    public function selectConstellationsUserEdited($appUserID, $limit, $offset)
    {
        $limitStr = '';
        $offsetStr = '';
        $limitStr = $this->doLOValue('limit', $limit);
        $offsetStr = $this->doLOValue('offset', $offset);

        $queryString = sprintf(
            'select id as ic_id, max(version) as latest_version from
                (select distinct id, version from version_history where user_id = $1) as a
                group by id order by latest_version desc %s %s;', $limitStr, $offsetStr);


        $this->logger->addDebug("Sending the following SQL request: " . $queryString);

        $result = $this->sdb->query($queryString,
                                    array($appUserID));
        $this->logger->addDebug("Done request");
        $all = array();
        while($row = $this->sdb->fetchrow($result)) {
            array_push($all, $row);
        }
        return $all;
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
     * @param boolean $secondary Whether to search the secondary user field rather than the primary user field.
     *
     * @return string[] Associative list with keys 'version', 'ic_id'. Values are integers.
     */
    public function selectEditList($appUserID, $status = 'locked editing', $limit, $offset, $secondary)
    {
        $limitStr = '';
        $offsetStr = '';
        $limitStr = $this->doLOValue('limit', $limit);
        $offsetStr = $this->doLOValue('offset', $offset);

        $queryString = sprintf(
                'select aa.version, aa.id as ic_id
                    from version_history as aa,
                        (select max(version) as version, id from version_history
                            where id in (select distinct id from version_history where user_id=$1)
                            group by id) as cc
                    where
                        aa.id=cc.id and
                        aa.version=cc.version and
                        aa.user_id=$1 and
                        aa.status = $2 %s %s', $limitStr, $offsetStr);
        if ($secondary) {
            $queryString = sprintf(
                    'select aa.version, aa.id as ic_id
                        from version_history as aa,
                            (select max(version) as version, id from version_history
                                where id in (select distinct id from version_history where user_id_secondary=$1)
                                group by id) as cc
                        where
                            aa.id=cc.id and
                            aa.version=cc.version and
                            aa.user_id_secondary=$1 and
                            aa.status = $2 %s %s', $limitStr, $offsetStr);
        }

        $this->logger->addDebug("Sending the following SQL request: " . $queryString);

        $result = $this->sdb->query($queryString,
                                    array($appUserID, $status));
        $this->logger->addDebug("Done request");
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            $mainID = $row['ic_id'];
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
     * @return string[] Associative list with keys 'version', 'ic_id'. Values are integers.
     */
    public function selectListByStatus($status = 'published', $limit, $offset)
    {
        $limitStr = '';
        $offsetStr = '';
        $limitStr = $this->doLOValue('limit', $limit);
        $offsetStr = $this->doLOValue('offset', $offset);
        $queryString = sprintf(
            'select aa.version, aa.id as ic_id
                from version_history as aa,
                    (select max(version) as version, id from version_history
                    where id in (select distinct id from version_history where status=$1)
                    group by id) as cc
                where
                    aa.id=cc.id and
                    aa.version=cc.version and
                    aa.status = $1
                order by aa.version desc %s %s', $limitStr, $offsetStr);

        $result = $this->sdb->query($queryString,
                                    array($status));
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            $mainID = $row['ic_id'];
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
     * select Current IC_IDs by arkID
     *
     * Returns the current IC ic_id (main_id in Tom's code) for the given Ark ID.  If the constellation pointed
     * to by the ARK was merged, this will return the the ic_id for the good, merged record (NOT the tombstoned version).
     * If the ic was split, it will return a list of ic_ids relating to the split.
     *
     * @param string $arkID The ARK id of a constellation
     * @return integer[] The constellation IDs aka mainIDs akd ic_ids aka version_history.ids.
     */
    public function selectCurrentMainIDsForArk($arkID)
    {
        $result = $this->sdb->query(
            'select current_ic_id
            from constellation_lookup
            where
            ark_id=$1',
            array($arkID));
        $all = array();
        while ($row = $this->sdb->fetchrow($result)) {
            array_push($all, $row['current_ic_id']);
        }
        return $all;
    }

    /**
     * select Current IC_IDs by otherID
     *
     * Returns the current IC ic_id (main_id in Tom's code) for the given OtherRecordID.  If the constellation pointed
     * to by the OtherID was merged, this will return the the ic_id for the good, merged record (NOT the tombstoned version).
     * If the ic was split, it will return a list of ic_ids relating to the split.
     *
     * @param string $otherID The other record id (sameas) of a constellation
     * @return integer[] The constellation IDs aka mainIDs akd ic_ids aka version_history.ids.
     */
    public function selectCurrentMainIDsForOtherID($otherID)
    {
        $result = $this->sdb->query(
            'select distinct l.current_ic_id
            from (
                select ic_id, id, max(version) as version from (
                    select distinct ic_id, id, version from otherid where uri = $1
                ) iq group by id, ic_id
            ) o, otherid oid, constellation_lookup l
            where o.id = oid.id and o.version = oid.version and not oid.is_deleted and
            o.ic_id = l.ic_id',
            array($otherID));
        $all = array();
        while ($row = $this->sdb->fetchrow($result)) {
            array_push($all, $row['current_ic_id']);
        }
        return $all;
    }

    /**
     * select Current IC_IDs by IC_ID
     *
     * Returns the current IC ic_id (main_id in Tom's code) for the given ic_id.  If the constellation pointed
     * to by the given ic_id was merged, this will return the the ic_id for the good, merged record (NOT the tombstoned version).
     * If the ic was split, it will return a list of ic_ids relating to the split.
     *
     * @param integer $icid The Constellation ID of the IC to lookup
     * @return integer[] The constellation IDs aka mainIDs akd ic_ids aka version_history.ids.
     */
    public function selectCurrentMainIDsForID($icid)
    {
        $result = $this->sdb->query(
            'select current_ic_id
            from constellation_lookup
            where
            ic_id=$1',
            array($icid));
        $all = array();
        while ($row = $this->sdb->fetchrow($result)) {
            array_push($all, $row['current_ic_id']);
        }
        return $all;
    }

    /**
     * Update the constellation lookup table
     *
     * Updates the mappings for icid/ark to current icid/ark by modifiying the current values.  If
     * the given icid and ark are not in the table, this method adds them first.  The `$currents` parameter
     * may contain solely the other parameters, i.e. `$currents = [$icid => $ark]`, with `count($currents) = 1`,
     * but in general the domain of this function (`$icid`, `$ark`) should NEVER appear in the range (`$currents`).
     *
     * This is a very tricky set of logic meant to maintain the endpoints of an implicit DAG of merge
     * and split activity throughout the system. The lookup table maintains a list of id/ark (possibly stale) to
     * current correct id/arks, but the lookup may not be unique (in the case of a split).  This method attempts
     * to keep track of updates to the lookup table, such as removing duplicates on a merge of a previous split,
     * etc.
     *
     * We do not store the entire DAG, but use an implicit 2-level version.  In the traditional DAG, we would need
     * to follow the path from a queried icid down to the end of the path (no out-edges) to get the current icid(s)
     * for that icid.  In our implicit DAG, we only store links from each node (possibly duplicated) directly to the
     * DAG's paths' endpoint(s).  Therefore, those links must be updated when a new endpoint (on a merge) or new endpoints
     * (on a split) are added and the DAG modified.  The DAG itself may be reconstructed from the version_history table.
     *
     * @param int $icid The ICID to re-map
     * @param string $ark The Ark ID to re-map
     * @param string[] $currents The associative array of icid->ark for current ids/arks to redirect to.
     */
    public function updateConstellationLookup($icid, $ark, $currents=null) {
        if ($currents === null || empty($currents)) {
            return;
        }

        $result = $this->sdb->query('select ic_id from constellation_lookup where ic_id = $1;', array($icid));
        if ($this->sdb->fetchrow($result) === false) {
            // If the ICID doesn't exist, then add it. Nothing could be pointing to it in this case.
            foreach ($currents as $currentICID => $currentArk) {
                $result = $this->sdb->query('insert into constellation_lookup
                    (ic_id, ark_id, current_ic_id, current_ark_id) values ($1, $2, $3, $4);',
                    array($icid, $ark, $currentICID, $currentArk));
            }
        } else if (count($currents) == 1 && isset($currents[$icid])) {
            // If the icid and the current[icid] are both the same, then we should not redirect because id->id already exists
            // and we don't want to accidentally delete the lookup
            return;
        } else {
            // Not trying to update a self-referencing link, so we should modify the table appropriately
            // Note: In this case, the user should NEVER ask to redirect "A" to "A" and "B"
            //       (the domain should not be a subset of the range)

            // Get anything pointing to $icid, as it will need to be redirected to each icid in the currents list
            $result1 = $this->sdb->query('select ic_id, ark_id, current_ic_id, current_ark_id from constellation_lookup where current_ic_id = $1;', array($icid));
            $toDelete = array();
            $toInsert = array();
            while($row = $this->sdb->fetchrow($result1))
            {
                // Keep a list if icid=>ark that will need to be rewritten to the database
                $toInsert[$row["ic_id"]] = $row["ark_id"];
                // We will eventually want to delete these
                array_push($toDelete, $row);
            }

            // Build the SQL prepare string
            $tmp = array();
            for ($i = 1; $i <= count($currents); $i++) {
                array_push($tmp, '$'.$i);
            }
            $inString = implode(",", $tmp);

            // Build a cache for relevant pieces of the lookup table
            $result2 = $this->sdb->query('select * from constellation_lookup where current_ic_id in ('.$inString.');',
                                        array_keys($currents));
            $cache = array();
            while($row = $this->sdb->fetchrow($result2))
            {
                if (!isset($cache[$row['current_ic_id']]))
                    $cache[$row['current_ic_id']] = array();
                // Cache is a reverse lookup (current <- ic_id) where current is in our list of $currents
                $cache[$row['current_ic_id']][$row['ic_id']] = $row['ark_id'];
            }

            // Do the insert of the correct redirects
            foreach ($toInsert as $oICID => $oArk) {
                foreach ($currents as $nICID => $nArk) {
                    if (!isset($cache[$nICID]) || !isset($cache[$nICID][$oICID])) {
                        // if nothing is pointing to nICID OR oICID is not pointing to nICID, then add the link
                        $result = $this->sdb->query('insert into constellation_lookup
                            (ic_id, ark_id, current_ic_id, current_ark_id) values ($1, $2, $3, $4);',
                            array($oICID, $oArk, $nICID, $nArk));
                    }
                }
            }

            // Remove any of the old redirects that are no longer active.
            foreach ($toDelete as $row) {
                $result = $this->sdb->query('delete from constellation_lookup
                    where current_ic_id = $3 and current_ark_id = $4 and
                    current_ic_id = $1 and current_ark_id = $2;',
                    $row);
            }
        }

    }

    /**
     * Add Not-Same Assertion
     *
     * Add a not-same assertion between icid1 and icid2, made by the given user and
     * having the given assertion statement.
     *
     * @param int $icid1 The first ICID
     * @param int $icid2 The second ICID
     * @param int $userid The ID of the asserting user
     * @param string $assertion The assertion to include with the link
     * @return boolean True if successful
     */
    public function addNotSameAssertion($icid1, $icid2, $userid, $assertion) {
        $result = $this->sdb->query("insert into not_same
                (ic_id1, ic_id2, user_id, assertion)
                values ($1, $2, $3, $4) returning *;",
            array($icid1, $icid2, $userid, $assertion));
        return true;
    }

    /**
     * List Assertions for Constellation
     *
     * Reads out the assertions from the database for the given Constellation.
     * If none exist, it will return null.
     *
     * @param int $icid Constellation ID to look up
     * @return string[]|null The array of data or null if no assertion exists
     */
    public function listAssertions($icid) {
        if ($icid == null)
            return null;

        $result = $this->sdb->query("select * from not_same where
            ic_id1 = $1 or ic_id2 = $1;",
                array($icid));
        $all = array();

        while($row = $this->sdb->fetchrow($result)) {
            array_push($all, array_merge($row, array("type"=>"not_same")));
        }

        if (count($all) < 1) {
            return null;
        }

        return $all;
    }

    /**
     * Read Assertion
     *
     * Reads out the assertion data from the database for the given type between
     * these two Constellation IDs.  If none exists, it will return null.
     *
     * @param string $type The type of the assertion (i.e. "not_same")
     * @param int $icid1 One IC ID
     * @param int $icid2 Another IC ID
     * @return string[]|null The array of data or null if no assertion exists
     */
    public function readAssertion($type, $icid1, $icid2) {
        if ($type == "not_same") {
            $result = $this->sdb->query("select * from not_same where
                (ic_id1 = $1 and ic_id2 = $2) or (ic_id2 = $1 and ic_id1 = $2);",
                array($icid1, $icid2));
            $all = array();

            while($row = $this->sdb->fetchrow($result)) {
                array_push($all, $row);
            }

            if (count($all) != 1) {
                return null;
            }

            $return = $all[0];
            $return["type"] = $type;
            return $return;
        }
        return null;
    }

    /**
     * Add Maybe-Same Link
     *
     * Add a maybe-same link between icid1 and icid2, made by the given user and
     * having the given assertion statement.
     *
     * @param int $icid1 The first ICID
     * @param int $icid2 The second ICID
     * @param int $userid The ID of the asserting user
     * @param string $assertion The assertion to include with the link
     * @return boolean True if successful
     */
    public function addMaybeSameLink($icid1, $icid2, $userid, $assertion) {
        $result = $this->sdb->query("insert into maybe_same
                (ic_id1, ic_id2, user_id, note)
                values ($1, $2, $3, $4) returning *;",
            array($icid1, $icid2, $userid, $assertion));
        return true;
    }



    /**
     * Update MaybeSame Links
     *
     * Updates the maybe same table to point any maybesame references from icid to currentICID.
     * After doing an update, it will remove any self-referencing maybesames.
     *
     * @param int $icid The icid to re-map in the maybe same table
     * @param int $currentICID The updated ICID to use instead of $icid
     */
    public function updateMaybeSameLinks($icid, $currentICID) {
        $result = $this->sdb->query('update maybe_same
            set ic_id1 = $2 where ic_id1 = $1;',
            array($icid, $currentICID));
        $result = $this->sdb->query('update maybe_same
            set ic_id2 = $2 where ic_id2 = $1;',
            array($icid, $currentICID));

        // Remove any self-referencing links
        $result = $this->sdb->query('delete from maybe_same where ic_id1 = ic_id2;', array());
    }

    /**
     * Remove MaybeSame Links
     *
     * Removes the maybe same links between the two ic_ids given.
     *
     * @param int $icid1 One ICID
     * @param int $icid2 Another ICID
     */
    public function removeMaybeSameLink($icid1, $icid2) {
        $result = $this->sdb->query('delete from maybe_same where (ic_id1 = $1 and ic_id2 = $2) or (ic_id2 = $1 and ic_id1 = $2);',
            array($icid1, $icid2));
    }

    /**
     * Select records from table source by foreign key
     *
     * Constrain sub query where fk_id, but group by id and return max(version) by id. Remember, our unique
     * key is always id,version. Joining the fk_id constrained subquery with the table on id and version gives
     * us all of the relevant id,version records, and nothing else.
     *
     * An old bug grouped the subquery in fk_id, and then joined on fk_id, which was wrong. It had the effect
     * of only returning record(s) for the overall max version, so the bug was only apparent when there were
     * at least 2 versions in group of records.
     *
     * @param integer $fkID A foreign key to record in another table.
     *
     * @param integer $version The constellation version. For edits this is max version of the
     * constellation. For published, this is the published constellation version.
     *
     * @return string[] A list of records (list of lists) with inner keys matching the database field names:
     * version, ic_id, id, text, note, uri, language_id.
     *
     */
    public function selectSource($fkID, $version)
    {
        $qq = 'select_source';
        $this->sdb->prepare($qq,
                            'select aa.version, aa.ic_id, aa.id, aa.text, aa.citation, aa.note, aa.uri, aa.language_id, aa.display_name
                            from source as aa,
                            (select id,max(version) as version from source where fk_id=$1 and version<=$2 group by id) as bb
                            where not is_deleted and aa.id=bb.id and aa.version=bb.version');
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
     * Get all sources for Constellation
     *
     * Returns the list of all sources for a given ic_id and version.
     *
     * @param int $mainID The IC id for the constellation
     * @param int $version The version number to get sources for
     * @return string[] An array of arrays of source data from the database
     */
    public function selectSourceList($mainID, $version)
    {
        $qq = 'select_source_list';
        $this->sdb->prepare($qq,
                            'select aa.version, aa.ic_id, aa.id, aa.text, aa.note, aa.citation, aa.uri, aa.language_id, aa.display_name
                            from source as aa,
                            (select id,max(version) as version from source where ic_id=$1 and version<=$2 group by id) as bb
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
     * Select all source id only by constellation ID
     *
     * Select only source.id values for a given constellation ID. Use this to get constellation source id
     * values, which higher level code uses inside populateSourceConstellation(), and which calls
     * populateSourceByID(). If you want full source record data, then you should use selectSourceByID().
     *
     * @param integer $mainID A foreign key to record in another table.
     *
     * @param integer $version The constellation version. For edits this is max version of the
     * constellation. For published, this is the published constellation version.
     *
     * @return string[] A list of list (records) with key 'id'
     */
    public function selectSourceIDList($mainID, $version)
    {
        $qq = 'select_source_id_list';
        $this->sdb->prepare($qq,
                            'select aa.id
                            from source as aa,
                            (select id,max(version) as version from source where ic_id=$1 and version<=$2 group by id) as bb
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
     * @param integer $sourceID A record ID here in the source table. Not a foreign key.
     *
     * @param integer $version The constellation version. For edits this is max version of the
     * constellation. For published, this is the published constellation version.
     *
     * @return string[] A single source record keys matching the database field names:
     * version, ic_id, id, text, note, uri, language_id.
     *
     */
    public function selectSourceByID($sourceID, $version)
    {
        $qq = 'select_source_by_id';
        $this->sdb->prepare($qq,
                            'select aa.version, aa.ic_id, aa.id, aa.text, aa.citation, aa.note, aa.uri, aa.language_id, aa.display_name
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
     * Write a source objec to the database. These are per-constellation so they have ic_id and no foreign
     * keys. These are linked to other tables by putting a source.id foreign key in that related table.
     * Language related is a Language object, and is saved in table language. It is related where
     * source.id=language.fk_id. There is no language_id in table source, and there should not be. However, a
     * lanugage may link to this source record via source.id. See DBUtil writeSource().
     * The "type" field was always "simple" and is no longer used.
     *
     *
     * @param string[] $vhInfo associative list with keys: version, ic_id
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
     * because it is used by language as a foreign key.
     *
     */
    public function insertSource($vhInfo, $id, $displayName, $text, $citation, $note, $uri)
    {
        if (! $id)
        {
            $id = $this->selectID();
        }
        $qq = 'insert_source';
        $this->sdb->prepare($qq,
                            'insert into source
                            (version, ic_id, id, display_name, text, citation, note, uri)
                            values
                            ($1, $2, $3, $4, $5, $6, $7, $8)');
        $this->sdb->execute($qq,
                            array($vhInfo['version'],
                                  $vhInfo['ic_id'],
                                  $id,
                                  $displayName,
                                  $text,
                                  $citation,
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
     * @param string[] $vhInfo associative list with keys: version, ic_id
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
                            (version, ic_id, id, text)
                            values
                            ($1, $2, $3, $4)');
        $this->sdb->execute($qq,
                            array($vhInfo['version'],
                                  $vhInfo['ic_id'],
                                  $id,
                                  $text));
        $this->sdb->deallocate($qq);
        return $id;
    }


    /**
     * @deprecated
     * Insert a constellation occupation. If the $id arg is null, get a new id. Always return $id.
     *
     * @param string[] $vhInfo associative list with keys: version, ic_id
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
                            (version, ic_id, id, occupation_id, vocabulary_source, note)
                            values
                            ($1, $2, $3, $4, $5, $6)');
        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['ic_id'],
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
     * @param string $userString A string value of the users unique username which is appuser.username.
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
                            appuser.username=$1
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
     * This always increments the version_history.version which is the version number. An old comment said: "That
     * needs to not be incremented in some cases." That is certainly not possible now. Our rule is: always
     * increment version on any database operation.
     *
     * The $mainID aka ic_id may not be minted. If we have an existing $mainID we do not create a new
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
     * @param integer $useridSecondary optional Foreign key to appuser.id, the secondary user's appuser id value.
     *
     * @return string[] $vhInfo An assoc list with keys 'version', 'ic_id'.
     */
    public function insertVersionHistory($mainID, $userid, $role, $status, $note, $useridSecondary=null)
    {
        if (! $mainID)
        {
            $mainID = $this->selectID();
        }
        $qq = 'insert_version_history';
        // We need version_history.id and version_history.id returned.
        $this->sdb->prepare($qq,
                            'insert into version_history
                            (id, user_id, role_id, status, is_current, note, user_id_secondary)
                            values
                            ($1, $2, $3, $4, $5, $6, $7)
                            returning version');

        $result = $this->sdb->execute($qq, array($mainID, $userid, $role, $status, true, $note, $useridSecondary));
        $row = $this->sdb->fetchrow($result);
        $vhInfo['version'] = $row['version'];
        $vhInfo['ic_id'] = $mainID;
        $this->sdb->deallocate($qq);
        return $vhInfo;
    }


    /**
     * New insert into version_history
     *
     * We already know the version_history.version, and ic_id, so we are not relying on the default
     * values. This all happens because we are using the same ic_id across an entire constellation. And we
     * might be using the same version across many Constellations and other inserts all in this current
     * session.
     *
     * @param string[] $vhInfo associative list with keys: version, ic_id
     *
     * @param integer $appUserID User id
     *
     * @param integer $roleID Role id
     *
     * @param string $status Constellation status
     *
     * @param string $note Note for this version
     *
     * @return string[] $vhInfo associative list with keys: version, ic_id
     *
     */
    public function insertIntoVH($vhInfo, $appUserID, $roleID, $status, $note)
    {
        $qq = 'insert_into_version_history';
        $this->sdb->prepare($qq,
                            'insert into version_history
                            (version, id, user_id, role_id, status, is_current, note)
                            values
                            ($1, $2, $3, $4, $5, $6, $7)
                            returning version, id as ic_id;');

        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'], $vhInfo['ic_id'], $appUserID, $roleID, $status, true, $note));
        $vhInfo = $this->sdb->fetchrow($result);
        $this->sdb->deallocate($qq);
        return $vhInfo;
    }

    /**
     * Update a version_history record
     *
     * Get a new version but keeping the existing ic_id. This also uses DatabaseConnector->query() in an
     * attempt to be more efficient, or perhaps just less verbose.
     *
     * @param integer $appUserID Foreign key to appuser.id, the current user's appuser id value.
     *
     * @param integer $roleID Foreign key to role.id, the role id value of the current user.
     *
     * @param string $status Status value from the enum icstatus. Using an enum from the db is a bit obscure
     * to all the php code, so maybe best to move icstatus to some util class and have a method to handle
     * these. Or a method that knows about the db class, but can hide the details from the application
     * code. Something.
     *
     * @param string $note A string the user enters to identify what changed in this version.
     *
     * @param integer $ic_id Constellation id
     *
     * @return string[] $vhInfo An assoc list with keys 'version', 'ic_id'.
     */
    public function updateVersionHistory($appUserID, $roleID, $status, $note, $ic_id)
    {
        /*
         * Note: query() as opposed to prepare() and execute()
         * query() has two args:
         * 1) a string (sql query)
         * 2) an array of the vars that match the query placeholders
         *
         */
        $result = $this->sdb->query('insert into version_history
                                    (id, user_id, role_id, status, is_current, note)
                                    values
                                    ($1, $2, $3, $4, $5, $6)
                                    returning version',
                                    array($ic_id, $appUserID, $roleID, $status, true, $note));
        $row = $this->sdb->fetchrow($result);
        return $row['version'];
    }


    /**
     * Insert date
     *
     * SNACDate.php has fromDateOriginal and toDateOriginal, but the CPF lacks date components, and the
     * database "original" is only the single original string.
     *
     * @param string[] $vhInfo associative list with keys: version, ic_id
     * @param integer $id Record id. If null a new one will be minted.
     * @param integer $isRange Boolean if this is a date range
     * @param string $fromDate The from date
     * @param string $fromType Type of from date, fk to vocabulary.id
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
     * @param string $descriptiveNote Descriptive note
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
                               $descriptiveNote,
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
                            (version, ic_id, id, is_range,
                            from_date, from_type, from_bc, from_not_before, from_not_after, from_original,
                            to_date, to_type, to_bc, to_not_before, to_not_after, to_original, descriptive_note, fk_table, fk_id)
                            values
                            ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17, $18, $19)');

        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['ic_id'],
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
                                            $descriptiveNote,
                                            $fk_table,
                                            $fk_id));

        $row = $this->sdb->fetchrow($result);
        $this->sdb->deallocate($qq);
        return $id;
    }


    /**
     * Select list of dates
     *
     * Select a date knowing a date id values. selectDate() relies on the date.id being in the original table,
     * thus $did is a foreign key of the record to which this date applies. selectDate() does not know or care
     * what the other record is.
     *
     * Constrain sub query where fk_id, but group by id and return max(version) by id. Remember, our unique
     * key is always id,version. Joining the fk_id constrained subquery with the table on id and version gives
     * us all of the relevant id,version records, and nothing else.
     *
     * An old bug grouped the subquery in fk_id, and then joined on fk_id, which was wrong. It had the effect
     * of only returning record(s) for the overall max version, so the bug was only apparent when there were
     * at least 2 versions in group of records.
     *
     * (What "other" date select? This old comment is unclear.) The other date select function would be by
     * original.id=date.fk_id. Maybe we only need by date.fk_id.
     *
     * @param integer $did A foreign key to record in another table.
     *
     * @param integer $version The constellation version. For edits this is max version of the
     * constellation. For published, this is the published constellation version.
     *
     * @param string $fkTable Name of the related table.
     *
     * @return string[] A list of date_range fields/value as list keys matching the database field names.
     */
    public function selectDate($did, $version, $fkTable)
    {
        $qq = 'select_date';

        $query = 'select
        aa.id, aa.version, aa.ic_id, aa.is_range, aa.descriptive_note,
        aa.from_date, aa.from_bc, aa.from_not_before, aa.from_not_after, aa.from_original,
        aa.to_date, aa.to_bc, aa.to_not_before, aa.to_not_after, aa.to_original, aa.fk_table, aa.fk_id,
        aa.from_type,aa.to_type
        from date_range as aa,
        (select id,max(version) as version from date_range where fk_id=$1 and fk_table=$3 and version<=$2 group by id) as bb
        where not is_deleted and aa.id=bb.id and aa.version=bb.version';

        $this->sdb->prepare($qq, $query);

        $result = $this->sdb->execute($qq, array($did, $version, $fkTable));
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }
        $this->sdb->deallocate($qq);
        return $all;
    }

    /**
     * Select all dates for constellation
     *
     * Returns an array of all Dates for a given constellation, regardless of what piece of the constellation they are
     * actually attached to.
     *
     * @param integer $cid A constellation ID
     *
     * @param integer $version The constellation version. For edits this is max version of the
     * constellation. For published, this is the published constellation version.
     *
     * @return string[][] A list of lists of fields/value as list keys matching the database field names
     */
    public function selectAllDatesForConstellation($cid, $version)
    {
        $qq = 'select_date';

        $query = 'select
        aa.id, aa.version, aa.ic_id, aa.is_range, aa.descriptive_note,
        aa.from_date, aa.from_bc, aa.from_not_before, aa.from_not_after, aa.from_original,
        aa.to_date, aa.to_bc, aa.to_not_before, aa.to_not_after, aa.to_original, aa.fk_table, aa.fk_id,
        aa.from_type,aa.to_type
        from date_range as aa,
        (select id,max(version) as version from date_range where ic_id=$1 and version<=$2 group by id) as bb
        where not is_deleted and aa.id=bb.id and aa.version=bb.version';

        $this->sdb->prepare($qq, $query);

        $result = $this->sdb->execute($qq, array($cid, $version));
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
     * Select a place. This relies on table.id==fk_id where $tid is a foreign key of the record to which this
     * place applies. We do not know or care what the other record is, and that works because all the ids come
     * from a single SQL sequence and therefore are unique.
     *
     * Constrain sub query where fk_id, but group by id and return max(version) by id. Remember, our unique
     * key is always id,version. Joining the fk_id constrained subquery with the table on id and version gives
     * us all of the relevant id,version records, and nothing else.
     *
     * An old bug grouped the subquery in fk_id, and then joined on fk_id, which was wrong. It had the effect
     * of only returning record(s) for the overall max version, so the bug was only apparent when there were
     * at least 2 versions in group of records.
     *
     * @param integer $tid A foreign key to record in the other table.
     *
     * @param integer $version The constellation version. For edits this is max version of the
     * constellation. For published, this is the published constellation version.
     *
     * @param string $fkTable Name of the related table, matches $tid aka fk_id aka fkID.
     *
     * @return string[] A list of fields/value as list keys matching the database field names: id,
     * version, ic_id, confirmed, geo_place_id, fk_table, fk_id, from_type, to_type
     */
    public function selectPlace($tid, $version, $fkTable)
    {
        $qq = 'select_place';
        $this->sdb->prepare($qq,
                         'select
                         aa.id, aa.version, aa.ic_id, aa.confirmed, aa.original,
                         aa.geo_place_id, aa.type, aa.role, aa.note, aa.score, aa.fk_table, aa.fk_id
                         from place_link as aa,
                         (select id,max(version) as version from place_link where fk_id=$1 and fk_table=$3 and version<=$2 group by id) as bb
                         where not is_deleted and aa.id=bb.id and aa.version=bb.version');

        $result = $this->sdb->execute($qq, array($tid, $version, $fkTable));
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
     * @param string[] $vhInfo associative list with keys: version, ic_id
     * @param integer $id The id (if null, one will be created)
     * @param string $confirmed Boolean confirmed by human
     * @param string $original The original string
     * @param string $geo_place_id The geo_place_id
     * @param integer $typeID Vocabulary ID of the place@localType
     * @param integer $roleID Vocabulary ID of the role
     * @param string $note A note
     * @param float $score The geoname matching score
     * @param string $fk_table The fk_table name
     * @param string $fk_id The fk_id of the related table.
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
                            (version, ic_id, id, confirmed, original, geo_place_id, type, role, note, score,  fk_id, fk_table)
                            values
                            ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12)');

        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['ic_id'],
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
     * Select all snac control meta data for constellation
     *
     * Returns an array of all SCMs for a given constellation, regardless of what piece of the constellation they are
     * actually attached to.
     *
     * @param integer $cid A constellation ID
     *
     * @param integer $version The constellation version. For edits this is max version of the
     * constellation. For published, this is the published constellation version.
     *
     * @return string[][] A list of lists of fields/value as list keys matching the database field names: id,
     * version, ic_id, citation_id, sub_citation, source_data, rule_id, language_id, note, fk_id, fk_table.
     */
    public function selectAllMetaForConstellation($cid, $version)
    {
        $qq = 'select_meta';
        $this->sdb->prepare($qq,
                            'select
                            aa.id, aa.version, aa.ic_id, aa.citation_id, aa.sub_citation, aa.source_data,
                            aa.rule_id, aa.note, aa.fk_id, aa.fk_table
                            from scm as aa,
                            (select id,max(version) as version from scm where ic_id=$1 and version<=$2 group by id) as bb
                            where not is_deleted and aa.id=bb.id and aa.version=bb.version');

        $result = $this->sdb->execute($qq, array($cid, $version));
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }
        $this->sdb->deallocate($qq);
        return $all;
    }

    /**
     * Select a snac control meta data record
     *
     * May 6 2016: Reading the code, we will return zero or many records. I'm pretty sure that anything which
     * can have an SCM can have multiple SCM records related to it.
     *
     * The query relies on table.id==fk_id where $tid is a foreign key of the record to which this applies. We
     * do not know or care what the other record is.
     *
     * Constrain sub query where fk_id, but group by id and return max(version) by id. Remember, our unique
     * key is always id,version. Joining the fk_id constrained subquery with the table on id and version gives
     * us all of the relevant id,version records, and nothing else.
     *
     * An old bug grouped the subquery in fk_id, and then joined on fk_id, which was wrong. It had the effect
     * of only returning record(s) for the overall max version, so the bug was only apparent when there were
     * at least 2 versions in group of records.
     *
     * @param integer $tid A foreign key to record in another table.
     *
     * @param integer $version The constellation version. For edits this is max version of the
     * constellation. For published, this is the published constellation version.
     *
     * @param string $fkTable Related table name.
     *
     * @return string[][] A list of lists of fields/value as list keys matching the database field names: id,
     * version, ic_id, citation_id, sub_citation, source_data, rule_id, language_id, note. I don't think
     * calling code has any use for fk_id, so we don't return it.
     */
    public function selectMeta($tid, $version, $fkTable)
    {
        $qq = 'select_meta';
        $this->sdb->prepare($qq,
                            'select
                            aa.id, aa.version, aa.ic_id, aa.citation_id, aa.sub_citation, aa.source_data,
                            aa.rule_id, aa.note
                            from scm as aa,
                            (select id,max(version) as version from scm where fk_id=$1 and fk_table=$3 and version<=$2 group by id) as bb
                            where not is_deleted and aa.id=bb.id and aa.version=bb.version');

        $result = $this->sdb->execute($qq, array($tid, $version, $fkTable));
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
     * @param string[] $vhInfo associative list with keys: version, ic_id
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
                            (version, ic_id, id, citation_id, sub_citation, source_data,
                            rule_id, note, fk_id, fk_table)
                            values ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)');
        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['ic_id'],
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
     * the nrd row key is ic_id,version which corresponds to the row key in all other tables being
     * id,version. Table nrd is the 1:1 table for the constellation. The peculiar thing about nrd is that
     * there is no corresponding php object. The Constellation has entityTyp and ARK, but the Constellation is
     * not an "nrd object".
     *
     * We happen to know that nrd.id currently the constellation ic_id. We are also sure that you do not want
     * to use this field for anything. We are writing a value into it for the sake of consistency.
     *
     * @param string[] $vhInfo associative list with keys: version, ic_id
     *
     * @param string $ark_id ARK string. There was a reason why I added _id to distinguish this from something
     * else with the naked name "ark".
     *
     * @param integer $entity_type A foreign key into table vocabulary, handled by Term related functions here and in
     * DBUtils.
     *
     * @param integer $tableID Value for nrd.id.
     *
     */
    public function insertNrd($vhInfo, $ark_id, $entity_type, $tableID)
    {
        $qq = 'insert_nrd';
        $this->sdb->prepare($qq,
                            'insert into nrd
                            (version, ic_id, ark_id, entity_type, id)
                            values
                            ($1, $2, $3, $4, $5)');
        $execList = array($vhInfo['version'], $vhInfo['ic_id'], $ark_id, $entity_type, $tableID);
        $result = $this->sdb->execute($qq, $execList);
        $this->sdb->deallocate($qq);
    }

    /**
     * Insert entityID record
     *
     * Insert an ID from external records for this constellation. For the sake of convention, we put
     * the SQL columns in the same order as the function args.
     *
     * @param string[] $vhInfo associative list with keys: version, ic_id
     *
     * @param int $id The id of this record, otherid.id
     *
     * @param string $text The text of the SameAs object.
     *
     * @param integer $typeID Vocabulary id foreign key for the type of this entityID. Probably the ids for
     * ISNI, LoC MARC organization, etc.
     *
     * @param string $uri The URI of the other record (not currently used)
     *
     */
    public function insertEntityID($vhInfo, $id, $text, $typeID, $uri)
    {
        if (! $id)
        {
            $id = $this->selectID();
        }
        $qq = 'insert_entity_id';
        $this->sdb->prepare($qq,
                            'insert into entityid
                            (version, ic_id, id, text, type, uri)
                            values
                            ($1, $2, $3, $4, $5, $6)');

        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['ic_id'],
                                            $id,
                                            $text,
                                            $typeID,
                                            $uri));
        $this->sdb->deallocate($qq);
        return $id;
    }

    /**
     * Insert otherID record
     *
     * Insert an ID from records that were merged into this constellation. For the sake of convention, we put
     * the SQL columns in the same order as the function args.
     *
     * @param string[] $vhInfo associative list with keys: version, ic_id
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
                            (version, ic_id, id, text, type, uri)
                            values
                            ($1, $2, $3, $4, $5, $6)');

        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['ic_id'],
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
     * @param string[] $vhInfo associative list with keys: version, ic_id
     *
     * @param string $original The original name string
     *
     * @param float $preferenceScore The preference score for ranking this as the preferred name. This
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
                            (version, ic_id, original, preference_score, id)
                            values
                            ($1, $2, $3, $4, $5)');
        $result = $this->sdb->execute($qq_1,
                                      array($vhInfo['version'],
                                            $vhInfo['ic_id'],
                                            $original,
                                            $preferenceScore,
                                            $nameID));
        $this->sdb->deallocate($qq_1);
        return $nameID;
    }

    /**
     * Insert a component record
     *
     * Related to name where component.name_id=name.id. This is a one-sided fk relationship also used for
     * date and language.
     *
     * @throws \snac\exceptions\SNACDatabaseException
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param integer $id Record id if this component. If null one will be minted. The id (existing or new) is always returned.
     *
     * @param integer $nameID Record id of related name
     *
     * @param string $text Text of the component
     *
     * @param integer $typeID Vocabulary fk id of the type of this component.
     * @param integer $order The ordering of this component in the name entry
     *
     * @return integer $id Return the existing id, or the newly minted id.
     */
    public function insertComponent($vhInfo, $id, $nameID, $text, $typeID, $order)
    {
        if ($nameID == null)
        {
            throw new \snac\exceptions\SNACDatabaseException("Tried to write a component for a non-existent name entry");
        }
        if (! $id)
        {
            $id = $this->selectID();
        }
        $qq_2 = 'insert_component';
        $this->sdb->prepare($qq_2,
                            'insert into name_component
                            (version, id, name_id, ic_id, nc_value, nc_label, c_order)
                            values
                            ($1, $2, $3, $4, $5, $6, $7)');
        $this->sdb->execute($qq_2,
                            array($vhInfo['version'],
                                  $id,
                                  $nameID,
                                  $vhInfo['ic_id'],
                                  $text,
                                  $typeID,
                                  $order));
        $this->sdb->deallocate($qq_2);
        return $id;
    }

    /**
     * Select a list of name component records
     *
     * Select all related name_component records and return a list of associated lists. This is a one-sided fk
     * relationship also used for data such as date and language. Related to name where
     * name_component.name_id=name.id.
     *
     * @param integer $nameID Record id of related name.
     *
     * @param inteter $version Version number.
     *
     * @return string[][] Return a list of associated lists, where each inner list is a single name_component.
     */
    public function selectComponent($nameID, $version)
    {
        $qq_2 = 'select_component';
        $this->sdb->prepare($qq_2,
                            'select
                            aa.id, aa.name_id, aa.version, aa.nc_label, aa.nc_value, aa.c_order
                            from name_component as aa,
                            (select name_id,max(version) as version from name_component where name_id=$1 and version<=$2 group by name_id) as bb
                            where not is_deleted and aa.name_id=bb.name_id and aa.version=bb.version');
        $result = $this->sdb->execute($qq_2, array($nameID, $version));
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }
        $this->sdb->deallocate($qq_2);
        return $all;
    }

    /**
     * Select a list of name components for an icid
     *
     * Select all name_component records for a constellation and return a list of associated lists. This is a one-sided fk
     * relationship also used for data such as date and language. Related to name where
     * name_component.name_id=name.id and name.ic_id = version_history.id.
     *
     * @param integer $icid Constellation ID.
     *
     * @param inteter $version Version number.
     *
     * @return string[][] Return a list of associated lists, where each inner list is a single name_component.
     */
    public function selectAllNameComponentsForConstellation($icid, $version) {

            $qq_2 = 'select_components';
            $this->sdb->prepare($qq_2,
                                'select aa.id, aa.name_id, aa.version, aa.nc_label, aa.nc_value, aa.c_order
                                from
                                    name_component as aa,
                                    (select nc.name_id,max(nc.version) as version
                                        from name_component nc,
                                        (select
                                        aa.id,aa.version, aa.ic_id
                                        from name as aa,
                                        (select id,max(version) as version from name where version<=$2 and ic_id=$1 group by id) as bb
                                        where
                                        aa.id = bb.id and not aa.is_deleted and
                                        aa.version = bb.version) as n
                                     where nc.name_id=n.id and nc.version<=$2 group by name_id) as bb
                                where not is_deleted and aa.name_id=bb.name_id and aa.version=bb.version order by aa.name_id, aa.c_order;');
            $result = $this->sdb->execute($qq_2, array($icid, $version));
            $all = array();
            while($row = $this->sdb->fetchrow($result))
            {
                array_push($all, $row);
            }
            $this->sdb->deallocate($qq_2);
            return $all;
    }

    /**
     * Insert an address line
     *
     * Related to place where component.place_id=place.id. This is a one-sided fk relationship also used for
     * date and language.
     *
     * @throws \snac\exceptions\SNACDatabaseException
     *
     * @param string[] $vhInfo associative list with keys: version, main_id
     *
     * @param integer $id Record id of this address line. If null one will be minted. The id (existing or new) is always returned.
     *
     * @param integer $placeID Record id of related place
     *
     * @param string $text Text of the address line
     *
     * @param integer $typeID Vocabulary fk id of the type of this address line.
     * @param integer $order The ordering of this line in the place's address
     *
     * @return integer $id Return the existing id, or the newly minted id.
     */
    public function insertAddressLine($vhInfo, $id, $placeID, $text, $typeID, $order)
    {
        if ($placeID == null)
        {
            throw new \snac\exceptions\SNACDatabaseException("Tried to write an address line for a non-existant place");
        }
        if (! $id)
        {
            $id = $this->selectID();
        }
        $qq_2 = 'insert_address_line';
        $this->sdb->prepare($qq_2,
                            'insert into address_line
                            (ic_id, version, id, place_id, value, label, line_order)
                            values
                            ($1, $2, $3, $4, $5, $6, $7)');
        $this->sdb->execute($qq_2,
                            array($vhInfo['ic_id'],
                                  $vhInfo['version'],
                                  $id,
                                  $placeID,
                                  $text,
                                  $typeID,
                                  $order));
        $this->sdb->deallocate($qq_2);
        return $id;
    }


    /**
     * Select a list of address line records
     *
     * Select all related address line records and return a list of associated lists. This is a one-sided fk
     * relationship also used for data such as date and language. Related to name where
     * address_line.place_id=place.id.
     *
     * @param integer $placeID Record id of related place.
     *
     * @param inteter $version Version number.
     *
     * @return string[][] Return a list of associated lists, where each inner list is a single address line.
     */
    public function selectAddress($placeID, $version)
    {
        $qq_2 = 'select_address';
        $this->sdb->prepare($qq_2,
                            'select
                            aa.id, aa.place_id, aa.version, aa.label, aa.value, aa.line_order
                            from address_line as aa,
                            (select place_id,max(version) as version from address_line
                                where place_id=$1 and version<=$2 group by place_id) as bb
                            where not is_deleted and aa.place_id=bb.place_id and aa.version=bb.version
                            order by aa.line_order asc');
        $result = $this->sdb->execute($qq_2, array($placeID, $version));
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }
        $this->sdb->deallocate($qq_2);
        return $all;
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
     * @param string[] $vhInfo associative list with keys: version, ic_id
     *
     * @param integer $id Record id if this contributor. If null one will be minted. The id (existing or new) is always returned.
     *
     * @param integer $nameID Record id of related name
     *
     * @param string $name Name of the contributor
     *
     * @param integer $typeID Vocabulary fk id of the type of this contributor.
     * @param integer $ruleID Vocabulary fk id of the rule of this contributor.
     *
     * @return integer $id Return the existing id, or the newly minted id.
     */
    public function insertContributor($vhInfo, $id, $nameID, $name, $typeID, $ruleID)
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
                            (version, ic_id, id, name_id, short_name, name_type, rule)
                            values
                            ($1, $2, $3, $4, $5, $6, $7)');
        $this->sdb->execute($qq_2,
                            array($vhInfo['version'],
                                  $vhInfo['ic_id'],
                                  $id,
                                  $nameID,
                                  $name,
                                  $typeID,
                                  $ruleID));
        $this->sdb->deallocate($qq_2);
        return $id;
    }

    /**
     * Insert a activity record
     * @deprecated
     * The SQL returns the inserted id which is used when inserting a date into table date_range. Activity
     * uses the same vocabulary terms as occupation.
     *
     * If the $id arg is null, get a new id. Always return $id.
     *
     * @param string[] $vhInfo associative list with keys: version, ic_id
     *
     * @param integer $id Record id
     *
     * @param integer $type Activity type controlled vocab term id
     *
     * @param string $vocabularySource The vocabulary source
     *
     * @param string $note Note for this activity
     *
     * @param integer $term Activity term controlled vocab id
     *
     * @return integer id of this activity
     *
     */
    public function insertActivity($vhInfo, $id, $type, $vocabularySource, $note, $term)
    {
        if (! $id)
        {
            $id = $this->selectID();
        }
        $qq = 'insert_activity';
        $this->sdb->prepare($qq,
                            'insert into activity
                            (version, ic_id, id, activity_type, vocabulary_source, note, activity_id)
                            values
                            ($1, $2, $3, $4, $5, $6, $7)');
        $eArgs = array($vhInfo['version'],
                       $vhInfo['ic_id'],
                       $id,
                       $type,
                       $vocabularySource,
                       $note,
                       $term);
        $result = $this->sdb->execute($qq, $eArgs);
        $this->sdb->deallocate($qq);
        return $id;
    }

    /**
     * Insert a Language link record
     *
     * @param string[] $vhInfo associative list with keys: version, ic_id
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
                            (version, ic_id, id, language_id, script_id, vocabulary_source, note, fk_table, fk_id)
                            values
                            ($1, $2, $3, $4, $5, $6, $7, $8, $9)');
        $eArgs = array($vhInfo['version'],
                       $vhInfo['ic_id'],
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
     * Note: This always gets the most recent <= $version for a given subquery id.
     *
     * Select fields for a language object knowing a fkID value of the related table. This relies on the
     * language.fk_id==orig_table.id. $fkID is a foreign key of the record to which this language
     * applies. This (mostly) does not know or care what the other record is. Note that for the
     * "foreign-key-across-all-tables" to work, all the tables must use the same sequence (that is: id_seq).
     *
     * Constrain sub query where fk_id, but group by id and return max(version) by id. Remember, our unique
     * key is always id,version. Joining the fk_id constrained subquery with the table on id and version gives
     * us all of the relevant id,version records, and nothing else.
     *
     * An old bug grouped the subquery in fk_id, and then joined on fk_id, which was wrong. It had the effect
     * of only returning record(s) for the overall max version, so the bug was only apparent when there were
     * at least 2 versions in group of records.
     *
     * @param integer $fkID A foreign key to record in another table.
     *
     * @param integer $version The constellation version. For edits this is max version of the
     * constellation. For published, this is the published constellation version.
     *
     * @param string $fkTable Name of the related table.
     *
     * @return string[] A list of location fields as list with keys matching the database field names.
     */
    public function selectLanguage($fkID, $version, $fkTable)
    {
        $qq = 'select_language';

        $query = 'select aa.version, aa.ic_id, aa.id, aa.language_id, aa.script_id, aa.vocabulary_source, aa.note
        from language as aa,
        (select id,max(version) as version from language where fk_id=$1 and fk_table=$3 and version<=$2 group by id) as bb
        where not is_deleted and aa.id=bb.id and aa.version=bb.version';

        $this->sdb->prepare($qq, $query);
        $result = $this->sdb->execute($qq, array($fkID, $version, $fkTable));
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }
        $this->sdb->deallocate($qq);
        return $all;
    }

    /**
     * Get all laguages for constellation
     *
     * Returns the list of all languages for a given ic_id and version.
     *
     * @param int $cid The IC id for the constellation
     * @param int $version The version number to get sources for
     * @return string[] An array of arrays of language data from the database
     */
    public function selectAllLanguagesForConstellation($cid, $version)
    {
        $qq = 'select_language';

        $query = 'select aa.version, aa.ic_id, aa.id, aa.language_id, aa.script_id, aa.vocabulary_source, aa.note,
            aa.fk_table, aa.fk_id
        from language as aa,
        (select id,max(version) as version from language where ic_id=$1 and version<=$2 group by id) as bb
        where not is_deleted and aa.id=bb.id and aa.version=bb.version';

        $this->sdb->prepare($qq, $query);

        $result = $this->sdb->execute($qq, array($cid, $version));
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }
        $this->sdb->deallocate($qq);
        return $all;
    }

    /**
     * Get languages for list of resources
     *
     * Returns the list of all languages for a given array of resource ids.
     *
     * @param int[] $resourceIDs The resource IDs to grab
     * @return string[] An array of arrays of language data from the database
     */
    public function selectResourceLanguagesByList($resourceIDs)
    {
        if (count($resourceIDs) === 0)
            return array();

        $countList = array();
        for ($i = 1; $i <= count($resourceIDs); $i++) {
            $countList[$i] = '$'.$i;
        }
        $resourceIDList = implode(",", $countList);
        $qq = 'select_resource_language';

        $query = 'select aa.version, aa.resource_id, aa.id, aa.language_id, aa.script_id, aa.vocabulary_source, aa.note
        from resource_language as aa
        where aa.resource_id in ('.$resourceIDList.') and  not aa.is_deleted ';

        $this->sdb->prepare($qq, $query);

        $result = $this->sdb->execute($qq, $resourceIDs);
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }
        $this->sdb->deallocate($qq);
        return $all;
    }

    /**
     * Select All Languages for Resource
     * @param  int $rid     Resource ID to look up
     * @param  int $version Version number for the resource
     * @return string[][]          Array of resource language data
     */
    public function selectAllLanguagesForResource($rid, $version)
    {
        $qq = 'select_language';

        $query = 'select aa.version, aa.resource_id, aa.id, aa.language_id, aa.script_id, aa.vocabulary_source, aa.note
        from resource_language as aa,
        (select id,max(version) as version from resource_language where resource_id=$1 and version<=$2 group by id) as bb
        where not is_deleted and aa.id=bb.id and aa.version=bb.version';

        $this->sdb->prepare($qq, $query);

        $result = $this->sdb->execute($qq, array($rid, $version));
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }
        $this->sdb->deallocate($qq);
        return $all;
    }

    /**
     * Insert subjects, activities, and occupations into identity_concept table
     *
     * If $id is null, get a new record id.
     *
     * @param string[] $vhInfo associative list with keys: version, ic_id
     *
     * @param integer $id Record id
     *
     * @param integer $termID Vocabulary foreign key for the term.
     *
     * @return integer $id
     *
     */
    public function insertIdentityConcept($vhInfo, $id, $conceptID, $type)
    {
        if (! $id)
        {
            $id = $this->selectID();
        }

        $qq = 'insert_identity_concept';
        $this->sdb->prepare($qq,
                            'insert into identity_concepts
                            (version, ic_id, id, concept_id, type)
                            values
                            ($1, $2, $3, $4, $5)');

        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['ic_id'],
                                            $id,
                                            $conceptID,
                                            $type));
        $this->sdb->deallocate($qq);
        return $id;
    }

    /**
     *
     * @deprecated
     * (Deprecated)
     * Insert into table subject.
     * Data is currently only a string from the Constellation. If $id is null, get
     * a new record id. Always return $id.
     *
     * @param string[] $vhInfo associative list with keys: version, ic_id
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
                            (version, ic_id, id, term_id)
                            values
                            ($1, $2, $3, $4)');

        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['ic_id'],
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
     * @param string[] $vhInfo associative list with keys: version, ic_id
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
                            (version, ic_id, id, term_id)
                            values
                            ($1, $2, $3, $4)');

        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['ic_id'],
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
     * @param string[] $vhInfo associative list with keys: version, ic_id
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
                            (version, ic_id, id, term_id)
                            values
                            ($1, $2, $3, $4)');

        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['ic_id'],
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
     * @param string[] $vhInfo associative list with keys: version, ic_id.
     *
     * @param string $table The table this text is related to.
     *
     * @return string[][] Return list of an associative list with keys: id, version, ic_id,
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
                                aa.id, aa.version, aa.ic_id, aa.text
                                from %s aa,
                                (select id, max(version) as version from %s where version<=$1 and ic_id=$2 group by id) as bb
                                where not aa.is_deleted and
                                aa.id=bb.id
                                and aa.version=bb.version', $table, $table));
        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['ic_id']));
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
     * @throws \snac\exceptions\SNACDatabaseException
     *
     * @param string[] $vhInfo associative list with keys: version, ic_id
     *
     * @param integer $id Record id
     *
     * @param string $text Text value we're saving
     *
     * @param string $table One of the approved tables to which this data is being written. These tables are
     * identical except for the name, so this core code saves duplication. See also selectTextCore().
     *
     * @return string[][] Return list of an associative list with keys: id, version, ic_id,
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
            throw new \snac\exceptions\SNACDatabaseException("Tried to insert on non-approved table: $table\n");
        }
        if (! $id)
        {
            $id = $this->selectID();
        }
        $qq = "select_$table";
        $this->sdb->prepare($qq,
                            sprintf(
                                'insert into %s
                                (version, ic_id, id, text)
                                values
                                ($1, $2, $3, $4)', $table));
        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['ic_id'],
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
     * @param string[] $vhInfo associative list with keys: version, ic_id
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
     * @param string[] $vhInfo associative list with keys: version, ic_id
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
     * @param string[] $vhInfo associative list with keys: version, ic_id
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
     * @param string[] $vhInfo associative list with keys: version, ic_id
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
     * @param string[] $vhInfo associative list with keys: version, ic_id
     *
     * @return string[][] Return list of an associative list with keys: id, version, ic_id,
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
     * @param string[] $vhInfo associative list with keys: version, ic_id
     *
     * @return string[][] Return list of an associative list with keys: id, version, ic_id,
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
     * @param string[] $vhInfo associative list with keys: version, ic_id
     *
     * @return string[][] Return list of an associative list with keys: id, version, ic_id,
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
     * @param string[] $vhInfo associative list with keys: version, ic_id
     *
     * @return string[][] Return list of an associative list with keys: id, version, ic_id,
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
     * @param string[] $vhInfo associative list with keys: version, ic_id
     *
     * @param integer $targetID The constellation id of the related entity (aka the relation)
     *
     * @param string $targetArkID The ARK of the related entity
     *
     * @param string $targetEntityTypeID The entity type of the target relation (aka the other entity aka the related entity)
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
                            (version, ic_id, related_id, related_ark, role, arcrole,
                            relation_type, relation_entry, descriptive_note, id)
                            values
                            ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)');

        // Combine vhInfo and the remaining args into a big array for execute().
        $execList = array($vhInfo['version'],
                          $vhInfo['ic_id'],
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
     * @param string[] $vhInfo associative list with keys: version, ic_id
     * @param int $resourceID The resource ID for this relation
     * @param int $resourceVersion The version of the resource
     * @param integer $arcRole Vocabulary id value of the arc role aka xlink:arcrole
     * @param string $relationEntry Often the name of the relation aka relationEntry
     * @param string $note A note aka descriptiveNote
     * @param integer $id The database record id (if null, one will be created
     *
     * @return integer $id The record id, which might be new if this is the first insert for this resource relation.
     *
     */
    public function insertResourceRelation($vhInfo,
                                           $resourceID,
                                           $resourceVersion,
                                           $arcRole,  // xlink:arcrole
                                           $relationEntry, // relationEntry
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
                            (version, ic_id, id, resource_id, resource_version, arcrole, relation_entry, descriptive_note)
                            values
                            ($1, $2, $3, $4, $5, $6, $7, $8)');
        /*
         * Combine vhInfo and the remaining args into a big array for execute().
         */
        $execList = array($vhInfo['version'], // 1
                          $vhInfo['ic_id'],   // 2
                          $id,                // 3
                          $resourceID,        // 4
                          $resourceVersion,   // 5
                          $arcRole,           // 6
                          $relationEntry,     // 7
                          $note);             // 8
        $this->sdb->execute($qq, $execList);
        $this->sdb->deallocate($qq);
        return $id;
    }

    /**
     * Replace Resource Relation Resource
     *
     * Replace
     * to sql fields. Note keys in $argList have a fixed order.
     *
     * @param int $victimID The resource ID for the discarded resource
     * @param int $victimVersion The version of the discarded resource
     * @param int $targetID The resource ID for target resource
     * @param int $targetVersion The version of target resource
     *
     * @return true
     *
     */
    public function replaceResourceRelationResource($victimID, $victimVersion , $targetID, $targetVersion) {
        $query = "UPDATE related_resource
                  SET resource_id = $1,
                      resource_version = $2
                  WHERE resource_id = $3";

        $result = $this->sdb->query($query, array( $targetID, $targetVersion, $victimID));

        return true;
    }

    /**
     * Delete Duplicate Resource Relations
     *
     * For a given resource, search through all its resource relations
     * and delete any exact duplicates
     *
     * @param int $resourceID The resource ID of the duplicate ResourceRelations
     *
     * @return true
     *
     */
    public function deleteDuplicateResourceRelations($resourceID) {
        $query = "UPDATE related_resource set is_deleted = 't' WHERE resource_id = $1
                    AND id NOT IN (
                        SELECT *
                        FROM (
                            SELECT MAX(id)
                            FROM related_resource
                            WHERE resource_id = $1
                            GROUP BY ic_id, version, arcrole, role, resource_version
                        ) rrs
                    )";

        $result = $this->sdb->query($query, array( $resourceID ));

        return true;
    }


    /**
     * Insert a Controlled Vocabulary Term
     *
     * @param  String $type        Type of the term
     * @param  String $term        Value of the Term
     * @param  String $uri         The URI of the term
     * @param  String $description Term description
     * @return int|boolean              ID on success or false on failure
     */
    public function insertVocabularyTerm($type,
                                   $term,
                                   $uri,
                                   $description)
    {
        $result = $this->sdb->query('insert into vocabulary (type, value, uri, description) values ($1, $2, $3, $4) returning *;',
                                array($type, $term, $uri, $description));

        $row = $this->sdb->fetchrow($result);

        if ($row && $row["id"]) {
            return $row["id"];
        }

        return false;
    }


    /**
     * Insert Controlled GeoTerm
     * @param  string $name        Name of the place
     * @param  string $uri         URI for the vocab term
     * @param  string $latitude    Latitude
     * @param  string $longitude   Longitude
     * @param  string $adminCode   Administration Code (state)
     * @param  string $countryCode Country Code
     * @return int|bool              ID on success, false on failure
     */
    public function insertGeoTerm($name,
                                   $uri,
                                   $latitude,
                                   $longitude,
                                   $adminCode,
                                   $countryCode)
    {
        $result = $this->sdb->query('insert into geo_place (name, uri, latitude, longitude, admin_code, country_code) values ($1, $2, $3, $4, $5, $6) returning *;',
                                array($name, $uri, $latitude, $longitude, $adminCode, $countryCode));

        $row = $this->sdb->fetchrow($result);

        if ($row && $row["id"]) {
            return $row["id"];
        }

        return false;
    }

    /**
     * Get Next Resource ID
     *
     * Gets the next resource id number from the resource_id sequence
     *
     * @return int next resource id number
     */
    private function selectResourceID()
    {
        $result = $this->sdb->query('select nextval(\'resource_id_seq\') as id',array());
        $row = $this->sdb->fetchrow($result);
        return $row['id'];
    }

    /**
     * Get Next Resource Version Number
     *
     * Gets the next version number from the resource_version sequence
     *
     * @return int next resource version number
     */
    private function selectResourceVersion()
    {
        $result = $this->sdb->query('select nextval(\'resource_version_id_seq\') as id',array());
        $row = $this->sdb->fetchrow($result);
        return $row['id'];
    }

    /**
     * Current resource version by ID
     *
     * The max, that is: current version for ID regardless of status. This will return max for deleted as
     * well as all other status values.
     * @param integer $id The resource ID
     *
     * @return integer Latest version number from resource.version
     *
     */
    public function selectCurrentResourceVersion($id)
    {
        $result = $this->sdb->query(
                                    'select max(version) as version
                                    from resource_cache
                                    where resource_cache.id=$1',
                                    array($id));
        $row = $this->sdb->fetchrow($result);
        return $row['version'];
    }



    /**
     * Insert resource
     *
     * @param  int|null $resourceID      Resource ID or null if it doesn't exist
     * @param  int|null $resourceVersion Resource version or null if it doesn't exist
     * @param  string $title           Title of the resource
     * @param  string $abstract        Abstract of the resource
     * @param  string $extent          Extent of the resource
     * @param  int $repoICID        Repository ID for the holding repository of this resource
     * @param  int $docTypeID       Document Type ID
     * @param  int $entryTypeID     Entity Type ID
     * @param  text|null $link            Link for this resource
     * @param  text|null $objectXMLWrap   Any ObjectXMLWrap XML
     * @param  text|null $date            Text entry date of this resource
     * @param  text|null $displayEntry    Display Entry of resource
     * @param  int $userID               The userid of the user
     * @param  bool $isDeleted           Whether resource is deleted
     * @return string[]                  Array containing id, version
     */
    public function insertResource(        $resourceID,
                                           $resourceVersion,
                                           $title,
                                           $abstract,
                                           $extent,
                                           $repoICID,
                                           $docTypeID,
                                           $entryTypeID,
                                           $link,
                                           $objectXMLWrap,
                                           $date,
                                           $displayEntry,
                                           $userID,
                                           $isDeleted = false)
    {
        if (! $resourceID)
        {
            $resourceID = $this->selectResourceID();
        }
        if (! $resourceVersion) {
            $resourceVersion = $this->selectResourceVersion();
        }

        $isDeleted = $this->sdb->boolToPg($isDeleted);

        $qq = 'insert_resource';
        $this->sdb->prepare($qq,
                            'insert into resource_cache
                            (id, version, title, abstract, extent, repo_ic_id, type, entry_type, href, object_xml_wrap, date, display_entry, user_id, is_deleted)
                            values
                            ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14)');
        /*
         * Combine vhInfo and the remaining args into a big array for execute().
         */
        $execList = array($resourceID,        // 1
                          $resourceVersion,   // 2
                          $title,             // 3
                          $abstract,          // 4
                          $extent,            // 5
                          $repoICID,          // 6
                          $docTypeID,         // 7
                          $entryTypeID,       // 8
                          $link,              // 9
                          $objectXMLWrap,     // 10
                          $date,              // 11
                          $displayEntry,      // 12
                          $userID,            // 13
                          $isDeleted);        // 14
        $this->sdb->execute($qq, $execList);
        $this->sdb->deallocate($qq);
        return array($resourceID, $resourceVersion);
    }


    /**
     * Insert Resource Language
     *
     * Insert (updated or new) version of the given language for a resource
     *
     * @param int $resourceID The resource ID
     * @param int $resourceVersion The resource version
     * @param int $id The id for this language (if null, one will be generated)
     * @param int $languageID The language ID from the vocabulary table
     * @param int $scriptID The script ID from the vocabulary table
     * @param string $vocabularySource The source for this vocab term
     * @param string $note The descriptive note for this language
     * @param boolean $is_deleted Whether Resource Language is deleted
     * @return int The ID for the language just written
     */
    public function insertResourceLanguage($resourceID, $resourceVersion, $id, $languageID, $scriptID, $vocabularySource, $note, $is_deleted)
    {
        if (! $id)
        {
            $id = $this->selectResourceID();
        }
        $qq = 'insert_resource_language';
        $this->sdb->prepare($qq,
                            'insert into resource_language
                            (resource_id, version, id, language_id, script_id, vocabulary_source, note, is_deleted)
                            values
                            ($1, $2, $3, $4, $5, $6, $7, $8)');
        $eArgs = array($resourceID,
                       $resourceVersion,
                       $id,
                       $languageID,
                       $scriptID,
                       $vocabularySource,
                       $note,
                       $this->sdb->boolToPg($is_deleted));
        $result = $this->sdb->execute($qq, $eArgs);
        $this->sdb->deallocate($qq);
        return $id;
    }


    /**
     * Insert into table resource_origination_name
     *
     * Write the given name into the origination name table for the resource (id, version)
     *
     * @param int $resourceID The resource ID
     * @param int $resourceVersion The resource version
     * @param int $id The id of this origination name (if null, one will be generated)
     * @param string $name The origination name
     *
     * @return integer $id The record id, which might be new if this is the first insert for this origination name.
     */
    public function insertOriginationName($resourceID, $resourceVersion,
                               $id,
                               $name)
    {
        if (! $id)
        {
            $id = $this->selectResourceID();
        }
        $qq = 'insert_origination_name';
        $this->sdb->prepare($qq,
                            'insert into resource_origination_name
                            (resource_id, version, id, name)
                            values
                            ($1, $2, $3, $4)');
        /*
         * Combine vhInfo and the remaining args into a big array for execute().
         */
        $execList = array($resourceID,
                          $resourceVersion,
                          $id,
                          $name);
        $this->sdb->execute($qq, $execList);
        $this->sdb->deallocate($qq);
        return $id;
    }

    /**
     * Select resource origination name records by list
     *
     * Get all the origination name data for a given list of resources
     *
     * TODO: This should take into account versioning
     *
     * @param int[] $resourceIDs The list of resource ids
     *
     * @return string[][] Return a list of lists. Inner list keys: id, version, name
     */
    public function selectOriginationNamesByList($resourceIDs)
    {
        if (count($resourceIDs) === 0)
            return array();

        $countList = array();
        for ($i = 1; $i <= count($resourceIDs); $i++) {
            $countList[$i] = '$'.$i;
        }
        $resourceIDList = implode(",", $countList);


        $qq = 'select_resource_origination_name';

        $query = 'select aa.version, aa.resource_id, aa.id, aa.name
        from resource_origination_name as aa
        where aa.resource_id in ('.$resourceIDList.') and not aa.is_deleted';

        $this->sdb->prepare($qq, $query);

        $result = $this->sdb->execute($qq,
                                      $resourceIDs);
        $all = array();
        while ($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }
        $this->sdb->deallocate($qq);
        return $all;
    }

    /**
     * Select resource origination name records
     *
     * Get all the origination name data for a given resource (id, version)
     *
     * @param int $resourceID The ID of the resource
     * @param int $version The version of the resource
     *
     * @return string[][] Return a list of lists. Inner list keys: id, version, name
     */
    public function selectOriginationNames($resourceID, $version)
    {
        $qq = 'select_resource_origination_name';

        $query = 'select aa.version, aa.id, aa.name
        from resource_origination_name as aa,
        (select id,max(version) as version from resource_origination_name where resource_id=$1 and version<=$2 group by id) as bb
        where not is_deleted and aa.id=bb.id and aa.version=bb.version';

        $this->sdb->prepare($qq, $query);

        $result = $this->sdb->execute($qq,
                                      array($resourceID,
                                            $version));
        $all = array();
        while ($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }
        $this->sdb->deallocate($qq);
        return $all;
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
     * Get a single vocabulary record by Type and Value
     *
     * @param string $value The value of a vocabulary term
     *
     * @param string $type The type of a vocabulary term
     *
     * @return string[] A list with keys: id, type, value, uri, description
     *
     */
    public function selectTermByValueAndType($value, $type)
    {
        $qq = 's_term_by_value';
        $this->sdb->prepare($qq,
                            'select
                            id, type, value, uri, description
                            from vocabulary where value=$1 and type=$2');
        $result = $this->sdb->execute($qq, array($value, $type));
        $row = $this->sdb->fetchrow($result);
        $this->sdb->deallocate($qq);
        return $row;
    }

    /**
     * Get a single vocabulary record by URI
     *
     * @param string $uri The uri of a vocabulary term
     *
     * @return string[] A list with keys: id, type, value, uri, description
     *
     */
    public function selectTermByUri($uri)
    {
        $qq = 's_term_by_uri';
        $this->sdb->prepare($qq,
                            'select
                            id, type, value, uri, description
                            from vocabulary where uri=$1');
        $result = $this->sdb->execute($qq, array($uri));
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
     * @param string[] $vhInfo associative list with keys: version, ic_id
     *
     * @return string[] An associative list with keys: version, ic_id, ark_id, entity_type.
     */
    public function selectNrd($vhInfo)
    {
        $qq = 'sc';
        $this->sdb->prepare($qq,
                            'select
                            aa.version,aa.ic_id,aa.ark_id,aa.entity_type
                            from nrd as aa,
                            (select ic_id, max(version) as version from nrd where version<=$1 and ic_id=$2 group by ic_id) as bb
                            where not aa.is_deleted and
                            aa.ic_id=bb.ic_id
                            and aa.version=bb.version');
        /*
         * Always use key names explicitly when going from associative context to flat indexed list context.
         */
        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['ic_id']));
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
     * @param string[] $vhInfo associative list with keys: version, ic_id
     *
     * @return string[] A list of associative list with keys: version, ic_id, id, text.
     *
     */
    public function selectBiogHist($vhInfo)
    {
        $qq = 'sbh';
        $this->sdb->prepare($qq,
                            'select
                            aa.version, aa.ic_id, aa.id, aa.text
                            from biog_hist as aa,
                            (select id, max(version) as version from biog_hist where version<=$1 and ic_id=$2 group by id) as bb
                            where not aa.is_deleted and
                            aa.id=bb.id
                            and aa.version=bb.version');
        /*
         * Always use key names explicitly when going from associative context to flat indexed list context.
         */
        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['ic_id']));
        $rowList = array();
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($rowList, $row);
        }
        $this->sdb->deallocate($qq);
        return $rowList;
    }

    /**
     * select other IDs
     *
     * These were originally ID values of merged records. DBUtils has code that adds an otherRecordID to a
     * Constellation object.
     *
     * @param string[] $vhInfo associative list with keys: version, ic_id
     *
     * @return string[] Return an associative ist of otherid rows with keys: id, version, ic_id, text, uri,
     * type, link_type. otherid.type is an integer fk id from vocabulary, not that we need to concern
     * ourselves with that here.
     *
     */
    public function selectOtherID($vhInfo)
    {
        $qq = 'sorid';
        $this->sdb->prepare($qq,
                            'select
                            aa.id, aa.version, aa.ic_id, aa.text, aa.uri, aa.type
                            from otherid as aa,
                            (select id,max(version) as version from otherid where version<=$1 and ic_id=$2 group by id) as bb
                            where not aa.is_deleted and
                            aa.id = bb.id and
                            aa.version = bb.version order by id asc');

        $all = array();
        $result = $this->sdb->execute($qq, array($vhInfo['version'], $vhInfo['ic_id']));
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }

        $this->sdb->deallocate($qq);
        return $all;
    }


    /**
    * select entity IDs
    *
    * These were originally ID values of external records for the same entity. DBUtils has code that adds an entityID to a
    * Constellation object.
    *
    * @param string[] $vhInfo associative list with keys: version, ic_id
    *
    * @return string[] Return an associative ist of otherid rows with keys: id, version, ic_id, text, uri,
    * type, link_type. otherid.type is an integer fk id from vocabulary, not that we need to concern
    * ourselves with that here.
    *
    */
    public function selectEntityID($vhInfo)
    {
        $qq = 'sedid';
        $this->sdb->prepare($qq,
            'select
                    aa.id, aa.version, aa.ic_id, aa.text, aa.uri, aa.type
                from entityid as aa,
                    (select id,max(version) as version from entityid where version<=$1 and ic_id=$2 group by id) as bb
                where
                    aa.id = bb.id and
                    aa.version = bb.version order by id asc');

        $all = array();
        $result = $this->sdb->execute($qq, array($vhInfo['version'], $vhInfo['ic_id']));
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }

        $this->sdb->deallocate($qq);
        return $all;
    }


    /**
     * Select subjects
     * @deprecated
     *
     * DBUtils has code to turn the return values into subjects in a Constellation object.
     *
     * Solve the multi-version problem by joining to a subquery.
     *
     * @param string[] $vhInfo associative list with keys: version, ic_id
     *
     * @return string[][] Return list of an associative list with keys: id, version, ic_id,
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
                            aa.id, aa.version, aa.ic_id, aa.term_id
                            from subject aa,
                            (select id, max(version) as version from subject where version<=$1 and ic_id=$2 group by id) as bb
                            where not aa.is_deleted and
                            aa.id=bb.id
                            and aa.version=bb.version');
        /*
         * Always use key names explicitly when going from associative context to flat indexed list context.
         */
        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['ic_id']));
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }
        $this->sdb->deallocate($qq);
        return $all;
    }

    /**
     * Select IdentityConcept Terms
     *
     * Selects IdentityConcept terms for subjects, activitities, and occupations from the Concept vocabulary system
     *
     * Solve the multi-version problem by joining to a subquery.
     *
     * @param string[] $vhInfo associative list with keys: version, ic_id
     *
     * @return string[][] Return list of an associative list with keys: id, version, ic_id,
     * term_id.
     *
     */
    public function selectIdentityConceptTerms($vhInfo)
    {
        $qq = 'select_identity_concept_terms';
        $this->sdb->prepare($qq,
            'select aa.id, aa.version, aa.ic_id, aa.concept_id as term_id, v.text as term_value, type
            from identity_concepts aa
            left outer join terms v on aa.concept_id = v.concept_id
            inner join (select id, max(version) as version from identity_concepts where version<=$1 and ic_id=$2 group by id) as bb on aa.id=bb.id and aa.version=bb.version where v.preferred and not aa.is_deleted;');


        /*
         * Always use key names explicitly when going from associative context to flat indexed list context.
         */
        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['ic_id']));
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }
        $this->sdb->deallocate($qq);
        return $all;
    }


    /**
     * Select Concept Term By Value
     *
     * Selects a Concept term by value for subjects, activitities, and occupations from the Concept system's terms table.
     * Returns the first term found.
     * 
     * Temporary way to access Concept Terms within SNAC. Will be replaced by call to SNAC Laravel
     *
     * @param string[] $value Text value of term to search
     *
     * @return string[][] Return list of matching concepts with terms.
     *
     */
    public function selectConceptTermByValue($value, $type)
    {
        if ($type) {
            $qq = 'select_concept_term_by_type';
            $query = "SELECT c.id as concept_id, t.id as term_id, text, value as type, preferred
                FROM concepts c
                LEFT JOIN terms t ON c.id = t.concept_id
                LEFT JOIN concept_categories cc on c.id = cc.concept_id
                LEFT JOIN vocabulary v on cc.category_id = v.id
                WHERE text ILIKE $1 AND v.value ILIKE $2 AND NOT deprecated;";
            $params = [$value, $type];
        } else {
            $qq = 'select_concept_term';
            $query = "SELECT c.id as concept_id, t.id as term_id, text, value as type, preferred
                FROM concepts c
                LEFT JOIN terms t ON c.id = t.concept_id
                LEFT JOIN concept_categories cc on c.id = cc.concept_id
                LEFT JOIN vocabulary v on cc.category_id = v.id
                WHERE text ILIKE $1 AND NOT deprecated;";
            $params = [$value];
        }

        $this->sdb->prepare($qq, $query);

        /*
         * Always use key names explicitly when going from associative context to flat indexed list context.
         */
        $result = $this->sdb->execute($qq, $params);

        $row = $this->sdb->fetchrow($result);
        $this->sdb->deallocate($qq);
        return $row;
    }

    /**
     * Insert legalStatus.
     *
     * @param string[] $vhInfo associative list with keys: version, ic_id
     * @param integer $id Record id from this object and table.
     * @param integer $termID Vocabulary foreign key for the term.
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
                            (version, ic_id, id, term_id)
                            values
                            ($1, $2, $3, $4)');

        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['ic_id'],
                                            $id,
                                            $termID));
        $this->sdb->deallocate($qq);
        return $id;
    }


    /**
     * Select legalStatus.
     *
     * Like subject, these are directly linked to Constellation only, and not to any other tables. Therefore
     * we only need version and ic_id.
     *
     * DBUtils has code to turn the returned values into legalStatus in a Constellation object.
     *
     * Solve the multi-version problem by joining to a subquery.
     *
     * @param string[] $vhInfo associative list with keys: version, ic_id
     *
     * @return string[][] Return list of an associative list with keys: id, version, ic_id,
     * term_id. There may be multiple records returned.
     *
     */
    public function selectLegalStatus($vhInfo)
    {
        $qq = 'ssubj';
        $this->sdb->prepare($qq,
                            'select
                            aa.id, aa.version, aa.ic_id, aa.term_id
                            from legal_status aa,
                            (select id, max(version) as version from legal_status where version<=$1 and ic_id=$2 group by id) as bb
                            where not aa.is_deleted and
                            aa.id=bb.id
                            and aa.version=bb.version');
        /*
         * Always use key names explicitly when going from associative context to flat indexed list context.
         */
        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['ic_id']));
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
     * @param string[] $vhInfo associative list with keys: version, ic_id
     *
     * @return string[][] Return list of an associative list with keys: id, version, ic_id,
     * term_id. There may be multiple rows returned.
     */
    public function selectGender($vhInfo)
    {
        $qq = 'select_gender';
        $this->sdb->prepare($qq,
                            'select
                            aa.id, aa.version, aa.ic_id, aa.term_id
                            from gender aa,
                            (select id, max(version) as version from gender where version<=$1 and ic_id=$2 group by id) as bb
                            where not aa.is_deleted and
                            aa.id=bb.id
                            and aa.version=bb.version');
        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['ic_id']));
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
     * @param string[] $vhInfo associative list with keys: version, ic_id
     *
     * @return string[][] Return list of an associative list with keys: id, version, ic_id,
     * term_id. There may be multiple rows returned.
     */
    public function selectNationality($vhInfo)
    {
        $qq = 'select_gender';
        $this->sdb->prepare($qq,
                            'select
                            aa.id, aa.version, aa.ic_id, aa.term_id
                            from nationality aa,
                            (select id, max(version) as version from nationality where version<=$1 and ic_id=$2 group by id) as bb
                            where not aa.is_deleted and
                            aa.id=bb.id
                            and aa.version=bb.version');
        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['ic_id']));
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
     * @param string[] $vhInfo associative list with keys: version, ic_id
     *
     * @return string[][] Return a list of lists. Inner list has keys: id, version, ic_id, note, vocabulary_source, occupation_id, date
     *
     */
    public function selectOccupation($vhInfo)
    {
        $qq = 'socc';
        $this->sdb->prepare($qq,
                            'select
                            aa.id, aa.version, aa.ic_id, aa.note, aa.vocabulary_source, aa.occupation_id
                            from occupation as aa,
                            (select id, max(version) as version from occupation where version<=$1 and ic_id=$2 group by id) as bb
                            where not aa.is_deleted and
                            aa.id=bb.id
                            and aa.version=bb.version');
        /*
         * Always use key names explicitly when going from associative context to flat indexed list context.
         */
        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['ic_id']));
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }
        $this->sdb->deallocate($qq);
        return $all;
    }

    /**
     * Select a related identity with Terms
     *
     * Related identity aka cpf relation. Code in DBUtils turns the returned array into a
     * ConstellationRelation object.
     *
     * @param string[] $vhInfo associative list with keys: version, ic_id
     *
     * @return string[][] Return a list of lists. There may be multiple relations. Each relation has keys: id,
     * version, ic_id, related_id, related_ark, relation_entry, descriptive_node, relation_type, role,
     * arcrole, date.
     *
     */
    public function selectRelationWithTerms($vhInfo)
    {
        $qq = 'selectrelatedidentity';
        $this->sdb->prepare($qq,
                            'select
                            aa.id, aa.version, aa.ic_id, aa.related_id, aa.related_ark,
                            aa.relation_entry, aa.descriptive_note,
                            aa.relation_type,
                                relt.value as relation_type_value,
                                relt.uri as relation_type_uri,
                                relt.description as relation_type_description,
                                relt.type as relation_type_type,
                            aa.role,
                                rolt.value as role_value,
                                rolt.uri as role_uri,
                                rolt.description as role_description,
                                rolt.type as role_type,
                            aa.arcrole,
                                arct.value as arcrole_value,
                                arct.uri as arcrole_uri,
                                arct.description as arcrole_description,
                                arct.type as arcrole_type
                            from related_identity as aa
                            left outer join vocabulary as relt on relt.id=aa.relation_type
                            left outer join vocabulary as rolt on rolt.id=aa.role
                            left outer join vocabulary as arct on arct.id=aa.arcrole
                            inner join
                                (select id, max(version) as version from related_identity where version<=$1 and ic_id=$2 group by id) as bb
                                on aa.id=bb.id and aa.version=bb.version
                            where not aa.is_deleted order by aa.relation_entry asc

                            ');

        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['ic_id']));
        $all = array();
        while ($row = $this->sdb->fetchrow($result))
        {
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
     * @param string[] $vhInfo associative list with keys: version, ic_id
     *
     * @return string[][] Return a list of lists. There may be multiple relations. Each relation has keys: id,
     * version, ic_id, related_id, related_ark, relation_entry, descriptive_node, relation_type, role,
     * arcrole, date.
     *
     */
    public function selectRelation($vhInfo)
    {
        $qq = 'selectrelatedidentity';
        $this->sdb->prepare($qq,
                            'select
                            aa.id, aa.version, aa.ic_id, aa.related_id, aa.related_ark,
                            aa.relation_entry, aa.descriptive_note, aa.relation_type,
                            aa.role,
                            aa.arcrole
                            from related_identity as aa,
                            (select id, max(version) as version from related_identity where version<=$1 and ic_id=$2 group by id) as bb
                            where not aa.is_deleted and
                            aa.id=bb.id
                            and aa.version=bb.version');

        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['ic_id']));
        $all = array();
        while ($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }
        $this->sdb->deallocate($qq);
        return $all;
    }

    /**
     * Select Related ICIDs for this target ignoring version
     *
     * Ignores the version, but quickly grabs all ICIDs that may still point at
     * the given target ICID.
     * @param  int $icid The target ICID to search
     * @return int[][]       Associative array of [ic_id, id] constituting (ICID, resourceRelationID) pairs that may still point to this target
     */
    public function selectUnversionedConstellationIDsForRelationTarget($icid) {
        $qq = 'selectrelatedicids';
        $this->sdb->prepare($qq,
                            'select distinct aa.ic_id, aa.id
                            from related_identity as aa
                            where not aa.is_deleted and
                            aa.related_id = $1');

        $result = $this->sdb->execute($qq,
                                      array($icid));
        $all = array();
        while ($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }
        $this->sdb->deallocate($qq);
        return $all;

    }

    /**
     * select related resource records
     *
     * Where $vhInfo 'version' and 'ic_id'. Code in DBUtils knows how to turn the return value into a php
     * ResourceRelation object.
     *
     * @param string[] $vhInfo associative list with keys: version, ic_id
     *
     * @return string[][] Return a list of lists. Inner list keys: id, version, ic_id, relation_entry_type,
     * href, relation_entry, object_xml_wrap, descriptive_note, role, arcrole
     *
     */
    public function selectResourceRelation($vhInfo)
    {
        $qq = 'select_resources';
        $this->sdb->prepare($qq,
            'select r.id as resource_id, r.version as resource_version, r.type as document_type, r.href, r.object_xml_wrap,
                r.title, r.extent, r.abstract, r.date, r.display_entry, r.repo_ic_id from
                (select id, max(version) as version from resource_cache where id in
                    (select aa.resource_id
                        from related_resource as aa,
                            (select id, max(version) as version from related_resource where version<=$1
                                and ic_id=$2 group by id) as bb
                        where not aa.is_deleted and
                        aa.id=bb.id
                        and aa.version=bb.version)
                    group by id) as ridvers,
                resource_cache r
                where not r.is_deleted and r.id = ridvers.id and r.version = ridvers.version;');

        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['ic_id']));
        $resources = array();
        while ($row = $this->sdb->fetchrow($result))
        {
            $resources[$row["resource_id"]] = $row;
        }
        $this->sdb->deallocate($qq);

        $qq = 'select_related_resource';
        $this->sdb->prepare($qq,
            'select rr.* from
                (select aa.id, aa.version, aa.ic_id,
                        aa.relation_entry, aa.descriptive_note, aa.arcrole,
                        aa.resource_id, aa.resource_version
                    from related_resource as aa,
                        (select id, max(version) as version from related_resource where version<=$1 and ic_id=$2 group by id) as bb
                    where not aa.is_deleted and
                    aa.id=bb.id
                    and aa.version=bb.version) rr;');

        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['ic_id']));
        $all = array();
        while ($row = $this->sdb->fetchrow($result))
        {
            // the following merge will preserve the resource_version from the resource_cache table and drop the one from related_resource
            // since the resource_cache results are merged first.
            array_push($all, array_merge($resources[$row["resource_id"]], $row));
        }
        $this->sdb->deallocate($qq);






        return $all;
    }


    /**
     * Select Resource
     *
     * Gets the resource data out of the database for the given id and version.
     *
     * @param int $id Resource ID
     * @param int $version Resource version
     * @return string[] Associative array of resource data
     */
    public function selectResource($id, $version)
    {
       $qq = 'select_resource';
       $this->sdb->prepare($qq,
                           'select
                           aa.id, aa.version, aa.title, aa.href, aa.abstract, aa.extent, aa.repo_ic_id,
                           aa.object_xml_wrap, aa.type, aa.date, aa.display_entry
                           from resource_cache as aa,
                           (select max(version) as version from resource_cache where version<=$1 and id=$2) as bb
                           where not aa.is_deleted and
                           aa.id=$2
                           and aa.version=bb.version');

       $result = $this->sdb->execute($qq,
                                     array($version,
                                           $id));
       $all = array();
       while ($row = $this->sdb->fetchrow($result))
       {
           array_push($all, $row);
       }
       $this->sdb->deallocate($qq);
       return $all;
   }

    /**
     * Select Resource By Data
     *
     * Checks to see if a resource already exists in database
     *
     * @param string $title optional The title of the resource to find
     * @param string $href optional The link of the resource to find
     * @param int $type optional The type of the resource to find
     * @return string[] Returns associative array of resource data if found
     */
    public function selectResourceByData($title = null, $href = null, $type = null) {

        $result = $this->sdb->query('select id, version from resource_cache
                                     where href = $1 and title = $2
                                     and type = $3 and not is_deleted
                                     and md5(title)::uuid = md5($2)::uuid', array($href,
                                                                                  $title,
                                                                                  $type));

        return $this->sdb->fetchAll($result);
    }


    /**
     * Select Holdings
     *
     * Selects all resource holdings of a holding repository.
     * Query searches for resources with the desired repo_ic_ids, first checking for merged repo_ic_ids,
     * then finding max version of those resource and finally pulling full resources and filtering them.
     *
     * @param integer $icid The id of the holding repository
     * @return string[] Returns associative array of resources data
     */
    public function selectHoldings($icid) {
        $query = "WITH holding_repo_ids AS ( SELECT ic_id FROM constellation_lookup WHERE current_ic_id = $1 )
                  SELECT r1.id AS \"RD-Source-ID\", r1.version, r1.href AS \"RD-URL\", v.value AS \"RD-Type\",
                      r1.title, r1.abstract, r1.extent, r1.date, $1 AS repository_id, r1.updated_at
                  FROM resource_cache r1
                      LEFT JOIN vocabulary v ON r1.type = v.id
                      INNER JOIN
                          (SELECT id, max(version) AS version
                              FROM resource_cache
                              WHERE id IN
                              ( SELECT id FROM resource_cache
                                  WHERE repo_ic_id IN (select ic_id from holding_repo_ids)
                              )
                              GROUP BY id
                          ) AS r2
                      ON r1.id = r2.id
                      AND r1.version = r2.version
                  WHERE r1.repo_ic_id in (select ic_id from holding_repo_ids)
                      AND NOT r1.is_deleted;";

        $result = $this->sdb->query($query, array($icid));
        return $this->sdb->fetchAll($result);
    }


    /**
     * Select all function records
     *
     * Constrain on version and ic_id. Code in DBUtils turns the return value into a Activity object.
     *
     * @param string[] $vhInfo associative list with keys: version, ic_id
     *
     * @return string[][] Return a list of list. The inner list has keys: id, version, ic_id, activity_type,
     * note, date.
     *
     */
    public function selectActivity($vhInfo)
    {
        $qq = 'select_activity';
        $this->sdb->prepare($qq,
                            'select
                            aa.id, aa.version, aa.ic_id, aa.activity_type, aa.vocabulary_source, aa.note,
                            aa.activity_id
                            from activity as aa,
                            (select id, max(version) as version from activity where version<=$1 and ic_id=$2 group by id) as bb
                            where not aa.is_deleted and
                            aa.id=bb.id
                            and aa.version=bb.version');

        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['ic_id']));
        $all = array();
        while ($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }
        $this->sdb->deallocate($qq);
        return $all;
    }


     /**
      * Select all names
      *
      * Constrain on version and ic_id. Code in DBUtils turns each returned list into a NameEntry
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
      * @param string[] $vhInfo with keys version, ic_id.
      *
      * @return string[][] Return a list of lists. The inner list has keys: id, version, ic_id, original,
      * preference_score.
      */
    public function selectName($vhInfo)
    {
        $qq_1 = 'selname';
        $this->sdb->prepare($qq_1,
                            'select
                            aa.is_deleted,aa.id,aa.version, aa.ic_id, aa.original, aa.preference_score
                            from name as aa,
                            (select id,max(version) as version from name where version<=$1 and ic_id=$2 group by id) as bb
                            where
                            aa.id = bb.id and not aa.is_deleted and
                            aa.version = bb.version order by preference_score desc,id asc');

        $name_result = $this->sdb->execute($qq_1,
                                           array($vhInfo['version'],
                                                 $vhInfo['ic_id']));
        $all = array();
        while($name_row = $this->sdb->fetchrow($name_result))
        {
            /*
             * printf("\nsn: id: %s version: %s ic_id: %s original: %s is_deleted: %s\n",
             *        $name_row['id'],
             *        $name_row['version'],
             *        $name_row['ic_id'],
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
     * aa.id,aa.version, aa.ic_id, aa.name_id, aa.short_name,aa.name_type
     * from  name_contributor as aa,
     * (select id, max(version) as version from name_contributor where version<=$1 and ic_id=$2 group by id) as bb
     * where not aa.is_deleted and
     * aa.id=bb.id
     * and aa.version=bb.version
     * and aa.name_id=$3');
     *
     * @param integer $nameID The foreign key record id from name.id
     *
     * @param integer $version The version number.
     *
     * @return string[] List of list, one inner list per contributor keys: id, version, ic_id, type, name, name_id
     */
    public function selectContributor($nameID, $version)
    {
        $qq_2 = 'selcontributor';
        $this->sdb->prepare($qq_2,
                            'select
                            aa.id, aa.version, aa.ic_id, aa.short_name, aa.name_type, aa.rule, aa.name_id
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
    * Select a list of name contributors for an icid
    *
    * Select all name_contributor records for a constellation and return a list of associated lists.
    *
    * @param integer $icid Constellation ID.
    *
    * @param integer $version Version number.
    *
    * @return string[][] Return a list of associated lists, where each inner list is a single name_component.
    */
    public function selectAllNameContributorsForConstellation($icid, $version) {

        $qq_2 = 'select_contributors';
        $this->sdb->prepare($qq_2,
            'select aa.id, aa.version, aa.ic_id, aa.short_name, aa.name_type, aa.rule, aa.name_id
            from
                name_contributor as aa,
                (select nc.name_id,max(nc.version) as version
                    from name_contributor nc,
                    (select
                    aa.id,aa.version, aa.ic_id
                    from name as aa,
                    (select id,max(version) as version from name where version<=$2 and ic_id=$1 group by id) as bb
                    where
                    aa.id = bb.id and not aa.is_deleted and
                    aa.version = bb.version) as n
                 where nc.name_id=n.id and nc.version<=$2 group by name_id) as bb
            where not is_deleted and aa.name_id=bb.name_id and aa.version=bb.version order by aa.name_id, aa.id asc');
        $result = $this->sdb->execute($qq_2, array($icid, $version));
        $all = array();
        while($row = $this->sdb->fetchrow($result)) {
            array_push($all, $row);
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
     * Note: Must select max(version_history.version) The max() version is the Constellation version.
     *
     * Mar 4 2016: Changed "nrd.id=date_range.fk_id" to "nrd.ic_id=date_range.fk_id" because getID of
     * nrd is ic_id not id as with other tables and other objects. We changed this a while back, but
     * (oddly?) this didn't break until today.
     *
     * May 6 2016: In fact, even using table nrd here is questionable. "the constellation" is
     * version_history. It works to use nrd.ic_id because this is the same value as version_history.id,
     * but intellectually this is inaccurate.
     *
     * @return string[] Return a flat array. This seems like a function that should return an associative
     * list. Currently, is only called in one place.
     */
    public function randomConstellationID()
    {
        $qq = 'rcid';
        $this->sdb->prepare($qq,
                            'select max(version_history.version) as version, version_history.id as ic_id
                            from nrd,date_range, version_history
                            where
                            nrd.ic_id=date_range.fk_id and
                            nrd.ic_id=version_history.id
                            and not date_range.is_deleted
                            and version_history.status <> $1
                            group by version_history.id
                            order by version_history.id
                            limit 1');

        $result = $this->sdb->execute($qq, array($this->deleted));
        $row = $this->sdb->fetchrow($result);
        $this->sdb->deallocate($qq);
        return array($row['version'], $row['ic_id']);
    }


    /**
     * Most recent version by status
     *
     * Helper function to return the most recent status version for a given ic_id. If the status is anything
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
     * @param integer $mainID id value matching version_history.id.
     *
     * @param string $status Constellation status we need
     *
     * @return integer Version number from version_history.version, as is our convention.
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
            'select max(version) as version
            from version_history
            where
            version_history.id=$1 and status=$2',
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
     * Return the lowest ic_id
     *
     * Return the lowest ic_id for a multi-name constellation with 2 or more non-deleted names. Returns a
     * version and ic_id for the constelletion to which this name belongs.
     *
     * This is a helper/convenience function for testing purposes only.
     *
     * Note: When getting a version number, we must always look for max(version_history.version) as version to be
     * sure we have "the" constellation version number.
     *
     * Subquery zz returns count of names that are not deleted, grouped by ic_id (akd main_id, aka
     * constellation id). Join that to version history, and constrain for zz.count>1 and you have multi-name
     * constellations, then limit to 1 result.
     *
     * @return integer[] Returns a vhInfo associateve list of integers with key names 'version' and
     * 'ic_id'. The ic_id is from table 'name' for the multi-alt string name. That ic_id is a
     * constellation id, so we call selectCurrentVersion() to get the current version for that
     * constellation. This allows us to return a conventional vhInfo associative list which is conventient
     * return value. (Convenient, in that we do extra work so the calling code is simpler.)
     */
    public function sqlMultiNameConstellationID()
    {
        $qq = 'mncid';
        $this->sdb->prepare($qq,
                            'select max(vh.version) as version, vh.id as ic_id
                            from version_history as vh,
                            (select count(distinct(aa.id)),aa.ic_id from name as aa
                            where aa.id not in (select id from name where is_deleted) group by ic_id order by ic_id) as zz
                            where
                            vh.id=zz.ic_id and
                            vh.status <> $1 and
                            zz.count>1 group by vh.id limit 1');

        $result = $this->sdb->execute($qq, array($this->deleted));
        $row = $this->sdb->fetchrow($result);

        $version = $this->selectCurrentVersion($row['ic_id']);

        $this->sdb->deallocate($qq);
        return array('version' => $version,
                     'ic_id' => $row['ic_id']);
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
     * Only return data (version, ic_id) that might be necessary for display
     * in the dashboard. Version and ic_id are sufficient to call selectConstellation(). The name is added
     * on the off chance that this could be used in some UI that needed a name displayed for the
     * constellation.
     *
     * Note: query() as opposed to prepare() and execute()
     *
     * @return string[] A list of 100 lists. Inner list keys are: 'version', 'ic_id', 'formatted_name'. At
     * this time 'formatted_name' is from table name.original
     */
    public function selectDemoRecs()
    {
        $sql =
            'select max(version) as version, id as ic_id
            from version_history
            where version_history.status <> $1
            group by id order by id limit 100';

        $result = $this->sdb->query($sql, array($this->deleted));
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            $nRow = $this->selectName(array('version' => $row['version'],
                                            'ic_id' => $row['ic_id']));
            if (count($nRow) == 0)
            {
                // Yikes, cannot have a constellation with zero names.
                printf("\nError: SQL.php No names for version: %s ic_id: %s\n", $row['version'], $row['ic_id']);
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
     * The unique primary key for a table is id,version. Field ic_id is the relational grouping field,
     * and used by higher level code to build the constellation, but by and large ic_id is not used for
     * record updates, so the code below makes no explicit mention of ic_id.
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
     * The unique primary key for a table is id,version. Field ic_id is the relational grouping field,
     * and used by higher level code to build the constellation, but by and large ic_id is not used for
     * record updates, so the code below makes no explicit mention of ic_id.
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
             * and/or add a primary key constraint. There can be only one ic_id for a given id.
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
         * Get the ic_id for $recID
         */
        $result = $this->sdb->query(
            "select aa.ic_id from name as aa,
            (select id, ic_id, max(version) as version from name group by id,ic_id) as bb
            where aa.id=bb.id and not aa.is_deleted and aa.version=bb.version and aa.ic_id=bb.ic_id and aa.id=$1",
            array($recID));

        $row = $this->sdb->fetchrow($result);
        $mainID = $row['ic_id'] ?? null;

        /*
         * Use the ic_id to find not is_deleted sibling names.
         */
        $result = $this->sdb->query(
            "select count(*) as count from name as aa,
            (select id, max(version) as version from name where ic_id=$1 group by id) as bb
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
     * @param $mainID Integer constellation id usually from version_history.id.
     *
     * @return interger Number of names meeting the criteria. Zero if no names or if the query fails.
     *
     */
    public function parentCountNames($mainID)
    {
        $selectSQL =
            "select count(*) as count from name as aa,
            (select id, ic_id, max(version) as version from name group by id,ic_id) as bb
            where aa.id=bb.id and not aa.is_deleted and aa.version=bb.version and aa.ic_id=bb.ic_id and aa.ic_id=$1";

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
     * @param string $uri The URI string to search through the vocabulary
     * @return string[]|null Array of information about the Geo Place or null if not found
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
     * Any term which is a key in $useStartsWith will use a "starts with" type of ilike match. That is, the
     * $query must be at the beginning of the search. The default is for $query to occur anywhere.
     *
     * Add new terms as keys in $useStartsWith as necessary. The choice of value 1 is not arbitrary, and works
     * well in most situations where a hash aka associative list is being used in a control statement.
     *
     * @param string $term The "type" term for what type of vocabulary to search
     * @param string $query The string to search through the vocabulary
     * @param integer $entityTypeID Numeric key related to vocabulary.id where type='entity_type' of one of
     * the three entity types.
     * @param int $count optional The number of search results to request
     * @return string[][] Returns a list of lists with keys id, value.
     */
    public function searchVocabulary($term, $query, $entityTypeID, $count = 100)
    {
        $useStartsWith = array('script_code' => 1,
                               'language_code' => 1,
                               'gender' => 1,
                               'nationality' => 1,
                               'subject' => 1,
                               'activity' => 1,
                               'occupation' => 1);
        $likeStr = "%$query%";
        if (isset($useStartsWith[$term]))
        {
            $likeStr = "$query%";
        }

        /*
         * $this->enableLogging();
         * $this->logDebug("sql.php term: $term likeStr: $likeStr", array());
         */
        if ($entityTypeID == null)
        {
            $queryStr =
                      'select id,value,type,uri,description
                      from vocabulary
                      where type=$1 and value ilike $2 order by value asc limit $3';
            $result = $this->sdb->query($queryStr, array($term, $likeStr, $count));
        }
        else
        {
            /*
             * If we have a non-null entityTypeID then the type should be 'name_component'. We could check
             * that although it isn't really necessary. When called with some other type, no records will be
             * returned, presumably because the only values using entity_group are type='name_component'.
             *
             * The values for type name_component are at the end of the file install/sql_files/vocabulary.sql.
             *
             * We need an "or" clause because NameAddition and Date are used for multiple name
             * components. Ideally, NameAddition is person and corporateBody and not family, but to simplify
             * things we say null is all three for NameAddition and Date.
             *
             * If null becomes a problem, zero or some other integer that is not an entity type would probably
             * work just as well. Better perhaps since nulls preclude using entity_group in a primary key.
             *
             * It might be better to return these ordered by id instead of value. The UI may have expectations
             * about the order.
             */
            $queryStr =
                      'select id,value
                      from vocabulary
                      where type=$1 and value ilike $2 and (entity_group=$3 or entity_group is null) order by value asc limit 100';
            $result = $this->sdb->query($queryStr, array($term, $likeStr, $entityTypeID));
        }
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }
        /* Disable sorting name components, for now.
         * if ($term == 'name_component' && count($all) > 1)
         * {
         *     return $this->specialSort($all);
         * }
         */
        return $all;
    }

    /**
     * Search Resources
     *
     * @param string $query The string to search through the vocabulary
     * @param boolean $urlOnly optional Whether to only search the URL
     * @return string[][] Returns a list of lists.
     */
    public function searchResources($query, $urlOnly = false)
    {
        $queryStr =
                  'select id, version, type, href, object_xml_wrap, title, extent, abstract, repo_ic_id, date, display_entry
                  from resource_cache
                  where href = $1 or title ilike $1 order by title asc';
        if ($urlOnly) {
            $queryStr =
                  'select id, version, type, href, object_xml_wrap, title, extent, abstract, repo_ic_id, date, display_entry
                  from resource_cache
                  where href = $1 order by title asc';
        }

        $result = $this->sdb->query($queryStr, array("%$query%"));

        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }
        return $all;
    }

    /**
     * Search Users
     *
     * Search the users in the appuser table of the database.  Returns only the userid and
     * full name of the query.  Searches the string in the full name, user name, and email
     * columns.
     *
     * @param string $query The string to search through the user tables
     * @param int $count optional The number of results to return (default null)
     * @param string $roleFilter optional Role name by which to filter the results (default null)
     * @param boolean $everyone optional Whether to include inactive users (default false)
     *
     * @return string[][] Returns a list of lists of search results.
     */
    public function searchUsers($query, $count=null, $roleFilter=null, $everyone=false) {
        $realCount = \snac\Config::$SQL_LIMIT;
        if ($count != null)
            $realCount = $count;

        $activeFilter = "and u.active";
        if ($everyone)
            $activeFilter = "";

        $result = null;

        if ($roleFilter == null) {
            $queryStr = "select u.id, u.fullname from appuser u
                        where u.fullname ilike $1 or u.username ilike $1 or u.email ilike $1
                        $activeFilter
                        order by u.fullname asc limit $2";
            $result = $this->sdb->query($queryStr, array("%$query%", $realCount));
        } else {
            $queryStr = "select u.id, u.fullname from appuser u, appuser_role_link rl, role r
                        where (u.fullname ilike $1 or u.username ilike $1 or u.email ilike $1)
                        and rl.uid = u.id and rl.rid = r.id and r.label = $2
                        $activeFilter
                        order by u.fullname asc limit $3";
            $result = $this->sdb->query($queryStr, array("%$query%", $roleFilter, $realCount));
        }

        $all = array();
        if ($result != null) {
            while($row = $this->sdb->fetchrow($result))
            {
                array_push($all, $row);
            }
        }
        return $all;

    }

    /**
     * Browse Name Index
     *
     * This function contains the SQL code required to browse through the name index in postgres
     * in (mostly) alphabetical order.
     *
     * @param string $query The string to search for
     * @param string $position The position of the search term in the results: before, middle, after
     * @param string $entityType The string representation of entity type: person, corporateBody, family
     * @param int    $icid The identity constellation ID to break ties on sorting (or 0 if ignored)
     * @return string[] List of name index results in raw format
     */
    public function browseNameIndex($query, $position, $entityType, $icid)
    {
        $entityQuery = "";
        if ($entityType != null && $entityType != "") {
            // Do this for safety concerns with SQL injections...
            switch ($entityType) {
                case "person":
                    $entityQuery = "and entity_type = 'person'";
                    break;
                case "corporateBody":
                    $entityQuery = "and entity_type = 'corporateBody'";
                    break;
                case "family":
                    $entityQuery = "and entity_type = 'family'";
                    break;
            }
        }

        $result = null;

        if ($position == "after") {
            $queryStr = "select * from (select * from name_index where (name_entry = $1 and ic_id >= $2) $entityQuery order by name_entry_lower, name_entry, ic_id asc limit 20) a union all (select * from name_index where name_entry > $1 $entityQuery order by name_entry_lower, name_entry, ic_id asc limit 20) order by name_entry, ic_id asc limit 20;";
            $result = $this->sdb->query($queryStr, array($query, $icid));
        } else if ($position == "before") {
            if ($icid !== 0) {
                // query using the ICID as well
                $queryStr = "select * from (select * from (select * from name_index where (name_entry = $1 and ic_id <= $2) $entityQuery order by name_entry_lower desc, name_entry desc, ic_id desc limit 20) a union all (select * from name_index where name_entry < $1 $entityQuery order by name_entry_lower desc, name_entry desc, ic_id desc limit 20) order by name_entry desc, ic_id desc limit 20) a order by name_entry asc, ic_id asc limit 20;";
                $result = $this->sdb->query($queryStr, array($query, $icid));
            } else {
                // query without the ICID, since it is meaningless
                $queryStr = "select * from (select * from name_index where name_entry_lower <= lower($1) $entityQuery order by name_entry_lower desc, ic_id desc limit 20) a order by name_entry, ic_id asc;";
                $result = $this->sdb->query($queryStr, array($query));
            }
        } else {
            $queryStr =
                "select * from (select * from name_index where name_entry_lower >= lower($1) $entityQuery order by name_entry_lower, ic_id asc limit 10) a union all (select * from name_index where name_entry_lower < lower($1) $entityQuery order by name_entry_lower desc, ic_id desc limit 10) order by name_entry, ic_id asc;";

            $result = $this->sdb->query($queryStr, array($query));
        }


        $all = array();
        while($row = $this->sdb->fetchRow($result))
        {
            array_push($all, $row);
        }
        return $all;
    }

    /**
     * Update the Name Index
     *
     * Checks to see if the ICID is in the name index.  If so, it will update the values there with the parameters.  Else,
     * it will insert the new ICID and related values into the name index.
     *
     * @param string $nameEntry The name entry to include in the index
     * @param int $icid The ICID for this constellation
     * @param string $ark The ARK ID for this constellation
     * @param string $entityType The string representation of the entity type
     * @param int $degree The degree of the constellation (number of out-edges to other constellations in snac)
     * @param int $resources The number of resource relations for the constellation
     *
     * @return string[]|boolean The updated name index values or false on failure
     */
    public function updateNameIndex($nameEntry, $icid, $ark, $entityType, $degree, $resources) {
        // First query to see if the name is already in the index.  If so, then do an update.  Else, this is an insert.
        $resultTest = $this->sdb->query("select * from name_index where ic_id = $1;", array($icid));
        $check = array();
        while($row = $this->sdb->fetchRow($resultTest))
        {
            array_push($check, $row);
        }

        if (empty($check)) {
            // Doing an insert, since nothing found
            $result = $this->sdb->query("insert into name_index
                                            (ic_id, ark, entity_type, name_entry, name_entry_lower, degree, resources, timestamp) values
                                            ($1, $2, $3, $4, lower($4), $5, $6, now()) returning *;",
                                        array($icid, $ark, $entityType, $nameEntry, $degree, $resources));
            $all = array();
            while($row = $this->sdb->fetchRow($result))
            {
                array_push($all, $row);
            }
            return $all;
        } else {
            // Doing an update, since ic_id was found
            $result = $this->sdb->query("update name_index set
                                            (ark, entity_type, name_entry, name_entry_lower, degree, resources, timestamp) =
                                            ($2, $3, $4, lower($4), $5, $6, now()) where ic_id = $1 returning *;",
                                        array($icid, $ark, $entityType, $nameEntry, $degree, $resources));
            $all = array();
            while($row = $this->sdb->fetchRow($result))
            {
                array_push($all, $row);
            }
            return $all;
        }
        return false;
    }

    /**
     * Delete from Name Index
     *
     * Deletes the given ICID's values in the name index.  This would remove the name from the browsing index.
     *
     * @param int $icid The ICID of the constellation to remove
     * @return boolean True if successfully deleted, False if nothing to delete (failure)
     */
    public function deleteFromNameIndex($icid) {
        $result = $this->sdb->query("delete from name_index where ic_id = $1 returning *;", array($icid));
        $check = array();
        while($row = $this->sdb->fetchRow($result))
        {
            array_push($check, $row);
        }

        if (!empty($check)) {
            return true;
        }

        return false;
    }


    /**
     * Temporary function to brute force order name components.
     *
     * Sort $orig to put it in the order we want, not the order it exists in the database. Could have added
     * another column to the db (or created a second table for vocabulary structure), but that would require a
     * db rebuild as well as altering the vocabulary initialization SQL. That might have been less work that
     * this (or more elegant) but this fix is totally localized right here.
     *
     * This also removes NameAddition from family by not including it in the $dest list.
     *
     * @param string[][] $orig A list of list with keys 'id','value'.
     *
     * @return string[][] Sorted copy of the $orig list.
     */
    private function specialSort($orig)
    {
        /*
         * List of keys and the order in which they should appear.
         */
        $personList = array('Surname' => 0,
                            'Forename' => 1,
                            'NameAddition' => 2,
                            'RomanNumeral' => 3,
                            'Date' => 4,
                            'NameExpansion' => 5,
                            'UnspecifiedName' => 6);

        $corpList = array('Name' => 0,
                          'JurisdictionName' => 1,
                          'SubdivisionName' => 2,
                          'NameAddition' => 3,
                          'Number' => 4,
                          'Location' => 5,
                          'Date' => 6);

        $familyList = array('FamilyName' => 0,
                            'FamilyType' => 1,
                            'Place' => 2,
                            'Date' => 3,
                            'NameAddition' => -1);

        if ('Forename' == $orig[1]['value'])
        {
            $useList = $personList;
        }
        else if ('JurisdictionName' == $orig[1]['value'])
        {
            $useList = $corpList;
        }
        else
        {
            $useList = $familyList;
        }

        $dest = array();
        foreach($orig as $record)
        {
            /*
             * Throw out anything with a negative index because that was never supposed to be in the list.
             * Specifically 'NameAddition' for family.
             */
            if ($useList[$record['value']] >= 0)
            {
                $dest[$useList[$record['value']]] = $record;
            }
        }
        /*
         * PHP gets the integer keys right, but treats the list as an associative list with numeric keys out
         * of order. ksort() fixes that.
         */
        ksort($dest);
        return $dest;
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

    /**
     * Select unique constellation ids
     *
     * Used for a one time export of all records. Seems like it might be useful later.
     *
     * @return string[] List of constellation id values.
     */
    public function selectAllConstellationID()
    {
        $selectSQL = "select distinct(ic_id) from nrd";
        $result = $this->sdb->query($selectSQL, array());
        $all = array();
        while ($row = $this->sdb->fetchrow($result)) {
            array_push($all, $row['ic_id']);
        }
        return $all;
    }

    /**
     * Insert a new group.
     *
     * Insert a new group and return the group's id.
     *
     * @param string $label Group label
     *
     * @param string $description Group description
     *
     * @return integer The inserted row id.
     */
    public function insertGroup($label, $description)
    {
        $result = $this->sdb->query("insert into appuser_group (label, description) values ($1, $2) returning id",
                          array($label, $description));
        $row = $this->sdb->fetchrow($result);
        return $row['id'];
    }

    /**
     * Update a group.
     *
     * @param integer $rid Group row id
     *
     * @param string $label Group label
     *
     * @param string $description Group description
     */
    public function updateGroup($rid, $label, $description)
    {
        $result = $this->sdb->query("update appuser_group set label=$2, description=$3 where id=$1",
                                    array($rid, $label, $description));
        $row = $this->sdb->fetchrow($result);
    }

    /**
     * Select a group record
     *
     * Get all fields of a single group record matching id $pid.
     *
     * @param integer $pid Group ID value.
     *
     * @return string[] All fields of a single group record.
     */
    public function selectGroup($pid)
    {
        $result = $this->sdb->query("select * from appuser_group where id=$1",
                                    array($pid));
        $row = $this->sdb->fetchrow($result);
        return $row;
    }

    /**
     * Really delete a group
     *
     * Used for testing only, maybe. In any case, deleting a group should be rare. To make this a little safer
     * it only deletes if the group is not in use.
     *
     * @throws \snac\exceptions\SNACDatabaseException
     *
     * @param integer $groupID An group id
     */
    public function deleteGroup($groupID)
    {
        $result = $this->sdb->query(
            'select email from appuser, appuser_group_link as agl where agl.gid=$1 and agl.uid=appuser.id',
            array($groupID));
        $email = "";
        while($row = $this->sdb->fetchrow($result))
        {
            $email .= $row['email'] . " ";
        }
        if ($email)
        {
            throw new \snac\exceptions\SNACDatabaseException("Tried to delete group still used by user(s): $email");
        }
        else
        {
            $this->sdb->query(
                'delete from appuser_group where id=$1 and id not in (select distinct(gid) from appuser_group_link)',
                array($groupID));
        }
    }

    /**
     * Select IDs of all group records
     *
     * Return a list group IDs
     *
     * @return integer[] List of strings for each groups. We expect the calling code in DBUser.php to send
     * each element of the list to populateGroup().
     */
    public function selectAllGroupIDs()
    {
        $result = $this->sdb->query("select id from appuser_group order by label", array());
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row['id']);
        }
        return $all;
    }

    /**
     * Select user group record IDs
     *
     * Select all the IDs of groups for a single user. Higher level code will use each id to build a group
     * object (and usually a list of group objects).
     *
     * @param int $appUserID The numeric ID for the user for whom to list groups.
     *
     * @return integer[] Return list of ID values. We expect the higher level calling code to pass each ID to
     * populateGroup().
     */
    public function selectUserGroupIDs($appUserID)
    {
        $result = $this->sdb->query("select gg.id from appuser_group as gg,appuser_group_link
                                    where appuser_group_link.uid=$1 and gg.id=gid order by label asc",
                                    array($appUserID));
        $all = array();
        while($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row['id']);
        }
        return $all;
    }

    /**
     * Add a group to a user
     *
     * Link a group to a user.
     *
     * @param integer $uid User id, aka appuser.id aka row id.
     * @param integer $newGroupID A group id
     */
    public function insertGroupLink($uid, $newGroupID)
    {
        $this->sdb->query("insert into appuser_group_link (uid, gid) values ($1, $2)",
                          array($uid, $newGroupID));
    }

    /**
     * Delete a user from a group
     *
     * Deleted an appuser to group link.
     *
     * @param integer $uid User id, aka appuser.id aka row id.
     * @param integer $groupID A group id
     */
    public function deleteGroupLink($uid, $groupID)
    {
        $this->sdb->query("delete from appuser_group_link where uid=$1 and gid=$2",
                          array($uid, $groupID));
    }

    /**
     * Select all user IDs in group
     *
     * @param integer $groupID A group id
     *
     * @param boolean $everyone If true include inactive users, else only list active.
     *
     * @return integer[] List of group ID values.
     */
    public function selectUserIDsFromGroup($groupID, $everyone)
    {
        $query = "select uid from appuser_group_link where gid=$1";
        if (! $everyone) {
            /*
             * Must join with appuser to get only active users.
             *
             * Table names are not necessary in the select or where clause. The field names are
             * unique. However, explicitly using table names (or the alias agl) makes the intent clear.
             */
            $query = "select agl.uid from appuser_group_link as agl, appuser where gid=$1 and agl.uid=appuser.id and appuser.active";
        }
        $result = $this->sdb->query($query,
                                    array($groupID));
        $all = array();
        while ($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row['uid']);
        }
        return $all;
    }


    /**
     * Select all snac_institution records
     *
     * This returns records in a 2D array, with inner list keys: id, ic_id
     *
     * @return string[][] List of accciative list
     */
    public function selectAllInstitution()
    {
        $selectSQL = "select * from snac_institution";
        $result = $this->sdb->query($selectSQL, array());
        $all = array();
        while ($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }
        return $all;
    }

    /**
     * Insert a new institution.
     *
     * Insert a new institution and return the institution's id. If institution already exists, simply return id.
     *
     * @param string $ic_id Institution's ic_id.
     * @return string[] $row Institution ic_id.
     */
    public function insertInstitution($ic_id) {

        $result = $this->sdb->query("select * from snac_institution where ic_id=$1", array($ic_id));
        $row = $this->sdb->fetchrow($result);

        if (!$row) {
            $result = $this->sdb->query("insert into snac_institution (ic_id) values ($1) returning ic_id", array($ic_id));
            $row = $this->sdb->fetchrow($result);
        }

        return $row["ic_id"];
    }

    /**
     * Delete a SNAC institution
     *
     * This will throw an exception if asked to delete an institution has affiliated users.
     *
     * @throws \snac\exceptions\SNACDatabaseException
     *
     * @param integer $ic_id An institution ic_id, aka constellation ID.
     */
    public function deleteInstitution($ic_id)
    {
        $result = $this->sdb->query(
            'select appuser.username from appuser where affiliation=$1',
            array($ic_id));
        $usernames = "";
        while($row = $this->sdb->fetchrow($result))
        {
            $usernames .= $row['username'] . " ";
        }
        if ($usernames)
        {
            throw new \snac\exceptions\SNACDatabaseException("Tried to delete institution still used by users: $usernames");
        }
        else
        {
            $this->sdb->query(
                'delete from snac_institution where ic_id=$1',
                array($ic_id));
        }
    }

    /**
     * Get publish version history info
     *
     * Currently used only the EACCPFSerializer.php for maintenanceEvent data.
     *
     * cpf.rng doesn't like milliseconds so create an ISO date without them. Note I have included the "T"
     * which has issues.
     *
     * https://www.postgresql.org/docs/current/static/datatype-datetime.html
     *
     * Note: ISO 8601 specifies the use of uppercase letter T to separate the date and time. PostgreSQL
     * accepts that format on input, but on output it uses a space rather than T, as shown above[below]. This is for
     * readability and for consistency with RFC 3339 as well as some other database systems.
     *
     * Bad: 2016-07-28 16:30:16.18485 Good: 2016-07-28T16:30:16
     *
     * @param string[] $vhInfo associative list with keys: version, ic_id
     * @param boolean $listAll optional If set to true, will list all the version history.  If not set, will only
     * list publicly available history
     * @return string[] An associative list with keys corresponding to the version_history table columns.
     */
    public function selectVersionHistory($vhInfo,$listAll = false) {
        $limitHistory = 'and (v.status=\'published\' or v.status=\'ingest cpf\' or v.status=\'deleted\' or v.status=\'tombstone\' or v.status=\'ingest cpf\' or v.status=\'merge split\')';
        if ($listAll === true)
            $limitHistory = "";
        $result = $this->sdb->query(
            'select v.*, to_char(v.timestamp, \'YYYY-MM-DD"T"HH24:MI:SS\') as update_date, a.username, a.fullname
            from version_history v, appuser a
            where v.user_id = a.id and v.id=$1 and v.version<=$2
                '.$limitHistory.'
            order by v.timestamp asc',
            array($vhInfo["ic_id"], $vhInfo["version"]));

        $all = array();
        while ($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }
        return $all;
    }

    /**
     * Select Message by ID
     *
     * Reads the message data out of the database for the given ID.
     *
     * @param int $id The message ID to read
     * @return string[] The message data
     */
    public function selectMessageByID($id) {
        $result = $this->sdb->query(
            'select m.*,to_char(m.time_sent, \'YYYY-MM-DD"T"HH24:MI:SS\') as sent_date from messages m where m.id = $1',
            array($id));
        $all = array();
        while ($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }

        if (count($all) === 1)
            return $all[0];

        return array();

    }

    /**
     * Archive Message
     *
     * Sets the deleted flag for the given message ID, denoting that this message has been archived
     * in the system.
     *
     * @param int $id The message ID to delete
     * @return boolean True on success, false otherwise
     */
    public function archiveMessageByID($id) {
        $result = $this->sdb->query(
            'update messages set deleted = true where id = $1 returning *',
            array($id));
        $all = array();
        while ($row = $this->sdb->fetchrow($result))
        {
            array_push($all, $row);
        }

        if (count($all) === 1)
            return true;

        return false;

    }

    /**
     * Mark Message Read
     *
     * Marks the message with given id as read in the database.  This just sets the read flag to
     * true.
     *
     * @param int $id The message ID to mark as read
     */
    public function markMessageReadByID($id) {
        $result = $this->sdb->query(
            'update messages set read = TRUE where id = $1',
            array($id));
    }

    /**
     * Insert Message
     *
     * Writes the given message data into the database as a new message.
     *
     * @param int $toUser The user id this message is directed to
     * @param int $fromUser The user id this message is from
     * @param string $fromString The string representation this message is from (if no user, such as feedback from IP address)
     * @param string $subject The subject of the message
     * @param string $body The body of the message (HTML)
     * @param string $attachmentContent The attached file encoded as a string
     * @param string $attachmentFilename The filename to give the attachment on reading
     * @return boolean True if succeeded, false otherwise
     */
    public function insertMessage($toUser,
                                    $fromUser,
                                    $fromString,
                                    $subject,
                                    $body,
                                    $attachmentContent,
                                    $attachmentFilename) {

        try {
            $this->sdb->prepare("insert_message",'insert into messages
                    (to_user, from_user, from_string, subject, body, attachment_content, attachment_filename)
                    values ($1, $2, $3, $4, $5, $6, $7);');

            $this->sdb->execute("insert_message",
                array($toUser, $fromUser, $fromString, $subject, $body, $attachmentContent, $attachmentFilename));
        } catch (\snac\exceptions\SNACDatabaseException $e) {
            return false;
        }
        return true;
    }

    /**
     * Select Messages for UserID
     *
     * Selects all non-deleted messages for the given userID.  By default, it gives both read and unread messages
     * sent to this user.
     *
     * @param int $userid The userid of the user
     * @param boolean $toUser optional Whether or not to select messages to this user (or from this user)
     * @param boolean $unreadOnly optional Whether to query only unread messages (default false)
     * @param boolean $archivedOnly optional Whether to query only archived messages (default false)
     * @return string[] The list of message data for the user
     */
    public function selectMessagesForUserID($userid, $toUser=true, $unreadOnly=false, $archivedOnly=false) {
        $searchUser = 'to_user';
        if (!$toUser) {
            $searchUser = 'from_user';
        }
        $readFilter = '';
        if ($unreadOnly) {
            $readFilter = 'and not read';
        }
        // select only deleted messages if $archivedOnly is true
        $archiveFilter= ($archivedOnly ? "deleted " : "not deleted");

        $result = $this->sdb->query(
            'select m.*,to_char(m.time_sent, \'YYYY-MM-DD"T"HH24:MI:SS\') as sent_date from messages m where '
            .$archiveFilter.' and '.$searchUser.' = $1 '.$readFilter.' order by m.time_sent desc',
            array($userid));

        $all = array();
        while ($row = $this->sdb->fetchrow($result)) {
            array_push($all, $row);
        }
        return $all;
    }

    /**
     * Unread Message Count
     *
     * Selects the unread message count for the given user id.
     *
     * @param int $userid The numeric userid for a user
     * @return int The number of messages unread for this user
     */
    public function selectNumUnreadMessagesByUserID($userid) {
        $retVal = 0;

        $result = $this->sdb->query(
            'select count(*) as count from messages m where to_user = $1 and not read',
            array($userid));

        while ($row = $this->sdb->fetchrow($result)) {
            $retVal = $row["count"];
        }

        return $retVal;
    }

    /**
     * Select Messages from User
     *
     * Selects all messages for the given userID.
     *
     * @param int $userid The userid of the user
     * @return string[] The list of message data for the user
     */
    public function selectMessagesFromUser($userid) {
        $result = $this->sdb->query(
            'select m.*, to_char(m.time_sent, \'YYYY-MM-DD"T"HH24:MI:SS\') as sent_date from messages m
             where from_user = $1 order by m.time_sent desc',
            array($userid));

        $all = array();
        while ($row = $this->sdb->fetchrow($result)){
            array_push($all, $row);
        }
        return $all;
    }

    /**
     * List MaybeSameIDs
     *
     * Gets a list of ICIDs that may be the same as the given parameter.
     *
     * @param  integer $icid IC ID for which to search
     * @return integer[]       List of ICIDs listed to be maybe the same
     */
    public function listMaybeSameIDsFor($icid) {
        $result = $this->sdb->query(
            'select ic_id1, ic_id2 from maybe_same where ic_id1=$1 or ic_id2=$1;',
            array($icid));
        $usernames = "";
        $all = array();
        while ($row = $this->sdb->fetchrow($result))
        {
            // Assign the ids as keys as well as values to ensure no duplicates
            if ($row['ic_id1'] == $icid) {
                $all[$row['ic_id2']] = $row['ic_id2'];
            } else {
                $all[$row['ic_id1']] = $row['ic_id1'];
            }
        }
        return $all;
    }

    /**
     * Insert Report
     *
     * Inserts the report into the database.
     *
     * @param string $name The name of the report
     * @param string $report The full text of the report (JSON)
     * @param int $userid The userid that requested the report
     * @param int $affiliationid The affiliation of the user that requested the report
     */
    public function insertReport($name, $report, $userid, $affiliationid) {
        $this->sdb->query('insert into reports (name, report, user_id, affiliation_id) values
                            ($1, $2, $3, $4)', array($name, $report, $userid, $affiliationid));
    }

    /**
     * Select Report By Time
     *
     * Reads the report from the database with the given name for the given timestamp.  By default,
     * this method will read the latest report by that name.
     *
     * @param string $name The name of the report to read
     * @param string $timestamp optional The timestamp for the report to read
     * @return string[] The report data from the database
     */
    public function selectReportByTime($name, $timestamp = null) {
        $result = null;
        if ($timestamp == null) {
            $result = $this->sdb->query("select * from reports where name = $1 order by timestamp desc limit 1",
                                        array($name));
        } else {
            $result = $this->sdb->query("select * from reports where name = $1 and timestamp = $2",
                                        array($name, $timestamp));
        }

        if (!$result)
            return false;

        return $this->sdb->fetchrow($result);
    }


    /**
     * Get Institutional Reporting Data
     *
     * Given an ICID, queries through Postgres to get statistical data
     * on the institution denoted by that IC.  This includes the number of recent
     * updates to constellations and the top editors at that institution.
     *
     * @param int $icid The Constellation ID to query
     * @return string[] The statistical data from the database
     */
    public function getInstitutionReportData($icid) {
        $return = [
            "week" => [],
            "month" => []
        ];

        $result = $this->sdb->query("select count(distinct id) from version_history
            where timestamp > CURRENT_TIMESTAMP - INTERVAL '7 days'
                and status != 'published' and status != 'deleted'
                and status != 'tombstoned' and status != 'needs review';", array());

        if ($result) {
            $all = $this->sdb->fetchAll($result);
            $return["week"]["allEditCount"] = $all[0]["count"];
        }

        $result = $this->sdb->query("select count(distinct v.id) from
            version_history v, appuser u
            where v.timestamp > CURRENT_TIMESTAMP - INTERVAL '7 days'
                and v.status != 'published' and v.status != 'deleted'
                and v.status != 'tombstoned' and v.status != 'needs review'
                and v.user_id = u.id and u.affiliation = $1", array($icid));

        if ($result) {
            $all = $this->sdb->fetchAll($result);
            $return["week"]["instEditCount"] = $all[0]["count"];
        }

        $result = $this->sdb->query("select fullname, count(*) from
                (select distinct u.fullname, v.id from
                version_history v, appuser u
                where v.timestamp > CURRENT_TIMESTAMP - INTERVAL '7 days'
                    and v.status != 'published' and v.status != 'deleted'
                    and v.status != 'tombstoned' and v.status != 'needs review'
                    and v.user_id = u.id and u.affiliation = $1) a
            group by fullname
            order by count desc, fullname asc", array($icid));

        if ($result) {
            $all = $this->sdb->fetchAll($result);
            $return["week"]["topEditors"] = $all;
        }

        $result = $this->sdb->query("select count(distinct id) from version_history
            where timestamp > CURRENT_TIMESTAMP - INTERVAL '30 days'
                and status != 'published' and status != 'deleted'
                and status != 'tombstoned' and status != 'needs review';", array());

        if ($result) {
            $all = $this->sdb->fetchAll($result);
            $return["month"]["allEditCount"] = $all[0]["count"];
        }

        $result = $this->sdb->query("select count(distinct v.id) from
            version_history v, appuser u
            where v.timestamp > CURRENT_TIMESTAMP - INTERVAL '30 days'
                and v.status != 'published' and v.status != 'deleted'
                and v.status != 'tombstoned' and v.status != 'needs review'
                and v.user_id = u.id and u.affiliation = $1", array($icid));

        if ($result) {
            $all = $this->sdb->fetchAll($result);
            $return["month"]["instEditCount"] = $all[0]["count"];
        }

        $result = $this->sdb->query("select fullname, count(*) from
                (select distinct u.fullname, v.id from
                version_history v, appuser u
                where v.timestamp > CURRENT_TIMESTAMP - INTERVAL '30 days'
                    and v.status != 'published' and v.status != 'deleted'
                    and v.status != 'tombstoned' and v.status != 'needs review'
                    and v.user_id = u.id and u.affiliation = $1) a
            group by fullname
            order by count desc, fullname asc", array($icid));

        if ($result) {
            $all = $this->sdb->fetchAll($result);
            $return["month"]["topEditors"] = $all;
        }

        return $return;
    }

    /**
     * Record Analytics
     *
     * Saves outbound link traffic for analytics
     *
     * @param int $icid The icid of the constellation page the link was clicked on, if any
     * @param string $url Url of the resource clicked
     * @param int $repo_ic_id Repository id of the resource clicked
     */
    public function recordAnalytics($icid, $url, $repoICID) {
        $sql = "INSERT INTO outbound_link (ic_id, url, repo_ic_id) VALUES ($1, $2, $3)";
        $result = $this->sdb->query($sql, array($icid, $url, $repoICID));
    }

    /**
     * Read Analytics by Domain
     *
     * Read outbound link traffic analytics
     *
     * @param string $domain The unique domain to return counts for
     * @return array $results Array of dates and hit counts
     */
    public function selectAnalyticsbyDomain($domain) {
        $sql = "SELECT count(*), to_char(timestamp, 'yyyy-mm') AS date
                FROM outbound_link
                WHERE url ilike $1
                AND timestamp > (NOW() - INTERVAL '1 year')
                GROUP BY date;";

        $result = $this->sdb->query($sql, ["%".$domain."%"]);
        $results = $this->sdb->fetchAll($result);
        return $results;
    }

    /**
     * Read Analytics by Repository
     *
     * Read outbound link traffic analytics by holding repository
     *
     * @param string $icid The id of the holding repository
     * @return array $results Array of dates and hit counts
     */
    public function selectAnalyticsByRepo($icid) {
        $sql = "SELECT count(*), to_char(timestamp, 'yyyy-mm') AS date
                FROM outbound_link o
                LEFT JOIN constellation_lookup c
                    ON o.repo_ic_id = c.ic_id
                WHERE repo_ic_id IN
                    (SELECT ic_id
                    FROM constellation_lookup
                    WHERE current_ic_id = $1)
                AND timestamp > (NOW() - INTERVAL '1 year')
                GROUP BY date;";

        $result = $this->sdb->query($sql, [$icid]);
        $results = $this->sdb->fetchAll($result);
        return $results;
    }



}
