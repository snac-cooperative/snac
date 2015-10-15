<?php


class EACCPFParser {
    
    private $namespaces;
    
    private $xml;
    
    private $unknowns;
    
    public function parse_file($filename) {
        return $this->parse(file_get_contents($filename));
    }
    
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
                            $identity->setMaintenanceAgency((string) $control);
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
                                        //TODO
                                        break;
                                    case "place":
                                        //TODO
                                        break;
                                    case "localDescription":
                                        //TODO
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
    
    public function getMissing() {
        return $this->unknowns;
    }
    
    
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
    
    private function markUnknownAtt($xpath, $missing) {
        $this->markUnknowns($xpath, $missing, false);
    }
    
    private function markUnknownTag($xpath, $missing) {
        foreach ($missing as $m) {
            $this->markUnknowns($xpath, array($m->getName() => (string)$m), true);
            $this->markUnknowns(array_merge($xpath, array($m->getName())), $this->getAttributes($m), false);
        }
    }
}