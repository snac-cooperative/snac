/**
 * Search Scripts
 *
 * Main search scripts for SNAC
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

jQuery.fn.exists = function(){return this.length>0;}


function searchFacet(facetName, value) {
    $("#faceted").bootstrapToggle('on');
    var newOption = new Option(value, value, false, true);  
    $("#"+facetName).append(newOption).trigger('change');
    goSearchAjax(0);
    return false;
} 

function goSearch(start) {
    return goSearchAjax(start);
}

function goSearchReload(start) {
    $("#start").val(start);
    $("#search_form").submit();
    return false;
}

function updateFacets(facet, facetName) {
    if (typeof facet !== "undefined" && facet.length > 0) {
        $("#faceted").bootstrapToggle('on');
        $("#facetedSearch").collapse("show");
        facet.forEach(function(term) {
            var newOption = new Option(term, term, false, true);  
            $("#"+facetName).append(newOption);
        });
    }
}

function setAjaxOptionsIntoPage(data) {
    $("#searchbox").val(data.term);
    $("#count").val(data.count);
    if (data.search_type == "advanced") {
        $("#advanced").bootstrapToggle('on');
        $("#advancedSearchText").collapse("show");
    }
    if (typeof data.biog_hist !== "undefined" && data.biog_hist == "true") {
        $("#biog_hist").bootstrapToggle('on');
    }
    if (typeof data.entityType !== "undefined" && data.entityType != "") {
        $("#entityType").val(data.entityType);
    }
    updateFacets(data.facets.subject, "subject");
    updateFacets(data.facets.occupation, "occupation");
    updateFacets(data.facets.function, "function");

}

function setAjaxResultsIntoPage(data) {
        $("#search_info").text("Found "+data.total+" results in "+data.timing+" ms.");
        $("#search_results").html("");
        $("#search_sidebar").removeClass("snac-hidden");

        // Update the search results list
        data.results.forEach(function(result) {
            var html = "<h4><a href=\""+snacUrl+"/view/"+result.id+"\">"+result.nameEntries[0].original+"</a></h4>"
                    + "<p class=\"identity-info\">"
                    + "    <span>"+result.ark+"</span>"
            if ($.inArray("holdingRepository", result.flags))
                html += "    <span>(Holding Repository)</span>";
            else
                html += "    <span>("+result.entityType.term+")</span>";
            html += "</p>";
            if (typeof result.biogHists == "undefined" || typeof result.biogHists[0] == "undefined" || typeof result.biogHists[0].text == "undefined")
                html += "<p class=\"missing\">No biographical history available for this identity.</p>";
            else 
                html += "<p>"+(result.biogHists[0].text).replace('/<citation(.|\n)*?<\\/citation>/','').replace(/<\/?[^>]+(>|$)/g, "").substring(0, 500).split(" ").slice(0, -1).join(" ") + "..." +"</p>";
            html += "<p class=\"final\"><input class=\"compare-checkbox\" type=\"checkbox\" value=\""+result.id+"\"> Select this Identity Constellation to compare</p>";

            $("#search_results").append(html);
            
        });
        $(".compare-checkbox").each(function() {
            $(this).on("change", function() {
                showCompareOption();
            });
        });

        // Update the pagination
        var html = "<nav class=\"text-center\"><ul class='pagination'>";
        if (data.page != 0) {
            html += "<li><a href='#' aria-label='Previous' onClick='return goSearch("+((data.page - 1) * data.count)+")'><span aria-hidden='true'>&lt;</span></a></li>";
        }

        for (var i = 1; i <= data.pagination; i++) {
            html += "<li "+(i-1 == data.page ? 'class="active"' : '')+"><a href=\"#\" onClick='return goSearch("+((i - 1) * data.count)+")'>"+i+"</a></li>";
        }

        if (data.page != data.pagination - 1) {
            html += "<li><a href='#' aria-label='Next' onClick='return goSearch("+((data.page + 1) * data.count)+")'><span aria-hidden='true'>&gt;</span></a></li>";
        }
        html += "</ul></nav>";
        $("#pagination").html(html);

        // Update the aggregations
        updateAggregations(data.aggregations.subject, data.facets.subject, "subject");
        updateAggregations(data.aggregations.occupation, data.facets.occupation, "occupation");
        updateAggregations(data.aggregations.function, data.facets.function,"function");
}

function goSearchAjax(start) {

    $("#start").val(start);
    $("#search_info").text("");
    $("#pagination").html("");
    $("#search_results").html("<p class=\"search-empty\">Refreshing Results... Please wait.</p>");
    $.post(snacUrl+"/search?format=json", $("#search_form").serialize(), function (data) {

        if (data.total == 0) {
            $("#search_results").html("<p class=\"search-empty\">No Results Found.</p>");
            $("#search_sidebar").addClass("snac-hidden");
            
            // set the hash to be empty
            document.location.hash = "";

            // empty out the local storage
            if (('localStorage' in window) && window['localStorage'] !== null) {
                localStorage.removeItem('snac_search');
            }

            return;
        }
        // set the hash so that browser back works
        document.location.hash = "results";

        // save the search to local storage
        if (('localStorage' in window) && window['localStorage'] !== null) {
            localStorage.setItem('snac_search', JSON.stringify(data));
        }
        
        setAjaxResultsIntoPage(data);
    });

    //$("#search_form").submit();
    return false;
}

function updateAggregations(agg, selected, aggtype) {
    var html = "";
    if (typeof agg !== "undefined") {
        agg.forEach(function(term) {
            var include = true;
            if (typeof selected !== "undefined") {
                if ($.inArray(term.term, selected) != -1) {
                    include = false;
                }
            }
            if (include)
                html += "<a href=\"#\" class=\"list-group-item\" onclick='searchFacet(\""+aggtype+"\", \""+term.term+"\"); return false;'>"+term.term+" ("+term.count+")</a>";
        });
    }
    $("#"+aggtype+"_agg").html(html);
}

function showCompareOption() {
    var constellation1 = null;
    var constellation2 = null;
    var count = 0;
    $(".compare-checkbox").each(function() {
        if (this.checked) {
            count++;
            if (constellation1 == null)
                constellation1 = $(this).val();
            else if (constellation2 == null)
                constellation2 = $(this).val();
        }
    });

    if (count > 0) {
       // Show the box with the options (disabled)
       $("#compareButton").prop("disabled", true).removeClass('btn-primary').addClass('btn-default');
       $("#compareBox").collapse("show");
    } else {
        // hide the box and disable the button
        $("#compareButton").prop("disabled", true).removeClass('btn-primary').addClass('btn-default');
        $("#compareBox").collapse("hide");
    }

    if (constellation1 != null && constellation2 != null && count == 2) {
        // Enable the option
        console.log("Can compare " + constellation1 + " and " + constellation2);
        $("#compare1").val(constellation1);
        $("#compare2").val(constellation2);
        $("#compareButton").prop("disabled", false).addClass('btn-primary').removeClass('btn-default');
    }
}
/**
 * Only load this script once the document is fully loaded
 */
$(document).ready(function() {
    var timeoutID = null;

    // If we had search results, then replay them into the page:
    if (document.location.hash == "#results" && ('localStorage' in window) && window['localStorage'] !== null) {
        var data = localStorage.getItem('snac_search');
        if (data !== null) {
            var json = JSON.parse(data);
            setAjaxOptionsIntoPage(json);
            setAjaxResultsIntoPage(json);
        }
    }
        

    $('.search-select').each(function() {
        $(this).select2({
            minimumResultsForSearch: Infinity,
            allowClear: false,
            theme: 'bootstrap',
            width: ''
        });
    });

    $('.facet-select').each(function() {
        var type = $(this).attr("id");
        $(this).select2({
            ajax: {
                url: function() {
                    var query = snacUrl + "/vocabulary?type=" + type;
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
                    data.results.forEach(function(result) {
                        result.id = result.value;
                    });
                    return { results: data.results };
                },
                cache: true
            },
            width: '100%',
            minimumInputLength: 1,
            theme: 'bootstrap'
        });
        $(this).on("change", function(e) {
            goSearch(0);
        });

    });

    $('#searchbox').autocomplete({
        minLength: 4,
        source: function(request, callback) {
            $.post(snacUrl+"/quicksearch", $("#search_form").serialize(), function (data) {
                var results = [];
                if (data.results.length > 0) {
                    for (var key in data.results) {
                        results[key] = data.results[key].nameEntries[0].original;
                    }
                }
                callback(results);
            });
        }
    });

    /**
     * If an advanced search button exists, then have it toggle the advanced search information box
     */
    if ($("#advanced").exists()) {
        $("#advanced").on("change", function() {
            if ( this.checked) {
                $("#advancedSearchText").collapse("show");
            } else {
                $("#advancedSearchText").collapse("hide");
            }
        });
    }

    /**
     * If an advanced search button exists, then have it toggle the advanced search information box
     */
    if ($("#faceted").exists()) {
        $("#faceted").on("change", function() {
            if ( this.checked) {
                $("#facetedSearch").collapse("show");
            } else {
                $("#facetedSearch").collapse("hide");
            }
        });
    }

    $(".compare-checkbox").each(function() {
        $(this).on("change", function() {
            showCompareOption();
        });
    });
});
