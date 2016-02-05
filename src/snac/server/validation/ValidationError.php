<?php
/**
 * Validation Error Class File
 *
 * Contains the validation error class
 *
 * License:
 *
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\server\validation;

/**
 * Validation Error
 *
 * This object will hold any validation errors that occur, including
 * an error message and the object that caused the error.
 *
 * @author Robbie Hott
 *
 */
class ValidationError {
    
    /**
     * @var string $message Human readable error message about what happened in validation
     */
    private $message;
    
    /**
     * @var \snac\data\AbstractData The object that caused the error
     */
    private $object;
    
    /**
     * Constructor
     * 
     * @param string $message optional The message for this error
     * @param \snac\data\AbstractData $object optional The object that caused the error
     */
    public function __construct($message = null, $object = null) {
        $this->message = $message;
        $this->object = $object;
    }
    
    /**
     * Set the message
     * 
     * @param string $message Error message, human readable and easy to understand
     */
    public function setMessage($message) {
        $this->message = $message;
    }
    
    /**
     * Set the offending object 
     * 
     * @param \snac\data\AbstractData $object Object that caused the error
     */
    public function setObject($object) {
        $this->object = $object;
    }
    
    /**
     * Get the error message
     * 
     * Get the human-readable, easy-to-understand error message
     * 
     * @return string Error message
     */
    public function getMessage() {
        return $this->message;
    }
    
    /**
     * Get the offending object
     * 
     * Get the object that caused the validation error
     * 
     * @return \snac\data\AbstractData The offending object
     */
    public function getObject() {
        return $this->object;
    }
    
    /**
     * Construct an array of this message
     * 
     * This method is useful to generate JSON objects out of the error message.  The
     * array returned has two keys, `message` that contains the error message and `object`
     * that contains the associative array for that object (by calling its `toArray` method
     * used to generate JSON output.
     * 
     * @return string[][] Associative array representing this error.
     */
    public function toArray() {
        $return = array();
        $return["message"] = $this->message;
        if($this->object != null) {
            $return["object"] = $this->object;
        }
        return $return;
    }
    
}