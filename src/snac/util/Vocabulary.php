<?php
namespace snac\util;

interface Vocabulary {

    public function getTermByValue($value, $type);

    public function getTermByID($id, $type);
}

