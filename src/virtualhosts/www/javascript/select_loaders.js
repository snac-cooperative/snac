/**
 * Select Box Loaders
 *
 * Functions that can be used to replace select boxes on the edit page with
 * pretty-formatted versions using JQuery and Select2
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

/**
 * Replace a select that is linked to a Vocabulary search
 *
 * Replaces the select with a select2 object capable of making AJAX queries
 *
 * @param  JQuery selectItem The JQuery item to replace
 * @param  string idMatch    ID string for the object on the page
 * @param  string type       The type of the vocabulary term
 * @param  int    minLength  The minimum required length of the autocomplete search
 */
function vocab_select_replace(selectItem, idMatch, type, minLength) {
    if (minLength === undefined) {
        minLength = 2;
    }

        if(selectItem.attr('id').endsWith(idMatch)
            && !selectItem.attr('id').endsWith("ZZ")) {
                selectItem.select2({
                    ajax: {
                        url: function() {
                            var query = snacUrl + "/vocabulary?type="+type+"&id=";
                                query += $("#constellationid").val()+"&version="+$("#version").val();
                                query += "&entity_type="+$("#entityType").val();
                                return query;
                        },
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return {
                                q: params.term,
                                page: params.page
                            };
                        },
                        processResults: function (data, page) {
                            return { results: data.results };
                        },
                        cache: true
                    },
                    width: '100%',
                    minimumInputLength: minLength,
                    allowClear: true,
                    theme: 'bootstrap',
                    placeholder: 'Select'
                });
            }
}

var geoPlaceSearchResults = null;

function geovocab_select_replace(selectItem, idMatch) {
    var minLength = 2;

    if(selectItem.attr('id').endsWith(idMatch)
        && !selectItem.attr('id').endsWith("ZZ")) {
            selectItem.select2({
                ajax: {
                    url: function() {
                        var query = snacUrl+"/vocabulary?type=geo_place&format=term";
                            query += "&entity_type="+$("#entityType").val();
                            return query;
                    },
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term,
                            page: params.page
                        };
                    },
                    processResults: function (data, page) {
                        if (data.results) {
                            geoPlaceSearchResults = data.results;
                            var selectResults = new Array();
                            data.results.forEach(function(result) {
                                selectResults.push({
                                    id: result.id,
                                    text: result.name + " (" + result.administrationCode + ", " + result.countryCode+ ")"
                                })
                            });
                            return {results: selectResults};
                        }
                        return { results: null };
                    },
                    cache: true
                },
                width: '100%',
                minimumInputLength: minLength,
                allowClear: true,
                theme: 'bootstrap',
                placeholder: 'Select'
            });
        }
}

var lastSourceSearchResults = null;

/**
 * Add <br> helper script
 *
 * Adds <br> to strings so that they can be shown to the user in HTML
 * after being input into a text-only field.
 */
function addbr(str) {
    if (typeof str !== 'undefined' && str !== null) {
        return (str + '').replace(/(\r\n|\n\r|\r|\n)/g, '<br>' + '$1');
    }
    return '';
}

/**
 * Replace a select that is linked to a Constellation Source search
 *
 * Replaces the select with a select2 object capable of making AJAX queries
 *
 * @param  JQuery selectItem The JQuery item to replace
 * @param  string idMatch    ID string for the object on the page
 */
function scm_source_select_replace(selectItem, idMatch) {
        if(selectItem.attr('id').endsWith(idMatch)
            && !selectItem.attr('id').endsWith("ZZ")) {
                selectItem.select2({
                    ajax: {
                        url: function() {
                            var query = snacUrl+"/vocabulary?type=ic_sources&id=";
                                query += $("#constellationid").val()+"&version="+$("#version").val();
                                query += "&entity_type="+$("#entityType").val();
                                return query;
                        },
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return {
                                q: params.term,
                                page: params.page
                            };
                        },
                        processResults: function (data, page) {
                            // Modify the results to be in the format we want
                            lastSourceSearchResults = data.results;
                            // need id, text
                            var results = new Array();
                            data.results.forEach(function(res) {
                                results.push({id: res.id, text: res.displayName});
                            });
                            return { results: results };
                        },
                        cache: true
                    },
                    width: '100%',
                    minimumInputLength: 0,
                    allowClear: true,
                    theme: 'bootstrap',
                    placeholder: 'Select'
                });

            selectItem.on('change', function (evt) {
                // TODO: Get the current selected value and update the well in the page to reflect it!
                // Note: all the selections are available in the global lastSourceSearchResults variable.
                var sourceID = $(this).val();
                var inPageID = $(this).attr("id");
                var idArray = inPageID.split("_");
                if (idArray.length >= 6) {
                    var i = idArray[5];
                    var j = idArray[4];
                    var shortName = idArray[1];
                    lastSourceSearchResults.forEach(function(source) {
                        if (source.id == sourceID) {
                            // Update the text of the source
                            if (typeof source.text !== 'undefined') {
                                $("#scm_" + shortName + "_source_text_" + j + "_" + i).html(addbr(source.text)).removeClass('hidden');
                                $("#scm_" + shortName + "_source_text_" + j + "_" + i).closest(".panel-body").removeClass('hidden');
                            } else {
                                $("#scm_" + shortName + "_source_text_" + j + "_" + i).text("").addClass('hidden');
                                $("#scm_" + shortName + "_source_text_" + j + "_" + i).closest(".panel-body").addClass('hidden');

                            }
                            // Update the URI of the source
                            if (typeof source.uri !== 'undefined')
                                $("#scm_" + shortName + "_source_uri_" + j + "_" + i).html('<a href="'+source.uri+'" target="_blank">'+source.uri+'</a>');
                            else
                                $("#scm_" + shortName + "_source_uri_" + j + "_" + i).html('');
                            // Update the URI of the source
                            if (typeof source.citation !== 'undefined')
                                $("#scm_" + shortName + "_source_citation_" + j + "_" + i).html(source.citation).removeClass('hidden');
                            else
                                $("#scm_" + shortName + "_source_citation_" + j + "_" + i).html('').addClass('hidden');
                        }
                    });
                }
            });

        }
}

/**
 * Replace a select that is linked to an affiliation search
 *
 * Replaces the select with a select2 object capable of making AJAX queries
 *
 * @param  JQuery selectItem The JQuery item to replace
 */
function affiliation_select_replace(selectItem) {
    $.get(snacUrl + "/vocabulary?type=affiliation").done(function(data) {
        var options = data.results;
        selectItem.select2({
            data: options,
            allowClear: true,
            theme: "bootstrap",
            placeholder: "Select Affiliation"
        });
    });
}

function reviewer_select_replace(selectItem) {
        if(selectItem != null) {
                selectItem.select2({
                    placeholder: "Reviewer Name or Email...",
                    ajax: {
                        url: function() {
                            var query = snacUrl+"/user_search?role=Reviewer";
                                return query;
                        },
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return {
                                q: params.term,
                                page: params.page
                            };
                        },
                        processResults: function (data, page) {
                            return { results: data.results };
                        },
                        cache: true
                    },
                    width: '100%',
                    minimumInputLength: 1,
                    allowClear: false,
                    theme: 'bootstrap'
                });
            }
}

function select_replace(selectItem, idMatch) {
        if(selectItem.attr('id').endsWith(idMatch)
            && !selectItem.attr('id').endsWith("ZZ")) {
                selectItem.select2({
                    allowClear: true,
                    theme: 'bootstrap'
                });
            }
}

function select_replace_simple(selectItem) {
    selectItem.select2({
        width: '100%',
        allowClear: true,
        theme: 'bootstrap'
    });
}



/**
 * Load Vocab Select Options
 *
 * Replaces the select with a select2 object preloaded with an array of options
 *
 * @param  JQuery selectItem             The JQuery item to replace
 * @param  string type                   The type of the vocabulary term
 * @param  string type                   Text placeholder for select
 * @param  string [useDescription=false] Use description instead of value for text field on return object
 */
function loadVocabSelectOptions(selectItem, type, placeholder, useDescription = false) {
    var url = "/vocabulary?type=" + type;
    if (useDescription == true) {
     url = url.concat("&use_description=true");
    }
    return $.get(snacUrl + url)
    .done(function(data) {
        var options = data.results;
        if (useDescription == true) {
          options = options.reduce(function(newOptions, option){
            var newElement = option;
            newElement["id"] = option["value"]
            newOptions.push(newElement);
            return newOptions;
          },[]);
        }
        selectItem.select2({
            data: options,
            allowClear: false,
            theme: 'bootstrap',
            placeholder: placeholder
        });
    });
}

function updateSameAsURI() {
  var id = this.id;
  var sequence = id.match(/_([0-9]+)$/)[1];
  var baseURI = $("#sameAs_baseuri_id_"+sequence).val();
  var uriId = $("#sameAs_uriid_"+sequence).val();
  $("#sameAs_uri_"+sequence).val(baseURI+uriId);
}

/**
 * Replace all the selects that exist on the page when the page has finished loading
 */
$(document).ready(function() {

    // Use select2 to display the select dropdowns
    // rather than the HTML default
    $("select").each(function() {
        if (typeof $(this).attr('id') !== typeof undefined && $(this).attr('id') !== false) {
            // Replace the subject selects
            vocab_select_replace($(this), "language_language_", "language_code", 1);

            // Replace the subject selects
            vocab_select_replace($(this), "language_script_", "script_code", 1);

            // Replace the subject selects
            vocab_select_replace($(this), "subject_", "subject", 4);

            // Replace the function selects
            vocab_select_replace($(this), "function_", "function", 4);

            // Replace the occupation selects
            vocab_select_replace($(this), "occupation_", "occupation", 4);

            // Replace the entityType select
            vocab_select_replace($(this), "entityType", "entity_type", 0);
        }
    });

    // Replace the Affiliation dropdowns, if one exists
    if ($("#affiliationid").exists())
        affiliation_select_replace($("#affiliationid"));

    // Replace the User search dropdown, if one exists
    if ($("#reviewersearchbox").exists())
        reviewer_select_replace($("#reviewersearchbox"));
});
