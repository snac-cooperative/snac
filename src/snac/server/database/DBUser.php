<?php
  /**
   * High level database abstraction layer for constellations
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
     * Constructor
     *
     * The constructor for the DBUtil class. Set up a database connector, and SQL object. All database layers
     * should behave this way, so DBUtil looks like this (but with some additional bookkeeping vars).
     */
    public function __construct()
    {
        $this->db = new \snac\server\database\DatabaseConnector();

        /*
         * Passing the value of deleted to the SQL constructor is a valiant, but probably pointless, attempt
         * to use the deleted status symbolically, instead of being tightly coupled with the string's
         * value. In reality, I think it only makes matters more complex. If the value changed (very unlikely)
         * the "fix" cwould be a simple search and replace.
         */
        
        $this->sql = new SQL($this->db, 'deleted');
    }

    /**
     * Get the $sql object
     *
     * Test code needs access to the sql object.
     *
     * @return \snac\server\database\SQL SQL object
     *
     */ 
    public function getSQL()
    {
        return $this->sql;
    }

    /**
     * Write the password
     *
     * This overwrites the password. Use this to initially set the password, as well as to set a new password.
     * In a world where logins are managed via OAuth, we may not have or use passwords. In that case,
     * passwords can be ignored.
     *
     *
     * The password must be encrypted.
     *
     * @param \snac\data\User $user
     *
     * @param string $passwd An encrypted password
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
     * Add a user record. Minimal requirements are user id or email (which ever is used to login). 
     * Return the new user object on success.
     *
     * Calling code had better do some sanity checking. This does not set the password. The password is not
     * part of a user object. We could add an optional arg, but for now, call writePassword()
     *
     * @param \snac\data\User $user A user object. 
     */
    public function createUser($user)
    {
        
        $appUserID = $this->sql->insertUser($user->getFirstName(),
                                            $user->getLastName(),
                                            $user->getFullName(),
                                            $user->getAvatar(),
                                            $user->getAvatarSmall(),
                                            $user->getAvatarLarge(),
                                            $user->getEmail());
        $newUser = clone($user);
        $newUser->setUserID($appUserID);
        $this->addDefaultRole($newUser);
        return $newUser;
    }

    /**
     * Update a user record.
     *
     * Write the User fields to the database where appuser.id=$user->getUserID(). There is no sanity checking
     * and no return value. This function probably needs some work.
     *
     * @param \snac\data\User $user A user object.
     */
    public function saveUser($user)
    {
        $this->sql->updateUser($user->getUserID(),
                               $user->getFirstName(),
                               $user->getLastName(),
                               $user->getFullName(),
                               $user->getAvatar(),
                               $user->getAvatarSmall(),
                               $user->getAvatarLarge(),
                               $user->getEmail());
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
     * Return a User object for the user id. Return false on failure.
     *
     * If you only know the email, call findUserID() which takes an email address and returns a user id.
     *
     * After calling this you probably want to call addSession().
     *
     * @param integer $appUserID
     *
     * @return \snac\data\User Returns a user object or false.
     */
    public function readUser($appUserID)
    {
        if ($appUserID)
        {
            $rec = $this->sql->selectUserByID($appUserID);
            $user = new \snac\data\User();
            $user->setUserID($rec['id']);
            $user->setFirstName($rec['first']);
            $user->setLastName($rec['last']);
            $user->setFullName($rec['fullname']);
            $user->setAvatar($rec['avatar']);
            $user->setAvatarSmall($rec['avatar_small']);
            $user->setAvatarLarge($rec['avatar_large']);
            $user->setEmail($rec['email']);
            $user->setRoleList($this->listUserRole($user));
            return $user;
        }
        return false;
    }

    /**
     * Return a user object for the email.
     *
     * Wrapper for readUser() getting a user by email address instead of user id.
     *
     * @param string $email User email address.
     * @return \snac\data\User Returns a user object or false.
     */
    public function readUserByEmail($email)
    {
        $uid = $this->sql->selectUserByEmail($email);
        return $this->readUser($uid);
    }

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
     * List all system roles. The simpliest form would be an associative list with keys: id, label, description.
     *
     * @return \snac\data\Role[] Return list of Role object
     */
    public function roleList()
    {
        $roleArray = $this->sql->selectRole();
        $roleObjList = array();
        foreach($roleArray as $roleHash)
        {
            $roleObj = new \snac\data\Role();
            $roleObj->setID($roleHash['id']);
            $roleObj->setLabel($roleHash['label']);
            $roleObj->setDescription($roleHash['description']);
            array_push($roleObjList, $roleObj);
        }
        return $roleObjList;
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
     * List all the roles a user currently has as an array of Role objects.
     *
     * @param \snac\data\User $user The user
     *
     * @return \snac\data\Role[] A list of Role objects.
     */
    public function listUserRole($user)
    {
        $roleList = $this->sql->selectUserRole($user->getUserID());
        $roleObjList = array();
        foreach($roleList as $roleHash)
            {
                $roleObj = new \snac\data\Role();
                $roleObj->setID($roleHash['id']);
                $roleObj->setLabel($roleHash['label']);
                $roleObj->setDescription($roleHash['description']);
                array_push($roleObjList, $roleObj);
            }
        return $roleObjList;
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
     * @param \snac\data\User $user A user
     * @param \snac\data\Role $role Role object
     */
    public function removeUserRole($user, $role)
    {
        $this->sql->deleteRoleLink($user->getUserID(), $role->getID());
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
     * Check $passwd matches the password stored for snac\data\User. Return true on success.
     *
     * Perhaps this won't be used in a world with OAuth.
     *
     * @param \snac\data\User $user The user object
     *
     * @param string $passwd An encrypted password
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
     * Add a new session token $accessToken with expiration time $expire for $user. Update expiration if
     * $accessToken exists.
     *
     * @param \snac\data\User $user User object
     *
     * @param string $accessToken The session token
     *
     * @param string $expire An expiration timestamp. 
     */
    public function addSession(\snac\data\User $user)
    {
        $currentToken = $user->getToken();
        $accessToken = $currentToken['access_token'];
        $expires = $currentToken['expires'];
        $rec = $this->sql->selectSession($accessToken);
        if ($rec['appuser_fk'])
        {
            $this->sql->updateSession($accessToken, $expires);
        }
        else
        {
            // For now, appuser.id is getUserID()
            $this->sql->insertSession($user->getUserID(), $accessToken, $expires);
        }
    }

    /**
     * Check session exists
     *
     * Find out if a session exists and if that session is associated with $user, and has not expired.
     *
     * @param \snac\data\User User object
     * @return boolean True on success, else false.
     */
    public function sessionExists($user)
    {
        $currentToken = $user->getToken();
        $accessToken = $currentToken['access_token'];
        $rec = $this->sql->selectSession($accessToken);
        // printf("\ndbuser appuser_fk: %s appUserID: %s", $rec['appuser_fk'], $user->getUserID() );
        if ($rec['appuser_fk'])
        {
            return true;
        }
        return false;
    }

    /**
     * Check that a session is active
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
    public function checkSessionActive(\snac\data\User $user)
    {
        $currentToken = $user->getToken();
        $accessToken = $currentToken['access_token'];
        $newUser = $this->readUserByEmail($user->getEmail());
        if ($newUser)
        {
            $newUser->setToken($user->getToken());
        }
        if (! $newUser)
        {
            $newUser = $this->createUser($user);
            $this->addSession($newUser); // adds or updates expires for existing session
            $newUser->setToken($currentToken);
            return $newUser;

        }
        // Now we have a good $newUser with the original $user token. Either the user was created or read from
        // the db.
        else if (! $this->sessionExists($newUser))
        {
            $this->addSession($newUser); // adds or updates expires for existing session
            $newUser->setToken($currentToken);
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
            return $newUser;
        }
        return false;
    }

    /**
     * Remove a session
     *
     * Assume that tokens are unique, which is important. Delete all sessions with the token no matter what user has that token.
     *
     * This showed up during testing where the appUserID and token got snarled.
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
    public function clearAllSessions(\snac\data\User $user)
    {
        $this->sql->deleteAllSession($user->getUserID());
        return true;
    }
}
