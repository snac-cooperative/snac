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
                            break;
                        case "languageDeclaration":
                            break;
                        case "maintenanceHistory":
                            break;
                        case "conventionDeclaration":
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
                                        break;
                                    case "nameEntry":
                                        break;
                                    default:
                                        $this->markUnknownTag(array($node->getName(), $desc->getName()), array($ident));
                                }
                            }
                            break;
                        case "description":
                            foreach ($this->getChildren($desc) as $desc2) {
                                $d2atts = $this->getAttributes($desc2);
                                switch($desc2->getName()) {
                                    case "existDates":
                                        break;
                                    case "place":
                                        break;
                                    case "localDescription":
                                        break;
                                    case "languageUsed":
                                        break;
                                    case "occupation":
                                        break;
                                    case "biogHist":
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
                                        break;
                                    case "resourceRelation":
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
                //print_r($node);
            } else {
                $this->markUnknownTag(array(), array($node->getName()));
                //print_r($node);
            }
        }
     
        echo "UNKNOWNS ";
        print_r($this->unknowns);
        return;
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