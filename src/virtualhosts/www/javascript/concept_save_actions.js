/**
 * Concept Edit-Page Save Actions
 *
 * Contains code that handles what happens in the GUI when
 * the user clicks any save button.
 *
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2025 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

jQuery.fn.exists = function(){return this.length>0;}

/**
 * Only load this script once the document is fully loaded
 */
$(document).ready(function() {

    /**
     * The following are Edit Page save actions
     */

    // Save and Continue button
    if($('#save_and_continue').exists()) {
        $('#save_and_continue').click(function(){

            if (!validateConcept()) {
                return false;
            }
        	// If nothing has changed, alert the user and do nothing
        	if (somethingHasBeenEdited == false) {
                $('#notification-message').html("<p>No new changes to save.</p>");
                setTimeout(function(){
                    $('#notification-message').slideDown();
                }, 500);
                setTimeout(function(){
                    $('#notification-message').slideUp();
                }, 7000);
        		return false;
        	}

            // Open up the warning alert box and note that we are saving
            $('#notification-message').html("<p>Saving Concept... Please wait.</p>");
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

            // Send the data back by AJAX call
            $.post(snacUrl+"/vocab_administrator/concept_save", $("#concept_form").serialize(), function (data) {
                // Check the return value from the ajax. If success, then alert the
                // user and make appropriate updates.
                if (data.result == "success") {
                    // If there were any new elements pre-save that have now been
                    // saved, put those IDs into the form
                	for (var key in data.updates) {
                		console.log("updating: " + key + " to value " + data.updates[key]);
                		$('#' + key).val(data.updates[key]);
                	}

                    // Clear the save message
                    $('#savemessage').val("");

                	// Remove the global operation, if one is set
                	$('#operation').val("");

                	// Remove any deleted items
                	// Note: deleted items will have the deleted-component class added to them, so
                	//       this line will just remove anything with that class from the DOM
                    $('.deleted-component').remove();


                	// Return edited items back to unedited state
                	$("div[id*='_panel_']").each(function() {
                		// for any div that has _panel_ in its name, we should check the ID
                		// and remove anything that didn't get an ID
                		// Note: this should be anything that the user started but didn't save.
            	        var cont = $(this);
            	        // Don't look at any of the ZZ hidden panels
	            	    if (cont.attr('id').indexOf("ZZ") == -1) {
	            	        var split = cont.attr('id').split("_");

	            	        // Split reveals a normal panel:
	            	        if (split.length == 3) {
	            	        	var short = split[0];
	        	            	var id = split[2];
	        	            	if ($("#"+short+"_id_"+id).val() == "")
	        	            		cont.remove();
	        	            	else {
	        	            		// Make Uneditable returns the editing item back to text and
	        	            		// clears the operation flag.
	        	            		makeUneditable(short, id);
	        	            	}
	        	            // Else if split reveals an SCM panel:
	            	        } else if (split.length == 5) {
	            	        	var short = split[1];
	        	            	var i = split[4];
	        	            	var j = split[3];
	        	            	if ($("#scm_"+short+"_id_"+j+"_"+i).val() == "")
	        	            		cont.remove();
	        	            	else {
	        	            		// Make Uneditable returns the editing item back to text and
	        	            		// clears the operation flag.
	        	            		makeSCMUneditable(short, i, j);
	        	            	}
	            	        }
            	        }

                	});

                    // Everything's been saved, so mark not in editing
                    setEditedFlag(false);
                    //somethingHasBeenEdited = false;

                    // Clear the undo set
                    undoSet = new Array();


                    $('#notification-message').slideUp();
                    // Show the success alert
                    $('#success-message').html("<p>Saved successfully!</p>");
                    setTimeout(function(){
                        $('#success-message').slideDown();
                    }, 1000);
                    setTimeout(function(){
                        $('#success-message').slideUp();
                    }, 3000);
                } else {
                    $('#notification-message').slideUp();
                    // Something went wrong in the ajax call. Show an error.
                    displayErrorMessage(data.error,data);
                }
            });
            return false;
        });
    }

    // Save and Dashboard button
    if($('#save_and_dashboard').exists()) {
        $('#save_and_dashboard').click(function(){

            if (!validateConcept()) {
                return false;
            }
        	// If nothing has changed, alert the user and unlock
        	if (somethingHasBeenEdited == false) {
		        $('#notification-message').html("<p>No new changes to save.  Updating Concept state... Please wait.</p>");
		        $('#notification-message').slideDown();

                setTimeout(function(){

                    // Go to dashboard
                    window.location.href = snacUrl+"/vocab_administrator/";

                }, 1000);
                return false;
        	} else {

	            // Open up the warning alert box and note that we are saving
	            $('#notification-message').html("<p>Saving Concept... Please wait.</p>");
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
                    }
                });

	            // Send the data back by AJAX call
	            $.post(snacUrl+"/vocab_administrator/concept_save", $("#concept_form").serialize(), function (data) {
	                // Check the return value from the ajax. If success, then go to dashboard
	                if (data.result == "success") {
	                    // No longer in editing, save succeeded
                        setEditedFlag(false);
	                    //somethingHasBeenEdited = false;

	                    $('#notification-message').slideUp();

	                    // Go to dashboard
		                $('#success-message').html("<p>Concept Saved. Going to dashboard.</p>");
		                setTimeout(function(){
		                    $('#success-message').slideDown();
		                }, 500);
                        setTimeout(function(){

                            // Go to dashboard
                            window.location.href = snacUrl+"/vocab_administrator/";

                        }, 1000);
	                } else {
	                    $('#notification-message').slideUp();
	                    // Something went wrong in the ajax call. Show an error and don't go anywhere.
                        displayErrorMessage(data.error,data);
	                }
	            });
                return false;
        	}
        });
    }



    // Cancel button
    if($('#cancel').exists()) {
        $('#cancel').click(function(){

            if(somethingHasBeenEdited){
                if (!confirm('You may have unsaved changes on this Concept.  Are you sure you want to cancel and lose those edits?')) {
                    // Don't want to cancel, so exit!
                    return false;
                }
            }

            // By setting this to false, the page will not prompt on exit
            setEditedFlag(false);
            //somethingHasBeenEdited = false;

            window.location.href = snacUrl+"/vocab_administrator/";

	        });
            return false;
        }






















    /**
     * What to do on page unload (leaving the page)
     *
     * Set the message to display if you try to leave the page without saving changes
     *
     * @param  event e The event that happened
     */
	function unloadPage(e) {
		if(somethingHasBeenEdited){
			var message = 'You may have unsaved changes on this Concept.  Are you sure you want to leave the page and risk losing those edits?';
			var e = e || window.event;
			// For IE and Firefox
			if (e) { e.returnValue = message; }
			// For Safari
			return message;
		}
	}
	window.onbeforeunload = unloadPage;


});



/**
* Validate Concept
*
* Validates that there are no edited components to be saved with empty term fields.
*
* @param Boolean True if valid, else false.
*/
function validateConcept() {
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

    // Validate Category and preferredTerm
    var noTermText = true;
    $("input[id^='term_text_']").each(function() {
        if ($(this).val() != "")
            noTermText = false;
    });
    var noCategoryText = true;
    $("[id^='category_term_id_']").each(function() {
        if ($(this).val() != "")
            noCategoryText = false;
    });
    if (noCategoryText || noTermText) {
        errorMessage += "<p>At least one term and category required in order to save.</p>"
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

