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
    
    private $message;
    
    private $object;
    
    public function __construct($message = null, $object = null) {
        $this->message = $message;
        $this->object = $object;
    }
    
    public function setMessage($message) {
        $this->message = $message;
    }
    
    public function setObject($object) {
        $this->object = $object;
    }
    
    public function getMessage() {
        return $this->message;
    }
    
    public function getObject() {
        return $this->object;
    }
    
    public function toArray() {
        $return = array();
        $return["message"] = $this->message;
        if($this->object != null) {
            $return["object"] = $this->object;
        }
        return $return;
    }
    
}