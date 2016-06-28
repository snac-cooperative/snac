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
     *
     * @param string $accessToken A session token
     *
     * @return boolean true for active, false for inactive or not found.
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
     * Field password is not returned.
     *
     * @param string $userName User name, a unique string, probably the user email
     *
     * @return string[] A list with keys: id, active, username, email, first, last, fullname, avatar, avatar_small, avatar_large
     */
    public function selectUserByEmail($email)
    {
        $result = $this->sdb->query("select id from appuser where email=$1 limit 1",
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
        $result = $this->sdb->query("select id from appuser where username=$1",
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
        $this->sdb->query("insert into privilege_role_link (rid, pid) values ($1, $2)",
                          array($rID, $pID));
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
     * @param string $roleLable A role label
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
     * This is probably called from DBUser populateRole() and nowhere else.
     *
     * If we really always (and only?) call selectRolePrivilegeList() from here, and nowhere else, could we do
     * a join and gain some efficiency? Calling code would have to be modified.
     *
     * @return string[] Return list with keys same as field names. Also includes key 'pid_list' which is a
     * list of related privilege ids.
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
     * @return string[] Associative list with keys 'version', 'ic_id'. Values are integers.
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
            'select aa.version, aa.id as ic_id
            from version_history as aa,
            (select max(bb.version) as version,bb.id from version_history as bb group by bb.id) as cc
            where
            aa.id=cc.id and
            aa.version=cc.version and
            aa.user_id=$1 and
            aa.status = $2 %s %s', $limitStr, $offsetStr);
        $result = $this->sdb->query($queryString,
                                    array($appUserID, $status));
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
            (select max(bb.version) as version,bb.id from version_history as bb group by bb.id) as cc
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
     * select mainID by arkID
     *
     * nrd.ic_id is the constellation id.
     *
     * Do not use nrd.id. The row identifier for nrd is nrd.ic_id. Do not join to table nrd. If you need to
     * join to the constellation use version_history.id. They are both the same, but nrd is a data table,
     * and version_history is the "root" of the constellation.
     *
     * Constellation->getID() gets the ic_id aka constellation id aka nrd.ic_id aka
     * version_history.id.
     *
     * non-constellation->getID() gets the row id. Non-constellation objects get the ic_id from the
     * constellation, and it is not stored in the php objects themselves. I mention this (again) because it
     * (again) caused confusion in the SQL below (now fixed).
     *
     * @param string $arkID The ARK id of a constellation
     *
     * @return integer The constellation ID aka mainID akd ic_id aka version_history.id.
     */
    public function selectMainID($arkID)
    {
        $result = $this->sdb->query(
            'select nrd.ic_id
            from version_history, nrd
            where
            nrd.ark_id=$1
            and version_history.id=nrd.ic_id',
            array($arkID));
        $row = $this->sdb->fetchrow($result);
        return $row['ic_id'];
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
                            'select aa.version, aa.ic_id, aa.id, aa.text, aa.note, aa.uri, aa.language_id, aa.display_name
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
                            'select aa.version, aa.ic_id, aa.id, aa.text, aa.note, aa.uri, aa.language_id, aa.display_name
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
    public function insertSource($vhInfo, $id, $displayName, $text, $note, $uri)
    {
        if (! $id)
        {
            $id = $this->selectID();
        }
        $qq = 'insert_source';
        $this->sdb->prepare($qq,
                            'insert into source
                            (version, ic_id, id, display_name, text, note, uri)
                            values
                            ($1, $2, $3, $4, $5, $6, $7)');
        $this->sdb->execute($qq,
                            array($vhInfo['version'],
                                  $vhInfo['ic_id'],
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
     *
     * @return string[] $vhInfo An assoc list with keys 'version', 'ic_id'.
     */
    public function insertVersionHistory($mainID, $userid, $role, $status, $note)
    {
        if (! $mainID)
        {
            $mainID = $this->selectID();
        }
        $qq = 'insert_version_history';
        // We need version_history.id and version_history.id returned.
        $this->sdb->prepare($qq,
                            'insert into version_history
                            (id, user_id, role_id, status, is_current, note)
                            values
                            ($1, $2, $3, $4, $5, $6)
                            returning version');

        $result = $this->sdb->execute($qq, array($mainID, $userid, $role, $status, true, $note));
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
     * Note: This always gets the max version (most recent) for a given fk_id. Published records (older than
     * an edit) will show the edit (more recent) date, which is a known bug, and on the todo list for a fix.
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
     * Select list of place_link
     *
     * Note: This always gets the max version (most recent) for a given fk_id. Published records (older than
     * an edit) will show the edit (more recent) date, which is a known bug, and on the todo list for a fix.
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
     * Select a snac control meta data record
     *
     * Note: This always gets the max version (most recent) for a given fk_id. Published records (older than
     * an edit) will show the edit (more recent) record, which is a known bug, and on the todo list for a fix.
     *
     * Old comment: Select a meta data record. We expect only one record, and will only return one (or zero).
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
                            (version, id, name_id, nc_value, nc_label, c_order)
                            values
                            ($1, $2, $3, $4, $5, $6)');
        $this->sdb->execute($qq_2,
                            array($vhInfo['version'],
                                  $id,
                                  $nameID,
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
     * Insert a function record
     *
     * The SQL returns the inserted id which is used when inserting a date into table date_range. Function
     * uses the same vocabulary terms as occupation.
     *
     * If the $id arg is null, get a new id. Always return $id.
     *
     * @param string[] $vhInfo associative list with keys: version, ic_id
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
     * @return integer id of this function
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
                            (version, ic_id, id, function_type, vocabulary_source, note, function_id)
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
                            (version, ic_id, id, role, relation_entry_type, href, arcrole, relation_entry,
                            object_xml_wrap, descriptive_note)
                            values
                            ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)');
        /*
         * Combine vhInfo and the remaining args into a big array for execute().
         */
        $execList = array($vhInfo['version'], // 1
                          $vhInfo['ic_id'], // 2
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
                            (select ic_id, max(version) as version from biog_hist where version<=$1 and ic_id=$2 group by ic_id) as bb
                            where not aa.is_deleted and
                            aa.ic_id=bb.ic_id
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
     * Helper for selectOtherID()
     *
     * Mar 1 2016: The comment below is incomplete because we have lots of cases where there could be multiple
     * versions. All queries deal with multiple version by using a subquery. This function is probably
     * redundant.
     *
     * Select flat list of distinct id values meeting the version and ic_id constraint. Specifically a
     * helper function for selectOtherID(). This deals with the possibility that a given otherid.id may
     * have several versions while other otherid.id values are different (and single) versions.
     *
     *
     * @param string[] $vhInfo associative list with keys: version, ic_id
     *
     * @return integer[] Return a list of record id values meeting the version and ic_id constriants.
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
                            version=(select max(version) from otherid where version<=$1 and ic_id=$2)
                            and ic_id=$2');
        /*
         * Always use key names explicitly when going from associative context to flat indexed list context.
         */
        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['ic_id']));
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
     * was all fairly odd, but worked. This code now follows our idiom for ic_id and version constraint via
     * a subquery. As far as I can tell from the full CPF test, this works. I have diff'd the parse and
     * database versions, and the otherRecordID JSON looks correct.
     *
     * select
     * id, version, ic_id, text, uri, type
     * from otherid
     * where
     * version=(select max(version) from otherid where version<=$1)
     * and ic_id=$2 order by id
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
     * Select subjects.
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
     * Insert legalStatus.
     *
     * @param string[] $vhInfo associative list with keys: version, ic_id
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
            /*
             * $rid = $row['id'];
             * $dateList = $this->selectDate($rid, $vhInfo['version']);
             * $row['date'] = array();
             * if (count($dateList)>=1)
             * {
             *     $row['date'] = $dateList[0];
             * }
             * if (count($dateList)>1)
             * {
             *     // TODO Throw an exception or write a log message. Or maybe this will never, ever happen. John
             *     // Prine says: "Stop wishing for bad luck and knocking on wood"
             * }
             */
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
            /*
             * $relationId = $row['id'];
             * $dateList = $this->selectDate($relationId, $vhInfo['version']);
             * $row['date'] = array();
             * if (count($dateList)>=1)
             * {
             *     $row['date'] = $dateList[0];
             * }
             * if (count($dateList)>1)
             * {
             *     //TODO Throw warning or log
             * }
             */
            array_push($all, $row);
        }
        $this->sdb->deallocate($qq);
        return $all;
    }

    /**
     * select related archival resource records
     *
     * Where $vhInfo 'version' and 'ic_id'. Code in DBUtils knows how to turn the return value into a pgp
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
        $qq = 'select_related_resource';
        $this->sdb->prepare($qq,
                            'select
                            aa.id, aa.version, aa.ic_id,
                            aa.relation_entry_type, aa.href, aa.relation_entry, aa.object_xml_wrap, aa.descriptive_note,
                            aa.role,
                            aa.arcrole
                            from related_resource as aa,
                            (select id, max(version) as version from related_resource where version<=$1 and ic_id=$2 group by id) as bb
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
     * Select all function records
     *
     * Constrain on version and ic_id. Code in DBUtils turns the return value into a SNACFunction object.
     *
     * @param string[] $vhInfo associative list with keys: version, ic_id
     *
     * @return string[][] Return a list of list. The inner list has keys: id, version, ic_id, function_type,
     * note, date.
     *
     */
    public function selectFunction($vhInfo)
    {
        $qq = 'select_function';
        $this->sdb->prepare($qq,
                            'select
                            aa.id, aa.version, aa.ic_id, aa.function_type, aa.vocabulary_source, aa.note,
                            aa.function_id
                            from function as aa,
                            (select id, max(version) as version from function where version<=$1 and ic_id=$2 group by id) as bb
                            where not aa.is_deleted and
                            aa.id=bb.id
                            and aa.version=bb.version');

        $result = $this->sdb->execute($qq,
                                      array($vhInfo['version'],
                                            $vhInfo['ic_id']));
        $all = array();
        while ($row = $this->sdb->fetchrow($result))
        {
            /*
             * $dateList = $this->selectDate($row['id'], $vhInfo['version']);
             * $row['date'] = array();
             * if (count($dateList)>=1)
             * {
             *     $row['date'] = $dateList[0];
             * }
             * if (count($dateList)>1)
             * {
             *     // TODO: Throw a warning or log
             * }
             */
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
        $mainID = $row['ic_id'];

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
     * Any term which is a key in $useStartsWith will use a "starts with" type of ilike match. That is, the
     * $query must be at the beginning of the search. The default is for $query to occur anywhere.
     *
     * Add new terms as keys in $useStartsWith as necessary. The choice of value 1 is not arbitrary, and works
     * well in most situations where a hash aka associative list is being used in a control statement.
     *
     * @param string $term The "type" term for what type of vocabulary to search
     *
     * @param string $query The string to search through the vocabulary
     *
     * @param integer $entityTypeID Numeric key related to vocabulary.id where type='entity_type' of one of
     * the three entity types.
     *
     * @return string[][] Returns a list of lists with keys id, value.
     */
    public function searchVocabulary($term, $query, $entityTypeID)
    {
        $useStartsWith = array('script_code' => 1,
                               'language_code' => 1,
                               'gender' => 1,
                               'nationality' => 1);
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
                      'select id,value
                      from vocabulary
                      where type=$1 and value ilike $2 order by value asc limit 100';
            $result = $this->sdb->query($queryStr, array($term, $likeStr));
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
     * @return integer[] List of group ID values.
     */
    public function selectUserIDsFromGroup($groupID)
    {
        $result = $this->sdb->query("select uid from appuser_group_link where gid=$1",
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
     * Insert a new institution and return the institution's id. We aren't using snac_institution.id so we
     * need to check for a record before inserting, just in calse.
     *
     * @param string $ic_id Institution ic_id, aka a constellation ic_id.
     */
    public function insertInstitution($ic_id)
    {
        $result = $this->sdb->query("select * from snac_institution where ic_id=$1",
                                    array($ic_id));

        if (! $this->sdb->fetchrow($result))
        {
            $result = $this->sdb->query("insert into snac_institution (ic_id) values ($1)",
                                        array($ic_id));
        }
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

    /* Not used. See insertInstitution() above */
    /*
     * Update a institution.
     *
     * @param integer $rid Institution row id
     *
     * @param string $ic_id Institution ic_id
     *
     */
    /*
     * public function updateInstitution($iid, $ic_id)
     * {
     *     $result = $this->sdb->query("update snac_institution set ic_id=$1 where id=$2",
     *                                 array($ic_id, $iid));
     * }
     */


}
