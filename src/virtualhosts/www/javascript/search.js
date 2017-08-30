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

    $('select').each(function() {
        $(this).select2({
            minimumResultsForSearch: Infinity,
            allowClear: false,
            theme: 'bootstrap',
            width: ''
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

    $(".compare-checkbox").each(function() {
        $(this).on("change", function() {
            showCompareOption();
        });
    });
});
