<?php
/**
 * Vocabulary interface file
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
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
     * @param string $value The value of the term to search for
     * @param string $type The type of controlled vocab
     * @return \snac\data\Term The Term object for the value
     */
    public function getTermByValue($value, $type);

    /**
     * Get a Term by integer id 
     *
     * @param string $id The persistent ID (int) for the term
     * @param string $type The type of controlled vocab
     * @return \snac\data\Term The Term object for the id
     */
    public function getTermByID($id, $type);
    
    /**
     * Get a GeoTerm by URI
     * 
     * @param string $uri The uri to look up
     * @return \snac\data\GeoTerm The GeoTerm object for the uri
     */
    public function getGeoTermByURI($uri);
}

