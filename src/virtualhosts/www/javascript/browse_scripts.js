/**
 * SNAC Browse Scripts
 *
 * Scripts used for browsing identity constellations in the UI
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
jQuery.fn.exists = function(){return this.length>0;}

var count = 0;
var toCompare = new Array();

var first = "";
var firstID = 0;
var last = "";
var lastID = 0;
var datatable;

function queryBrowse(position, term, entityType, icid) {

    $.post("?command=browse_data&position="+position+"&term="+term+"&entity_type="+entityType+"&ic_id="+icid, null, function (data) {
        var results = [];
        datatable.clear();
        first = "";
        last = "";
        if (data.results.length > 0) {
            var list = "";
            first = data.results[0].name_entry;
            firstID = data.results[0].ic_id;
            last = data.results[data.results.length - 1].name_entry;
            lastID = data.results[data.results.length -1].ic_id;
            for (var key in data.results) {
                result = data.results[key];
                var link = "<a target=\"_blank\" href=\"?command=view&constellationid="+result.ic_id+"\">"+result.name_entry+"</a>";
                var checkbox = "<input class=\"compare-checkbox\" type=\"checkbox\" value=\""+result.ic_id+"\"";
                var checked = false;
                toCompare.forEach(function(icid){
                    if (icid == result.ic_id)
                        checked = true;
                });
                if (checked)
                    checkbox += " checked";
                checkbox += ">";
                var row = new Array(checkbox, link, result.entity_type, result.resources, result.degree);
                var node = datatable.row.add(row).draw().node();

                if (position == "middle" && key == 10)
                    $(node).css("font-weight", "bold").css("background-color", "#eeeeee");
            }
        }
        enableButtons();
        enableCompareboxes();
    });
    return false;
}

function enableCompareboxes() {
    $(".compare-checkbox").each(function() {
        $(this).on("change", function() {
            showCompareOption();
        });
    });
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

function showCompareOption() {
    $(".compare-checkbox").each(function() {
        var checkbox = this;
        if (this.checked) {
            var found = false;
            toCompare.forEach(function(icid) {
                if (icid == ($(checkbox).val()))
                    found = true;
            });
            if (!found) {
                toCompare.push(($(checkbox).val()));
            }
        } else {
            var idx = -1;
            toCompare.forEach(function(icid, id) {
                if (icid == ($(checkbox).val()))
                    idx = id;
            });
            if (idx >= 0) {
                toCompare.splice(idx, 1);
            }
        }
    });

    if (toCompare.length > 0) {
       // Show the box with the options (disabled)
       $("#compareButton").prop("disabled", true).removeClass('btn-primary').addClass('btn-default');
       $("#compareBox").collapse("show");
    } else {
        // hide the box and disable the button
        $("#compareButton").prop("disabled", true).removeClass('btn-primary').addClass('btn-default');
        $("#compareBox").collapse("hide");
    }

    if (toCompare.length == 2) {
        var constellation1 = toCompare[0];
        var constellation2 = toCompare[1];
        // Enable the option
        console.log("Can compare " + constellation1 + " and " + constellation2);
        $("#compare1").val(constellation1);
        $("#compare2").val(constellation2);
        $("#compareButton").prop("disabled", false).addClass('btn-primary').removeClass('btn-default');
    }

    // Auto-merge functionality
    if (toCompare.length > 1 && $("#autoMergeButton").exists()) {
        $("#autoMergeButton").prop("disabled", false);
        $('#autoMergeButton').off("click");
        $('#autoMergeButton').click(function() {
            // do auto merge
            if (toCompare.length >= 2) {

                bootbox.confirm({
                    title: "Automatic Merge",
                    message: "This action automatically combines all data elements from all <strong>"+toCompare.length+"</strong> selected Constellations to create a merged version.  This operation cannot be undone.  Are you sure you want to continue?",
                    buttons: {
                        cancel: {
                            label: '<i class="fa fa-times"></i> Cancel'
                        },
                        confirm: {
                            label: '<i class="fa fa-check"></i> Confirm'
                        }
                    },
                    callback: function (result) {
                        if (result) {
                            $("#please_wait_modal").modal("show");
                            var i = 1;
                            var form = $("#merge_form");
                            form.html("");
                            form.append("<input type='hidden' value='"+toCompare.length+"' name='mergecount'/>");
                            toCompare.forEach(function(icid) {
                                form.append("<input type='hidden' value='"+icid+"' name='constellationid"+i+"'/>");
                                i++;
                            });

                            form.attr('action', '?command=auto_merge').attr('method', 'post').attr('target', '_self');
                            form.submit();
                        }
                    }
                });

            }
        });
    } else {
        $("#autoMergeButton").prop("disabled", true);
        $('#autoMergeButton').off("click");
    }
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

        // Load the table into a datatable
        datatable = $('.table').DataTable({ "sorting": false, "searching" : false, "paging" : false, "info" : false});

        // Get the first bit of data
        queryBrowse("after", "", "", 0);

        // Set up the search/next/previous buttons
        $('#searchbutton').click(function() {
            disableButtons();
            return queryBrowse("middle", $("#searchbox").val(), $("#entityType").val(), 0);
        });
        $('#nextbutton').click(function() {
            disableButtons();
            return queryBrowse("after", last, $("#entityType").val(), lastID);
        });
        $('#prevbutton').click(function() {
            disableButtons();
            return queryBrowse("before", first, $("#entityType").val(), firstID);
        });

        enableCompareboxes();

});

