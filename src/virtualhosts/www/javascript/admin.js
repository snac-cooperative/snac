/**
 * Admin Actions
 *
 * Contains code that handles what happens in the admin GUI
 *
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

jQuery.fn.exists = function(){return this.length>0;}

function checkAllUserAdd(element, event) {
    if (element.checked){ //checked) {
        $("input[id^='useradd_']").each(function() {this.checked = true;});
    } else {
        $("input[id^='useradd_']").each(function() {this.checked = false;});
    }
}

/**
 * Only load this script once the document is fully loaded
 */
$(document).ready(function() {
    /**
     * The following are User Admin Page actions
     */

    // Save  button
    if($('#save_new_user').exists()) {
        $('#save_new_user').click(function(){

            // Open up the warning alert box and note that we are saving
            $('#notification-message').html("<p>Saving User... Please wait.</p>");
            $('#notification-message').slideDown();


            // Send the data back by AJAX call
            $.post("?command=administrator&subcommand=edit_user_post", $("#new_user_form").serialize(), function (data) {
                // Check the return value from the ajax. If success, then go to dashboard
                if (data.result == "success") {
                    // No longer in editing, save succeeded
                    somethingHasBeenEdited = false;

                    $('#notification-message').slideUp();

                    console.log(data);

                    $('#success-message').html("<p>User successfully saved. Going to user management.</p>");
	                setTimeout(function(){
	                    $('#success-message').slideDown();
	                }, 500);
	                setTimeout(function(){

	                    // Go to dashboard
	                    window.location.href = "?command=administrator&subcommand=users";

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
     * The following are Group Admin Page actions
     */


    // Save button
    if($('#save_new_group').exists()) {
        $('#save_new_group').click(function(){

            // Open up the warning alert box and note that we are saving
            $('#notification-message').html("<p>Saving Group... Please wait.</p>");
            $('#notification-message').slideDown();


            // Send the data back by AJAX call
            $.post("?command=administrator&subcommand=edit_group_post", $("#new_group_form").serialize(), function (data) {
                // Check the return value from the ajax. If success, then go to dashboard
                if (data.result == "success") {
                    // No longer in editing, save succeeded
                    somethingHasBeenEdited = false;

                    $('#notification-message').slideUp();

                    console.log(data);

                    $('#success-message').html("<p>Group successfully saved. Going to group management.</p>");
	                setTimeout(function(){
	                    $('#success-message').slideDown();
	                }, 500);
	                setTimeout(function(){

	                    // Go to dashboard
	                    window.location.href = "?command=administrator&subcommand=groups";

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


    if($('#addSelectedUsersButton').exists()) {
        $('#addSelectedUsersButton').click(function(){
            $("input[id^='useradd_']").each(function() {
                if ($(this).is(":checked")) {
                    console.log("Adding user " + $(this).val());
                }
            });

        });
    }


    if($('#add_users_to_group').exists()) {
        $('#add_users_to_group').click(function(){

            var users = [];
            $("input[id^='userid_']").each(function() {
                users.push($(this).val());
            });
            $.post("?command=administrator&subcommand=user_list", null, function (data) {
                if (data.users.length > 0) {
                    var html = "";
                    html += '<table class="table">'
                                + '<thead>'
                            + '<tr>'
                                + '<th><input type="checkbox" id="checkall"></th>'
                                + '<th>Name</th>'
                                + '<th>Affiliation</th>'
                            + '</tr>'
                        + '</thead>'
                        + '<tbody>';
                    for (var key in data.users) {
                        if (jQuery.inArray(data.users[key].userid, users) == -1 && data.users[key].active) {
                            console.log(data.users[key]);
                            var affil = "";
                            if ( typeof data.users[key].affiliation != 'undefined')
                                affil = data.users[key].affiliation.nameEntries[0].original;

                            html += '<tr>'
                                + '<td><input type="checkbox" name="useradd_'+data.users[key].userid+'" id="useradd_'+data.users[key].userid+'" value="'+data.users[key].userid+'"></td>'
                                + '<td>' + data.users[key].fullName
                                    + '<input type="hidden" name="useraddname_'+data.users[key].userid+'" id="useraddname_'+data.users[key].userid+'" value="'+data.users[key].fullName+'">'
                                    + '<input type="hidden" name="useraddaff_'+data.users[key].userid+'" id="useraddaff_'+data.users[key].userid+'" value="'+affil+'">'
                                + '</td>';
                            html += "<td>"+affil+"</td>";
                            html += '</tr>';
                        }
                    }
                    html += '</tbody></table>';

                    $("#addUsersPaneContent").html(html);
                    $("#checkall").click(function(event) {checkAllUserAdd(this, event);});
                } else {
                    console.log("An error occurred");
                }
            });

            // don't reload the page
            //return false;
        });
    }












    /**
     * The following apply to multiple pages
     */

    // Admin cancel to dashboard
    if($('#admin_dashboard_cancel').exists()) {
        $('#admin_dashboard_cancel').click(function(){

            if (!confirm('Are you sure you want to cancel?')) {
                // Don't want to cancel, so exit!
                return;
            }

            $('#notification-message').html("<p>Cancelling...</p>");
            $('#notification-message').slideDown();
            setTimeout(function(){

                // Go to dashboard
                window.location.href = "?command=administrator";

            }, 1500);

            return false;
        });
    }














});
