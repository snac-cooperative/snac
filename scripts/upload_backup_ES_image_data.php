<?php
/**
 *
 * Takes a json file of cached wikidata image metadata and reloads it into Elasticsearch.
 *
 *
 * @author Joseph Glass
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2021 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

 include "../vendor/autoload.php";


//  Cache image data before rebuild
// curl -X PUT "http://snac-dev.iath.virginia.edu:9200/development/_search" -H 'Content-Type: application/json' -d '
// {
//          "query" : {
//             "match" : {
//                 "hasImage" : "true"
//         }
//     },
//     "_source": ["id", "imageMeta", "imageURL"],
//     "size": 70000
// }' > full_image_meta.json


$fh = file_get_contents('full_image_meta.json');
$imageJson = json_decode($fh, true);

$imageInfos = $imageJson['hits']['hits'];


// $url = 'http://localhost:9200/snac/_update/';
$url = \snac\Config::$ELASTIC_SEARCH_URI . '/' . \snac\Config::$ELASTIC_SEARCH_BASE_INDEX . '/_update/';

foreach ($imageInfos as $imageInfo) {

    $id = $imageInfo['_id'];

    $data = ['hasImage' => true];
    $data['imageMeta']  = $imageInfo['_source']['imageMeta'];
    $data['imageURL']  = $imageInfo['_source']['imageURL'];
    $request = ['doc' => $data];
    $request = json_encode($request);

    //  Create a new cURL resource
    $ch = curl_init($url . $id);


    curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($ch);
    echo $result;
    curl_close($ch);


}