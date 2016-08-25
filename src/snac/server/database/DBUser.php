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
 * Functions that return lists: listUsers, listRoles, listGroups, listPrivileges, listInstitutions
 *
 * Functions that return constrained lists: listUserRoles, listUsersInGroup, listGroupsForUser
 *
 * Functions that add/remove relations: addRoleToUser, addPrivilegeToRole,
 * addSession, removeSession, addUserToGroup, removeUserFromGroup, removeUserRole (better name:
 * removeRoleFromUser?), removePrivilegeFromRole
 *
 * Functions that read: readUser, readRole, readGroup
 *
 * Functions that create/update/write: writePassword, createUser, saveUser, writeRole, writePrivilege,
 * writeGroup, writeInstitution
 *
 * Functions that delete: eraseRole, eraseRoleByID, erasePrivilege, eraseGroup, eraseInstitution
 *
 * Functions that check/has: userExists, hasPrivilege, hasPrivilegeByLabel, hasRole, checkRoleByLabel,
 * sessionExists, sessionActive, checkSessionActive, checkPassword
 *
 * Other functions: findUserID, private populateUser, disableUser, enableUser, private populatePrivilege,
 * sessionExtend, private populateGroup, clearAllSessions
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
     * @var \snac\server\database\DBUtil object.
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
     * Remove user role links as well.
     *
     * Delete the user, and delete user role links.
     *
     * @param \snac\data\User $user
     */
    public function eraseUser($user)
    {
        $roleList = $this->listUserRoles($user);
        foreach($roleList as $role)
        {

        }
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
     * user->active does not default to true. In retrospect this seems like a dubious feature that should be
     * discussed. Currently, if you want your new user to be active then $user->setUserActive(true) before
     * calling createUser().
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
                                                $user->getPreferredRules(),
                                                $user->getUserActive());
            $newUser = clone($user);
            $newUser->setUserID($appUserID);
            // $this->addDefaultRole($newUser);
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
     * Users do not have a groupList. They did, and it was removed, so you won't find any reference to it,
     * except this comment.
     *
     * @param \snac\data\User $user A user object.
     * @param boolean $saveRole optionsl If true, save the roles of this user
     * @return boolean True on success, else false.
     */
    public function saveUser($user, $saveRole=false)
    {
        $retVal = $this->sql->updateUser($user->getUserID(),
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
                                         $user->getPreferredRules(),
                                         $user->getUserActive());
        /*
         * Admins can run this on behalf of another user. It requires authorization to save roles and save
         * groups.
         */
        if ($saveRole) {
            /*
             * - remove all old roles, removeUserRole()?
             * - add back all current roles
             */
            $rolesToDelete = $this->listUserRoles($user);
            $rolesToAdd = $user->getRoleList();
            foreach($rolesToDelete as $role) {
                // This method will munge the list of roles attached to the user (Side-effect-full)
                $this->removeUserRole($user, $role);
            }
            foreach($rolesToAdd as $role) {
                // This method will munge the list of roles attached to the user (Side-effect-full)
                $this->addRoleToUser($user, $role);
            }
        }
        return $retVal;
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
        /*
         * In fact, selectUserByEmail() returns an entire user record from the database as an associative
         * list.
         */
        $appUserID = $this->sql->selectUserByEmail($email);
        if (isset($appUserID['id']))
        {
            return $appUserID['id'];
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
     * @return \snac\data\User|boolean Returns a user object or false.
     */
    public function readUser($user)
    {
        $newUser = null;
        if ($newUserRec = $this->sql->selectUserByID($user->getUserID()))
        {
            $newUser = $this->populateUser($newUserRec, $user);
        }
        else if ($newUserRec = $this->sql->selectUserByUserName($user->getUserName()))
        {
            $newUser = $this->populateUser($newUserRec, $user);
        }
        else if ($newUserRec = $this->sql->selectUserByEmail($user->getEmail()))
        {
            // Warning: the returned user may not be the only user with the given email address.
            $newUser = $this->populateUser($newUserRec, $user);
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
     * @param \snac\data\User $userQuery optional User object with query data to read the user out of the database
     *
     * @return \snac\data\User Returns a user object or false.
     */
    private function populateUser($record, $userQuery=null)
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
        $user->setWorkEmail($record['work_email']);
        $user->setWorkPhone($record['work_phone']);
        $user->setUserActive($record['active']);
        /*
         * We may need the functions listUserRoles() and listGroupsForUser() for uses outside of simply building
         * user objects, although no other uses are possible as long as these functions take $user as an
         * argument.
         *
         * This is different from how things are done in DBUtil, for good reason. This code may be more legible.
         */
        $user->setRoleList($this->listUserRoles($user));

        /*
         * readConstellation($mainID, $version=null, $summary=false)
         *
         * The ic_id aka $mainID is the affiliation field from the db. We pass null for version in order to
         * get the most recent. We pass true for $summary so that we get a summary constellation which only
         * has ic_id, entityType, ARK, Name entry.
         */
        if ($userQuery != null && $userQuery->getAffiliation()->getID() == $record['affiliation']) {
            $user->setAffiliation($userQuery->getAffiliation());
        } else {
            $user->setAffiliation($this->dbutil->readConstellation($record['affiliation'], null, DBUtil::$READ_MICRO_SUMMARY));
        }

        $user->setPreferredRules($record['preferred_rules']);
        return $user;
    }


    /**
     * Disable log in to this account.
     *
     * Update table appuser.active to false. Return true on success.
     *
     * Should we also munge their password so login becomes impossible? Perhaps not.
     *
     * @param \snac\data\User $user The user to disable
     * @return boolean Return true.
     */
    public function disableUser($user)
    {
        $this->sql->updateActive($user->getUserID(), $this->db->boolToPg(false));
        return true;
    }

    /**
     * Enable log in to this account.
     *
     * Update table appuser.active to true. Return true on success.
     *

     * @param \snac\data\User $user The user to disable
     * @return boolean Return true.
     */
    public function enableUser($user)
    {
        $this->sql->updateActive($user->getUserID(), $this->db->boolToPg(true));
        return true;
    }


    /**
     * List all system privileges.
     *
     * Create a list of Privilege objects of all privileges.
     *
     * @return \snac\data\Privilege[] Return list of Privilege objects
     */
    public function listPrivileges()
    {
        $pidList = $this->sql->selectAllPrivilegeIDs();
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
    public function listRoles()
    {
        /*
         * This might have been called: listallroles listallrole allrolelist.
         */
        $roleIDList = $this->sql->selectAllRoleIDs();
        $roleObjList = array();
        foreach($roleIDList as $rid)
        {
            $roleObj = $this->readRole($rid);
            array_push($roleObjList, $roleObj);
        }
        return $roleObjList;
    }

    /**
     * Read a role object from the database
     *
     * Select role data from the database, and populate a role object.
     *
     * @param integer $rid Role ID value.
     *
     * @return \snac\data\Role A Role object.
     *
     */
    public function readRole($rid)
    {
        $row = $this->sql->selectRole($rid);
        $roleObj = new \snac\data\Role();
        $roleObj->setID($row['id']);
        $roleObj->setLabel($row['label']);
        $roleObj->setDescription($row['description']);
        foreach($row['pid_list'] as $pid)
        {
            $roleObj->addPrivilege($this->populatePrivilege($pid));
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
    private function populatePrivilege($pid)
    {
        $row = $this->sql->selectPrivilege($pid);
        $privilegeObj = new \snac\data\Privilege();
        $privilegeObj->setID($row['id']);
        $privilegeObj->setLabel($row['label']);
        $privilegeObj->setDescription($row['description']);
        return $privilegeObj;
    }

    /**
     * Add a role to the User
     *
     * Add role via table appuser_role_link. Return true on success.
     *
     * Use this when adding a role.
     *
     * After adding the role, set the users's role list by getting the list from the db.
     *
     * @param \snac\data\User $user A user
     *
     * @param \snac\data\Role $newRole is associative list with keys id, label, description. We really only
     * care about id, which is important because the web UI might pass up a role object with only the id
     * property set.
     */
    public function addRoleToUser($user, $newRole) {
        $this->sql->insertRoleLink($user->getUserID(), $newRole->getID());
        $user->setRoleList($this->listUserRoles($user));
    }

    /**
     * Add a privilege to a role
     *
     * The privilege must exist before calling this. Use createPrivilege().
     *
     * This is an alternate to adding the priv to the role, then calling writeRole(). Having two ways of
     * adding a priv to a role is probably less than ideal.
     *
     * @param \snac\data\Role $role The role.
     * @param \snac\data\Privilege $privilege is a Role object. Role object has identical fields to privilege.
     */
    public function addPrivilegeToRole($role, $privilege)
    {
        $role->addPrivilege($privilege);
        $this->sql->insertPrivilegeRoleLink($role->getID(), $privilege->getID());
        return true;
    }

    /**
     * Does user have a privilege
     *
     * Returns true if the privilege exists. Build a list of all the privs, then test the list for key
     * existence.
     *
     * @return boolean True if the $user has $privilege in any of the roles, return false otherwise.
     */
    public function hasPrivilege($user, $privilege)
    {
        $allPrivs = array(); // assoc list
        foreach($user->getRoleList() as $userRole)
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
     * @param string $label Symbolic label of a privilege.
     * @return boolean True if the $user has $privilege in any of the roles, return false otherwise.
     */
    public function hasPrivilegeByLabel($user, $label)
    {
        $allPrivs = array(); // assoc list
        foreach($user->getRoleList() as $userRole)
        {
            foreach($userRole->getPrivilegeList() as $priv)
            {
                $allPrivs[$priv->getLabel()] = 1;
            }
        }
        return isset($allPrivs[$label]);
    }


    /**
     * List roles for a user.
     *
     * List all the roles for a user by reading from the database. Return an array of Role objects.
     *
     * You might be have expected this function to be called: listalluserroles, listallrolesuser,
     * userallroles, or userroles.
     *
     * @param \snac\data\User $user The user
     *
     * @return \snac\data\Role[] A list of Role objects.
     */
    public function listUserRoles($user)
    {
        $ridList = $this->sql->selectUserRoleIDs($user->getUserID());
        $roleObjList = array();
        foreach($ridList as $rid)
            {
                $roleObj = $this->readRole($rid);
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
     * Remove the role from the user is how we say it, but the relation is bi-directional. In retrospect, this
     * function should have been called removeRoleFromUser.
     *
     * After removing the role, refresh the User role list by reading it back from the database.
     *
     * @param \snac\data\User $user A user
     * @param \snac\data\Role $role Role object
     */
    public function removeUserRole($user, $role)
    {
        $this->sql->deleteRoleLink($user->getUserID(), $role->getID());
        $user->setRoleList($this->listUserRoles($user));
        return true;
    }

    /**
     * Remove a privilege from a role
     *
     * Remvoe the privilege role link from the db, also remove the privilege from the role object as well.
     *
     * @param \snac\data\Role $role A role
     * @param \snac\data\Privilege $privilege Privilege is a Role object
     */
    public function removePrivilegeFromRole($role, $privilege)
    {
        $this->sql->deletePrivilegeRoleLink($role->getID(), $privilege->getID());
        // $role is changed in place.
        $role->removePrivilege($privilege);
        return true;
    }


    /**
     * Write or update a new role to the database
     *
     * Linked privileges have to exist before the role, logically. A privilege must have an ID in order to be
     * linked to a role, and a privilege doesn't have an ID until that privilege has been written to the
     * database.
     *
     * @param \snac\data\Role $role The role objectc.
     *
     * @return \snac\data\Role Role object.
     */
    public function writeRole($role)
    {
        if ($role->getID())
        {
            $this->sql->updateRole($role->getID(),
                                   $role->getLabel(),
                                   $role->getDescription());
        }
        else
        {
            $rid = $this->sql->insertRole($role->getLabel(),
                                          $role->getDescription());
            $role->setID($rid);
        }
        foreach($role->getPrivilegeList() as $priv)
        {
            $this->sql->insertPrivilegeRoleLink($role->getID(), $priv->getID());
        }
        // Objects are passed by referece. How does it make sense to return an arg passed by reference?
        return $role;
    }



    /**
     * Insert or update a privilege into the database
     *
     * Insert update a privilege. If insert, call setID() with the returned ID. Return the privilege, which if
     * inserted, will have an ID.
     *
     * @param \snac\data\Privilege $privilege, the privilege object
     *
     * @return \snac\data\Privilege Privilege object, with an ID.
     */
    public function writePrivilege($privilege)
    {
        if ($privilege->getID())
        {
            $this->sql->updatePrivilege($privilege->getID(),
                                        $privilege->getLabel(),
                                        $privilege->getDescription());
        }
        else
        {
            $pid = $this->sql->insertPrivilege($privilege->getLabel(),
                                               $privilege->getDescription());
            $privilege->setID($pid);
        }
        // Objects are passed by referece. How does it make sense to return an arg passed by reference?
        return $privilege;
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
     * @return boolean true Always returns true, although an exception will be thrown by low level db code if
     * something fails.
     */
    public function clearAllSessions($user)
    {
        $this->sql->deleteAllSession($user->getUserID());
        return true;
    }

    /**
     * List all users
     *
     * Return a list of all users as user objects.
     *
     * If you want to pass an affiliation, then you must also pass $everyOne.
     *
     * @param boolean $everyone optional Defaults to false. Pass true to get both active and inactive.
     * Everyone false is only active users.
     *
     * @param \snac\data\Constellation $affiliation optional Optional affiliation.
     *
     * @return \snac\data\User[] $allUserList
     */
    public function listUsers($everyone=false, $affiliation=null)
    {
        $idList = $this->sql->selectAllUserIDList($everyone, $affiliation==null?null:$affiliation->getID());
        $allUserList = array();
        foreach($idList as $userID)
        {
            $newUserRec = $this->sql->selectUserByID($userID);
            $newUser = $this->populateUser($newUserRec);
            array_push($allUserList, $newUser);
        }
        return $allUserList;
    }

    /**
     * Write or update a group to the database
     *
     * @param \snac\data\Group $group The group object
     *
     * @return \snac\data\Group The group object
     */
    public function writeGroup($group)
    {
        if ($group->getID())
        {
            $this->sql->updateGroup($group->getID(),
                                    $group->getLabel(),
                                    $group->getDescription());
        }
        else
        {
            $rid = $this->sql->insertGroup($group->getLabel(),
                                           $group->getDescription());
            $group->setID($rid);
        }
        // Objects are passed by referece. It is something of a duplication to pass-by-reference, then return
        // the reference.
        return $group;
    }


    /**
    * Read a group from the database
    *
    * @param \snac\data\Group $group The original, incomplete group object. We only use getID()
    *
    * @return \snac\data\Group The new group object
    */
    public function readGroup($group)
    {
        $groupObj = $this->populateGroup($group->getID());
        return $groupObj;
    }


    /**
     * Populate a group object
     *
     * Return a group object based on the $pid ID value
     *
     * @return \snac\data\Group A group object.
     */
    private function populateGroup($pid)
    {
        $row = $this->sql->selectGroup($pid);
        $groupObj = new \snac\data\Group();
        $groupObj->setID($row['id']);
        $groupObj->setLabel($row['label']);
        $groupObj->setDescription($row['description']);
        return $groupObj;
    }

    /**
     * Really delete a group from the db
     *
     * Deleting a group should be rare. To make this a little safer deleteGroup() only deletes if the
     * group is not in use. If the group exists and is not linked to any appuser, it will be
     * deleted. Otherwise, it is not deleted.
     *
     * This function's name might be confused with removeGroup, although we have removeUserFromGroup
     * (non-existant: removeGroupFromUser) specifically for users. The method deleteGroup is in SQL.php since
     * we reserve the word "delete" for SQL functions.
     *
     * @param \snac\data\Group A Group object
     */
    public function eraseGroup($groupObj)
    {
        $this->sql->deleteGroup($groupObj->getID());
    }

    /**
     * List all system groups.
     *
     * Create a list of Group objects of all groups.
     *
     * @return \snac\data\Group[] Return list of Group objects
     */
    public function listGroups()
    {
        $gidList = $this->sql->selectAllGroupIDs();
        $groupObjList = array();
        foreach($gidList as $gid)
        {
            $groupObj = $this->populateGroup($gid);
            array_push($groupObjList, $groupObj);
        }
        return $groupObjList;
    }

    /**
     * List users in a group
     *
     * @param \snac\data\Group $group The group
     *
     * @param boolean $everyone optional If true list both inactive and active users. If false, only list
     * active users.
     *
     * @return \snac\data\User[] List of users
     */
    public function listUsersInGroup($group, $everyone=false)
    {
        $idList = $this->sql->selectUserIDsFromGroup($group->getID(), $everyone);
        $userList = array();
        foreach($idList as $uid)
        {
            array_push($userList, $this->populateUser($this->sql->selectUserByID($uid)));
        }
        return $userList;
    }

    /**
     * List groups for a user
     *
     * Read the groups from the database. List the groups this user is in.
     *
     * @param \snac\data\User The user we want to list groups for.
     * @return \snac\data\Group[] List of group objects.
     */
    public function listGroupsForUser($user)
    {
        $gids = $this->sql->selectUserGroupIDs($user->getUserID());
        $groupList = array();
        foreach($gids as $gid)
        {
            $row = $this->sql->selectGroup($gid);
            $grp = new \snac\data\Group();
            $grp->setID($row['id']);
            $grp->setLabel($row['label']);
            $grp->setDescription($row['description']);
            array_push($groupList, $grp);
        }
        return $groupList;
    }

    /**
     * Add a group to the User
     *
     * The group must exist in the database. That is: the group must have a valid id.
     *
     * Due to naming confusiong, you might be searching for nonexistant functions: addgroup, add group adding
     * a group adding group.
     *
     * After adding the group, set the users's group list by getting the list from the db.
     *
     * @param \snac\data\User $user A user
     * @param \snac\data\Group $newGroup is associative list with keys id, label, description. We really only care about id.
     */
    public function addUserToGroup($user, $newGroup)
    {
        $this->sql->insertGroupLink($user->getUserID(), $newGroup->getID());
    }

    /**
     * Remove user from group
     *
     * Remove a group-user link by deleting a record from table appuser_group_link. The user will no longer be
     * in the group. The semantics are confusing because it is a bi-directional link. The user is in this
     * group (has this group), and the group has this user (is related to this user).
     *
     * After removing the user, refresh the User group list by reading it back from the database.
     *
     * @param \snac\data\User $user A user
     * @param \snac\data\Group $group Group object
     */
    public function removeUserFromGroup($user, $group)
    {
        $this->sql->deleteGroupLink($user->getUserID(), $group->getID());
        return true;
    }

    /**
     * List all system institutions.
     *
     * Create a list of summary constellation objects for each SNAC institution.
     *
     * @return \snac\data\Constellation[] Return list of constellations (summary) for the SNAC institutions.
     */
    public function listInstitutions()
    {
        /*
         * Select all the institution data, returned as a list of associative lists. We don't have any use for
         * a list of ids, unlike Role and some other data. So, there is no populateInstitution(). It all happens here.
         *
         */
        $rowList = $this->sql->selectAllInstitution();
        $instList = array();
        foreach($rowList as $row)
        {
            $cObj = $this->dbutil->readPublishedConstellationByID($row['ic_id'], true);
            array_push($instList, $cObj);
        }
        return $instList;
    }

    /**
     * Write or update a new institution to the database
     *
     * The part of the constellation we most care about is the ic_id. getConstellationID() is the ic_id.
     *
     * If the institution has an id, it must have previously have been written to the db, so update.
     *
     * The update use case is unclear. Insert and delete (erase) seem reasonable, but update will probably never happen.
     *
     * @param \snac\data\Constellation $institution The SNAC institution in a constellation.
     *
     * @return \snac\data\Constellation object, summary, holding a SNAC institution
     */
    public function writeInstitution($institution)
    {
        $this->sql->insertInstitution($institution->getID());
    }

    /**
     * Really delete a SNAC institution from the db
     *
     * Remember that SNAC Institution records are simply the ic_id of an existing SNAC constellation. This only
     * deletes from the snac_institution table, and nothing else is effected.
     *
     * deleteInstitution() will throw an exception if asked to delete an institution which has any affiliated
     * users.
     *
     * @param \snac\data\Constellation  A SNAC Institution in a summary constellation object.
     */
    public function eraseInstitution($institutionObj)
    {
        $this->sql->deleteInstitution($institutionObj->getID());
    }
}
