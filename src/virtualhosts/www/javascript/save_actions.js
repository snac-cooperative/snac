/**
 * Edit-Page Save Actions
 *
 * Contains code that handles what happens in the GUI when 
 * the user clicks any save button.
 * 
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
    


    // Save and Continue button
    if($('#save_and_continue').exists()) {
        $('#save_and_continue').click(function(){
            // Open up the warning alert box and note that we are saving
            $('#notification-message').html("<p>Saving Constellation... Please wait.</p>");
            $('#notification-message').slideDown();

            // Send the data back by AJAX call
            $.post("?command=save", $("#constellation_form").serialize(), function (data) {
                // Check the return value from the ajax. If success, then alert the
                // user and make appropriate updates.
                if (data.result == "success") {
                    // If there were any new elements pre-save that have now been
                    // saved, put those IDs into the form
                	for (var key in data.updates) {
                		console.log("updating: " + key + " to value " + data.updates[key]);
                		$('#' + key).val(data.updates[key]);
                	}
                	

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
	            	        if (split.length = 3) {
	            	        	var short = split[0];
	        	            	var id = split[2];
	        	            	if ($("#"+short+"_id_"+id).val() == "")
	        	            		cont.remove();
	        	            	else {
	        	            		// Make Uneditable returns the editing item back to text and
	        	            		// clears the operation flag.
	        	            		makeUneditable(short, id);
	        	            	}
	            	        }
            	        }
        	            
                	});

                    // Everything's been saved, so mark not in editing
                    somethingHasBeenEdited = false;
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
                    $('#error-message').html("<p>An error occurred while saving.</p>");
                    setTimeout(function(){
                        $('#error-message').slideDown();
                    }, 500);
                    setTimeout(function(){
                        $('#error-message').slideUp();
                    }, 8000);
                }
            });
        });
    }

    // Save and Dashboard button
    if($('#save_and_dashboard').exists()) {
        $('#save_and_dashboard').click(function(){
            // Open up the warning alert box and note that we are saving
            $('#notification-message').html("<p>Saving Constellation... Please wait.</p>");
            $('#notification-message').slideDown();

            // Send the data back by AJAX call
            $.post("?command=save", $("#constellation_form").serialize(), function (data) {
                // Check the return value from the ajax. If success, then go to dashboard
                if (data.result == "success") {
                    // No longer in editing, save succeeded
                    somethingHasBeenEdited = false;
                    
                    $('#notification-message').slideUp();
                    
                    // Go to dashboard
                    window.location.href = "?command=dashboard";

                } else {
                    $('#notification-message').slideUp();
                    // Something went wrong in the ajax call. Show an error and don't go anywhere.
                    $('#error-message').html("<p>An error occurred while saving.</p>");
                    setTimeout(function(){
                        $('#error-message').slideDown();
                    }, 500);
                    setTimeout(function(){
                        $('#error-message').slideUp();
                    }, 8000);
                }
            });
        });
    }

    // Save and Submit button
    if($('#save_and_submit').exists()) {
        $('#save_and_submit').click(function(){
            // Open up the warning alert box and note that we are saving
            $('#notification-message').html("<p>Saving Constellation... Please wait.</p>");
            $('#notification-message').slideDown();

            // Send the data back by AJAX call
            $.post("?command=save_submit", $("#constellation_form").serialize(), function (data) {
                // Check the return value from the ajax. If success, then go to dashboard
                if (data.result == "success") {
                    // Edit succeeded, so save mode off
                    somethingHasBeenEdited = false;
                    
                    $('#notification-message').slideUp();
                    
                    // TODO: Go to dashboard?? Show notice then dashboard?
                    window.location.href = "?command=dashboard";

                } else {
                    $('#notification-message').slideUp();
                    // Something went wrong in the ajax call. Show an error and don't go anywhere.
                    $('#error-message').html("<p>An error occurred while saving.</p>");
                    setTimeout(function(){
                        $('#error-message').slideDown();
                    }, 500);
                    setTimeout(function(){
                        $('#error-message').slideUp();
                    }, 8000);
                }
            });
        });
    }

    // Set the message to display if you try to leave the page without saving changes
    
	function unloadPage(e){ 
		if(somethingHasBeenEdited){
			var message = 'You may have unsaved changes on this Constellation.  Are you sure you want to leave the page and risk losing those edits?';
			var e = e || window.event;
			// For IE and Firefox
			if (e) { e.returnValue = message; }
			// For Safari
			return message;
		}
	}
	window.onbeforeunload = unloadPage;


});
