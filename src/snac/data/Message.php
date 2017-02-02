<?php
/**
 * Message File
 *
 * Contains a message in the system.
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
 * Message Class
 *
 * Storage class for a message
 *
 * @author Robbie Hott
 *
 */
class Message implements \Serializable {

    /**
     * @var int The Message Id
     */
    private $id;

    /**
     * @var \snac\data\User The To User
     */
    private $toUser;

    /**
     * @var \snac\data\User The From User
     */
    private $fromUser;

    /**
     * @var string From string (if not from a user)
     */
    private $fromString;

    /**
     * @var string The subject of the message
     */
    private $subject;

    /**
     * @var string The body of the message
     */
    private $body;

    /**
     * @var string Timestamp of the message
     */
    private $timestamp;

    /**
     * @var boolean Whether or not the message was read
     */
    private $read = false;

    /**
     * @var string Attachment content (base64 encoded, likely)
     */
    private $attachmentContent;

    /**
     * @var string Attachment filename
     */
    private $attachmentFilename;




    /**
     * Constructor
     *
     * @param string[] $data Array object of Message information
     */
    public function __construct($data = null) {
        if ($data != null)
            $this->fromArray($data);
    }

    /**
     * Get Message ID
     *
     * @return int Message ID
     */
    public function getID() {
        return $this->id;
    }
    /**
     * Get the To User
     *
     * @return \snac\data\User The To User
     */
    public function getToUser() {
        return $this->toUser;
    }

    /**
     * Get the From User
     *
     * @return \snac\data\User The From User
     */
    public function getFromUser() {
        return $this->fromUser;
    }

    /**
     * Get the From String
     *
     * @return string From string (if not from a user)
     */
    public function getFromString() {
        return $this->fromString;
    }

    /**
     * Get the subject
     *
     * @return string The subject of the message
     */
    public function getSubject() {
        return $this->subject;
    }

    /**
     * Get the message body
     *
     * @return string The body of the message
     */
    public function getBody() {
        return $this->body;
    }

    /**
     * Get the message Timestamp
     *
     * @return string Timestamp of the message
     */
    public function getTimestamp() {
        return $this->timestamp;
    }

    /**
     * Has it been read?
     *
     * @return boolean Whether or not the message was read
     */
    public function isRead() {
        return $this->read;
    }

    /**
     * Get Attachment Content
     *
     * @return string Attachment content (base64 encoded, likely)
     */
    public function getAttachmentContent() {
        return $this->attachmentContent;
    }


    /**
     * Get Attachment Filename
     *
     * @return string Attachment filename
     */
    public function getattachmentFilename() {
        return $this->attachmentFilename;
    }

    /**
     * Set Message ID
     *
     * @param int $id Message ID number
     */
    public function setID($id) {
        $this->id = $id;
    }

    /**
     * Set the To User
     *
     * @param $toUser \snac\data\User The To User
     */
    public function setToUser($toUser) {
        $this->toUser = $toUser;
    }

    /**
     * Set the From User
     *
     * Also unsets from string
     *
     * @param $fromUser \snac\data\User The From User
     */
    public function setFromUser($fromUser) {
        $this->fromString = null;
        $this->fromUser = $fromUser;
    }

    /**
     * Set the From string
     *
     * Also unsets the from User
     *
     * @param $fromString string From string (if not from a user)
     */
    public function setFromString($fromString) {
        $this->fromString = $fromString;
        $this->fromUser = null;
    }

    /**
     * Set the Subject
     *
     * @param $subject string The subject of the message
     */
    public function setSubject($subject) {
        $this->subject = $subject;
    }

    /**
     * Set the Body
     *
     * @param $body string The body of the message
     */
    public function setBody($body) {
        $this->body = $body;
    }

    /**
     * Set the Timestamp
     *
     * @param $timestamp string Timestamp of the message
     */
    public function setTimestamp($timestamp) {
        $this->timestamp = $timestamp;
    }

    /**
     * Set the message read flag
     *
     * @param $read boolean Whether or not the message was read
     */
    public function setRead($read = false) {
        $this->read = $read;
    }

    /**
     * Set the attachment content
     *
     * @param $attachmentContent string Attachment content (base64 encoded, likely)
     */
    public function setAttachmentContent($attachmentContent) {
        $this->attachmentContent = $attachmentContent;
    }



    /**
     * Set the attachment filename
     *
     * @param $attachmentFilename string Attachment filename
     */
    public function setAttachmentFilename($attachmentFilename) {
        $this->attachmentFilename = $attachmentFilename;
    }


    /**
     * Returns this object's data as an associative array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This objects data in array form
     */
    public function toArray($shorten = true) {

        $return = array (
            "id" => $this->id,
            "toUser" => $this->toUser === null ? null : $this->toUser->toArray(),
            "fromUser" => $this->fromUser === null ? null : $this->fromUser->toArray(),
            "fromString" => $this->fromString,
            "subject" => $this->subject,
            "body" => $this->body,
            "timestamp" => $this->timestamp,
            "read" => $this->read,
            "attachmentContent" => $this->attachmentContent,
            "attachmentFilename" => $this->attachmentFilename
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
        if (isset($data["id"]))
            $this->fromString = $data["id"];
        else
            $this->fromString = null;

        if (isset($data["toUser"]) && $data["toUser"] !== null)
            $this->toUser = new \snac\data\User($data["toUser"]);
        else
            $this->toUser = null;

        if (isset($data["fromUser"]) && $data["fromUser"] !== null)
            $this->fromUser = new \snac\data\User($data["fromUser"]);
        else
            $this->fromUser = null;

        if (isset($data["fromString"]))
            $this->fromString = $data["fromString"];
        else
            $this->fromString = null;

        if (isset($data["subject"]))
            $this->subject = $data["subject"];
        else
            $this->subject = null;

        if (isset($data["body"]))
            $this->body = $data["body"];
        else
            $this->body = null;

        if (isset($data["timestamp"]))
            $this->timestamp = $data["timestamp"];
        else
            $this->timestamp = null;

        if (isset($data["read"]))
            $this->read = $data["read"];
        else
            $this->read = false;

        if (isset($data["attachmentContent"]))
            $this->attachmentContent = $data["attachmentContent"];
        else
            $this->attachmentContent = null;

        if (isset($data["attachmentFilename"]))
            $this->attachmentFilename = $data["attachmentFilename"];
        else
            $this->attachmentFilename = null;

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
