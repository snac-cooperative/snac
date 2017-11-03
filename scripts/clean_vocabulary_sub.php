<?php
/**
 * Clean the Vocabulary
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

function vote(&$list) {

    foreach ($list as &$arr) {
        $tmp = array();
        $id = null;
        // pull out data from the original candidates
        foreach ($arr["originals"] as $s) {
            if (!isset($tmp[$s["clean"]]))
                $tmp[$s["clean"]] = 0;
            $tmp[$s["clean"]]++;
            if ($id === null)
                $id = $s["id"];
            else if ($s["id"] < $id)
                $id = $s["id"];

        }

        $max = 0;
        $consensus = "";
        foreach ($tmp as $k=>$v) {
            if ($v > $max) {
                $max = $v;
                $consensus = $k;
            }
        }

        $arr["chosen"] = $consensus;
        $arr["id"] = $id;
    }
}

function fixup($v, $id, &$arr) {

    $c = clean_string($v);
    $n = normalize_string($c);

    if (!isset($arr[$n]))
        $arr[$n] = array(
            "chosen" => "",
            "originals" => array());

    array_push($arr[$n]["originals"], [
        'id' => $id,
        'orig' => "$v",
        'clean' => $c
    ]);


}

/*
$x (space before and after or not)                                                                                                                                                                                 
$y (space before and after or not)                                                                                                                                                                                 
$z (space before and after or not)                                                                                                                                                                                 
$v (space before and after or not)                                                                                                                                                                                 
                                                                                                                                                                                                                   
|x (space before and after or not)                                                                                                                                                                                 
|y (space before and after or not)                                                                                                                                                                                 
|z (space before and after or not)                                                                                                                                                                                 
|v (space before and after or not)                                                                                                                                                                                 
                                                                                                                                                                                                                   
$|x (space before and after or not)                                                                                                                                                                                
$|y (space before and after or not)                                                                                                                                                                                
$|z (space before and after or not)                                                                                                                                                                                
$|v (space before and after or not)                                                                                                                                                                                
                                                                                                                                                                                                                   
| x or | y  or | z with space both before and after                                                                                                                                                                
                                                                                                                                                                                                                   
x with space both before and after (NOT y, as it occurs in Spanish phrases). “x” lowercase only, to avoid altering such as MAC OS X. There were no “ z “.       
*/

function clean_string($str) {
    $c = trim($str);
    $c = preg_replace("/\s\s*/", ' ', $c);
    $c = preg_replace("/^\./", '', $c);
    $c = preg_replace("/\.$/", '', $c);
    $c = preg_replace("/ -- /", '--', $c);
    $c = preg_replace("/ --/", '--', $c);
    $c = preg_replace("/-- /", '--', $c);
    $c = preg_replace("/ – /", '--', $c);
    $c = preg_replace("/–/", '--', $c);
    $c = preg_replace("/ — /", '--', $c);
    $c = preg_replace("/—/", '--', $c);
    $c = preg_replace("/ --- /", '--', $c);
    $c = preg_replace("/---/", '--', $c);
    $c = preg_replace("/ \|z /", '--', $c);
    $c = preg_replace("/ \|v /", '--', $c);
    $c = preg_replace("/\s*[\$\|]+[xyzv]\s*/", "--", $c);
    $c = preg_replace("/ \| [xyz] /", "--", $c);
    $c = preg_replace("/ x /", '--', $c);
    $c = preg_replace("/ z /", '--', $c);
    $c = preg_replace("/([0-9]+)\s*-\s*([0-9]+)/", "$1-$2", $c);
    $c = preg_replace("/([a-z,\.])\s*-\s*([A-Z])/", "$1--$2", $c);
    $c = trim($c);
    return $c;
}

function normalize_string($str) {
    $l = strtolower($str);

    $sp = preg_split("/--|,|\./", $l);
    foreach ($sp as $s) {
        if (substr($s, -1) == 's') {
            if (substr($s, -3) == 'ies')
                $l = str_replace($s, substr($s, 0, strlen($s) - 3) . "y", $l);
            else
                $l = str_replace($s, substr($s, 0, strlen($s) - 1), $l);
        }
    }
    //$n = preg_replace("/[^A-Za-z0-9 ]/", '', $l);
    $n = preg_replace("/[^A-Za-z0-9]/", '', $l);
    return $n;
}

function compute_histogram($data) {
    $hist = array();
    foreach ($data as $d) {
        $count = count($d["originals"]);
        if (!isset($hist[$count]))
            $hist[$count] = 0;
        $hist[$count]++;
    }
    ksort($hist, SORT_NUMERIC);

    return $hist;
}

function print_histogram($hist, $title="", $as_string=false) {
    $keys = array_keys($hist);
    rsort($keys);
    $k_max = $keys[0];

    $max = 0;
    foreach ($hist as $h)
        if ($h > $max)
            $max = $h;

    $step = $max / 60;

    $str = "";
    $str .= sprintf("%".(36 + round(strlen($title)/2))."s\n", $title);
    $str .= sprintf("%'-72s\n", "");
    $str .= sprintf("%10s| %34s%26s (%s)\n", "# Matched", "Histogram", "", "Count");
    $str .= sprintf("%'-72s\n", "");
    //foreach ($hist as $k=>$h) {
    for ($k=1; $k<=$k_max; $k++) {
        $h = 0;
        if (isset($hist[$k]))
            $h = $hist[$k];

        $line = "";
        for ($i = 1; $i <= $h; $i += $step)
            $line .= "*";

        $str .= sprintf("%10s| %-60s (%s)\n", $k, $line, $h);
    }

    if ($as_string)
        return $str;
    else
        echo $str;
}
