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
class User implements \Serializable {

    /**
     * @var int The numeric user ID for this user
     */
    private $userid;

    /**
     * @var string The user name (unique for each user)
     */
    private $userName;

    /**
     * @var string The user's first name
     */
    private $firstName;

    /**
     * @var string The user's last name
     */
    private $lastName;

    /**
     * @var string The user's full name
     */
    private $fullName;

    /**
     * @var string A URL to the user's avatar image (default size)
     */
    private $avatar;

    /**
     * @var string A URL to the user's avatar image (small size)
     */
    private $avatarSmall;

    /**
     * @var string A URL to the user's avatar image (large size)
     */
    private $avatarLarge;

    /**
     * @var string The user's email address. One email address may be registered to multiple users.
     */
    private $email;

    /**
     * @var string[] The user's session token
     */
    private $token;

    /**
     * List of role
     *
     * A list of each role this user has.
     *
     * @var snac\data\Role[] List of Role objects
     */
    private $roleList = null;

    /**
     * Work email
     *
     * @var string Work email
     */
    private $workEmail = null;

    /**
     * Work phone
     *
     * @var string Work phone
     */
    private $workPhone = null;

    /**
     * Affiliation Constellation
     *
     * @var snac\data\Constellation Populated as a summary mini-constellation.
     */
    private $affiliation = null;

    /**
     * Preferred descriptive name rules
     *
     * @var string Preferred rules
     */
    private $preferredRules = null;

    /**
     * Whether the user is active
     *
     * @var boolean User is active
     */
    private $active = false;

    /**
     * Constructor
     *
     * @param string[] $data Array object of User information
     */
    public function __construct($data = null) {

        $this->roleList = array();
        if ($data != null)
            $this->fromArray($data);
    }

    /**
     * Set the User active
     *
     * Sets the user as active or not.
     *
     * @param boolean $active Whether or not the user is active
     */
    public function setUserActive($active = false) {
        $this->active = $active;
    }

    /**
     * Get the User active
     *
     * Gets the user as active or not.
     *
     * @param boolean $active Whether or not the user is active
     */
    public function getUserActive() {
        return $this->active;
    }

    /**
     * Set the user name
     * @param string $userName username
     */
    public function setUserName($userName)
    {
        $this->userName = $userName;
    }

    /**
     * Get the user name
     * @return string the username
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
     * @return snac\data\Role[] a list of Role objects
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
        /*
         * Interesting that we set the whole list at once, and don't use addRole() (which doesn't exist) or
         * something like that. That is probably due to writing user role links to the db, then getting back the
         * whole role list from the db.
         */
        $this->roleList = $roleList;
    }

    /**
     * Add a role
     *
     * Adds a Role to this User.
     *
     * @param \snac\data\Role $role The role to add to this user
     */
    public function addRole($role) {
        array_push($this->roleList, $role);
    }


    /**
     * Set the UserID
     *
     * @param int $id The User's numeric id
     */
    public function setUserID($id) {

        $this->userid = $id;
    }

    /**
     * Set the first name
     *
     * @param string $first The User's first name
     */
    public function setFirstName($first) {

        $this->firstName = $first;
    }

    /**
     * Set the last name
     *
     * @param string $last The user's last name
     */
    public function setLastName($last) {

        $this->lastName = $last;
    }

    /**
     * Set the full name
     *
     * @param string $full The user's full name
     */
    public function setFullName($full) {

        $this->fullName = $full;
    }

    /**
     * Set the avatar
     *
     * Sets the default-sized avatar link
     *
     * @param string $avatar URL to the default-size avatar image
     */
    public function setAvatar($avatar) {

        $this->avatar = $avatar;
    }

    /**
     * Set the small avatar
     *
     * Sets the small-sized avatar link
     *
     * @param string $avatar URL to the small-size avatar image
     */
    public function setAvatarSmall($avatar) {

        $this->avatarSmall = $avatar;
    }

    /**
     * Set the large avatar
     *
     * Sets the large-sized avatar link
     *
     * @param string $avatar URL to the large-size avatar image
     */
    public function setAvatarLarge($avatar) {

        $this->avatarLarge = $avatar;
    }

    /**
     * Set the email address
     *
     * @param string $email The user's email address
     */
    public function setEmail($email) {

        $this->email = $email;
    }

    /**
     * Set the access token
     *
     * This sets the token for the user.  The token is an associative array that contain the "access_token" and
     * a "expires" that states when the token expires in terms of the linux epoch.
     *
     * @param string[] $token Associative array for the token, with "access_token" and "expires" keys
     */
    public function setToken($token) {

        $this->token = $token;
    }

    /**
     * Get user id
     *
     * @return int the user's numeric ID
     */
    public function getUserID() {

        return $this->userid;
    }

    /**
     * Get the first name
     *
     * @return string The user's first name
     */
    public function getFirstName() {

        return $this->firstName;
    }

    /**
     * Get the last name
     *
     * @return string The user's last name
     */
    public function getLastName() {

        return $this->lastName;
    }

    /**
     * Get the full name
     *
     * @return string The user's full name
     */
    public function getFullName() {

        return $this->fullName;
    }

    /**
     * Get the default-size avatar
     *
     * @return string The URL to the default-size avatar
     */
    public function getAvatar() {

        return $this->avatar;
    }

    /**
     * Get the small-size avatar
     *
     * @return string The URL to the small-size avatar
     */
    public function getAvatarSmall() {

        return $this->avatarSmall;
    }

    /**
     * Get the large-size avatar
     *
     * @return string The URL to the large-size avatar
     */
    public function getAvatarLarge() {

        return $this->avatarLarge;
    }

    /**
     * Get the email address
     *
     * @return string The user's email address
     */
    public function getEmail() {

        return $this->email;
    }

    /**
     * Get the token
     *
     * Get's the full token array, including the "access_token" and "expires" fields.
     *
     * @return \string[] The associative array containing the user's "access_token" and "expires" keys.
     */
    public function getToken() {

        return $this->token;
    }

    /**
     * Generate a temporary session
     *
     * Generates a temporary session, using the timestamp as an access token and the expiration to be
     * the number of hours specified by the parameter from now.
     *
     * @param int $hours optional number of valid hours for the token (default 2)
     */
    public function generateTemporarySession($hours = 2) {
        $token = array(
                "access_token" => time(),                // use time() to be unique-ish
                "expires" => time() + ($hours * 60 * 60), // Generates expiration $hours away
                "authority" => "snac"                   // We faked this session
        );
        $this->setToken($token);
    }

    /**
     * Get work email
     *
     * @return string Work email
     */
    public function getWorkEmail()
    {
        return $this->workEmail;
    }

    /**
     * Get work phone
     *
     * @return string Work phone
     */
    public function getWorkPhone()
    {
        return $this->workPhone;
    }

    /**
     * Get affiliation Constellation
     *
     * @return snac\data\Constellation Affiliation constellation, in summary form
     */
    public function getAffiliation()
    {
        return $this->affiliation;
    }

    /**
     * Get preferred descriptive name rules
     *
     * @return string Preferred rules
     */
    public function getPreferredRules()
    {
        return $this->preferredRules;
    }

    /**
     * Set work email
     *
     * @param  string $workEmail Work email
     */
    public function setWorkEmail($workEmail)
    {
        $this->workEmail = $workEmail;
    }

    /**
     * Set work phone
     *
     * @param string $workPhone Work phone
     */
    public function setWorkPhone($workPhone)
    {
        $this->workPhone = $workPhone;
    }

    /**
     * Set affiliation Constellation
     *
     * @param snac\data\Constellation $affiliation Affiliation constellation, only summary fields populated.
     */
    public function setAffiliation($affiliation)
    {
        $this->affiliation = $affiliation;
    }

    /**
     * Set preferred descriptive name rules
     *
     * @param string $preferredRules Preferred rules
     */
    public function setPreferredRules($preferredRules)
    {
        $this->preferredRules = $preferredRules;
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
                "workEmail" => $this->workEmail,
                "workPhone" => $this->workPhone,
                "active" => $this->active,
                "affiliation" => $this->affiliation==null?null:$this->affiliation->toArray($shorten),
                "token" => $this->token,
                "roleList" => array()
        );

        foreach ($this->roleList as $i => $v)
            $return["roleList"][$i] = $v->toArray($shorten);


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

        if (isset($data["workEmail"]))
            $this->workEmail = $data["workEmail"];
        else
            $this->workEmail = null;

        if (isset($data["workPhone"]))
            $this->workPhone = $data["workPhone"];
        else
            $this->workPhone = null;

        if (isset($data["affiliation"]) && $data['affiliation'] != null)
            $this->affiliation = new \snac\data\Constellation($data["affiliation"]);
        else
            $this->affiliation = null;

        if (isset($data["token"]))
            $this->token = $data["token"];
        else
            $this->token = null;

        if (isset($data["active"]))
            $this->active = $data["active"];
        else
            $this->active = false;

        unset($this->roleList);
        $this->roleList = array();
        if (isset($data["roleList"])) {
            foreach ($data["roleList"] as $i => $entry) {
                if ($entry != null)
                    $this->roleList[$i] = new \snac\data\Role($entry);
            }
        }

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

    /**
     * Serialization Method
     *
     * Allows PHP's serialize() method to correctly serialize the object.
     *
     * {@inheritDoc}
     *
     * @return string The serialized form of this object
     */
    public function serialize() {
        return $this->toJSON();
    }

    /**
     * Un-Serialization Method
     *
     * Allows PHP's unserialize() method to correctly unserialize the object.
     *
     * {@inheritDoc}
     *
     * @param string $data the serialized object
     */
    public function unserialize($data) {
        $this->fromJSON($data);
    }

}
