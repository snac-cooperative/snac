<?php

/**
 * Subject Class
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
 * Subject Class
 *
 * Class that holds Subject Information
 *
 * @author Robbie Hott
 */
class Subject extends AbstractTermData {

    /**
     * Constructor
     *
     * @param string[] $data optional An array of data to pre-fill this object
     */
    public function __construct($data = null) {
        $this->setDataType("Subject");
        $this->setMaxDateCount(0);
        parent::__construct($data);
    }

}
