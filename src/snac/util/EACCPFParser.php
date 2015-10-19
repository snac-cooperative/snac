<?php
/**
 * EAC-CPF Parser File
 *
 * Contains the configuration options for this instance of the server
 *
 * License:
 *
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\util;

/**
 * EAC-CPF Parser
 * 
 * This class provides the utility to parser EAC-CPF XML files into PHP Identity constellations.
 * After parsing, it returns the \snac\data\Constellation object and provides a method to
 * access any tags or attributes from the file (including their values) that were not
 * understood by the parser.
 * 
 * @author Robbie Hott
 *
 */
class EACCPFParser {
    
    /**
     * @var string[] The list of namespaces in the document
     */
    private $namespaces;
    
    /**
     * @var string[] The list of unknown elements and their values
     */
    private $unknowns;
    
    /**
     * Parse a file into an identity constellation.
     * 
     * @param string $filename  Filename of the file to parse
     * @return \snac\data\Constellation The resulting constellation
     */
    public function parse_file($filename) {
        return $this->parse(file_get_contents($filename));
    }
    
    /**
     * Parse a string containing EAC-CPF XML into an identity constellation.
     * 
     * @param string $xmlText XML text to parse
     * @return \snac\data\Constellation The resulting constellation
     */
    public function parse($xmlText) {
        $xml = simplexml_load_string($xmlText);
        
        $identity = new \snac\data\Constellation();
        
        $this->unknowns = array();
        $this->namespaces = $xml->getNamespaces(true);
        
        foreach ($this->getChildren($xml) as $node) {
            if ($node->getName() == "control") {
                
                foreach ($this->getChildren($node) as $control) {
                    $catts = $this->getAttributes($control);
                    switch($control->getName()) {
                        case "recordId":
                            $identity->setArkID((string) $control);
                            $this->markUnknownAtt(array($node->getName(), $control->getName()), $catts);
                            break;
                        case "otherRecordId":
                            $identity->addOtherRecordID($catts["localType"], (string) $control);
                            break;
                        case "maintenanceStatus":
                            $identity->setMaintenanceStatus((string) $control);
                            $this->markUnknownAtt(array($node->getName(), $control->getName()), $catts);
                            break;
                        case "maintenanceAgency":
                            $agencyInfo = $this->getChildren($control);
                            for ($i = 1; $i < count($agencyInfo); $i++)
                                $this->markUnknownTag(array($node->getName, $control->getName()), array($agencyInfo[$i]));
                            $identity->setMaintenanceAgency((string) $agencyInfo[0]);
                            $this->markUnknownAtt(array($node->getName(), $control->getName(), $agencyInfo[0]->getName()), $this->getAttributes($agencyInfo[0]));
                            $this->markUnknownAtt(array($node->getName(), $control->getName()), $catts);
                            break;
                        case "languageDeclaration":
                            foreach ($this->getChildren($control) as $lang) {
                                $latts = $this->getAttributes($lang);
                                switch ($lang->getName()) {
                                    case "language":
                                        if (isset($latts["languageCode"])) {
                                            $code = $latts["languageCode"];
                                            unset($latts["languageCode"]);
                                        }
                                        $identity->setLanguage($code, (string)$lang);
                                        $this->markUnknownAtt(array($node->getName(), $control->getName(), $lang->getName()), $latts);
                                        break;
                                    case "script":
                                        if (isset($latts["scriptCode"])) {
                                            $code = $latts["scriptCode"];
                                            unset($latts["scriptCode"]);
                                        }
                                        $identity->setScript($code, (string)$lang);
                                        $this->markUnknownAtt(array($node->getName(), $control->getName(), $lang->getName()), $latts);
                                        break;
                                    default:
                                        $this->markUnknownTag(array($node->getName(), $control->getName()), $lang);
                                }
                            }
                            $this->markUnknownAtt(array($node->getName(), $control->getName()), $catts);
                            break;
                        case "maintenanceHistory":
                            foreach ($this->getChildren($control) as $mevent) {
                                $event = new \snac\data\MaintenanceEvent();
                                foreach ($this->getChildren($mevent) as $mev) {
                                    $eatts = $this->getAttributes($mev);
                                    switch ($mev->getName()) {
                                        case "eventType":
                                            $event->setEventType((string) $mev);
                                            break;
                                        case "eventDateTime":
                                            $event->setEventDateTime((string) $mev);
                                            break;
                                        case "agentType":
                                            $event->setAgentType((string) $mev);
                                            break;
                                        case "agent":
                                            $event->setAgent((string) $mev);
                                            break;
                                        case "eventDescription":
                                            $event->setEventDescription((string) $mev);
                                            break;
                                        default:
                                            $this->markUnknownTag(array($node->getName(), $control->getName(), $mevent->getName()), $mev);
                                    }
                                    $this->markUnknownAtt(array($node->getName(), $control->getName(),$mevent->getName(), $mev->getName()), $eatts);
                                }
                                $this->markUnknownAtt(array($node->getName(), $control->getName(),$mevent->getName()), $this->getAttributes($mevent));
                            
                                $identity->addMaintenanceEvent($event);
                            }
                            $this->markUnknownAtt(array($node->getName(), $control->getName()), $catts);
                            break;
                        case "conventionDeclaration":
                            $identity->setConventionDeclaration((string) $control);                            
                            $this->markUnknownAtt(array($node->getName(), $control->getName()), $catts);
                            break;
                        case "sources":
                            foreach ($this->getChildren($control) as $source) {
                                $satts = $this->getAttributes($source);
                                $identity->addSource($satts['type'], $satts['href']);
                            }
                            break;
                        default:
                            $this->markUnknownTag(array($node->getName()), array($control));
                    }
                }
            } elseif ($node->getName() == "cpfDescription") {
                
                foreach($this->getChildren($node) as $desc) {
                    $datts = $this->getAttributes($desc);
                    
                    switch($desc->getName()) {
                        case "identity":
                            foreach ($this->getChildren($desc) as $ident) {
                                $iatts = $this->getAttributes($ident);
                                switch($ident->getName()) {
                                    case "entityType":
                                        $identity->setEntityType((string)$ident);
                                        break;
                                    case "nameEntry":
                                        $nameEntry = new \snac\data\NameEntry();
                                        $nameEntry->setPreferenceScore($iatts["preferenceScore"]);
                                        unset($iatts["preferenceScore"]);
                                        foreach ($this->getChildren($ident) as $npart) {
                                            switch($npart->getName()){
                                                case "part":
                                                    $nameEntry->setOriginal((string)$npart);
                                                    break;
                                                case "alternativeForm":
                                                case "authorizedForm":
                                                    $nameEntry->addContributor($npart->getName(), (string) $npart);
                                                    break;
                                                default:
                                                    $this->markUnknownTag(array($node->getName(), $desc->getName(), $ident->getName()), array($npart));
                                            }
                                            $this->markUnknownAtt(array($node->getName(), $desc->getName(), $ident->getName(), $npart->getName()), $this->getAttributes($npart));
                                        }
                                        $identity->addNameEntry($nameEntry);
                                        break;
                                    default:
                                        $this->markUnknownTag(array($node->getName(), $desc->getName()), array($ident));
                                }
                                $this->markUnknownAtt(array($node->getName(), $desc->getName(), $ident->getName()), $iatts);
                            }
                            break;
                        case "description":
                            foreach ($this->getChildren($desc) as $desc2) {
                                $d2atts = $this->getAttributes($desc2);
                                switch($desc2->getName()) {
                                    case "existDates":
                                        foreach ($this->getChildren($desc2) as $dates) {
                                            $date = new \snac\data\SNACDate();
                                            if ($dates->getName() == "dateRange") {
                                                // Handle the date range
                                                $date->setRange(true);
                                                foreach ($this->getChildren($dates) as $dateTag) {
                                                    $dateAtts = $this->getAttributes($dateTag);
                                                    switch ($dateTag->getName()) {
                                                        case "fromDate":
                                                            if (((string) $dateTag) != null && ((string) $dateTag) != '') {
                                                                $date->setFromDate((string) $dateTag, $dateAtts["standardDate"], $dateAtts["localType"]);
                                                                unset($dateAtts["standardDate"]);
                                                                unset($dateAtts["localType"]);
                                                                $this->markUnknownAtt(array($node->getName(), $desc->getName(), $desc2->getName(), $dates->getName(), $dateTag->getName()), $dateAtts);
                                                            }
                                                            break;
                                                        case "toDate":
                                                            if (((string) $dateTag) != null && ((string) $dateTag) != '') {
                                                                $date->setToDate((string) $dateTag, $dateAtts["standardDate"], $dateAtts["localType"]);
                                                                unset($dateAtts["standardDate"]);
                                                                unset($dateAtts["localType"]);
                                                                $this->markUnknownAtt(array($node->getName(), $desc->getName(), $desc2->getName(), $dates->getName(), $dateTag->getName()), $dateAtts);
                                                            }
                                                            break;
                                                        default:
                                                            $this->markUnknownTag(array($node->getName(), $desc->getName(), $desc2->getName(), $dates->getName()), array($dateTag));
                                                    }
                                                }
                                                $identity->setExistDates($date);
                                            } elseif ($dates->getName() == "date") {
                                                // Handle the single date that appears
                                                $date->setRange(false);
                                                $dateAtts = $this->getAttributes($dates);
                                                $date->setDate((string) $dates, $dateAtts["standardDate"], $dateAtts["localType"]);
                                                unset($dateAtts["standardDate"]);
                                                unset($dateAtts["localType"]);
                                                $identity->setExistDates($date);
                                                $this->markUnknownAtt(array($node->getName(), $desc->getName(), $desc2->getName(), $dates->getName()), $dateAtts);
                                            } else {
                                                $this->markUnknownTag(array($node->getName(), $desc->getName(), $desc2->getName()), array($dates));
                                            }
                                        }
                                        break;
                                    case "place":
                                        //TODO
                                        break;
                                    case "localDescription":
                                        $subTags = $this->getChildren($desc2);
                                        $subTag = $subTags[0];
                                        for( $i = 1; $i < count($subTags); $i++) {
                                                $this->markUnknownTag(array($node->getName(), $desc->getName(), $desc2->getName()), array($subTags[$i]));
                                        }
                                        switch($d2atts["localType"]) {
                                            // Each of these is in a sub element
                                            case "http://socialarchive.iath.virginia.edu/control/term#AssociatedSubject":
                                                $identity->addSubject((string) $subTag);
                                                break;
                                            case "http://viaf.org/viaf/terms#nationalityOfEntity":
                                                $identity->setNationality((string) $subTag);
                                                break;
                                            case "gender":
                                                $identity->setGender((string) $subTag);
                                                break;
                                            default:
                                                $this->markUnknownTag(array($node->getName(), $desc->getName()), array($desc2));
                                        }
                                        break;
                                    case "languageUsed":
                                        foreach ($this->getChildren($desc2) as $lang) {
                                            $latts = $this->getAttributes($lang);
                                            switch ($lang->getName()) {
                                                case "language":
                                                    if (isset($latts["languageCode"])) {
                                                        $code = $latts["languageCode"];
                                                        unset($latts["languageCode"]);
                                                    }
                                                    $identity->setLanguageUsed($code, (string)$lang);
                                                    $this->markUnknownAtt(array($node->getName(), $desc->getName(), $desc2->getName(), $lang->getName()), $latts);
                                                    break;
                                                case "script":
                                                    if (isset($latts["scriptCode"])) {
                                                        $code = $latts["scriptCode"];
                                                        unset($latts["scriptCode"]);
                                                    }
                                                    $identity->setScript($code, (string)$lang);
                                                    $this->markUnknownAtt(array($node->getName(), $desc->getName(), $desc2->getName(), $lang->getName()), $latts);
                                                    break;
                                                default:
                                                    $this->markUnknownTag(array($node->getName(), $desc->getName(), $desc2->getName()), $lang);
                                            }
                                        }
                                        $this->markUnknownAtt(array($node->getName(), $desc->getName(), $desc2->getName()), $d2atts);
                                        break;
                                    case "occupation":
                                        foreach ($this->getChildren($desc2) as $occ) {
                                            $oatts = $this->getAttributes($occ);
                                            if ($occ->getName() == "term")
                                                $identity->addOccupation((string) $occ);
                                            else 
                                                $this->markUnknownTag(array($node->getName(), $desc->getName(), $desc2->getName()), array($occ));
                                            $this->markUnknownAtt(array($node->getName(), $desc->getName(), $desc->getName(), $occ->getName()), $oatts);
                                        }
                                        break;
                                    case "biogHist":
                                        $identity->addBiogHist($desc2->asXML());
                                        break;
                                    default:
                                        $this->markUnknownTag(array($node->getName(), $desc->getName()), array($desc2));
                                }
                            }
                            break;
                        case "relations":
                            foreach ($this->getChildren($desc) as $rel) {
                                $ratts = $this->getAttributes($rel);
                                switch($rel->getName()) {
                                    case "cpfRelation":
                                        //TODO
                                        break;
                                    case "resourceRelation":
                                        //TODO
                                        break;
                                    default:
                                        $this->markUnknownTag(array($node->getName(), $desc->getName()), array($rel));
                                }
                            }
                            break;
                        default:
                            $this->markUnknownTag(array($node->getName()), array($desc));
                    }
                }
            } else {
                $this->markUnknownTag(array(), array($node->getName()));
            }
        }
        return $identity;
    }
    
    /**
     * Get the tags and attributes that were not understood by this parser.
     * The resulting strings are 
     * <code>
     * full/path/to/tag :: value
     * full/path/to/@att :: value
     * </code>
     * 
     * @return string[] List of tags and attributes and their values
     */
    public function getMissing() {
        return $this->unknowns;
    }
    
    
    /**
     * Get the attributes for a given SimpleXMLElement, ignoring all namespaces.  This is a way
     * to get around the need to query for each namespace separately
     * 
     * @param SimpleXMLElement $element Element to query for attributes
     * @return string[] Attributes and values, attName => value
     */
    private function getAttributes($element) {
        $att = array();
        
        foreach ($element->attributes() as $k => $v)
            $att[$k] = (string)$v;
        
        foreach ($this->namespaces as $s => $n) {
            foreach ($element->attributes($n) as $k => $v)
                $att[$k] = (string)$v;
        }
        return $att;
    }
    
    /**
     * Get the children for a given SimpleXMLElement, ignoring all namespaces. This is a way to 
     * get around the need to query for each namespace separately.
     * 
     * @param SimpleXMLElement $element Element to query for children
     * @return SimpleXMLElement[] array of children elements from any namespace
     */
    private function getChildren($element) {
        $children = array();
        
        foreach ($element->children() as $k => $v) {
            //if (!isset($children[$k])) $children[$k] = array();
            //array_push($children, $v);
        }
        
        foreach ($this->namespaces as $s => $n) {
            foreach ($element->children($n) as $k => $v)
                //if (!isset($children[$k])) $children[$k] = array();
                array_push($children, $v);
        }
        return $children;
    }
    
    /**
     * Mark a tag or element as unknown to this parser.  
     * Adds the given information to the list of missing data.
     * 
     * @param string[] $xpath Ordered array of the path names down to the current element.
     * @param string[] $missing Array of missing elements (tag or att) as "name"=>"value" pairs.
     * @param boolean $isTag Flag to determine if the $missing is a list of tags or attributes.
     */
    private function markUnknowns($xpath, $missing, $isTag) {
        $path = implode("/", $xpath);
        $path .= "/";
        
        if (!$isTag) {
            $path .= "@";
        }
        
        foreach ($missing as $k => $v) {
            array_push($this->unknowns, $path . $k . " :: " . $v);
        }
    }
    
    /**
     * Mark an unknown attribute from the given path and element
     * 
     * @param string[] $xpath Ordered array of the path names down to just before the current element.
     * @param string[] $missing Array of missing tags as "name"=>"value" pairs.
     */
    private function markUnknownAtt($xpath, $missing) {
        $this->markUnknowns($xpath, $missing, false);
    }
    
    /**
     * Mark an unknown tag from the given path and element
     * 
     * @param string[] $xpath Ordered array of the path names down to the current tag.
     * @param string[] $missing Array of missing attributes as "name"=>"value" pairs.
     */
    private function markUnknownTag($xpath, $missing) {
        foreach ($missing as $m) {
            $this->markUnknowns($xpath, array($m->getName() => (string)$m), true);
            $this->markUnknowns(array_merge($xpath, array($m->getName())), $this->getAttributes($m), false);
        }
    }
}