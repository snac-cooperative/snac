<?php

/**
 * Mandate Class
 *
 *
 * License:
 *
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\data;

/**
 * Mandate Class
 *
 * Class that holds Mandate Information
 *
 * @author Robbie Hott
 */
class Mandate extends AbstractTextData {

    /**
     * Constructor
     *
     * @param string[] $data optional An array of data to pre-fill this object
     */
    public function __construct($data = null) {
        $this->setDataType("Mandate");
        $this->setMaxDateCount(0);
        parent::__construct($data);
    }

}
