<?php

/**
 * ConventionDeclaration Class
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
 * ConventionDeclaration Class
 *
 * Class that holds ConventionDeclaration Information
 *
 * @author Robbie Hott
 */
class ConventionDeclaration extends AbstractTextData {

    /**
     * Constructor
     */
    public function __construct($data = null) {
        $this->setDataType("ConventionDeclaration");
        parent::__construct($data);
    }

}
