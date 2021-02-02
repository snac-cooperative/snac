<?php
/**
 * Resource ID Validator Class File
 *
 * Contains the resource ID validator class
 *
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\server\validation\validators;

use snac\server\validation\ValidationError;
/**
 * Resource ID Validator
 *
 * Validator that checks to ensure that all resources in a constellation have IDs and Versions
 * so that they correctly can be inserted into the database.
 *
 * * Any validation errors must be reported by calling the `addError()` method, giving a useful
 * message for the user (string) and the object (inheriting from AbstractData) that caused the
 * validation error.
 *
 * @author Robbie Hott
 *
 */
class ResourceValidator extends Validator {

    /**
     * Constructor
     *
     * Needed to instantiate errors
     */
    public function __construct() {
        $this->validatorName = "ResourceValidator";
        parent::__construct();
    }

    /**
     * Set Constellation root info
     *
     * Grab global constellation-level information that is needed in the validation
     *
     * @param \snac\data\Constellation $constellation constellation
     */
    public function setConstellation($constellation) {
        return;
    }

    /**
     * Validate the root of the constellation
     *
     * @param \snac\data\Constellation $constellation constellation root to validate
     * @return boolean true if valid, false otherwise
     */
    public function validateRoot($constellation) {
        return true;
    }

    /**
     * Validate a biog hist
     *
     * @param \snac\data\BiogHist $biogHist BiogHist to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateBiogHist($biogHist, $context=null) {
        return true;
    }

    /**
     * Validate a Convention Declaration
     *
     * @param \snac\data\ConventionDeclaration $cd ConventionDeclaration to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateConventionDeclaration($cd, $context=null) {
        return true;
    }

    /**
     * Validate a Date
     *
     * @param \snac\data\SNACDate $date SNACDate to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateDate($date, $context=null) {
        return true;
    }

    /**
     * Validate an Activity
     *
     * @param \snac\data\SNACActivity $fn SNACActivity to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateActivity($fn, $context=null) {
        return true;
    }

    /**
     * Validate a gender
     *
     * @param \snac\data\Gender $gender Gender to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateGender($gender, $context=null) {
        return true;
    }

    /**
     * Validate a general context
     *
     * @param \snac\data\GeneralContext $gc GeneralContext to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateGeneralContext($gc, $context=null) {
        return true;
    }

    /**
     * Validate a language
     *
     * @param \snac\data\Language $lang Language to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateLanguage($lang, $context=null) {
        return true;
    }

    /**
     * Validate a legal status
     *
     * @param \snac\data\LegalStatus $legalStatus LegalStatus to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateLegalStatus($legalStatus, $context=null) {
        return true;
    }

    /**
     * Validate a Maintenance Event
     *
     * @param \snac\data\MaintenanceEvent $event MaintenanceEvent to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateMaintenanceEvent($event, $context=null) {
        return true;
    }

    /**
     * Validate a Mandate
     *
     * @param \snac\data\Mandate $mandate Mandate to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateMandate($mandate, $context=null) {
        return true;
    }

    /**
     * Validate a Name Entry
     *
     * @param \snac\data\NameEntry $nameEntry NameEntry to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateNameEntry($nameEntry, $context=null) {
        return true;
    }

    /**
     * Validate a Nationality
     *
     * @param \snac\data\Nationality $nationality Nationality  to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateNationality($nationality, $context=null) {
        return true;
    }

    /**
     * Validate an Occupation
     *
     * @param \snac\data\Occupation $occupation Occupation to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateOccupation($occupation, $context=null) {
        return true;
    }

    /**
     * validate an Other Record ID
     *
     * @param \snac\data\SameAs $other OtherID  to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateOtherRecordID($other, $context=null) {
        return true;
    }

    /**
     * validate an EntityID
     *
     * @param \snac\data\EntityId $other EntityID  to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateEntityID($other, $context=null) {
        return true;
    }

    /**
     * Validate a Place
     *
     * @param \snac\data\Place $place Place to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validatePlace($place, $context=null) {
        return true;
    }

    /**
     * Validate a ConstellationRelation
     *
     * @param \snac\data\ConstellationRelation $relation ConstellationRelation  to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateRelation($relation, $context=null) {
        return true;
    }

    /**
     * Validate a Resource Relation
     *
     * @param \snac\data\ResourceRelation $relation ResourceRelation to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateResourceRelation($relation, $context=null) {
        if ($relation == null)
            return true;
        if ($relation->getResource() == null) {
            $this->addError("Missing resource object", $relation);
            return false;
        }
        $resource = $relation->getResource();
        if ($resource->getID() == null) {
            $this->addError("Resource missing ID or version", $relation);
            return false;
        }
        return true;
    }

    /**
     * Validate a SCM Object
     *
     * @param \snac\data\SNACControlMetadata $scm Metadata to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateSNACControlMetadata($scm, $context=null) {
        return true;
    }

    /**
     * Validate a Source
     *
     * @param \snac\data\Source $source Source to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateSource($source, $context=null) {
        return true;
    }

    /**
     * Validate a StructureOrGenealogy
     *
     * @param \snac\data\StructureOrGenealogy $sog StructureOrGenealogy to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateStructureOrGenealogy($sog, $context=null) {
        return true;
    }

    /**
     * Validate a Subject
     *
     * @param \snac\data\Subject $subject Subject to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateSubject($subject, $context=null) {
        return true;
    }

    /**
     * Validate a Contributor
     *
     * @param \snac\data\Contributor $contributor Contributor to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateContributor($contributor, $context=null) {
        return true;
    }


    /**
     * Validate a Term
     *
     * @param \snac\data\Term $term Term to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateTerm($term, $context=null) {
        return true;
    }

    /**
     * Validate a GeoTerm
     *
     * @param \snac\data\GeoTerm $geoTerm GeoTerm to validate
     * @param mixed[] $context optional Any context information needed for validation
     * @return boolean true if valid, false otherwise
     */
    public function validateGeoTerm($geoTerm, $context=null) {
        return true;
    }

}
