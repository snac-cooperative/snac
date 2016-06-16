<?php
  /**
   * High level database abstraction layer for constellations
   *
   * License:
   *
   *
   * @author Tom Laudeman <twl8n@virginia.edu>
   * @copyright 2015 the Rector and Visitors of the University of Virginia, and
   *            the Regents of the University of California
   * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
   */

namespace snac\server\database;

/**
 * High level API for storing user account and session data.
 *
 * This manages user, role, and session data. User data is in table appuser. Roles are in table role. Table
 * appuser_role_link relates roles to users. Table session has session data, and joined where
 * appuser.id=session.appuser_fk.
 *
 */

class DBUser
{
    /**
     * SQL object
     *
     * @var \snac\server\database\SQL low-level SQL class
     */
    private $sql = null;

    /**
     * Database connector object
     *
     * @var \snac\server\database\DatabaseConnector object.
     */
    private $db = null;

    /**
     * Database Utility object
     *
     * We need to be able to read a constellation, so we need a DBUtil object.
     *
     * @var snac\server\database\DBUtil object.
     */
    private $dbutil;

    /** 
     * Constructor
     *
     * The constructor for the class. Set up a database connector, and SQL object. All database layers
     * should behave this way, so DBUtil looks like this (but with some additional bookkeeping vars).
     */
    public function __construct()
    {
        $this->db = new \snac\server\database\DatabaseConnector();
        $this->dbutil = new \snac\server\database\DBUtil();

        /*
         * Passing the value of deleted to the SQL constructor is a valiant, but probably pointless, attempt
         * to use the deleted status symbolically, instead of being tightly coupled with the string's
         * value. In reality, I think it only makes matters more complex. If the value changed (very unlikely)
         * the "fix" cwould be a simple search and replace.
         */
        
        $this->sql = new SQL($this->db, 'deleted');
    }


    /**
     * Really delete a user
     *
     * Used for testing only. Normally, users are inactivated.
     *
     * Delete the user, and delete user role links.
     *
     * @param \snac\data\User $user
     */
    public function eraseUser($user)
    {
        $this->sql->deleteUser($user->getUserID());
    }

    /**
     * Really delete a role from the db
     *
     * Deleting a role should be rare. To make this a little safer deleteRole() only deletes if the role is
     * not in use. If the role exists and is not linked to any appuser, it will be deleted. Otherwise, it is
     * not deleted.
     *
     * @param integer $roleID An role id
     */
    public function eraseRoleByID($roleID)
    {
        $this->sql->deleteRole($roleID);
    }

    /**
     * Really delete a role from the db
     *
     * Deleting a role should be rare. To make this a little safer deleteRole() only deletes if the role is
     * not in use. If the role exists and is not linked to any appuser, it will be deleted. Otherwise, it is
     * not deleted.
     *
     * @param \snac\data\Role A Role object
     */
    public function eraseRole($roleObj)
    {
        $this->sql->deleteRole($roleObj->getID());
    }


    /**
     * Really delete a privilege from the db
     *
     * Deleting a privilege should be rare. To make this a little safer deletePrivilege() only deletes if the
     * privilege is not in use. If the privilege exists and is not linked to any appuser, it will be
     * deleted. Otherwise, it is not deleted.
     *
     * @param \snac\data\Privilege A Privilege object
     */
    public function erasePrivilege($privilegeObj)
    {
        $this->sql->deletePrivilege($privilegeObj->getID());
    }

        

    /**
     * Write the password
     *
     * This overwrites the password. Use this to initially set the password, as well as to set a new password.
     * In a world where logins are managed via OAuth, we may not have or use passwords. In that case,
     * passwords can be ignored.
     *
     * The password must be hashed.
     *
     * @param \snac\data\User $user A user object
     *
     * @param string $passwd A hashed password
     *
     * @return boolean True on success, false for any type of failure.
     *
     */
    public function writePassword($user, $passwd)
    {
        if (! $user || ! $user->getUserID())
        {
            return false;
        }
        $this->sql->updatePassword($user->getUserID(),
                                   $passwd);
        return true;
    }

    /**
     * Add a user record.
     *
     * Minimal requirements are username which must be unique. We plan to use email as username, but calling
     * code creates the User object, so this code will just use what getUserName() returns.
     *
     * Param $user is not modified, although it is cloned and the clone has the userID added, and the clone is
     * returned on success.
     *
     * Affiliation is a Constellation, but only has the minimal summary fields. We save the Constellation ID
     * aka ic_id aka getID() to the database.
     * 
     * Return the new user object on success.
     *
     * Calling code had better do some sanity checking. This does not set the password. The password is not
     * part of a user object. We could add an optional arg, but for now, call writePassword()
     *
     * @param \snac\data\User $user A user object.
     *
     * @return \snac\data\User Return the cloned User with the userID set (and all other fields populated)
     */
    public function createUser($user)
    {
        if (! $this->userExists($user))
        {
            $appUserID = $this->sql->insertUser($user->getUserName(),
                                                $user->getFirstName(),
                                                $user->getLastName(),
                                                $user->getFullName(),
                                                $user->getAvatar(),
                                                $user->getAvatarSmall(),
                                                $user->getAvatarLarge(),
                                                $user->getEmail(),
                                                $user->getWorkEmail(),
                                                $user->getWorkPhone(),
                                                $user->getAffiliation()==null?null:$user->getAffiliation()->getID(),
                                                $user->getPreferredRules());
            $newUser = clone($user);
            $newUser->setUserID($appUserID);
            $this->addDefaultRole($newUser);
            return $newUser;
        }
        return false;
    }

    /**
     * Does a user exist
     *
     * Find out if a user exists. True for success, else false.
     *
     * @param \snac\data\User $user A User object, must have user ID or username set.
     * 
     * @return boolean True for exists, else false.
     */
    public function userExists($user)
    {
        if ($record = $this->sql->selectUserByID($user->getUserID()))
        {
            return true;
        }
        else if ($uid = $this->sql->selectUserByUserName($user->getUserName()))
        {
            return true;
        }
        return false;
    }

    /**
     * Update a user record.
     *
     * Write the User fields to the database where appuser.id=$user->getUserID(). By checking the returned id
     * inside updateUser() we at least know a record was updated in the database.
     *
     * @param \snac\data\User $user A user object.
     * @return boolean True on success, else false.
     */
    public function saveUser($user)
    {
        return $this->sql->updateUser($user->getUserID(),
                                      $user->getFirstName(),
                                      $user->getLastName(),
                                      $user->getFullName(),
                                      $user->getAvatar(),
                                      $user->getAvatarSmall(),
                                      $user->getAvatarLarge(),
                                      $user->getEmail(),
                                      $user->getUserName(),
                                      $user->getWorkEmail(),
                                      $user->getWorkPhone(),
                                      $user->getAffiliation()==null?null:$user->getAffiliation()->getID(),
                                      $user->getPreferredRules());
    }
    

    /**
     * Get the user id if you only know the email address. We must be assuming the email addresses are unique.
     *
     * Might be called readUserIDByEmail. "Find" is not a word we use anywhere else in function names
     * here. There is an attempt to follow some predictable convention for function names.
     * 
     * @param string $email
     *
     * @return integer $uid Return an integer user id or false.
     */
    public function findUserID($email)
    {
        $appUserID = $this->sql->selectUserByEmail($email);
        if ($appUserID)
        {
            return $appUserID;
        }
        return false;
    }
    
    /**
     * Return a User object
     *
     * Get a user record from the db, create a User object, and return it. Return false on failure.
     *
     * Robbie's commentary: readUser() is a little more forgiving [than userExists()] and reads the user out
     * of the database.  It doesn’t call userExists(), but tries to do something different.  It tries to read
     * the user by id (first), then if there is no id in the User object, it tries username (second).  Those
     * are the only two options that guarantee a unique user.  If they’re not set in the User object, then it
     * looks for a user with the given email address.  This is NOT a check of existence, since that would
     * require uniqueness.  This is a "get me the first user you find that has email address..."
     *
     * Important to copy the session token from arg $user into $newUser that has come back from the database.
     *
     * @param \snac\data\User A user object with a good value for getUserID() or getUserName().
     *
     * @return \snac\data\User Returns a user object or false.
     */
    public function readUser($user)
    {
        $newUser = null;
        if ($newUserRec = $this->sql->selectUserByID($user->getUserID()))
        {
            $newUser = $this->populateUser($newUserRec);
        }
        else if ($newUserRec = $this->sql->selectUserByUserName($user->getUserName()))
        {
            $newUser = $this->populateUser($newUserRec);
        }
        else if ($newUserRec = $this->sql->selectUserByEmail($user->getEmail()))
        {
            // Warning: the returned user may not be the only user with the given email address.
            $newUser = $this->populateUser($newUserRec);
        }
        if ($newUser)
        {
            $newUser->setToken($user->getToken());
            return $newUser;
        }
        return false;
    }


    /**
     * Return a User object for the user id.
     *
     * Get a user record from the db, create a User object, and return it. Return false on failure.
     *
     * After calling this you probably want to call addSession().
     *
     * @param string[] $record A list with keys: id, active, username, email, first, last, fullname, avatar, avatar_small, avatar_large
     *
     * @return \snac\data\User Returns a user object or false.
     */
    private function populateUser($record)
    {
        $user = new \snac\data\User();
        $user->setUserID($record['id']);
        $user->setUserName($record['username']);
        $user->setFirstName($record['first']);
        $user->setLastName($record['last']);
        $user->setFullName($record['fullname']);
        $user->setAvatar($record['avatar']);
        $user->setAvatarSmall($record['avatar_small']);
        $user->setAvatarLarge($record['avatar_large']);
        $user->setEmail($record['email']);
        $user->setRoleList($this->listUserRole($user));
        $user->setWorkEmail($record['work_email']);
        $user->setWorkPhone($record['work_phone']);

        /* 
         * readConstellation($mainID, $version=null, $summary=false)
         * 
         * The ic_id aka $mainID is the affiliation field from the db. We pass null for version in order to
         * get the most recent. We pass true for $summary so that we get a summary constellation which only
         * has ic_id, entityType, ARK, Name entry.
         */
        $user->setAffiliation($this->dbutil->readConstellation($record['affiliation'], null, true));

        $user->setPreferredRules($record['preferred_rules']);
        return $user;
    }

    /*
     * This function removed.
     * 
     * Return a user object for the email.
     *
     * Wrapper for readUser() getting a user by email address instead of user id.
     *
     * @param string $email User email address.
     * @return \snac\data\User Returns a user object or false.
     */
    /* 
     * public function readUserByEmail($email)
     * {
     *     $uid = $this->sql->selectUserByEmail($email);
     *     return $this->readUser($uid);
     * }
     */

    /**
     * Disable log in to this account. Update table appuser.active to false. Return true on success.
     *
     * Should we also munge their password so login becomes impossible? Perhaps not.
     *
     * @param \snac\data\User $user The user to disable
     * @return boolean Return true on success
     */
    public function disableUser($user)
    {
        $this->sql->updateActive($user->getUserID(), $this->db->boolToPg(false));
        return true;
    }


    /**
     * List all system privileges.
     *
     * Create a list of Privilege objects of all privileges.
     *
     * @return \snac\data\Privilege[] Return list of Privilege objects
     */
    public function privilegeList()
    {
        $pidList = $this->sql->selectAllPrivilege();
        $privilegeObjList = array();
        foreach($pidList as $pid)
        {
            $privilegeObj = $this->populatePrivilege($pid);
            array_push($privilegeObjList, $privilegeObj);
        }
        return $privilegeObjList;
    }



    /**
     * List all system roles.
     *
     * Create a list of Role objects of all roles.
     *
     * @return \snac\data\Role[] Return list of Role object, each of which contains a list of Privilege
     * objects.
     */
    public function roleList()
    {
        $roleIDList = $this->sql->selectAllRole();
        $roleObjList = array();
        foreach($roleIDList as $rid)
        {
            $roleObj = $this->populateRole($rid);
            array_push($roleObjList, $roleObj);
        }
        return $roleObjList;
    }

    /**
     * Populate a role object
     *
     * Select role data from the database, and populate a role object.
     *
     * @param integer $rid Role ID value.
     *
     * @return \snac\data\Role A Role object.
     *
     */
    public function populateRole($rid)
    {
        $rid = $this->sql->selectRole($rid);
        $roleObj = new \snac\data\Role();
        $roleObj->setID($row['id']);
        $roleObj->setLabel($row['label']);
        $roleObj->setDescription($row['description']);
        foreach($row['pid_list'] as $pid)
        {
            $roleObj->addPrivilege(populatePrivilege($pid));
        }
        return $roleObj;
    }

    /**
     * Populate a privilege object
     *
     * Return a privilege object based on the $pid ID value
     *
     * @return \snac\data\Privilege A privilege object.
     */ 
    public function populatePrivilege($pid)
    {
        $row = $this->sql->selectPrivilege($pid);
        $privilegeObj = new \snac\data\Privilege();
        $privilegeObj->setID($row['id']);
        $privilegeObj->setLabel($row['label']);
        $privilegeObj->setDescription($row['description']);
        return $privilegeObj;
    }

    /**
     * Add a role to the User via table appuser_role_link. Return true on success.
     *
     * You might be searching for addrole, add role adding a role adding role.
     *
     * After adding the role, set the users's role list by getting the list from the db.
     * 
     * @param \snac\data\User $user A user
     * @param \snac\data\Role $newRole is associative list with keys id, label, description. We really only care about id.
     */
    public function addUserRole($user, $newRole)
    {
        $this->sql->insertRoleLink($user->getUserID(), $newRole->getID());
        $user->setRoleList($this->listUserRole($user));
        return true;
    }

    /**
     * Add a privilege to a role
     *
     * The privilege must exist before calling this. Use createPrivilege(). You might be searching for
     * addprivilege, add privilege adding a privilege adding privilege.
     *
     * @param \snac\data\Role $role The role.
     * @param \snac\data\Privilege $privilege is a Role object. Role object has identical fields to privilege.
     */
    public function addPrivilegeToRole($role, $privilege)
    {
        $this->sql->insertPrivilegeRoleLink($role->getID(), $privilege->getID());
        return true;
    }

    /**
     * Does use have a privilege
     *
     * Returns true if the privilege exists. Build a list of all the privs, then test the list for key
     * existence.
     * 
     * @return boolean True if the $user has $privilege in any of the roles, return false otherwise.
     */ 
    public function hasPrivilege($user, $privilege)
    {
        $allPrivs = array(); // assoc list
        foreach($user->getRoleList as $userRole)
        {
            foreach($userRole->getPrivilegeList() as $priv)
            {
                $allPrivs[$priv->getID()] = 1;
            }
        }
        return isset($allPrivs[$privilege->getID()]);
    }

    /**
     * Does user have a privilege, by label
     *
     * Returns true if the privilege with $label exists. Build a list of all the privilege labels, then test
     * the list for key existence.
     * 
     * @return boolean True if the $user has $privilege in any of the roles, return false otherwise.
     */ 
    public function hasPrivilegeByLabel($user, $label)
    {
        $allPrivs = array(); // assoc list
        foreach($user->getRoleList as $userRole)
        {
            foreach($userRole->getPrivilegeList() as $priv)
            {
                $allPrivs[$priv->getLabel()] = 1;
            }
        }
        return isset($allPrivs[$label]);
    }


    /**
     * Add default role(s)
     *
     * Current there are no default roles. If we ever have default role(s) add them here. You might be
     * searching for addrole, add role adding a role adding role, addUserRole
     *
     * After adding the role, set the users's role list by getting the list from the db.
     *
     * When we have more default roles, just add additional insertRoleByLabel() calls.
     * 
     * @param \snac\data\User $user A user
     * @return boolean Return true on success, else false.
     */
    public function addDefaultRole($user)
    {
        return true;
        /* 
         * $result = $this->sql->insertRoleByLabel($user->getUserID(), 'Public HRT');
         * $user->setRoleList($this->listUserRole($user));
         * return $result;
         */
    }


    /**
     * List roles for a user.
     *
     * List all the roles for a user, as an array of Role objects.
     *
     * @param \snac\data\User $user The user
     *
     * @return \snac\data\Role[] A list of Role objects.
     */
    public function listUserRole($user)
    {
        $ridList = $this->sql->selectUserRole($user->getUserID());
        $roleObjList = array();
        foreach($ridList as $rid)
            {
                $roleObj = $this->populateRole($rid);
                array_push($roleObjList, $roleObj);
            }
        return $roleObjList;
    }

    /**
     * Check if a user has a role
     *
     * The role may be partial, but must have and id that is getID() must return a value.
     * 
     * @param \snac\data\User $user A user
     *
     * @param \snac\data\Role $role A role, may be incomplete, but must at least have an id.
     *
     * @return boolean True if user has the role, else false.
     */
    public function hasRole($user, $role)
    {
        foreach($user->getRoleList() as $userRole)
        {
            if ($userRole->getID() == $role->getID())
            {
                return true;
            }
        }
        return false;
    }

       
    /**
     * Check for role by label
     * 
     * @param \snac\data\User User object
     * @param string $label A label for a role
     * @return \snac\data\Role A role or null. Or false?
     */
    public function checkRoleByLabel($user, $label)
    {
        foreach($user->getRoleList() as $role)
        {
            if ($role->getLabel() == $label)
            {
                return $role;
            }
        }
        return false;
    }

    /**
     * Remove a role from the User via table appuser_role_link.
     *
     * After removing the role, refresh the User role list by reading it back from the database.
     *
     * @param \snac\data\User $user A user
     * @param \snac\data\Role $role Role object
     */
    public function removeUserRole($user, $role)
    {
        $this->sql->deleteRoleLink($user->getUserID(), $role->getID());
        $user->setRoleList($this->listUserRole($user));
        return true;
    }

    /**
     * Remove a privilege from a role
     *
     * @param \snac\data\Role $role A role
     * @param \snac\data\Privilege $privilege Privilege is a Role object
     */
    public function removePrivilegeFromRole($role, $privilege)
    {
        $this->sql->deletePrivilegeRoleLink($role->getID(), $privilege->getID());
        return true;
    }


    /**
     * Create a new role with $label and $description. Return true on success.
     *
     * @param string $label The label of a role, aka short name
     *
     * @param string $description The description of the role, a phrase or sentence.
     *
     * @return \snac\data\Role Role object.
     */
    public function createRole($label, $description)
    {
        $roleHash = $this->sql->insertRole($label, $description);
        $roleObj = new \snac\data\Role();
        $roleObj->setID($roleHash['id']);
        $roleObj->setLabel($roleHash['label']);
        $roleObj->setDescription($roleHash['description']);
        return $roleObj;
    }

    /**
     * Create a new privilege
     *
     * New privilege with $label and $description. Return true on success.
     *
     * @param string $label The label of a privilege, aka short name
     *
     * @param string $description The description of the privilege, a phrase or sentence.
     *
     * @return \snac\data\Privilege Privilege object. Roles and privileges are identical objects.
     */
    public function createPrivilege($label, $description)
    {
        $privilegeHash = $this->sql->insertPrivilege($label, $description);
        $privilegeObj = new \snac\data\Privilege();
        $privilegeObj->setID($privilegeHash['id']);
        $privilegeObj->setLabel($privilegeHash['label']);
        $privilegeObj->setDescription($privilegeHash['description']);
        return $privilegeObj;
    }


    /**
     * Check $passwd matches the password stored for \snac\data\User. Return true on success.
     *
     * Perhaps this won't be used in a world with OAuth.
     *
     * @param \snac\data\User $user The user object
     *
     * @param string $passwd A hashed password
     *
     * @return boolean Returns true if the password matches the database, false for any failure.
     */
    public function checkPassword($user, $passwd)
    {
        $appUserID = $this->sql->selectMatchingPassword($user->getUserID(), $passwd);
        if ($appUserID >= 1)
        {
            return true;
        }
        return false;
    }

    /**
     * Extend a session expires
     *
     * @param \snac\data\User $user A User
     *
     * @param integer $extend optional Optional number of seconds to extend the expires time. Defaults to
     * 3600.
     *
     * @return boolean True on success, else false.
     */ 
    public function sessionExtend(&$user, $extend=3600)
    {
        $success = $this->sql->updateByExtendingSession($user->getUserID(),
                                                    $user->getToken()['access_token'],
                                                    $extend);
        if ($success === false)
            return false;

        // Update the token for the user object
        $user->setToken(array(
            "access_token" => $user->getToken()["access_token"],
            "expires" => $this->sql->selectSession($user->getUserID(),
                                                   $user->getToken()["access_token"])));
        return true;
    }

    /**
     * Add a new session to the database
     *
     * Add a new session to the database using the token expiration from getToken(). Do nothing if the session
     * already exists. Return true if the session already exists.
     *
     * @param \snac\data\User $user User object
     *
     * @return boolean True if session add was successful, else false
     */
    public function addSession($user)
    {
        if (! $this->sessionExists($user))
        {
            $currentToken = $user->getToken();
            $accessToken = $currentToken['access_token'];
            $expires = $currentToken['expires'];
            return $this->sql->insertSession($user->getUserID(), $accessToken, $expires);
        }
        return true; // Nothing added, but also no errors, so true is the return value.
    }

    /**
     * Check session exists
     *
     * Find out if a session exists for this User with the token getToken(). Expiration time is ignored. If
     * somehow a session token has been applied to 2 users, this will still only return the token for $user,
     * and that is good.
     *
     * @param \snac\data\User User object
     * @return boolean True on success, else false.
     */
    public function sessionExists($user)
    {
        $currentToken = $user->getToken();
        $accessToken = $currentToken['access_token'];
        $rec = $this->sql->selectSession($user->getUserID(), $accessToken);
        if ($rec && array_key_exists('appuser_fk', $rec))
        {
            return true;
        }
        return false;
    }

    /**
     * Is a session active
     *
     * If a session exists for this user and is not expired, return true, else false.
     *
     * @param \snac\data\User $user
     *
     * @return boolean True for active, not expired, for this user. False otherwise.
     */ 
    public function sessionActive($user)
    {
        if ($this->sessionExists($user))
        {
            return $this->sql->selectActive($user->getUserID(), $user->getToken()['access_token']);
        }
        return false;
    }

    /**
     * Check that a session is active, with side effects.
     *
     * You probably want to use sessionExists(), sessionActive() and readUser(). This function may be
     * deprecated.
     *
     * Check that we have a non-expired session for $user and with token getToken(). Time is assumed to be
     * "now" UTC. Return the User on success, false otherwise. If the user does not exist, a DB record is
     * created in appuser. If the session does not exist, a session is created in table session. If the
     * session is active, 'expires' is updated. If the session has expired, it is deleted, and the User object
     * token is cleared.
     *
     * This checks that a session is active and associated with the $user. In theory is it possible to
     * ask if a session is active, without knowing the user. In fact, a session could be check as active, and
     * could return the user id.
     *
     * It is important to read or create a user at the top of the function. Everything after depends on a
     * valid User from the SNAC appuser database table.
     *
     * Features: auto-create unknown user, auto-create unknown session, delete expired session. 
     *
     * @param \snac\data\User $user User object
     *
     * @return \snac\data\User when successful or return false on failure.
     */
    public function checkSessionActive($user)
    {
        $currentToken = $user->getToken();
        $accessToken = $currentToken['access_token'];
        
        // Try to get this user from the database
        $newUser = $this->readUser($user);
        
        if ($newUser === false) {
            // If the user doesn't exist, then create them
            $newUser = $this->createUser($user);
            
        }
        $newUser->setToken($user->getToken());
        
        // Now we have a good $newUser with the original $user token. Either the user was created or read from
        // the db.
        
        // Create the session if it doesn't exist, and then return (no need to check that the session is active
        if (! $this->sessionExists($newUser))
        {
            $this->addSession($newUser); // adds a new session
            return $newUser;
        }

        /*
         * Do this last, since if this fails we need to essentially logoff the user.
         *
         * If the session exists, but doesn't belong to this user, selectActive() will fail.
         * If the sesion has expired, selectActive() will fail.
         */ 
        if (! $this->sql->selectActive($newUser->getUserID(), $accessToken))
        {
            /*
             * Shouldn't this call removeSession() instead of a low-level SQL function.
             */  
            $this->sql->deleteSession($newUser->getToken()['access_token']);
            $newUser->setToken(array('access_token' => '', 'expires' => 0));
            
            // The user isn't logged in, so we should not let validate their session
            return false;
        }
        
        // The user is set, their session is active
        return $newUser;
    }

    /**
     * Remove a session
     *
     * Assume that tokens are unique, which is important. Delete all sessions with the token no matter what user has that token.
     *
     * This might benefit from additional sanity checking, although that would change the meaning and use of this function.
     *
     * @param \snac\data\User $user
     *
     */
    public function removeSession($user)
    {
        $this->sql->deleteSession($user->getToken()['access_token']);
    }


    /**
     * Delete all session records for $user.
     *
     * Unclear when this is used, but it will logout from all sessions.
     *
     * @param \snac\data\User $user A user object
     *
     * @return boolean true Returns true or an exception will be thrown by low level db code if something fails.
     */
    public function clearAllSessions($user)
    {
        $this->sql->deleteAllSession($user->getUserID());
        return true;
    }

    /**
     * List all users
     *
     * Return a list of all users as user objects. You might be searching for selectallusers selectusers select all users.
     *
     * @param \snac\data\Constellation $affiliation optional
     * 
     * @return \snac\data\User[] $allUserList
     */ 
    public function listAllUsers($affiliation=null)
    {
        $idList = $this->sql->selectAllUserIDList($affiliation==null?null:$affiliation->getID());
        $allUserList = array();
        foreach($idList as $userID)
        {
            $newUserRec = $this->sql->selectUserByID($userID);
            $newUser = $this->populateUser($newUserRec);
            array_push($allUserList, $newUser);
        }
        return $allUserList;
    }
}
