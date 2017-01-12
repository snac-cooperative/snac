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
 * Set the search position (page number)
 * @param int start The starting position
 */
function setSearchPosition(start) {
    $('#start').val(start);
}

/**
 * Search with AJAX and update the display of search results
 *
 * @return boolean false to play nice with the browser
 */
function searchAndUpdate() {
    if ($("#searchbox").val() == "" || $("#searchbox").val().length < 2) {
        $("#search-results-box").html("");
    } else {
        $.post("?command=quicksearch", $("#search_form").serialize(), function (data) {
            //var previewWindow = window.open("", "Preview");
            //previewWindow.document.write(data);

            var html = "";
            html += "<div class='list-group text-left' style='margin-bottom:0px'>";
            if (data.results.length > 0) {
                for (var key in data.results) {
                    html += "<a href='?command=search&q="+data.results[key].nameEntries[0].original+"' class='list-group-item'>"+data.results[key].nameEntries[0].original+"</a>";
                }
            }
            html += "</div>";

            $("#search-results-box").html(html);
        });
    }
    return false;
}


/**
 * Only load this script once the document is fully loaded
 */
$(document).ready(function() {
    var timeoutID = null;

    $('#searchbox').keyup(function() {
        clearTimeout(timeoutID);
        var $target = $(this);
        timeoutID = setTimeout(function() { setSearchPosition(0); searchAndUpdate(); }, 500);
    });
});
