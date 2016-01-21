<?php
namespace snac\util;

class LocalVocabulary implements \snac\util\Vocabulary {

    private $vocab = null;

    public function __construct() {
        $db = new \snac\server\database\DBUtil();
        $vocab = $db->getAllVocabulary();
        // Fix up the vocabulary into a nested array
        foreach($vocab as $v) {
            if (!isset($this->vocab[$v["type"]]))
                $this->vocab[$v["type"]] = array();
            array_push($this->vocab[$v["type"]], array("id"=>$v["id"], "value"=>$v["value"]));
        }
    }


    public function getTermByValue($value, $type) {
        $term = new \snac\data\Term();
        if (isset($this->vocab[$type]))
            foreach ($this->vocab[$type] as $k => $v) {
                if ($v["value"] == $value) {
                    $term->setTerm($value);   
                    $term->setID($v["id"]);
                    return $term;
                }
            }
        return $term;
    }

    public function getTermByID($id, $type) {
            return null;
    }
}
