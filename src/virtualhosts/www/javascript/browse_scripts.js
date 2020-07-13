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

function queryBrowse(position, term, entityType, icid) {

    $.post(snacUrl+"/browse_data?position="+position+"&term="+term+"&entity_type="+entityType+"&ic_id="+icid, null, function (data) {
        var results = [];
        datatable.clear();
        first = "";
        last = "";
        if (data.results.length > 0) {
            var list = "";
            recentResults = data.results;
            first = data.results[0].name_entry;
            firstID = data.results[0].ic_id;
            last = data.results[data.results.length - 1].name_entry;
            lastID = data.results[data.results.length -1].ic_id;
            for (var key in data.results) {
                result = data.results[key];
                var entityType = "<span class=\"fa-stack fa-sm\" style=\"margin-top: -2px;\" title=\"Entity Type: "+result.entity_type+"\">";
                if (result.entity_type == "person") {
                     entityType += "<i class=\"fa fa-square fa-stack-2x\" style=\"color: #5CB9A6;\"></i>"
                          +   "<i class=\"fa fa-user fa-stack-1x fa-inverse\"></i>";
                } else if (result.entity_type == "corporateBody") {
                     entityType += "<i class=\"fa fa-square fa-stack-2x\" style=\"color: #7E72B5;\"></i>"
                          +   "<i class=\"fa fa-university fa-stack-1x fa-inverse\"></i>";
                } else if (result.entity_type == "family") {
                     entityType += "<i class=\"fa fa-square fa-stack-2x\" style=\"color: #EA6820;\"></i>"
                          +   "<i class=\"fa fa-users fa-stack-1x fa-inverse\"></i>";
                } else if (result.entity_type == "holdingRepository") {
                     entityType += "<i class=\"fa fa-square fa-stack-2x\" style=\"color: #888;\"></i>"
                          +   "<i class=\"fa fa-archive fa-stack-1x fa-inverse\"></i>";
                }
                entityType += "</span>";
                var link = "<a target=\"_blank\" href=\""+snacUrl+"/view/"+result.ic_id+"\">"+result.name_entry+"</a>";
                var checkbox = "<input class=\"compare-checkbox\" type=\"checkbox\" value=\""+result.ic_id+"\"";
                var checked = false;
                toCompare.forEach(function(obj){
                    if (obj.icid == result.ic_id)
                        checked = true;
                });
                if (checked)
                    checkbox += " checked";
                checkbox += ">";
                var row = new Array(checkbox, entityType + " " + link, result.resources, result.degree);
                var node = datatable.row.add(row).draw().node();

                if (position == "middle" && key == 10)
                    $(node).css("font-weight", "bold").css("background-color", "#eeeeee");
            }
        }
        enableButtons();
        enableCompareboxes();
        enableShiftSelectCheckboxes();
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

function clearSelected() {
    $("#shoppingCartButton").popover('destroy');
    toCompare = new Array();
    $(".compare-checkbox").each(function() {
        $(this).attr('checked', false);
    });
    showCompareOption();
}

function showCompareOption() {
    $(".compare-checkbox").each(function() {
        var checkbox = this;
        if (this.checked) {
            var found = false;
            toCompare.forEach(function(obj) {
                if (obj.icid == ($(checkbox).val()))
                    found = true;
            });
            if (!found) {
                var name = null;
                recentResults.forEach(function(res) {
                    if (res.ic_id == ($(checkbox).val()))
                        name = res.name_entry;
                });
                toCompare.push({'icid':($(checkbox).val()), 'name':name});
                $("#shoppingCartCount").text(toCompare.length);
            }
        } else {
            var idx = -1;
            toCompare.forEach(function(obj, id) {
                if (obj.icid == ($(checkbox).val()))
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
        console.log("Can compare " + constellation1.icid + " and " + constellation2.icid);
        $("#compare1").val(constellation1.icid);
        $("#compare2").val(constellation2.icid);
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
                    message: function() {
                        var message = "<p>This action automatically combines all data elements from all <strong>"+toCompare.length+"</strong> of the following selected Constellations to create a merged version.</p>";
                        message += "<ul class='list-group'>";
                        toCompare.forEach(function(obj) {
                            message += "<li class='list-group-item'>"+obj.name+"</li>";
                        });
                        message += "</ul>";
                        message += "<p><strong>This operation cannot be undone.  Are you sure you want to continue?</strong></p>";
                        return message;
                    },
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
                            toCompare.forEach(function(obj) {
                                form.append("<input type='hidden' value='"+obj.icid+"' name='constellationid"+i+"'/>");
                                i++;
                            });

                            form.attr('action', snacUrl+'/auto_merge').attr('method', 'post').attr('target', '_self');
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

    // Add maybe-same functionality
    if (toCompare.length > 1 && $("#addMaybeSameButton").exists()) {
        $("#addMaybeSameButton").prop("disabled", false);
        $('#addMaybeSameButton').off("click");
        $('#addMaybeSameButton').click(function() {
            // do auto merge
            if (toCompare.length >= 2) {

                bootbox.confirm({
                    title: "Add Maybe Same Assertions",
                    message: function() {
                        var message = "<p>This action denotes all <strong>"+toCompare.length+"</strong> of the following selected Constellations as potentially being the same.</p>";
                        message += "<ul class='list-group'>";
                        toCompare.forEach(function(obj) {
                            message += "<li class='list-group-item'>"+obj.name+"</li>";
                        });
                        message += "</ul>";
                        message += "<p><strong>This operation cannot be quickly undone.  Are you sure you want to continue?</strong></p>";
                        return message;
                    },
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
                            var i = 1;
                            var form = $("#merge_form");
                            form.html("");
                            form.append("<input type='hidden' value='"+toCompare.length+"' name='maybesamecount'/>");
                            toCompare.forEach(function(obj) {
                                form.append("<input type='hidden' value='"+obj.icid+"' name='constellationid"+i+"'/>");
                                i++;
                            });

                            $.post(snacUrl+"/add_maybesame", $("#merge_form").serialize(), function(data) {
                                if (data.result == "success") {
                                    clearSelected();
                                    $("#merge_form").html("");

                                    $('#success-message').html("<p>Sucessfully added Maybe-Same relationships.</p>");
                                    setTimeout(function(){
                                        $('#success-message').slideDown();
                                    }, 500);
                                    setTimeout(function(){
                                        $('#success-message').slideUp();
                                    }, 7000);
                                } else {
                                    $('#error-message').html("<p>An error occurred.</p>");
                                    setTimeout(function(){
                                        $('#error-message').slideDown();
                                    }, 500);
                                    setTimeout(function(){
                                        $('#error-message').slideUp();
                                    }, 7000);
                                }
                            });
                        }
                    }
                });

            }
        });
    } else {
        $("#addMaybeSameButton").prop("disabled", true);
        $('#addMaybeSameButton').off("click");
    }
}

function enableShiftSelectCheckboxes() {
    var $checkboxes = $(".compare-checkbox")
    var lastChecked = null;
    $checkboxes.click(function(e) {
        if(!lastChecked) {
            lastChecked = this;
            return;
        }    
        if(e.shiftKey) { 
            var start = $checkboxes.index(this);
            var end = $checkboxes.index(lastChecked);
            $checkboxes.slice(Math.min(start,end), Math.max(start,end) + 1).prop('checked', lastChecked.checked);
        }
        lastChecked = this;
    });
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


        $("#shoppingCartButton").click(function() {
            if ($("#shoppingCartButton").next('div.popover:visible').length) {
                $("#shoppingCartButton").popover('destroy');
            } else {
                var html = "<p>You have the following Constellations selected:</p>";
                html += "<ul class='list-group'>";
                toCompare.forEach(function(obj) {
                    html += "<li class='list-group-item'>"+obj.name+"</li>";
                });
                html += "</ul>";

                html += "<p class='text-center'><button id='shoppingCartEmpty' class='btn btn-default'>";
                html += "<i class='fa fa-trash' aria-hidden='true'></i> Clear All Selections</button></p>";

                $("#shoppingCartButton").popover({
                    placement: "bottom",
                    title: "Selected Constellations",
                    trigger: "manual",
                    html: true,
                    content: html
                });

                $("#shoppingCartButton").popover('show');
                $("#shoppingCartEmpty").click(clearSelected);
            }
        });

        enableCompareboxes();

});
