<?php
/**
 * Check VIAF for dead LoC links.  This script reads a CSV file of:
 *   ic_id, viafURL, locURL
 * and checks VIAF for the locURL's LoC ID.  If that ID does not exist
 * in VIAF, then this script will write that ic_id, LoC URL, and VIAF ID
 * to a file: viafloc_nomatch.csv. 
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


if (isset($argv[1]) && (($handle = fopen($argv[1], "r")) !== FALSE) && (($wh = fopen("viafloc_nomatch.csv", "w")) !== FALSE)) {
    // Loop over the data in the CSV file
    while (($data = fgetcsv($handle)) !== FALSE) {
        $icid = $data[0];
        $viafURL = $data[1];
        $locURL = $data[2];

        if ($locURL != "") {
            $viafID = substr($viafURL, strrpos($viafURL, '/') + 1);
            $locID = substr($locURL, strrpos($locURL, '/') + 1);

            $vid = viafGetRedirect($locID);
            if ($vid == false) {
                fputcsv($wh, [$icid, $locURL, $viafURL]);
            }
            usleep(200000);
        }
    }
    fclose($handle);
    fclose($wh);
}

