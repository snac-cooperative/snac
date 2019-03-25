/**
 * Vocab Admin Actions
 *
 * Contains code that handles what happens in the vocabulary admin GUI
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

jQuery.fn.exists = function(){return this.length>0;}

/**
 * Only load this script once the document is fully loaded
 */
$(document).ready(function() {
    /**
     * The following are Controlled Vocabulary Page actions
     */

    // Save  button
    if($('#save_new_vocab').exists()) {
        $('#save_new_vocab').click(function(){

            // Open up the warning alert box and note that we are saving
            $('#notification-message').html("<p>Saving Vocabulary Term... Please wait.</p>");
            $('#notification-message').slideDown();


            // Send the data back by AJAX call
            $.post(snacUrl+"/vocab_administrator/add_term_post", $("#new_term_form").serialize(), function (data) {
                // Check the return value from the ajax. If success, then go to dashboard
                if (data.result == "success") {
                    // No longer in editing, save succeeded
                    somethingHasBeenEdited = false;

                    $('#notification-message').slideUp();

                    console.log(data);

                    $('#success-message').html("<p>Vocabulary term successfully saved. Going to term search.</p>");
	                setTimeout(function(){
	                    $('#success-message').slideDown();
	                }, 500);
	                setTimeout(function(){

	                    // Go to search
	                    window.location.href = snacUrl+"/vocab_administrator/search";

	                }, 1500);

                } else {
                    $('#notification-message').slideUp();
                    // Something went wrong in the ajax call. Show an error and don't go anywhere.
                    displayErrorMessage(data.error,data);
                }
            });

            return false;
        });
    }

    if($('#save_new_geovocab').exists()) {
        $('#save_new_geovocab').click(function(){

            // Open up the warning alert box and note that we are saving
            $('#notification-message').html("<p>Saving Geographic Vocabulary Term... Please wait.</p>");
            $('#notification-message').slideDown();


            // Send the data back by AJAX call
            $.post(snacUrl+"/vocab_administrator/add_geoterm_post", $("#new_term_form").serialize(), function (data) {
                // Check the return value from the ajax. If success, then go to dashboard
                if (data.result == "success") {
                    // No longer in editing, save succeeded
                    somethingHasBeenEdited = false;

                    $('#notification-message').slideUp();

                    console.log(data);

                    $('#success-message').html("<p>Geographic vocabulary term successfully saved. Going to geoterm search.</p>");
                    setTimeout(function(){
                        $('#success-message').slideDown();
                    }, 500);
                    setTimeout(function(){

                        // Go to search
                        window.location.href = snacUrl+"/vocab_administrator/geosearch";

                    }, 1500);

                } else {
                    $('#notification-message').slideUp();
                    // Something went wrong in the ajax call. Show an error and don't go anywhere.
                    displayErrorMessage(data.error,data);
                }
            });

            return false;
        });
    }

    /**
     * The following apply to multiple pages
     */

    // Vocab cancel to dashboard
    if($('#vocab_dashboard_cancel').exists()) {
        $('#vocab_dashboard_cancel').click(function(){

            if (!confirm('Are you sure you want to cancel?')) {
                // Don't want to cancel, so exit!
                return;
            }

            $('#notification-message').html("<p>Cancelling...</p>");
            $('#notification-message').slideDown();
            setTimeout(function(){

                // Go to dashboard
                window.location.href = snacUrl+"/vocab_administrator";

            }, 1500);

            return false;
        });
    }














});
