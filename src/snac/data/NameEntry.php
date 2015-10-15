<?php

namespace snac\data;

class NameEntry {

    private $original;

    private $preferenceScore;

    private $contributors;

    public function __construct() {

        $this->contributors = array ();
    }

    public function setOriginal($original) {

        $this->original = $original;
    }

    public function addContributor($type, $name) {

        array_push($this->contributors, array (
                "type" => $type,
                "contributor" => $name
        ));
    }

    public function setPreferenceScore($score) {

        $this->preferenceScore = $score;
    }
}