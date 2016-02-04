<?php

/**
 * LegalStatus Class
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
 * LegalStatus Class
 *
 * Class that holds LegalStatus Information
 *
 * @author Robbie Hott
 */
class LegalStatus extends AbstractTermData {

    /**
     * Constructor
     */
    public function __construct($data = null) {
        // $this->setDataType("LegalStatus");
        $this->validType = "legal_status";
        parent::__construct($data);
    }

}
