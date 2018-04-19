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
 * @author Robbie Hott
 *
 */
class SNACDate extends AbstractData {

    /**
     * Begin Date
     *
     * From EAC-CPF tag(s):
     *
     * * dateRange/fromDate/@standardDate
     * * date/@standardDate
     *
     * @var string Begin date (if range)
     */
    private $fromDate;

    /**
     * Original begin date string
     *
     * From EAC-CPF tag(s):
     *
     * * dateRange/fromDate/
     * * date/
     *
     * @var string Original string given for the from date
     */
    private $fromDateOriginal;

    /**
     * Begin type
     *
     * From EAC-CPF tag(s):
     *
     * * dateRange/fromDate/@localType
     * * date/@localType
     *
     * @var \snac\data\Term Type of the from date, a full Term object.
     */
    private $fromType;

    /**
     * Begin date is in BC
     *
     * From EAC-CPF tag(s):
     *
     * * dateRange/fromDate/@standardDate (if negative)
     * * date/@standardDate (if negative)
     *
     * @var boolean If the from date is BC
     */
    private $fromBC = false;

    /**
     * Range of fuzziness for begin date
     *
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
     * End date
     *
     * From EAC-CPF tag(s):
     *
     * * dateRange/toDate/@standardDate
     *
     * @var string End date (if range)
     */
    private $toDate;

    /**
     * End date original string
     *
     * From EAC-CPF tag(s):
     *
     * * dateRange/toDate/
     *
     * @var string Original string given for the to date
     */
    private $toDateOriginal;

    /**
     * End date type
     *
     * From EAC-CPF tag(s):
     *
     * * dateRange/toDate/@localType
     *
     * @var \snac\data\Term Type of the "to date", a full Term object.
     */
    private $toType;

    /**
     * End date is in BC
     *
     * From EAC-CPF tag(s):
     *
     * * dateRange/toDate/@standardDate (if negative)
     *
     * @var boolean If the to date is BC
     */
    private $toBC = false;

    /**
     * Fuzzy range on End date
     *
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
    private $isRange = false;

    /**
     * Descriptive Note
     *
     * From EAC-CPF tag(s):
     *
     * * dateRange/descriptiveNote
     * * date/descriptiveNote
     *
     * @var string Note about this date
     */
    private $note;

    /**
     * Get the machine-parseable from date
     *
     * @return string Begin date (if range)
     */
    function getFromDate()
    {
        return $this->fromDate;
    }

    /**
     * Get the originally-entered human from date
     *
     * There intentionally is no setFromDateOriginal(). Instead call setFromDate() or setDate()
     *
     * @return string Original string given for the from date
     */
    function getFromDateOriginal()
    {
        return $this->fromDateOriginal;
    }

    /**
     * Get from date type
     *
     * Get the type of the from date, such as "Birth"
     *
     * @return \snac\data\Term The type for the "from date", a full Term object.
     */
    function getFromType()
    {
        return $this->fromType;
    }


    /**
     * Is From date BC?
     *
     * Tells if the from date is a BC date. True if in BC, false otherwise.
     *
     * @return boolean If the from date is BC
     */
    function getFromBC()
    {
        return $this->fromBC;
    }

    /**
     * Get Begin date fuzzy range
     *
     * Get the fuzziness range on the from date, if it exists
     *
     * @return string[] From date range, array ("notBefore" => null, "notAfter" => null);
     */
    function getFromRange()
    {
        return $this->fromRange;
    }

    /**
     * Get End date
     *
     * Get the machine-actionable to/end date
     *
     * @return string End date (if range)
     */
    function getToDate()
    {
        return $this->toDate;
    }

    /**
     * Get the human-entered end date
     *
     * There intentionally is no setToDateOriginal(). Instead call setToDate().
     *
     * @return string Original string given for the to date
     */
    function getToDateOriginal()
    {
        return $this->toDateOriginal;
    }

    /**
     * Get End date type
     *
     * Get the type of the end date, such as "Death"
     *
     * @return \snac\data\Term Type of the "to date", a full Term object.
     */
    function getToType()
    {
        return $this->toType;
    }

    /**
     * Is To date BC?
     *
     * Tells whether the end date is in BC. True if in BC, false otherwise.
     *
     * @return boolean If the to date is BC
     */
    function getToBC()
    {
        return $this->toBC;
    }

    /**
     * Get End date fuzzy range
     *
     * Get the fuzziness range for the end date, if it exists
     *
     * @return string[] To date range, array ("notBefore" => null, "notAfter" => null);
     */
    function getToRange()
    {
        return $this->toRange;
    }

    /**
     * Is this date a range
     *
     * Tells whether this SNACDate object contains a range (from-to) or just a single date (from)
     *
     * Something has broken a couple of times round tripping isRange to the database and back. Make absolutely
     * certain that we only return true and false by only returning true and false based on the truthiness of
     * $this->isRange rather than its actual value.
     *
     * @return boolean If this SNACDate object contains a range or a single date
     */
    function getIsRange()
    {
        return $this->isRange;
        /*
         * if ($this->isRange)
         * {
         *     return true;
         * }
         * return false;
         */
    }

    /**
     * Get descriptive note for this date
     *
     *
     * @return string Note about this date
     */
    function getNote()
    {
        return $this->note;
    }

    /**
     * To String
     *
     * Converts this object to a human-readable summary string.  This is enough to identify
     * the object on sight, but not enough to discern programmatically.
     *
     * @return string A human-readable summary string of this object
     */
    function toString() {
        $str = "Date: ";
        if ($this->fromDate)
            $str .= $this->fromDate;
        else
            $str .= $this->fromDateOriginal;

        if ($this->fromType)
            $str .= " (".$this->fromType->getTerm().")";

        if ($this->isRange) {
            $str .= " - ";
            if ($this->toDate)
                $str .= $this->toDate;
            else
                $str .= $this->toDateOriginal;

        if ($this->toType)
            $str .= " (".$this->toType->getTerm().")";
        }

        return $str;
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
            "fromType" => $this->fromType == null ? null : $this->fromType->toArray($shorten),
            "fromBC" => $this->fromBC,
            "fromRange" => $this->fromRange,
            "toDate" => $this->toDate,
            "toDateOriginal" => $this->toDateOriginal,
            "toType" => $this->toType == null ? null : $this->toType->toArray($shorten),
            "toBC" => $this->toBC,
            "toRange" => $this->toRange,
            "isRange" => $this->isRange,
            "note" => $this->note
        );

        $return = array_merge($return, parent::toArray($shorten));

        // Shorten if necessary
        if ($shorten) {
            $return2 = array();
            foreach ($return as $i => $v)
                if ($v != null && !empty($v))
                    $return2[$i] = $v;

            if (isset($return2["fromRange"]) && $return2["fromRange"]["notBefore"] == null && $return2["fromRange"]["notAfter"] == null)
                unset($return2["fromRange"]);
            if (isset($return2["toRange"]) && $return2["toRange"]["notBefore"] == null && $return2["toRange"]["notAfter"] == null)
                unset($return2["toRange"]);

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

        parent::fromArray($data);

        if (isset($data["fromDate"]))
            $this->fromDate = $data["fromDate"];
        else
            $this->fromDate = null;

        if (isset($data["fromDateOriginal"]))
            $this->fromDateOriginal = $data["fromDateOriginal"];
        else
            $this->fromDateOriginal = null;

        if (isset($data["fromType"]) && $data["fromType"] != null)
            $this->fromType = new \snac\data\Term($data["fromType"]);
        else
            $this->fromType = null;

        if (isset($data["fromBC"]))
            $this->fromBC = $data["fromBC"];
        else
            $this->fromBC = false;

        if (isset($data["fromRange"]))
            $this->fromRange = $data["fromRange"];
        else
            $this->fromRange = array("notBefore"=> null, "notAfter"=> "");

        if (isset($data["toDate"]))
            $this->toDate = $data["toDate"];
        else
            $this->toDate = null;

        if (isset($data["toDateOriginal"]))
            $this->toDateOriginal = $data["toDateOriginal"];
        else
            $this->toDateOriginal = null;

        if (isset($data["toType"]) && $data["toType"] != null)
            $this->toType = new \snac\data\Term($data["toType"]);
        else
            $this->toType = null;

        if (isset($data["toBC"]))
            $this->toBC = $data["toBC"];
        else
            $this->toBC = false;

        if (isset($data["toRange"]))
            $this->toRange = $data["toRange"];
        else
            $this->toRange = array("notBefore"=> null, "notAfter"=>null);

        if (isset($data["isRange"]))
            $this->isRange = $data["isRange"];
        else
            $this->isRange = false;

        if (isset($data["note"]))
            $this->note = $data["note"];
        else
            $this->note = null;

        return true;

    }

    /**
     * Set whether or not this is a date range.
     *
     * Test the truthiness of $isRange because this keeps breaking. Postgres and php have a disagreement on
     * what is true, and even using DBUtil functions to convert has not solved the problem
     *
     * @param boolean $isRange Whether or not this is a range
     */
    public function setRange($isRange) {
        $this->isRange = $isRange;

        /*
         * if ($isRange !== true && $isRange !== false)
         * {
         *     printf("\nSNACDate.php isRange problem: $isRange\n");
         * }
         * if ($isRange)
         * {
         *     $this->isRange = true;
         * }
         * $this->isRange = false;
         */
    }

    /**
     * Set the from date BC
     *
     * Set the before common epoch boolean property of a from date.
     *
     * @param boolean $arg True for BC dates, false otherwise.
     */
    public function setFromBC($arg)
    {
        $this->fromBC = false;
        if ($arg)
        {
            $this->fromBC = true;
        }
    }

    /**
     * Set the to date BC
     *
     * Set the before common epoch boolean property of a to date.
     *
     * @param boolean $arg True for BC dates, false otherwise.
     */
    public function setToBC($arg)
    {
        $this->toBC = false;
        if ($arg)
        {
            $this->toBC = true;
        }
    }

    /**
     * Set the date BC
     *
     * Set the before common epoch boolean property of a single date. It is likely that implementation of a
     * single date vs date range is a from date and $isRange = false. Regardless of implementation, this
     * function changes the BC for a single date.
     *
     * Current implementation is to call setFromBC().
     *
     * Use this when you want to set (or clear) the BC boolean (fromBC) after calling setDate().
     *
     * @param boolean $arg True for BC dates, false otherwise.
     */
    public function setBC($arg)
    {
        return $this->setFromBC($arg);
    }

    /**
     * Set the "from date"
     *
     * Set the from date in this object, as well as setting some related private variables.
     *
     * If you need to set BC, but you have BC in a separate field, call this with the non-BC annotated date,
     * then call setFromBC(), setToBC() or setBC().
     *
     * @param string $original Original date
     * @param string $standardDate Standardized date
     * @param \snac\data\Term $type Type of the date, a full Term object.
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
     * Set the "to date"
     *
     * Set the to date in this object, as well as setting some related private variables.
     *
     * @param string $original Original date
     * @param string $standardDate Standardized date
     * @param \snac\data\Term $type Type of the date, a full Term object.
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
     * Set the "single date"
     *
     * Set this object as a single date. Single date is current the from date, and the date isRange is set
     * to false. This sets several private variables.
     *
     * @param string $original Original date
     * @param string $standardDate Standardized date
     * @param \snac\data\Term $type Type of the date, a full Term object.
     */
    public function setDate($original, $standardDate, $type) {
        $this->setFromDate($original, $standardDate, $type);
        $this->isRange = false;
    }

    /**
     * Set the fuzzy range around the single date
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
     * Parse a date for BC
     *
     * Parse the given standard date string and determine if the date is BC and strip the date out if possible.
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

    /**
     *
     * {@inheritDoc}
     *
     * @param \snac\data\SNACDate $other Other object
     * @param boolean $strict optional Whether or not to check id, version, and operation
     * @param boolean $checkSubcomponents optional Whether or not to check SNACControlMetadata, nameEntries contributors & components
     * @return boolean true on equality, false otherwise
     *
     * @see \snac\data\AbstractData::equals()
     */
    public function equals($other, $strict = true, $checkSubcomponents = true) {

        if ($other == null || ! ($other instanceof \snac\data\SNACDate))
            return false;

        if (! parent::equals($other, $strict, $checkSubcomponents))
            return false;

        if ($this->getFromBC() != $other->getFromBC())
            return false;
        if ($this->getFromDate() != $other->getFromDate())
            return false;
        if ($this->getFromDateOriginal() != $other->getFromDateOriginal())
            return false;
        if ($this->getToBC() != $other->getToBC())
            return false;
        if ($this->getToDate() != $other->getToDate())
            return false;
        if ($this->getToDateOriginal() != $other->getToDateOriginal())
            return false;
        if ($this->getIsRange() != $other->getIsRange())
            return false;
        if ($this->getNote() != $other->getNote())
            return false;


        // handle ranges
        if ($this->getFromRange() != null && $other->getFromRange() != null) {
            if ($this->getFromRange()["notAfter"] != $other->getFromRange()["notAfter"] ||
                    $this->getFromRange()["notBefore"] != $other->getFromRange()["notBefore"])
                return false;
        } else if (($this->getFromRange() == null && $other->getFromRange() != null) ||
           ($this->getFromRange() != null && $other->getFromRange() == null)) {
            return false;
        }

        if ($this->getToRange() != null && $other->getToRange() != null) {
            if ($this->getToRange()["notAfter"] != $other->getToRange()["notAfter"] ||
                    $this->getToRange()["notBefore"] != $other->getToRange()["notBefore"])
                return false;
        } else if (($this->getToRange() == null && $other->getToRange() != null) ||
                ($this->getToRange() != null && $other->getToRange() == null)) {
            return false;
        }


        if (($this->getFromType() != null && ! $this->getFromType()->equals($other->getFromType())) ||
                 ($this->getFromType() == null && $other->getFromType() != null))
            return false;
        if (($this->getToType() != null && ! $this->getToType()->equals($other->getToType())) ||
                 ($this->getToType() == null && $other->getToType() != null))
            return false;

        return true;
    }
}
