<?php
/**
 * Clean the Vocabulary
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
// Include the global autoloader generated by composer
include "../vendor/autoload.php";
include "clean_vocabulary_sub.php";


use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;

// Set up the global log stream
$log = new StreamHandler(\snac\Config::$LOG_DIR . \snac\Config::$SERVER_LOGFILE, Logger::DEBUG);

// SNAC Postgres DB Connector
$db = new \snac\server\database\DatabaseConnector();

$vocab = array();

$vocQuery = $db->query("select id, type, value from
            vocabulary where type in ('subject', 'function', 'occupation');", array());
while($v = $db->fetchrow($vocQuery))
{
    if (!isset($vocab[$v["type"]]))
        $vocab[$v["type"]] = array();
    $vocab[$v["type"]][$v["id"]] = $v["value"];
}


$clean = array(
    "subject" => [],
    "function" => [],
    "occupation" => []);

foreach ($vocab["subject"] as $k => $v) {
    fixup($v, $k, $clean["subject"]);
}

foreach ($vocab["function"] as $k => $v) {
    fixup($v, $k, $clean["function"]);
}

foreach ($vocab["occupation"] as $k => $v) {
    fixup($v, $k, $clean["occupation"]);
}



usort($clean["subject"], function($a, $b) {
    return (count($a["originals"]) < count($b["originals"])) ? 1 : -1;
});
usort($clean["function"], function($a, $b) {
    return (count($a["originals"]) < count($b["originals"])) ? 1 : -1;
});

usort($clean["occupation"], function($a, $b) {
    return (count($a["originals"]) < count($b["originals"])) ? 1 : -1;
});

vote($clean["subject"]);
vote($clean["function"]);
vote($clean["occupation"]);

echo "<html><body><h1>Vocabulary Cleanup</h1>\n\nQuickLinks: <a href='#subjects'>Subjects</a> -  <a href='#occupations'>Occupations</a> -  <a href='#functions'>Functions</a>\n\n";

echo "<br><br>Current counts:<br>\n  Subject: ".count($vocab["subject"])."<br>\n  Functn:  ".count($vocab["function"])."<br>\n  Occptn:  ".count($vocab["occupation"])."<br>\n";
echo "  Total:   ". (count($vocab["subject"]) + count($vocab["function"]) + count($vocab["occupation"])) ."<br>\n<br>\n";
echo "Cleaned counts:<br>\n  Subject: ".count($clean["subject"])."<br>\n  Functn:  ".count($clean["function"])."<br>\n  Occptn:  ".count($clean["occupation"])."<br>\n";
echo "  Total:   ".(count($clean["subject"]) + count($clean["function"]) + count($clean["occupation"]))."<br>\n<br>\n";

echo "<a name='subjects'>\n";
print_htmllist($clean["subject"], "Subjects");
echo "<a name='occupations'>\n";
print_htmllist($clean["occupation"], "Occupations");
echo "<a name='functions'>\n";
print_htmllist($clean["function"], "Functions");
echo "</body></html>";


function print_htmllist($data, $title="", $as_string = false) {
    $str = "<h2>$title</h2>\n";
    $str .= "<dl>\n";

    foreach ($data as $d) {
        $count = count($d["originals"]);
        $str .= "    <dt><strong>{$d["chosen"]}</strong></dt>\n";
        $str .= "        <dd>\n";
        $cleans = array();
        foreach ($d["originals"] as $o) {
            array_push($cleans, $o["orig"]);
        }
        $str .= "          ";
        $str .= implode("<br/>          \n", $cleans);
        $str .= "        </dd>\n";
    }
    $str .= "</dl>\n";
    if ($as_string)
        return $str;
    else
        echo $str;
}
