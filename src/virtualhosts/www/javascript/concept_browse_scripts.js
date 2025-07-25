/**
 * SNAC Browse Scripts
 *
 * Scripts used for browsing identity constellations in the UI
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
jQuery.fn.exists = function(){return this.length>0;}

var count = 0;
var toCompare = new Array();
var recentResults = null;

var first = "";
var firstID = 0;
var last = "";
var lastID = 0;
var datatable;

function queryBrowse(position, term, category, id) {

    $.post(snacUrl+"/vocab_administrator/concept_browse_data?position="+position+"&term="+term+"&category="+category+"&concept_id="+id, null, function (data) {
        var results = [];
        datatable.clear();
        first = "";
        last = "";
        if (data.results.length > 0) {
            var list = "";
            recentResults = data.results;
            first = data.results[0].text;
            firstID = data.results[0].id;
            last = data.results[data.results.length - 1].text;
            lastID = data.results[data.results.length -1].id;
            for (var key in data.results) {
                result = data.results[key];
                var link = "<a target=\"_blank\" href=\""+snacUrl+"/vocab_administrator/concept/"+result.id+"\">"+result.text+"</a>";

                var row = new Array("", link, result.category);
                var node = datatable.row.add(row).draw().node();

                if (position == "middle" && key == 10)
                    $(node).css("font-weight", "bold").css("background-color", "#eeeeee");
            }
        } else {
            datatable.draw();
        }
        enableButtons();
    });
    return false;
}

function disableButtons() {
    $("#searchbutton").prop('disabled', true);
    $("#nextbutton").prop('disabled', true);
    $("#prevbutton").prop('disabled', true);
}

function enableButtons() {
    $("#nextbutton").prop('disabled', false);
    $("#prevbutton").prop('disabled', false);
    $("#searchbutton").prop('disabled', false);
}


$(document).ready(function() {

    // Use select2 to display the select dropdowns
    // rather than the HTML default

        $('select').each(function() {
            $(this).select2({
                minimumResultsForSearch: Infinity,
                allowClear: false,
                theme: 'bootstrap'
            });
        });

        var isEditor = $("#isEditor").val();

        // Load the table into a datatable
        datatable = $('.table').DataTable({ "sorting": false, "searching" : false, "paging" : false, "info" : false});

        // Get the first bit of data
        queryBrowse("after", "", "", 0, isEditor);

        // Set up the search/next/previous buttons
        $('#searchbutton').click(function() {
            disableButtons();
            return queryBrowse("middle", $("#searchbox").val(), $("#category").val(), 0, isEditor);
        });
        $('#nextbutton').click(function() {
            disableButtons();
            return queryBrowse("after", last, $("#category").val(), lastID, isEditor);
        });
        $('#prevbutton').click(function() {
            disableButtons();
            return queryBrowse("before", first, $("#category").val(), firstID, isEditor);
        });

});
