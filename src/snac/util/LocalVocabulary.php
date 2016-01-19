<?php
namespace snac\util;

class LocalVocabulary implements \snac\util\Vocabulary {

    private $vocab = null;

    public function __construct() {
        $db = new \snac\server\database\DBUtil();
        $this->vocab = $db->getAllVocabulary();
    }


    public function getTermByValue($value, $type) {
        $term = new \snac\data\Term();
        foreach ($this->vocab as $k => $v) {
            if ($v["term"] == $value) {
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
