/**
 * Wikipedia Image Gatherer
 *
 * Code that connects to wikimedia commons and gathers image information for the given Constellation,
 * if that constellation has an image in wikipedia.
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
jQuery.fn.exists = function(){return this.length>0;}

// This list of licenses was modified from the license list provided by:
// https://github.com/wmde/Lizenzhinweisgenerator/blob/master/js/app/LICENCES.js
var wikipediaLicenses = [
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


$(document).ready(function() {
    // Check that we're on the view page
    if ($('#wikipediaImage').exists() && $('#hasWikipediaLink').exists()){
        var shortArk = $('#ark').val().replace("http://n2t.net/ark:/99166/", "");
        var query = "SELECT ?_image WHERE {" +
            "?q wdt:P3430 \""+ shortArk +"\"." +
            "OPTIONAL { ?q wdt:P18 ?_image.}" +
            //"SERVICE wikibase:label {" +
            //    "bd:serviceParam wikibase:language \"en\" . " +
            //"}" +
        "}";

        // Try to ask Wikidata for the image URL
        $.get("https://query.wikidata.org/sparql?format=json&query="+query, null, function (data) {
            if (data.results && data.results.bindings
                    && data.results.bindings[0] && data.results.bindings[0]["_image"]) {
                var imageURL = data.results.bindings[0]["_image"].value;

                var parts = imageURL.split("/Special:FilePath/");
                var file = parts[1];
                var apiCallLink = "https://commons.wikimedia.org/w/api.php?format=json&action=query&prop=revisions&rvprop=content&origin=*&titles=File:" + file;

                $.ajax( {
                    url: apiCallLink,
                    data: null,
                    dataType: 'json',
                    type: 'POST',
                    headers: { 'Api-User-Agent': 'SNAC-Web-Client/1.0 (http://socialarchive.iath.virginia.edu/)' },
                    success: function(info) {
                        var caption = "<span class='wikipedia-caption'><a href=\"https://commons.wikimedia.org/wiki/File:"+file+"\">Image from Wikimedia Commons</a></span>";
                        var realAuthor = null;
                        var realLicense = null;
                        if (info.query && info.query.pages) {
                            // There should only be one
                            for ( key in info.query.pages) {
                                var page = info.query.pages[key];
                                if (page.revisions && page.revisions[0]["*"]) {
                                    wikidata = page.revisions[0]["*"];
                                    // We have the metadata
                                    var split1 = wikidata.split(/[Aa]uthor=/);
                                    if (split1[1]) {
                                        var split2 = split1[1].split("\n");
                                        var authors = split2[0].replace("[[", "").replace("]]", "").trim().split("|");
                                        authors.forEach(function(authorTmp) {
                                            var author = authorTmp.replace("{{", "").replace("}}", "").trim();
                                            if (realAuthor == null && !author.startsWith("User:")) {
                                                realAuthor = author.replace("creator:", "");
                                            } else if (realAuthor == null && author.startsWith("User:")) {
                                                realAuthor = "<a href=\"https://commons.wikimedia.org/wiki/\"" +
                                                author +">" + author.replace("User:", "") + "</a>";
                                            }
                                        });
                                        var split3 = split1[1].split(/license-header}}\s*==/);
                                        if (split3[1]) {
                                            var split4 = split3[1].trim().split("\n");
                                            var licenses = split4[0].replace("{{", "").replace("}}", "").trim().split("|");
                                            licenses.forEach(function(license) {
                                                if (realLicense == null) {
                                                    for (var i = 0; i < wikipediaLicenses.length; i++) {
                                                        if (license.toLowerCase().indexOf(wikipediaLicenses[i][0]) !== -1) {
                                                            if (wikipediaLicenses[i][2] == null) {
                                                                realLicense = wikipediaLicenses[i][1];
                                                            } else {
                                                                realLicense = "<a href=\""+
                                                                    wikipediaLicenses[i][2]+"\">"+
                                                                    wikipediaLicenses[i][1]+"</a>";
                                                            }
                                                            break;
                                                        }
                                                    }
                                                }
                                            });
                                        }
                                    }
                                }
                            }

                        }
                        if (realLicense != null && realAuthor != null) {
                            caption += "<br><span class='wikipedia-byline'>" + realAuthor + " - " + realLicense + "</span>";
                        }

                        var html = "<img src=\""+imageURL+"?width=300\"/><div>"+caption+"</div>";
                        $('#wikipediaImage').html(html);
                    }
                } );
            }
        });
    }
});
