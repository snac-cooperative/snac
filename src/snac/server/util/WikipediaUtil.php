<?php
/**
 * Wikipedia Utility Class File
 *
 * Contains class with wikipedia helper functions
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */


 namespace snac\server\util;

/**
 * Wikipedia Utility Class
 *
 * This class provides methods to interact with wikipedia.
 *
 * @author Robbie Hott
 *
 */
class WikipediaUtil {

    /**
     * @var \Monolog\Logger $logger the logger for this server
     */
    private $logger;

    /**
     * Default Constructor
     *
     * Constructor for the elastic search utility.  It connects to a logger and to elastic search.
     */
    public function __construct() {
        global $log;

        // create a log channel
        $this->logger = new \Monolog\Logger('WikipediaUtil');
        $this->logger->pushHandler($log);
    }

    /**
     * Get Image Info from Wikipedia
     *
     * Given an ark, this function queries wikidata and wikimedia commons for the image URL and image
     * metadata (author and license).  It returns an array of (hasImage:bool, imgURL:string, imgMeta:string[])
     * that is NOT associative.
     *
     * @param  string $ark  ARK id for the constellation to query
     * @return mixed[]      The array of information about the image [hasImage, url, metadata]
     */
    function getWikiImage($ark) {

        $wikipediaLicenses = [
             ['cc-by-sa-4.0', 'CC BY-SA 4.0', 'https://creativecommons.org/licenses/by-sa/4.0/legalcode'],
             ['cc-by-sa-3.0', 'CC BY-SA 3.0', 'https://creativecommons.org/licenses/by-sa/3.0/legalcode'],
             ['cc-by-sa-2.5', 'CC BY-SA 2.5', 'https://creativecommons.org/licenses/by-sa/2.5/legalcode'],
             ['cc-by-sa-2.0', 'CC BY-SA 2.0', 'https://creativecommons.org/licenses/by-sa/2.0/legalcode'],
             ['cc-by-sa-1.0', 'CC BY-SA 1.0', 'https://creativecommons.org/licenses/by-sa/1.0/legalcode'],
             ['cc-by-4.0', 'CC BY 4.0', 'https://creativecommons.org/licenses/by/4.0/legalcode'],
             ['cc-by-3.0', 'CC BY 3.0', 'https://creativecommons.org/licenses/by/3.0/legalcode'],
             ['cc-by-2.5', 'CC BY 2.5', 'https://creativecommons.org/licenses/by/2.5/legalcode'],
             ['cc-by-2.0', 'CC BY 2.0', 'https://creativecommons.org/licenses/by/2.0/legalcode'],
             ['cc-by-1.0', 'CC BY 1.0', 'https://creativecommons.org/licenses/by/1.0/legalcode'],
             ['cc-zero', 'CC0 1.0', 'https://creativecommons.org/publicdomain/zero/1.0/legalcode'],
             ['pd', 'Public Domain', null]];

        $imgURL = null;
        $metadata = null;
        $shortArk = str_replace("http://n2t.net/ark:/99166/", "", $ark);

        $query = "SELECT ?_image WHERE {" .
            "?q wdt:P3430 \"". $shortArk ."\"." .
            "OPTIONAL { ?q wdt:P18 ?_image.}" .
            "}";

        // Ask wikidata for the image URL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://query.wikidata.org/sparql?format=json&query=" . urlencode($query));
        curl_setopt($ch, CURLOPT_HTTPHEADER,
                array (
                        'Api-User-Agent: SNAC-Web-Client/1.0 (http://socialarchive.iath.virginia.edu/)'
                ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $imgdata = json_decode($response, true);
        if (is_array($imgdata) && isset($imgdata["results"]) && isset($imgdata["results"]["bindings"])
                && isset($imgdata["results"]["bindings"][0]) && isset($imgdata["results"]["bindings"][0]["_image"])) {
            // We have an image!
            $imgURL = $imgdata["results"]["bindings"][0]["_image"]["value"];
        }
        /** STOPED HERE **/
        if ($imgURL === null) {
            return array(false, null, null);
        }

        $parts = explode("/Special:FilePath/", $imgURL);
        if (count($parts) < 1) {
            return array(false, null, null);
        }
        $imgFileName = $parts[1];

        $metadata = array(
            "infoURL" => "https://commons.wikimedia.org/wiki/File:".$imgFileName,
            "info" => "Image from Wikimedia Commons"
        );


        // Ask wikimedia commons for the image information
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://commons.wikimedia.org/w/api.php?format=json&action=query&prop=revisions&rvprop=content&origin=*&titles=File:'. $imgFileName);
        curl_setopt($ch, CURLOPT_HTTPHEADER,
                array (
                        'Api-User-Agent: SNAC-Web-Client/1.0 (http://socialarchive.iath.virginia.edu/)'
                ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response === null) {
            return array(true, $imgURL, $metadata);
        }

        $imgdata = json_decode($response, true);
        if (is_array($imgdata) && isset($imgdata["query"]) && isset($imgdata["query"]["pages"])
                    && is_array($imgdata["query"]["pages"])) {
            // There should only be one here based on our query
            foreach ($imgdata["query"]["pages"] as $page) {
                if (isset($page["revisions"]) && isset($page["revisions"][0]) && isset($page["revisions"][0]["*"])) {
                    // We have metadata!
                    $wikidata = $page["revisions"][0]["*"];

                    // Parse out the author and license
                    $authorStr = null;
                    $licenseStr = null;
                    $stringret = preg_match_all('/[Aa]uthor=(.*?)\n/', $wikidata, $matches);
                    if (isset($matches[1][0]))
                        $authorStr = $matches[1][0];
                    $stringret = preg_match_all('/license-header}}\s*==\n(.*?)\n.*/', $wikidata, $matches);
                    if (isset($matches[1][0]))
                        $licenseStr = $matches[1][0];

                    if ($authorStr === null || $licenseStr === null) {
                        return array(true, $imgURL, $metadata);
                    }

                    // Clean out the author and license strings
                    $authorStr = trim(str_replace(array("[[","]]"), "", $authorStr));
                    $authors = explode("|", $authorStr);
                    foreach ($authors as $author) {
                        $tmpAuthor = trim(str_replace(array("{{","}}", "creator:", "Creator:"), "", $author));
                        $matchingURLFormat = preg_match_all('/\[(.*?) (.*?)\](.*)/', $tmpAuthor, $matches);
                        if (!isset($metadata["author"]) && $matchingURLFormat !== false && $matchingURLFormat > 0) {
                            $metadata["author"] = array ();
                            if (isset($matches[1]) && isset($matches[1][0]))
                                $metadata["author"]["url"] = $matches[1][0];
                            if (isset($matches[2]) && isset($matches[2][0]))
                                $metadata["author"]["name"] = $matches[2][0];

                        } else if (!isset($metadata["author"]) && stristr($tmpAuthor, "user:") === false) {
                            $metadata["author"] = array("name" => $tmpAuthor);
                        } else if (!isset($metadata["author"])) {
                            $metadata["author"] = array(
                                "name" => str_ireplace(array(":en:User:", "User:"), "", $tmpAuthor),
                                "url" => "https://commons.wikimedia.org/wiki/" . $tmpAuthor
                            );
                        }
                    }

                    foreach($wikipediaLicenses as $license) {
                        if (stristr($licenseStr, $license[0]) !== false) {
                            // The license type was found in the license string
                            if ($license[2] === null) {
                                $metadata["license"] = array(
                                    "name" => $license[1]
                                );
                            } else {
                                $metadata["license"] = array(
                                    "name" => $license[1],
                                    "url" => $license[2]
                                );
                            }
                            break;
                        }
                    }

                    return array(true, $imgURL, $metadata);
                }
            }
        }
        return array(false, null, null);

    }

}
