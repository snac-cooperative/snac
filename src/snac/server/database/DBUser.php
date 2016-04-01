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
     * The constructor for the DBUtil class.
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
     * Add a user record. Minimal requirements are user id or email (which ever is used to login). 
     * Return the new user object on success.
     *
     * Calling code had better do some sanity checking.
     */
    public function createUser($user)
    {
        
        $id = insertUser($user->getFirstName(),
                         $user->getLastName()
                         $user->getFullName(),
                         $user->getAvatar(),
                         $user->getAvatarSmall(),
                         $user->getAvatarLarge(),
                         $user->getEmail());
        $newUser = clone($user);
        $newUser->setUserID($id);
        return $newUser;
    }

    /**
     * Update a user record. Verify that read-only fields match, overwrite everthing else with values from the
     * User object. Return true for success.
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
        return true;
    }
    

    /**
     * Get the user id if you only know the email address. We must be assuming the email addresses are unique.
     */
    public function findUserID($email)
    {
        $uid = selectUserByEmail($email);
        if ($uid)
        {
            return $uid;
        }
        return false;
    }


    /**
     * Return a User object for the user id. Return false on failure.
     *
     * If you only know the email, call findUserID() which takes an email address and returns a user id.
     *
     * After calling this you probably want to call addSession().
     */
    public function readUser($userid)
    {
        if ($userid)
        {
            $rec = selectUserByID($userid);
            $user = new \snac\data\User();
            $user->setUserID($rec['id']);
            $user->setFirstName($rec['first']);
            $user->setLastName($rec['last']);
            $user->setFullName($rec['fullname']);
            $user->setAvatar($rec['avatar']);
            $user->setAvatarSmall($rec['avatar_small']);
            $user->setAvatarLarge($rec['avatar_large']);
            $user->setEmail($rec['email']);
            return $user;
        }
        return false;
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
     * Add a role to the User via table appuser_role_link. Return true on success.
     *
     * @param \snac\data\User $user A user
     * @param string[] $newRole is associative list with keys id, label, description. We really only care about id.
     */
    public function addUserRole($user, $newRole)
    {
        $this->sql->insertRoleLink($user->getUserID(), $newRole['id']);
        return true;
    }


    /**
     * Remove a role from the User via table appuser_role_link.
     *
     * @param \snac\data\User $user A user
     * @param string[] $role is associative list with keys id, label, description. We really only care about id.
     */
    public function removeUserRole($user, $role)
    {
        $this->sql->deleteRoleLink($user->getUserID(), $role['id']);
        return true;
    }

    /**
     * List all system roles. The simpliest form would be an associative list with keys: id, label, description.
     *
     * @return string[][] Return list of list with keys: id, label, description.
     */
    public function roleList()
    {
        return $this->sql->selectRole();
    }

    /**
     * Create a new role with $label and $description. Return true on success.
     */
    public function createRole($label, $description)
    {
        return $this->sql->insertRole($label, $description);
    }

    /**
     * Check $passwd matches the password stored for snac\data\User. Return true on success.
     */
    public function checkPassword($user, $passwd)
    {
        return selectMatchingPassword($user->getUserID, $passwd);
    }

    /**
     * Add a new session token $accessToken with expiration time $expire for $user. Update if $accessToken
     * exists.
     */
    public function addSession(snac\data\User $user, $accessToken, $expire)
    {
        if ($this->sql->sessionExists($accessToken))
        {
            $this->sql->updateSession($accessToken, $expire);
        }
        else
        {
            // For now, appuser.id is getUserID()
            $this->sql->insertSession($user->getUserID(), $accessToken, $expire);
        }
        $user->setToken($accessToken);
    }

    /**
     * Check that a session is active (not expired) for $user and $accessToken. Time is assumed to be
     * "now". Return true for success (session is active now).
     *
     * @param snac\data\User $user 
     */
    public function checkSessionActive(snac\data\User $user, $accessToken)
    {
        return selectActive($user-getUserID(), $accessToken);
    }

    /**
     * Delete all session records for $user.
     */
    public function clearAllSessions(snac\data\User $user)
    {
        return deleteAllSessions($user->getUserID);
    }
}
