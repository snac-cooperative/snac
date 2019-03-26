<?php

/**
 * Groups for users
 *
 * Groups exist for the workflow to be able to send work or notifications to a group, with the assumption that
 * one member of that group will take the work.
 *
 * License:
 *
 * @author Tom Laudeman
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2016 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\data;

/**
 * Group class
 *
 * Users are in groups, for example "all reviewers" might form a group, and we might want to send a
 * notification to all members of the group.
 *
 * @author Tom Laudeman
 *
 */
class Group extends AbstractGrouping {

    /**
     * Constructor
     *
     * @param string[][] $data The data for this object in an associative array
     */
    public function __construct($data=null)
    {
        parent::__construct($data);
        $this->dataType = 'group';
    }


}
