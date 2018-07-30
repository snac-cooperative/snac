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

    /**
     * The following are Edit Page save actions
     */

    // Save and Continue button
    if($('#save_and_continue').exists()) {
        $('#save_and_continue').click(function(){

        	// If EntityType and NameEntry do not have values, then don't let the user save
        	var noNameEntryText = true;
        	$("input[id^='nameEntry_original_']").each(function() {
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
            $.post(snacUrl+"/save", $("#constellation_form").serialize(), function (data) {
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
                    somethingHasBeenEdited = false;

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
        });
    }

    // Save and Dashboard button
    if($('#save_and_dashboard').exists()) {
        $('#save_and_dashboard').click(function(){

        	// If EntityType and NameEntry do not have values, then don't let the user save
        	var noNameEntryText = true;
        	$("input[id^='nameEntry_original_']").each(function() {
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
		        $.post(snacUrl+"/unlock", $("#constellation_form").serialize(), function (data) {
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
		                    window.location.href = snacUrl+"/dashboard";

		                }, 1000);

		            } else {
		                $('#notification-message').slideUp();
		                // Something went wrong in the ajax call. Show an error and don't go anywhere.
                        displayErrorMessage(data.error,data);
		            }
		        });
        	} else {

	            // Open up the warning alert box and note that we are saving
	            $('#notification-message').html("<p>Saving Constellation... Please wait.</p>");
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
	            $.post(snacUrl+"/save_unlock", $("#constellation_form").serialize(), function (data) {
	                // Check the return value from the ajax. If success, then go to dashboard
	                if (data.result == "success") {
	                    // No longer in editing, save succeeded
	                    somethingHasBeenEdited = false;

	                    $('#notification-message').slideUp();

	                    // Go to dashboard
		                $('#success-message').html("<p>Constellation Saved. Going to dashboard.</p>");
		                setTimeout(function(){
		                    $('#success-message').slideDown();
		                }, 500);
                        setTimeout(function(){

                            // Go to dashboard
                            window.location.href = snacUrl+"/dashboard";

                        }, 1000);
	                } else {
	                    $('#notification-message').slideUp();
	                    // Something went wrong in the ajax call. Show an error and don't go anywhere.
                        displayErrorMessage(data.error,data);
	                }
	            });
        	}
        });
    }

    // Save and Publish button
    if($('#save_and_publish').exists()) {
        $('#save_and_publish').click(function(){

        	// If EntityType and NameEntry do not have values, then don't let the user save or publish
        	var noNameEntryText = true;
        	$("input[id^='nameEntry_original_']").each(function() {
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
		        $.post(snacUrl+"/publish", $("#constellation_form").serialize(), function (data) {
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
		                    window.location.href = snacUrl+"/dashboard";

		                }, 1000);

		            } else {
		                $('#notification-message').slideUp();
		                // Something went wrong in the ajax call. Show an error and don't go anywhere.
                        displayErrorMessage(data.error,data);
		            }
		        });
        	} else {

	            // Open up the warning alert box and note that we are saving
	            $('#notification-message').html("<p>Saving and Publishing Constellation... Please wait.</p>");
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
	            $.post(snacUrl+"/save_publish", $("#constellation_form").serialize(), function (data) {
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
		                    window.location.href = snacUrl+"/dashboard";

		                }, 1000);
	                } else {
	                    $('#notification-message').slideUp();
	                    // Something went wrong in the ajax call. Show an error and don't go anywhere.
                        displayErrorMessage(data.error,data);
	                }
	            });
        	}
        });
    }

    function save_and_review(){
        // If EntityType and NameEntry do not have values, then don't let the user save or publish
        var noNameEntryText = true;
        $("input[id^='nameEntry_original_']").each(function() {
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

        // Copy the review message from the modal into the form body
        $("#reviewmessage").val($("#sendReviewMessage").val());

        // If nothing has changed, alert the user and publish
        if (somethingHasBeenEdited == false) {
            $('#notification-message').html("<p>No new changes to save.  Sending Constellation for review... Please wait.</p>");
            $('#notification-message').slideDown();

            // Publish by AJAX call
            $.post(snacUrl+"/review", $("#constellation_form").serialize(), function (data) {
                // Check the return value from the ajax. If success, then go to dashboard
                if (data.result == "success") {
                    // Edit succeeded, so save mode off
                    somethingHasBeenEdited = false;

                    $('#notification-message').slideUp();

                    $('#success-message').html("<p>Constellation sent for review. Going to dashboard.</p>");
                    setTimeout(function(){
                        $('#success-message').slideDown();
                    }, 500);
                    setTimeout(function(){

                        // Go to dashboard
                        window.location.href = snacUrl+"/dashboard";

                    }, 1000);

                } else {
                    $('#notification-message').slideUp();
                    // Something went wrong in the ajax call. Show an error and don't go anywhere.
                    displayErrorMessage(data.error,data);
                }
            });
        } else {

            // Open up the warning alert box and note that we are saving
            $('#notification-message').html("<p>Saving and sending Constellation for review... Please wait.</p>");
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
            $.post(snacUrl+"/save_review", $("#constellation_form").serialize(), function (data) {
                // Check the return value from the ajax. If success, then go to dashboard
                if (data.result == "success") {
                    // Edit succeeded, so save mode off
                    somethingHasBeenEdited = false;

                    $('#notification-message').slideUp();


                    $('#success-message').html("<p>Constellation saved and sent for review. Going to dashboard.</p>");
                    setTimeout(function(){
                        $('#success-message').slideDown();
                    }, 500);
                    setTimeout(function(){

                        // Go to dashboard
                        window.location.href = snacUrl+"/dashboard";

                    }, 1000);
                } else {
                    $('#notification-message').slideUp();
                    // Something went wrong in the ajax call. Show an error and don't go anywhere.
                    displayErrorMessage(data.error,data);
                }
            });
        }
    }

    function save_and_send_editor(){
        // If EntityType and NameEntry do not have values, then don't let the user save or publish
        var noNameEntryText = true;
        $("input[id^='nameEntry_original_']").each(function() {
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
            $('#notification-message').html("<p>No new changes to save.  Sending Constellation to editor... Please wait.</p>");
            $('#notification-message').slideDown();

            // Publish by AJAX call
            $.post(snacUrl+"/send", $("#constellation_form").serialize(), function (data) {
                // Check the return value from the ajax. If success, then go to dashboard
                if (data.result == "success") {
                    // Edit succeeded, so save mode off
                    somethingHasBeenEdited = false;

                    $('#notification-message').slideUp();

                    $('#success-message').html("<p>Constellation sent for review. Going to dashboard.</p>");
                    setTimeout(function(){
                        $('#success-message').slideDown();
                    }, 500);
                    setTimeout(function(){

                        // Go to dashboard
                        window.location.href = snacUrl+"/dashboard";

                    }, 1000);

                } else {
                    $('#notification-message').slideUp();
                    // Something went wrong in the ajax call. Show an error and don't go anywhere.
                    displayErrorMessage(data.error,data);
                }
            });
        } else {

            // Open up the warning alert box and note that we are saving
            $('#notification-message').html("<p>Saving and sending Constellation to editor... Please wait.</p>");
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
            $.post(snacUrl+"/save_send", $("#constellation_form").serialize(), function (data) {
                // Check the return value from the ajax. If success, then go to dashboard
                if (data.result == "success") {
                    // Edit succeeded, so save mode off
                    somethingHasBeenEdited = false;

                    $('#notification-message').slideUp();


                    $('#success-message').html("<p>Constellation saved and sent to editor. Going to dashboard.</p>");
                    setTimeout(function(){
                        $('#success-message').slideDown();
                    }, 500);
                    setTimeout(function(){

                        // Go to dashboard
                        window.location.href = snacUrl+"/dashboard";

                    }, 1000);
                } else {
                    $('#notification-message').slideUp();
                    // Something went wrong in the ajax call. Show an error and don't go anywhere.
                    displayErrorMessage(data.error,data);
                }
            });
        }
    }

    // Save and Send Back to Editor button
    if($('#save_and_send_back').exists()) {
        $('#save_and_send_back').click(function() {
            save_and_send_editor();
        });
    }


    // Save and Send for Review button
    if($('#save_and_review_touser').exists()) {
        $('#save_and_review_touser').click(function() {
            $("#reviewer").val($("#reviewersearchbox").val());
            save_and_review();
        });
    }

    if($('#save_and_review_general').exists()) {
        $('#save_and_review_general').click(function() {
            $("#reviewer").val("");
            save_and_review();
        });
    }



    // Cancel button
    if($('#cancel').exists()) {
        $('#cancel').click(function(){

            if(somethingHasBeenEdited){
                if (!confirm('You may have unsaved changes on this Constellation.  Are you sure you want to cancel and lose those edits?')) {
                    // Don't want to cancel, so exit!
                    return;
                }
            }

            // By setting this to false, the page will not prompt on exit
            somethingHasBeenEdited = false;

        	// If Constellation ID or EntityType or NameEntry do not have values, don't update state and go to dashboard
        	var noNameEntryText = true;
        	$("input[id^='nameEntry_original_']").each(function() {
        		if ($(this).val() != "")
        			noNameEntryText = false;
        	});
        	if ($('#constellationid').val() == "" || $('#entityType').val() == "" || noNameEntryText) {
        		// Go to dashboard
                window.location.href = snacUrl+"/dashboard";
        		return;
        	}



        	// Unlock
	        $.post(snacUrl+"/unlock", $("#constellation_form").serialize(), function (data) {
	            // Check the return value from the ajax. If success, then go to dashboard
	            if (data.result == "success") {
	                somethingHasBeenEdited = false;
	                $('#success-message').html("<p>Constellation unlocked. Going to dashboard.</p>");
	                setTimeout(function(){
	                    $('#success-message').slideDown();
	                }, 500);
	                setTimeout(function(){

	                    // Go to dashboard
	                    window.location.href = snacUrl+"/dashboard";

	                }, 1000);

	            } else {
	                $('#notification-message').slideUp();
	                // Something went wrong in the ajax call. Show an error and don't go anywhere.
                    displayErrorMessage(data.error,data);
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
            $.post(snacUrl+"/preview", $("#constellation_form").serialize(), function (data) {
                var previewWindow = window.open("", "Preview");
                previewWindow.document.write(data);
            });
        });
    }


    /**
     * The following are New Constellation Edit Page save actions
     */


    // Reconcile and then continue to create new button
    if($('#continue_and_reconcile').exists()) {
        $('#continue_and_reconcile').click(function(){

            // If EntityType and NameEntry do not have values, then don't let the user save
            var noNameEntryText = true;
            $("input[id^='nameEntry_original_']").each(function() {
                if ($(this).val() != "")
                noNameEntryText = false;
            });
            if ($('#entityType').val() == "" || noNameEntryText) {
                $('#error-message').html("<p>Entity Type and at least one Name Entry required for continuing.</p>");
                setTimeout(function(){
                    $('#error-message').slideDown();
                }, 500);
                setTimeout(function(){
                    $('#error-message').slideUp();
                }, 10000);
                return;
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
                    somethingHasBeenEdited = false;

                    $('#notification-message').slideUp();

                    // We got the reconciliation results from the WebUI, which we now need to display
                    console.log(data);

                    var html = "";
                    html += "<p class='text-left'>Before continuing, please check the following Constellation matches.  If the "
                            + " Constellation you wish to add is below, please edit it (if it is not checked out) rather than "
                            + " creating a duplicate.</p>";
                    html += "<div class='list-group text-left' style='margin-bottom:0px'>";
                    if (data.results.length > 0) {
                        for (var key in data.results) {
                            //html += "<div class='input-group'><span class='input-group-addon'><input type='radio'></span><p class='form-static'>Blah</p><span class='input-group-button'><button class='btn btn-default' type='button'>View</button></span></div>";
                            html += "<div class='list-group-item'><div class='row'>";
                            html += "<div class='col-xs-8'><h4 class='list-group-item-heading'>"+data.results[key].nameEntries[0].original+"</h4>";
                            html += "<p class='list-group-item-text'>"+data.results[key].ark+"</p></div>";
                            html += "<div class='col-xs-4 list-group'>";
                            html += "<a class='list-group-item list-group-item-success' target='_blank' href='"+snacUrl+"/view/"+data.results[key].id+"?preview'><span  class='fa fa-eye' aria-hidden='true'></span> View</a></a>";
                            html += "<a class='list-group-item list-group-item-info' href='"+snacUrl+"/edit/"+data.results[key].id+"'><span  class='fa fa-pencil-square-o' aria-hidden='true'></span> Edit</a></div>";
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
        });
    }

    // Confirm Create New button
    if($('#confirm_create_new').exists()) {
        $('#confirm_create_new').click(function(){
            // Send the data back by AJAX call
            $("#constellation_form").submit();
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





















    /**
     * What to do on page unload (leaving the page)
     *
     * Set the message to display if you try to leave the page without saving changes
     *
     * @param  event e The event that happened
     */
	function unloadPage(e) {
		if(typeof(somethingHasBeenEdited) != "undefined" && somethingHasBeenEdited){
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
