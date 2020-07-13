<?php

/**
 * StructureOrGenealogy Class
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
 * StructureOrGenealogy Class
 *
 * Class that holds StructureOrGenealogy Information
 *
 * @author Robbie Hott
 */
class StructureOrGenealogy extends AbstractTextData {

    /**
     * Constructor
     *
     * @param string[] $data optional Array of data to pre-fill this object
     */
    public function __construct($data = null) {
        $this->setDataType("StructureOrGenealogy");
        $this->setMaxDateCount(0);
        parent::__construct($data);
    }

}
