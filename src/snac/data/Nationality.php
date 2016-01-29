<?php

/**
 * Nationality Class
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
 * Nationality Class
 *
 * Class that holds Nationality Information
 *
 * @author Robbie Hott
 */
class Nationality extends AbstractTermData {

    /**
     * Constructor
     */
    public function __construct($data = null) {
        // $this->setDataType("Nationality");
        $this->validType = "nationality";
        parent::__construct($data);
    }

}
