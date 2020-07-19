jQuery.fn.exists = function(){return this.length>0;}

var parser = new NameParser();

/**
 * Only load this script once the document is fully loaded
 */
$(document).ready(function() {

    $('#nameEntry_original_0').on("change", function() {
        
        parsed = parser.parsePerson($("#nameEntry_original_0").val());
        var html = "";
        var inputs = "";
        for (var key in parsed.parsed) {
            if (parsed.parsed[key] && parsed.parsed[key].length != 0) {
                html += "<span style='font-weight: bold;'>" + key + "</span>: " + parsed.parsed[key] + "&nbsp;&nbsp;";
                inputs += "<input type='hidden' name='nameEntry_namePart_i' value='" + key + "'><input type='hidden' name='nameEntry_namePart_value_i' value='" + parsed.parsed[key] + "'>\n";
            }
        }
        $('#nameEntry_info_0').html(html + inputs);
        console.log(parsed);
    });


    // Reconcile and then continue to create new button
    if($('#continue_and_reconcile').exists()) {
        $('#continue_and_reconcile').click(function(){

            if (!validateConstellation()) {
                return false;
            }
            // Open up the warning alert box and note that we are saving
            $('#notification-message').html("<p>Reconciling Constellation... Please wait.</p>");
            $('#notification-message').slideDown();

            // Save any XML editor contents back to their text areas before saving
            $("textarea[id*='_text_']").each(function() {
                var obj = $(this);
                if (obj.get(0).CodeMirror) {
                    obj.get(0).CodeMirror.save();
                }

            });
            $("textarea[id*='_source_']").each(function() {
                var obj = $(this);
                if (obj.get(0).CodeMirror) {
                    obj.get(0).CodeMirror.save();
                }

            });

            // Go through all the panels and update any dates
            $("div[id*='_panel_']").each(function() {
                var cont = $(this);
                // Don't look at any of the ZZ hidden panels
                if (cont.attr('id').indexOf("ZZ") == -1) {
                    var split = cont.attr('id').split("_");

                    // Split reveals a normal panel:
                    if (split.length == 3) {
                        var short = split[0];
                        var id = split[2];

                        updateDate(short, id);
                    }
                }
            });

            // Send the data back by AJAX call
            $.post(snacUrl+"/new_reconcile", $("#constellation_form").serialize(), function (data) {
                // Check the return value from the ajax. If success, then go to dashboard
                if (data.result == "success") {
                    // No longer in editing, save succeeded
                    setEditedFlag(false);
                    //somethingHasBeenEdited = false;

                    $('#notification-message').slideUp();

                    // We got the reconciliation results from the WebUI, which we now need to display
                    console.log(data);

                    var html = "";
                    html += "<p class='text-left'>Before continuing, please check the following Constellation matches.  If the "
                            + " Constellation you wish to suggest is below, please select the \"Suggest\" button for it rather than "
                            + " creating a duplicate.  In both cases, your submission will be forwarded to a SNAC Reviewer.</p>";
                    html += "<div class='list-group text-left' style='margin-bottom:0px'>";
                    if (data.results.length > 0) {
                        for (var key in data.results) {
                            html += "<div class='list-group-item'><div class='row'>";
                            html += "<div class='col-xs-8'><h4 class='list-group-item-heading'>"+data.results[key].nameEntries[0].original+"</h4>";
                            html += "<p class='list-group-item-text'>"+data.results[key].ark+"</p></div>";
                            html += "<div class='col-xs-4 list-group'>";
                            html += "<a class='list-group-item list-group-item-success' target='_blank' href='"+snacUrl+"/view/"+data.results[key].id+"?preview'><span  class='fa fa-eye' aria-hidden='true'></span> View</a></a>";
                            html += "<a class='list-group-item list-group-item-info' href='"+snacUrl+"/new_simple_suggest/"+data.results[key].id+"'><span  class='fa fa-pencil-square-o' aria-hidden='true'></span> Suggest Changes</a></div>";
                            html += "<input type='hidden' id='relationChoice_nameEntry_"+data.results[key].id+"' value='"+data.results[key].nameEntries[0].original.replace("'", "&#39;")+"'/>";
                            var arkID = "";
                            if (data.results[key].ark != null)
                                arkID = data.results[key].ark;
                            html += "<input type='hidden' id='relationChoice_arkID_"+data.results[key].id+"' value='"+arkID+"'/>";
                            html += "</div></div>";
                        }
                    } else {
                        html += "<a href='#' class='list-group-item list-group-item-danger' onClick='return false;'>No results found.</a>";
                    }
                    html += "</div>";

                    $('#reconcileModalContent').html(html);
                    $('#reconcilePane').modal();

                } else {
                    $('#notification-message').slideUp();
                    // Something went wrong in the ajax call. Show an error and don't go anywhere.
                    displayErrorMessage(data.error,data);
                }
            });

            return false;
        });
    }

    // Confirm Create New button
    if($('#confirm_create_new').exists()) {
        $('#confirm_create_new').click(function(){
            // Send the data back by AJAX call
            $("#constellation_form").submit();
            return false;
        });
    }


    // Cancel and go back
    if($('#cancel_back').exists()) {
        $('#cancel_back').click(function(){

            if (!confirm('Are you sure you want to cancel?')) {
                // Don't want to cancel, so exit!
                return;
            }

            $('#notification-message').html("<p>Cancelling...</p>");
            $('#notification-message').slideDown();
            setTimeout(function(){

                // Go to back to the previous page
                window.history.back()

            }, 1500);

            return false;
        });
    }

});

/**
* Validate Constellation
*
* Validates that there are no edited components to be saved with empty term fields.
*
* @param Boolean True if valid, else false.
*/
function validateConstellation() {
    var errorMessage = ""

    // Validate Term Fields
    var emptyTermCount = $(".edited-component select[id*='term']")
        .find("option:selected").filter( function() {
                return this.value == '';
            }).length;
    if (emptyTermCount) {
        var plural = emptyTermCount > 1 ? "s" : "";
        errorMessage += `<p>You have ${emptyTermCount} empty term field${plural}. Please enter a valid value for each term field and save again.</p>`
    }

    // Validate entityType and nameEntry
    var noNameEntryText = true;
    $("input[id^='nameEntry_original_']").each(function() {
        if ($(this).val() != "")
            noNameEntryText = false;
    });
    if ($("#entityType").val() == "" || noNameEntryText) {
        errorMessage += "<p>Entity Type and at least one Name Entry required in order to save.</p>"
    }

    if (errorMessage.length) {
        $("#error-message").html(errorMessage);
        setTimeout(function() {
            $("#error-message").slideDown();
        }, 500);
        setTimeout(function() {
            $("#error-message").slideUp();
        }, 10000);
        return false;
    } else {
        return true;
    }
}
