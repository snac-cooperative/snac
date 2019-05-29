<?php
/**
 * Check VIAF for LoC links to update.  This script reads a CSV file of:
 *   ic_id, viafURL, locURL
 * and checks VIAF for the locURL's LoC ID.  If that ID exists in VIAF, 
 * then this script will query VIAF for additional SameAs links.  By default
 * it prints out the SameAs links to StdOut (terminal) and saves the new
 * and old VIAF IDs to the file viafloc_matching.csv, including a DRIFT flag
 * if the VIAF ID has changed for the given LoC ID. (That is, the LoC ID has
 * drifted from one VIAF cluster to another).
 *
 * For the full license, see the LICENSE file in the repository root
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2017 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

function viafGetRedirect($id) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://www.viaf.org/viaf/sourceID/LC|".$id);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $lines = preg_split('/$\R?^/m', $response);
    $viafID = false;
    foreach ($lines as $line) {
        if (strstr($line, "Location:") !== false) {
            $viafID = trim(str_replace("Location: http://viaf.org/viaf/", "", $line));
            return $viafID;
        }
    }

    return $viafID;
}

function viafGetLinks($id) {
    

    $url = "http://www.viaf.org/viaf/".$id."/justlinks.json";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $arr = json_decode($response, true);
    return formatLinks($arr);
}

function formatLinks($viafList) {
    $urlFormats = [
        "BNF" => ["service" => "BNF",
                  "url" => "(ID)"
              ],
        "LC" => ["service" => "LC",
                  "url" => "http://id.loc.gov/authorities/names/(ID)"
              ],
        "WKP" => ["service" => "Wikidata",
                  "url" => "https://www.wikidata.org/wiki/(ID)"
              ],
        "Wikipedia" => ["service" => "Wikipedia",
                  "url" => "(ID)"
              ]
    ];

    $return = [];


    foreach ($viafList as $k => $entry) {
        if (isset($urlFormats[$k])) {
            foreach ($entry as $id) {
                array_push($return, [
                    "service" => $urlFormats[$k]["service"],
                    "id" => $id,
                    "url" => str_replace("(ID)", $id, $urlFormats[$k]["url"])
                ]);
            }
        }
    }
    return $return;
}



//$vid = viafGetRedirect("n85171077");
//echo $vid . "\n\n";
//print_r(viafGetLinks($vid));

echo "\n\n";

if (isset($argv[1]) && (($handle = fopen($argv[1], "r")) !== FALSE) && (($wh = fopen("viafloc_match.csv", "w")) !== FALSE)) {
    // Loop over the data in the CSV file
    while (($data = fgetcsv($handle)) !== FALSE) {
        $icid = $data[0];
        $viafURL = $data[1];
        $locURL = $data[2];

        if ($locURL != "") {
            $viafID = substr($viafURL, strrpos($viafURL, '/') + 1);
            $locID = substr($locURL, strrpos($locURL, '/') + 1);

            $vid = viafGetRedirect($locID);
            $list = viafGetLinks($vid);
            // add VIAF by default
            array_push($list, [
                "service" => "VIAF",
                "id" => $vid,
                "url" => "http://viaf.org/viaf/$vid"
            ]);

            $drift = "";
            if ($vid != $viafID)
                $drift = "DRIFT";

            // Print out links
            echo "$icid: $viafURL\n";
            print_r($list);
            echo "\n";

            fputcsv($wh, [$icid, $locURL, $viafURL, $vid, $drift]);
            usleep(100000);
            die();
        }
    }
    fclose($handle);
    fclose($wh);
}

