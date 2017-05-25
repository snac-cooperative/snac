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
            $.post("?command=quicksearch", $("#search_form").serialize(), function (data) {
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
});
