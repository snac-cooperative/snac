<?php
/**
 * SNAC Date File
 *
 * Contains the date storage class.
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
 * SNACDate class
 * 
 * Storage class for dates.
 *
 *  See the abstract parent class for common methods setDBInfo() and getDBInfo().
 * 
 * @author Robbie Hott
 *
 */
class SNACDate extends AbstractData {

    /**
     * From EAC-CPF tag(s):
     * 
     * * dateRange/fromDate/@standardDate
     * * date/@standardDate
     * 
     * @var string Begin date (if range)
     */
    private $fromDate;

    /**
     * From EAC-CPF tag(s):
     * 
     * * dateRange/fromDate/
     * * date/
     * 
     * @var string Original string given for the from date
     */
    private $fromDateOriginal;

    /**
     * From EAC-CPF tag(s):
     * 
     * * dateRange/fromDate/@localType
     * * date/@localType
     * 
     * @var[w string Type of the from date
     */
    private $fromType;

    /**
     * From EAC-CPF tag(s):
     * 
     * * dateRange/fromDate/@standardDate (if negative)
     * * date/@standardDate (if negative)
     * 
     * @var boolean If the from date is BC
     */
    private $fromBC;
    
    /**
     * From EAC-CPF tag(s):
     * 
     * * dateRange/fromDate/@notBefore
     * * dateRange/fromDate/@notAfter
     * * date/@notBefore
     * * date/@notAfter
     * 
     * $var string[] From date range
     */
    private $fromRange = array ("notBefore" => null, "notAfter" => null);

    /**
     * From EAC-CPF tag(s):
     * 
     * * dateRange/toDate/@standardDate
     * 
     * @var string End date (if range)
     */
    private $toDate;

    /**
     * From EAC-CPF tag(s):
     * 
     * * dateRange/toDate/
     * 
     * @var string Original string given for the to date
     */
    private $toDateOriginal;

    /**
     * From EAC-CPF tag(s):
     * 
     * * dateRange/toDate/@localType
     * 
     * @var string Type of the to date
     */
    private $toType;

    /**
     * From EAC-CPF tag(s):
     * 
     * * dateRange/toDate/@standardDate (if negative)
     *  
     * @var boolean If the to date is BC
     */
    private $toBC;

    /**
     * From EAC-CPF tag(s):
     * 
     * * dateRange/toDate/@notBefore
     * * dateRange/toDate/@notAfter
     * 
     * $var string[] To date range
     */
    private $toRange = array ("notBefore" => null, "notAfter" => null);

    /**
     * If this is a dateRange or just a date
     * 
     * @var boolean If this SNACDate object contains a range or a single date
     */
    private $isRange;

    /**
     * From EAC-CPF tag(s):
     * 
     * * dateRange/descriptiveNote
     * * date/descriptiveNote
     * 
     * (currently not used)
     * 
     * @var string Note about this date
     */
    private $note;

    /**
     * getter for $this->fromDate
     *
     * @return string Begin date (if range)
     *
     *
     */
    function getFromDate()
    {
        return $this->fromDate;
    }

    /**
     * getter for $this->fromDateOriginal
     *
     * @return string Original string given for the from date
     *
     *
     */
    function getFromDateOriginal()
    {
        return $this->fromDateOriginal;
    }

    /**
     * getter for $this->fromType
     *
     * @return string Original string given for the from date
     *
     *
     */
    function getFromType()
    {
        return $this->fromType;
    }


    /**
     * getter for $this->fromBC
     *
     * This works as expected. A boolean is returns (in as much as php vars have a type). However, Postgres
     * expects bools to be 't' or 'f' and pg_execute() doesn't mogrify boolean that way. We transform boolean
     * ourselves with DatabaseConnector->boolToPg().
     *
     * @return boolean If the from date is BC
     *
     *
     */
    function getFromBC()
    {
        return $this->fromBC;
    }

    /**
     * getter for $this->fromRange
     *
     * @return string[] From date range, array ("notBefore" => null, "notAfter" => null);
     *
     *
     */
    function getFromRange()
    {
        return $this->fromRange;
    }

    /**
     * getter for $this->toDate
     *
     * @return string End date (if range)
     *
     *
     */
    function getToDate()
    {
        return $this->toDate;
    }

    /**
     * getter for $this->toDateOriginal
     *
     * @return string Original string given for the to date
     *
     *
     */
    function getToDateOriginal()
    {
        return $this->toDateOriginal;
    }

    /**
     * getter for $this->toType
     *
     * @return string Type of the to date
     *
     *
     */
    function getToType()
    {
        return $this->toType;
    }

    /**
     * getter for $this->toBC
     *
     * @return boolean If the to date is BC
     *
     *
     */
    function getToBC()
    {
        if ($this->toBC)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * getter for $this->toRange
     *
     * @return string[] To date range, array ("notBefore" => null, "notAfter" => null);
     *
     *
     */
    function getToRange()
    {
        return $this->toRange;
    }

    /**
     * getter for $this->isRange
     *
     * @return boolean If this SNACDate object contains a range or a single date
     *
     *
     */
    function getIsRange()
    {
        if ($this->isRange)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * getter for $this->note
     *
     * (currently not used)
     *
     * @return string Note about this date
     *
     *
     */
    function getNote()
    {
        return $this->note;
    }

    /**
     * Returns this object's data as an associative array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This objects data in array form
     */
    public function toArray($shorten = true) {
        $return = array(
            "dataType" => "SNACDate",
            "fromDate" => $this->fromDate,
            "fromDateOriginal" => $this->fromDateOriginal,
            "fromType" => $this->fromType,
            "fromBC" => $this->fromBC,
            "fromRange" => $this->fromRange,
            "toDate" => $this->toDate,
            "toDateOriginal" => $this->toDateOriginal,
            "toType" => $this->toType,
            "toBC" => $this->toBC,
            "toRange" => $this->toRange,
            "isRange" => $this->isRange,
            "note" => $this->note,
            'dbInfo' => $this->getDBInfo()
        );

        // Shorten if necessary
        if ($shorten) {
            $return2 = array();
            foreach ($return as $i => $v)
                if ($v != null && !empty($v))
                    $return2[$i] = $v;
            unset($return);
            $return = $return2;
        }

        return $return;
    }

    /**
     * Replaces this object's data with the given associative array
     *
     * @param string[][] $data This objects data in array form
     * @return boolean true on success, false on failure
     */
    public function fromArray($data) {
        if (!isset($data["dataType"]) || $data["dataType"] != "SNACDate")
            return false;

        if (isset($data['dbInfo']))
        {
            $this->setDBInfo($data['dbInfo']);
        }

        if (isset($data["fromDate"]))
            $this->fromDate = $data["fromDate"];
        else
            $this->fromDate = null;

        if (isset($data["fromDateOriginal"]))
            $this->fromDateOriginal = $data["fromDateOriginal"];
        else
            $this->fromDateOriginal = null;

        if (isset($data["fromType"]))
            $this->fromType = $data["fromType"];
        else
            $this->fromType = null;

        if (isset($data["fromBC"]))
            $this->fromBC = $data["fromBC"];
        else
            $this->fromBC = null;

        if (isset($data["fromRange"]))
            $this->fromRange = $data["fromRange"];
        else
            $this->fromRange = null;

        if (isset($data["toDate"]))
            $this->toDate = $data["toDate"];
        else
            $this->toDate = null;

        if (isset($data["toDateOriginal"]))
            $this->toDateOriginal = $data["toDateOriginal"];
        else
            $this->toDateOriginal = null;

        if (isset($data["toType"]))
            $this->toType = $data["toType"];
        else
            $this->toType = null;

        if (isset($data["toBC"]))
            $this->toBC = $data["toBC"];
        else
            $this->toBC = null;

        if (isset($data["toRange"]))
            $this->toRange = $data["toRange"];
        else
            $this->toRange = null;

        if (isset($data["isRange"]))
            $this->isRange = $data["isRange"];
        else
            $this->isRange = null;

        if (isset($data["note"]))
            $this->note = $data["note"];
        else
            $this->note = null;

        return true;

    }

    /**
     * Set whether or not this is a date range.
     * 
     * @param boolean $isRange Whether or not this is a range
     */
    public function setRange($isRange) {

        $this->isRange = $isRange;
    }

    /**
     * Set the from date in this object
     * 
     * @param string $original Original date
     * @param string $standardDate Standardized date
     * @param string $type Type of the date
     */
    public function setFromDate($original, $standardDate, $type) {

        list ($this->fromBC, $this->fromDate) = $this->parseBC($standardDate);
        $this->fromDateOriginal = $original;
        $this->fromType = $type;
    }
    
    /**
     * Set the fuzzy range around the from date
     * 
     * @param string $notBefore Beginning of fuzzy range
     * @param string $notAfter End of fuzzy range
     */
    public function setFromDateRange($notBefore, $notAfter) {
        $this->fromRange["notBefore"] = $notBefore;
        $this->fromRange["notAfter"] = $notAfter;
    }

    /**
     * Set the to date in this object
     * 
     * @param string $original Original date
     * @param string $standardDate Standardized date
     * @param string $type Type of the date
     */
    public function setToDate($original, $standardDate, $type) {

        list ($this->toBC, $this->toDate) = $this->parseBC($standardDate);
        $this->toDateOriginal = $original;
        $this->toType = $type;
    }

    /**
     * Set the fuzzy range around the to date
     * 
     * @param string $notBefore Beginning of fuzzy range
     * @param string $notAfter End of fuzzy range
     */
    public function setToDateRange($notBefore, $notAfter) {
        $this->toRange["notBefore"] = $notBefore;
        $this->toRange["notAfter"] = $notAfter;
    }

    /**
     * Set the single date in this object
     * 
     * @param string $original Original date
     * @param string $standardDate Standardized date
     * @param string $type Type of the date
     */
    public function setDate($original, $standardDate, $type) {

        $this->setFromDate($original, $standardDate, $type);
        $this->isRange = false;
    }
    
    /**
     * Set the fuzzy range around the date
     * 
     * @param string $notBefore Beginning of fuzzy range
     * @param string $notAfter End of fuzzy range
     */
    public function setDateRange($notBefore, $notAfter) {
        $this->setFromDateRange($notBefore, $notAfter);
    }

    /**
     * Set note about this date
     * 
     * @param string $note Note about this date
     */
    public function setNote($note) {
        $this->note = $note;
    }
    
    /**
     * Parse the given standard date string and determine if the date is BC and strip the date out if possible
     * 
     * @param string $standardDate The standard date
     * @return [boolean, string] Whether is BC or not and the standard date without negative.
     */
    public function parseBC($standardDate) {

        $tmp = $standardDate;
        $isBC = false;
        // If the standardDate starts with a minus sign, it is BC
        if (mb_substr($standardDate, 0, 1) == "-") {
            $isBC = true;
            $tmp = mb_substr($standardDate, 1);
        }
        return array (
                $isBC,
                $tmp
        );
    }
}
