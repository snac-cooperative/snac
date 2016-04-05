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

        	// If EntityType and NameEntry do not have values, then don't let the user save
        	var noNameEntryText = true;
        	$("input[id^=nameEntry_original_").each(function() {
        		if ($(this).val() != "")
        			noNameEntryText = false;
        	});
        	if ($('#entityType').val() == "" || noNameEntryText) {
        		$('#error-message').html("<p>Entity Type and at least one Name Entry required for saving.</p>");
                setTimeout(function(){
                    $('#error-message').slideDown();
                }, 500);
                setTimeout(function(){
                    $('#error-message').slideUp();
                }, 10000);
        		return;
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
        		return;
        	}
        	
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
                    var errorMsg = "";
                    if (data.error && data.error.type)
                    	errorMsg += ": " + data.error.type;
                    $('#error-message').html("<p>An error occurred while saving"+errorMsg+".</p>");
                    setTimeout(function(){
                        $('#error-message').slideDown();
                    }, 500);
                    setTimeout(function(){
                        $('#error-message').slideUp();
                    }, 10000);
                }
            });
        });
    }

    // Save and Dashboard button
    if($('#save_and_dashboard').exists()) {
        $('#save_and_dashboard').click(function(){
        	
        	// If EntityType and NameEntry do not have values, then don't let the user save
        	var noNameEntryText = true;
        	$("input[id^=nameEntry_original_").each(function() {
        		if ($(this).val() != "")
        			noNameEntryText = false;
        	});
        	if ($('#entityType').val() == "" || noNameEntryText) {
        		$('#error-message').html("<p>Entity Type and at least one Name Entry required for saving.</p>");
                setTimeout(function(){
                    $('#error-message').slideDown();
                }, 500);
                setTimeout(function(){
                    $('#error-message').slideUp();
                }, 10000);
        		return;
        	}
        	
        	// If nothing has changed, alert the user and unlock
        	if (somethingHasBeenEdited == false) {
		        $('#notification-message').html("<p>No new changes to save.  Updating Constellation state... Please wait.</p>");
		        $('#notification-message').slideDown();
		
		        // Publish by AJAX call
		        $.post("?command=unlock", $("#constellation_form").serialize(), function (data) {
		            // Check the return value from the ajax. If success, then go to dashboard
		            if (data.result == "success") {
		                // Edit succeeded, so save mode off
		                somethingHasBeenEdited = false;
		                
		                $('#notification-message').slideUp();
		                
		                $('#success-message').html("<p>Constellation state updated. Going to dashboard.</p>");
		                setTimeout(function(){
		                    $('#success-message').slideDown();
		                }, 500);
		                setTimeout(function(){
		                	
		                    // Go to dashboard
		                    window.location.href = "?command=dashboard";
		                    
		                }, 1000);
		
		            } else {
		                $('#notification-message').slideUp();
		                // Something went wrong in the ajax call. Show an error and don't go anywhere.
		                $('#error-message').html("<p>An error occurred while updating state.</p>");
		                setTimeout(function(){
		                    $('#error-message').slideDown();
		                }, 500);
		                setTimeout(function(){
		                    $('#error-message').slideUp();
		                }, 8000);
		            }
		        });
        	} else {
	        	
	            // Open up the warning alert box and note that we are saving
	            $('#notification-message').html("<p>Saving Constellation... Please wait.</p>");
	            $('#notification-message').slideDown();
	
	            // Send the data back by AJAX call
	            $.post("?command=save_unlock", $("#constellation_form").serialize(), function (data) {
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
	                    var errorMsg = "";
	                    if (data.error && data.error.type)
	                    	errorMsg += ": " + data.error.type;
	                    $('#error-message').html("<p>An error occurred while saving"+errorMsg+".</p>");
	                    setTimeout(function(){
	                        $('#error-message').slideDown();
	                    }, 500);
	                    setTimeout(function(){
	                        $('#error-message').slideUp();
	                    }, 10000);
	                }
	            });
        	}
        });
    }

    // Save and Submit button
    if($('#save_and_submit').exists()) {
        $('#save_and_submit').click(function(){
        	
        	// If EntityType and NameEntry do not have values, then don't let the user save or publish
        	var noNameEntryText = true;
        	$("input[id^=nameEntry_original_").each(function() {
        		if ($(this).val() != "")
        			noNameEntryText = false;
        	});
        	if ($('#entityType').val() == "" || noNameEntryText) {
        		$('#error-message').html("<p>Entity Type and at least one Name Entry required for saving.</p>");
                setTimeout(function(){
                    $('#error-message').slideDown();
                }, 500);
                setTimeout(function(){
                    $('#error-message').slideUp();
                }, 10000);
        		return;
        	}

        	// If nothing has changed, alert the user and publish
        	if (somethingHasBeenEdited == false) {
		        $('#notification-message').html("<p>No new changes to save.  Publishing Constellation... Please wait.</p>");
		        $('#notification-message').slideDown();
		
		        // Publish by AJAX call
		        $.post("?command=publish", $("#constellation_form").serialize(), function (data) {
		            // Check the return value from the ajax. If success, then go to dashboard
		            if (data.result == "success") {
		                // Edit succeeded, so save mode off
		                somethingHasBeenEdited = false;
		                
		                $('#notification-message').slideUp();
		                
		                $('#success-message').html("<p>Constellation Published. Going to dashboard.</p>");
		                setTimeout(function(){
		                    $('#success-message').slideDown();
		                }, 500);
		                setTimeout(function(){
		                	
		                    // Go to dashboard
		                    window.location.href = "?command=dashboard";
		                    
		                }, 1000);
		
		            } else {
		                $('#notification-message').slideUp();
		                // Something went wrong in the ajax call. Show an error and don't go anywhere.
		                $('#error-message').html("<p>An error occurred while publishing.</p>");
		                setTimeout(function(){
		                    $('#error-message').slideDown();
		                }, 500);
		                setTimeout(function(){
		                    $('#error-message').slideUp();
		                }, 8000);
		            }
		        });
        	} else {
	        	
	            // Open up the warning alert box and note that we are saving
	            $('#notification-message').html("<p>Saving and Publishing Constellation... Please wait.</p>");
	            $('#notification-message').slideDown();
	
	            // Send the data back by AJAX call
	            $.post("?command=save_publish", $("#constellation_form").serialize(), function (data) {
	                // Check the return value from the ajax. If success, then go to dashboard
	                if (data.result == "success") {
	                    // Edit succeeded, so save mode off
	                    somethingHasBeenEdited = false;
	                    
	                    $('#notification-message').slideUp();

		                
		                $('#success-message').html("<p>Constellation Saved and Published. Going to dashboard.</p>");
		                setTimeout(function(){
		                    $('#success-message').slideDown();
		                }, 500);
		                setTimeout(function(){
		                	
		                    // Go to dashboard
		                    window.location.href = "?command=dashboard";
		                    
		                }, 1000);
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
        	}
        });
    }
    
    

    // Cancel button
    if($('#cancel').exists()) {
        $('#cancel').click(function(){
        	
        	// If EntityType and NameEntry do not have values, don't update state and go to dashboard
        	var noNameEntryText = true;
        	$("input[id^=nameEntry_original_").each(function() {
        		if ($(this).val() != "")
        			noNameEntryText = false;
        	});
        	if ($('#entityType').val() == "" || noNameEntryText) {
        		// Go to dashboard
                window.location.href = "?command=dashboard";
        		return;
        	}
        	
        	// Unlock
	        $.post("?command=unlock", $("#constellation_form").serialize(), function (data) {
	            // Check the return value from the ajax. If success, then go to dashboard
	            if (data.result == "success") {
	                
	                $('#success-message').html("<p>Constellation unlocked. Going to dashboard.</p>");
	                setTimeout(function(){
	                    $('#success-message').slideDown();
	                }, 500);
	                setTimeout(function(){
	                	
	                    // Go to dashboard
	                    window.location.href = "?command=dashboard";
	                    
	                }, 1000);
	
	            } else {
	                $('#notification-message').slideUp();
	                // Something went wrong in the ajax call. Show an error and don't go anywhere.
	                $('#error-message').html("<p>An error occurred while unlocking the constellation.</p>");
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

    

    // Preview button
    if($('#preview').exists()) {
        $('#preview').click(function(){
            // Open up the warning alert box and note that we are saving
            $('#notification-message').html("<p>Generating Preview... Please wait.</p>");
            $('#notification-message').slideDown();
            setTimeout(function(){
                $('#notification-message').slideUp();
            }, 3000);

            // Send the data back by AJAX call
            $.post("?command=preview", $("#constellation_form").serialize(), function (data) {
                var previewWindow = window.open("", "Preview");
                previewWindow.document.write(data);
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
