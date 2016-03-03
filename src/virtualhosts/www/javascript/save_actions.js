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
    
    // Set a variable noting that this page has been saved.  (It's new and unchanged)
    var unsaved = false;

    // Save and Continue button
    if($('#save_and_continue').exists()) {
        $('#save_and_continue').click(function(){
            // Open up the warning alert box and note that we are saving
            $('.alert-warning').html("<p>Saving Constellation... Please wait.</p>");
            $('.alert-warning').slideDown();

            // Send the data back by AJAX call
            $.post("?command=save", $("#constellation_form").serialize(), function (data) {
                // Check the return value from the ajax. If success, then alert the
                // user and make appropriate updates.
                if (data.result == "success") {
                    // If there were any new elements pre-save that have now been
                    // saved, put those IDs into the form
                    for (var key in data.updates) {
                        // TODO
                    }

                    unsaved = false;
                    $('.alert-warning').slideUp();
                    // Show the success alert
                    $('.alert-success').html("<p>Saved successfully!</p>");
                    setTimeout(function(){
                        $('.alert-success').slideDown();
                    }, 1000);
                    setTimeout(function(){
                        $('.alert-success').slideUp();
                    }, 3000);
                } else {
                    $('.alert-warning').slideUp();
                    // Something went wrong in the ajax call. Show an error.
                    $('.alert-danger').html("<p>An error occurred while saving.</p>");
                    setTimeout(function(){
                        $('.alert-danger').slideDown();
                    }, 500);
                    setTimeout(function(){
                        $('.alert-danger').slideUp();
                    }, 8000);
                }
            });
        });
    }

    // Save and Dashboard button
    if($('#save_and_dashboard').exists()) {
        $('#save_and_dashboard').click(function(){
            // Open up the warning alert box and note that we are saving
            $('.alert-warning').html("<p>Saving Constellation... Please wait.</p>");
            $('.alert-warning').slideDown();

            // Send the data back by AJAX call
            $.post("?command=save", $("#constellation_form").serialize(), function (data) {
                // Check the return value from the ajax. If success, then go to dashboard
                if (data.result == "success") {
                    unsaved = false;
                    
                    $('.alert-warning').slideUp();
                    
                    // Go to dashboard
                    window.location.href = "?command=dashboard";

                } else {
                    $('.alert-warning').slideUp();
                    // Something went wrong in the ajax call. Show an error and don't go anywhere.
                    $('.alert-danger').html("<p>An error occurred while saving.</p>");
                    setTimeout(function(){
                        $('.alert-danger').slideDown();
                    }, 500);
                    setTimeout(function(){
                        $('.alert-danger').slideUp();
                    }, 8000);
                }
            });
        });
    }

    // Save and Submit button
    if($('#save_and_submit').exists()) {
        $('#save_and_submit').click(function(){
            // Open up the warning alert box and note that we are saving
            $('.alert-warning').html("<p>Saving Constellation... Please wait.</p>");
            $('.alert-warning').slideDown();

            // Send the data back by AJAX call
            $.post("?command=save_submit", $("#constellation_form").serialize(), function (data) {
                // Check the return value from the ajax. If success, then go to dashboard
                if (data.result == "success") {
                    unsaved = false;
                    
                    $('.alert-warning').slideUp();
                    
                    // TODO: Go to dashboard?? Show notice then dashboard?
                    window.location.href = "?command=dashboard";

                } else {
                    $('.alert-warning').slideUp();
                    // Something went wrong in the ajax call. Show an error and don't go anywhere.
                    $('.alert-danger').html("<p>An error occurred while saving.</p>");
                    setTimeout(function(){
                        $('.alert-danger').slideDown();
                    }, 500);
                    setTimeout(function(){
                        $('.alert-danger').slideUp();
                    }, 8000);
                }
            });
        });
    }



});
