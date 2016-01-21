<?php
/**
 * Vocabulary interface file
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\util;

/**
 * Vocabulary Interface
 *
 * This interface defines required methods for any Vocabulary class
 * to allow searching a vocabulary source for a string or id and creating
 * a Term object.
 */
interface Vocabulary {

    /**
     * Get a Term by string value/term
     *
     * @param string $type The type of controlled vocab
     * @param string $value The value of the term to search for
     * @return \snac\data\Term The Term object for the value
     */
    public function getTermByValue($value, $type);

    /**
     * Get a Term by integer id 
     *
     * @param string $type The type of controlled vocab
     * @param string $id The persistent ID (int) for the term
     * @return \snac\data\Term The Term object for the id
     */
    public function getTermByID($id, $type);
}

