<?php

/**
 * SNAC Control Metadata Object class.
 *
 * Contains the snac control metadata class.
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
 * SNAC Control Metadata class
 *
 * This class contains the snac control metadata block associated with any other piece of
 * data.  It is included in the AbstractData class and is therefore allowed on
 * any piece of data that inherits from the abstract class.
 *
 * @author Robbie Hott
 */
class SNACControlMetadata extends AbstractData {


    /**
     * @var \snac\data\Source the citation source object
     */
    private $citation;

    /**
     * @var string sub citation, the exact location in the source
     */
    private $subCitation;

    /**
     * Source Data
     *
     * We put original strings in here. For example an original place string.
     *
     * @var string source data, the "as recorded" data: exactly what was found in the source
     */
    private $sourceData;

    /**
     * @var \snac\data\Term the descriptive rule associated with formulating the data
     */
    private $descriptiveRule;

    /**
     * @var \snac\data\Language the language associated with this citation/data
     */
    private $language;

    /**
     * @var string human-readable note associated with this data/metadata/citation
     */
    private $note;

    /**
     * @var string human-readable string representing the object pointed to by this SCM
     */
    private $object;

    /**
     * Constructor
     *
     * The associative array $data varies depending on the object being created, but is always consistent
     * between toArray() and fromArray() for each object. By and large, outside an object, nothing cares about
     * the internal structure of the $data array. The standard way to create one of these objects is to
     * instantiate with no $data, and then use the getters to set the object's properties.
     *
     * @param string[][] $data optional Associative array of data to fill this
     *                                  object with.
     */
    public function __construct($data = null) {

        $this->setMaxDateCount(0);
        parent::__construct($data);

        if ($data != null && is_array($data))
            $this->fromArray($data);

        // Metadata should never have metadata
        unset ($this->snacControlMetadata);
    }


    /**
     * Required method to convert this data object to an array
     *
     * @param boolean $shorten optional Whether or not to include null/empty components
     * @return string[][] This object as an associative array
     */
    public function toArray($shorten = true) {
        $return = array(
            "dataType" => "SNACControlMetadata",
            "citation" => $this->citation == null ? null : $this->citation->toArray($shorten),
            "subCitation" => $this->subCitation,
            "sourceData" => $this->sourceData,
            "descriptiveRule" => $this->descriptiveRule == null ? null : $this->descriptiveRule->toArray($shorten),
            "language" => $this->language == null ? null : $this->language->toArray($shorten),
            "object" => $this->object,
            "note" => $this->note
        );
        $return = array_merge($return, parent::toArray($shorten));

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
     * Required method to import an array into this data object
     *
     * @param string[][] $data The data for this object in an associative array
     */
    public function fromArray($data) {
        if (!isset($data["dataType"]) || $data["dataType"] != "SNACControlMetadata")
            return false;

        parent::fromArray($data);
        // Metadata should never have metadata
        unset ($this->snacControlMetadata);

        if (isset($data["language"]) && $data["language"] != null)
            $this->language = new Language($data["language"]);
        else
            $this->language = null;

        if (isset($data["citation"]) && $data["citation"] != null)
            $this->citation = new Source($data["citation"]);
        else
            $this->citation = null;

        if (isset($data["descriptiveRule"]) && $data["descriptiveRule"] != null)
            $this->descriptiveRule = new Term($data["descriptiveRule"]);
        else
            $this->descriptiveRule = null;

        if (isset($data["note"]))
            $this->note = $data["note"];
        else
            $this->note = null;

        if (isset($data["subCitation"]))
            $this->subCitation = $data["subCitation"];
        else
            $this->subCitation = null;

        if (isset($data["sourceData"]))
            $this->sourceData = $data["sourceData"];
        else
            $this->sourceData = null;

        return true;
    }

    /**
     * Get the citation
     *
     * @return \snac\data\Source the citation source
     */
    public function getCitation() {
        return $this->citation;
    }

    /**
     * Get the subcitation
     *
     * @return string sub citation, the exact location in the source
     */
    public function getSubCitation() {
        return $this->subCitation;
    }

    /**
     * Get the source data
     *
     * @return string source data, the "as recorded" data: exactly what was found in the source
     */
    public function getSourceData() {
        return $this->sourceData;
    }

    /**
     * Get the descriptive rule
     *
     * @return \snac\data\Term the descriptive rule associated with formulating the data
     */
    public function getDescriptiveRule() {
        return $this->descriptiveRule;
    }

    /**
     * Get the language
     *
     * @return \snac\data\Language the language associated with this citation/data
     */
    public function getLanguage() {
        return $this->language;
    }

    /**
     * Get the human readable note
     *
     * @return string human-readable note associated with this data/metadata/citation
     */
    public function getNote() {
        return $this->note;
    }

    /**
     * Get the human-readable object string
     *
     * @return string The human-readable object string this SCM is attached to
     */
    public function getObject() {
        return $this->object;
    }

    /**
     * Set the human-readable object string
     *
     * @param string $object The human-readable string of the object to which this SCM is attached
     */
    public function setObject($object) {
        $this->object = $object;
    }

    /**
     * Set the citation
     *
     * @param \snac\data\Source $citation the citation source
     */
    public function setCitation($citation) {
        $this->citation = $citation;
    }

    /**
     * Set the subcitation
     *
     * @param string $subCitation sub citation, the exact location in the source
     */
    public function setSubCitation($subCitation) {
        $this->subCitation = $subCitation;
    }

    /**
     * Set the source data
     *
     * @param string $sourceData source data, the "as recorded" data: exactly what was found in the source
     */
    public function setSourceData($sourceData) {
        $this->sourceData = $sourceData;
    }

    /**
     * Set the descriptive rule
     *
     * @param \snac\data\Term $rule the descriptive rule associated with formulating the data
     */
    public function setDescriptiveRule($rule) {
        $this->descriptiveRule = $rule;
    }

    /**
     * Set the language
     *
     * @param \snac\data\Language $language the language associated with this citation/data
     */
    public function setLanguage($language) {
        $this->language = $language;
    }

    /**
     * Set the human readable note
     *
     * @param string $note human-readable note associated with this data/metadata/citation
     */
    public function setNote($note) {
        $this->note = $note;
    }

    /**
     *
     * {@inheritDoc}
     *
     * @param \snac\data\SNACControlMetadata $other Other object
     * @param boolean $strict optional Whether or not to check id, version, and operation
     * @return boolean true on equality, false otherwise
     *
     * @see \snac\data\AbstractData::equals()
     */
    public function equals($other, $strict = true) {

        if ($other == null || ! ($other instanceof \snac\data\SNACControlMetadata))
            return false;

        if (! parent::equals($other, $strict))
            return false;

        if ($this->getSubCitation() != $other->getSubCitation())
            return false;
        if ($this->getSourceData() != $other->getSourceData())
            return false;
        if ($this->getNote() != $other->getNote())
            return false;

        // Citations are special. They are Source objects, but they may not be completely filled in.  In fact, the only thing we may know
        // about them within an SCM is their ID.  So, for equality, we may only check ID.
        if (($this->getCitation() !== null && $other->getCitation() === null) ||
            ($this->getCitation() === null && $other->getCitation() !== null) ||
            ($this->getCitation() !== null && $other->getCitation() !== null && $this->getCitation()->getID() !== $other->getCitation()->getID()))
            return false;

        if (($this->getDescriptiveRule() != null && ! $this->getDescriptiveRule()->equals($other->getDescriptiveRule())) ||
                 ($this->getDescriptiveRule() == null && $other->getDescriptiveRule() != null))
            return false;

        if (($this->getLanguage() != null && ! $this->getLanguage()->equals($other->getLanguage(), $strict)) ||
                 ($this->getLanguage() == null && $other->getLanguage() != null))
            return false;

        return true;
    }

    /**
     * Cleanse all sub-elements
     *
     * Removes the ID and Version from sub-elements and updates the operation to be
     * INSERT.  If the operation is specified by the parameter, this method
     * will use that operation instead of INSERT.
     *
     * @param string $operation optional The operation to use (default is INSERT)
     */
    public function cleanseSubElements($operation=null) {
        $newOperation = \snac\data\AbstractData::$OPERATION_INSERT;
        if ($operation !== null) {
            $newOperation = $operation;
        }

        parent::cleanseSubElements($newOperation);

        if (isset($this->language) && $this->language != null) {
            $this->language->setID(null);
            $this->language->setVersion(null);
            $this->language->setOperation($newOperation);
            $this->language->cleanseSubElements($newOperation);
        }
    }
}
