<?php

/**
 * Gender Class
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
 * Gender Class
 *
 * Class that holds Gender Information
 *
 * @author Robbie Hott
 */
class Gender extends AbstractTermData {

    /**
     * Constructor
     */
    public function __construct($data = null) {
        $this->validType = "gender";
        parent::__construct($data);
    }

}
