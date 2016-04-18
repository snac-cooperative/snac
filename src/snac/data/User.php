<?php

/**
 * User File
 *
 * Contains the information about an individual user of the system.
 *
 * License:
 *
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\data;

/**
 * User Class
 *
 * Storage class for information about a user of the system and their login information.
 *
 * @author Robbie Hott
 *        
 */
class User {

    /**
     * It seems like a good idea for this to be appuser.id, but that needs to be confirmed.
     */
    private $userid;

    private $userName;

    private $firstName;

    private $lastName;

    private $fullName;

    private $avatar;
    
    private $avatarSmall;
    
    private $avatarLarge;

    private $email;

    private $token;

    /**
     * List of role
     *
     * A list of each role this user has.
     *
     * @var snac\data\Role[] List of Role objects
     */
    
    /**
     * Constructor 
     * 
     * @param string[] $data Array object of User information
     */
    public function __construct($data = null) {
        if ($data != null)
            $this->fromArray($data);
        
    }

    /**
     * Set the user name
     * @param string $userName
     */
    public function setUserName($userName)
    {
        $this->userName = $userName;
    }

    /**
     * Get the user name
     * @return string $userName
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * Get the list of roles
     *
     * Return the role list
     *
     * @return snac\data\Role[] Return a list of Role objects
     */ 
    public function getRoleList()
    {
        return $this->roleList;
    }
    
    /**
     * Set role list
     *
     * Set the user role list to a list of roles. The list probably comes from from DBUser->listUserRole().
     *
     * @param \snac\data\Role[] $roleList A list of roles. 
     */ 
    public function setRoleList($roleList)
    {
        $this->roleList = $roleList;
    }


    public function setUserID($id) {

        $this->userid = $id;
    }

    public function setFirstName($first) {

        $this->firstName = $first;
    }

    public function setLastName($last) {

        $this->lastName = $last;
    }

    public function setFullName($full) {

        $this->fullName = $full;
    }

    public function setAvatar($avatar) {

        $this->avatar = $avatar;
    }

    public function setAvatarSmall($avatar) {
    
        $this->avatarSmall = $avatar;
    }

    public function setAvatarLarge($avatar) {
    
        $this->avatarLarge = $avatar;
    }

    public function setEmail($email) {

        $this->email = $email;
    }

    public function setToken($token) {

        $this->token = $token;
    }

    public function getUserID() {

        return $this->userid;
    }

    public function getFirstName() {

        return $this->firstName;
    }

    public function getLastName() {

        return $this->lastName;
    }

    public function getFullName() {

        return $this->fullName;
    }

    public function getAvatar() {

        return $this->avatar;
    }
    public function getAvatarSmall() {

        return $this->avatarSmall;
    }

    public function getAvatarLarge() {

        return $this->avatarLarge;
    }

    public function getEmail() {

        return $this->email;
    }

    public function getToken() {

        return $this->token;
    }
    
    public function generateTemporarySession($hours = 2) {
        $token = array(
                "access_token" => time(),                // use time() to be unique-ish
                "expires" => time() + ($hours * 60 * 60) // Generates expiration $hours away
        );
        $this->setToken($token);
    }

    /**
     * Returns this object's data as an associative array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This objects data in array form
     */
    public function toArray($shorten = true) {

        $return = array (
                "userid" => $this->userid,
                "userName" => $this->userName,
                "firstName" => $this->firstName,
                "lastName" => $this->lastName,
                "fullName" => $this->fullName,
                "avatar" => $this->avatar,
                "avatarSmall" => $this->avatarSmall,
                "avatarLarge" => $this->avatarLarge,
                "email" => $this->email,
                "token" => $this->token
        );
        
        // Shorten if necessary
        if ($shorten) {
            $return2 = array ();
            foreach ($return as $i => $v)
                if ($v != null && ! empty($v))
                    $return2[$i] = $v;
            unset($return);
            $return = $return2;
        }
        
        return $return;
    }

    /**
     * Replaces this object's data with the given associative array
     *
     * @param string[][] $data This objects data in array form
     * @return boolean true on success, false on failure
     */
    public function fromArray($data) {

        if (isset($data["userid"]))
            $this->userid = $data["userid"];
        else
            $this->userid = null;
        
        if (isset($data["userName"]))
            $this->userName = $data["userName"];
        else
            $this->userName = null;
        
        if (isset($data["firstName"]))
            $this->firstName = $data["firstName"];
        else
            $this->firstName = null;
        
        if (isset($data["lastName"]))
            $this->lastName = $data["lastName"];
        else
            $this->lastName = null;
        
        if (isset($data["fullName"]))
            $this->fullName = $data["fullName"];
        else
            $this->fullName = null;
        
        if (isset($data["avatar"]))
            $this->avatar = $data["avatar"];
        else
            $this->avatar = null;
        
        if (isset($data["avatarSmall"]))
            $this->avatarSmall = $data["avatarSmall"];
        else
            $this->avatarSmall = null;
        
        if (isset($data["avatarLarge"]))
            $this->avatarLarge = $data["avatarLarge"];
        else
            $this->avatarLarge = null;
        
        if (isset($data["email"]))
            $this->email = $data["email"];
        else
            $this->email = null;
        
        if (isset($data["token"]))
            $this->token = $data["token"];
        else
            $this->token = null;
        
        return true;
    }
    
    /**
     * Convert this object to JSON
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string JSON encoding of this object
     */
    public function toJSON($shorten = true) {
        return json_encode($this->toArray($shorten), JSON_PRETTY_PRINT);
    }
    
    /**
     * Prepopulate this object from the given JSON
     *
     * @param string $json JSON encoding of this object
     * @return boolean true on success, false on failure
     */
    public function fromJSON($json) {
        $data = json_decode($json, true);
        $return = $this->fromArray($data);
        unset($data);
        return $return;
    }
}
