<?php

/**
 * SameAs Class
 *
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
 * SameAs Class
 *
 * Class that holds SameAs Information
 *
 * @author Robbie Hott
 */
class SameAs extends AbstractTermData {

    /**
     * Constructor
     */
    public function __construct($data = null) {
        $this->setDataType("SameAs");
        parent::__construct($data);
    }

}
