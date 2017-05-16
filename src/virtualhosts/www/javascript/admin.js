/**
 * Admin Actions
 *
 * Contains code that handles what happens in the admin GUI
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

function removeUserFromGroup(id) {
    $("#userrow_" + id).remove();
    return false;
}


function checkAllGroupAdd(element, event) {
    if (element.checked){ //checked) {
        $("input[id^='groupadd_']").each(function() {this.checked = true;});
    } else {
        $("input[id^='groupadd_']").each(function() {this.checked = false;});
    }
}

function removeGroupFromUser(id) {
    $("#grouprow_" + id).remove();
    return false;
}

function unlockConstellation(id, version) {
    $.get("?command=administrator&subcommand=unlock_constellation&constellationid="+id+"&version="+version, null, function(data) {
        if (data.result == "success") {
            $('#status-message').html("<p>Successfully Unlocked Constellation.</p>");
            $('#status-message').slideDown();
            setTimeout(function(){
                $('#status-message').slideUp();
            }, 3000);

            $("#button_"+id).addClass("disabled").removeClass("btn-warning").addClass("btn-default");
            $("#status_"+id).text("Checked out (not editing)");
        }
    });
}

function doReassignConstellation() {
    var id = $('#reassignedConstellationID').val();
    var version = $('#reassignedConstellationVersion').val();

    if (typeof $('input[name=reassignTo]:checked') == 'undefined')
        return false;

    var toUserID = $('input[name=reassignTo]:checked').val();

    if (typeof toUserID == 'undefined')
        return false;

    // We have an ID to reassign this constellation
    $.get("?command=administrator&subcommand=reassign_constellation&constellationid="+id+"&version="+version+"&userid="+toUserID, null, function(data) {
        if (data.result == "success") {
            $('#status-message').html("<p>Successfully Reassigned Constellation.</p>");
            $('#status-message').slideDown();
            setTimeout(function(){
                $('#status-message').slideUp();
            }, 3000);

            // Remove this constellation from the DOM (no longer attached to this user)
            $("#constellation_"+id).remove();
        }
    });


}

function reassignConstellation(id, version) {

    $("#usersPaneContent").html("<p class='text-center'>Loading...</p>");
    $("#usersPane").modal();

    $.post("?command=administrator&subcommand=user_list", null, function (data) {
        if (data.users.length > 0) {
            var html = "";
            var heading = '<input type="hidden" id="reassignedConstellationID" value="'+id+'">'
                        + '<input type="hidden" id="reassignedConstellationVersion" value="'+version+'">'
                        + '<table class="table">'
                        + '<thead>'
                    + '<tr>'
                        + '<th></th>'
                        + '<th>Name</th>'
                        + '<th>User Name</th>'
                        + '<th>Affiliation</th>'
                    + '</tr>'
                + '</thead>'
                + '<tbody>';
            for (var key in data.users) {
                var affil = "";
                if ( typeof data.users[key].affiliation != 'undefined')
                    affil = data.users[key].affiliation.nameEntries[0].original;

                html += '<tr>'
                    + '<td><input type="radio" name="reassignTo" value="'+data.users[key].userid+'"></td>'
                    + '<td>' + data.users[key].fullName
                    + '</td>';
                html += "<td>"+data.users[key].userName+"</td>";
                html += "<td>"+affil+"</td>";
                html += '</tr>';
            }
            var footing = '</tbody></table>';

            if (html == "") {
                $("#usersPaneContent").html("<p class='text-center'>There are no users</p>");
            } else {
                $("#usersPaneContent").html(heading + html + footing);
            }
        } else {
            $("#usersPaneContent").html("<p class='text-center'>There are no users</p>");
        }
    });
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



    if($('#add_groups_to_user').exists()) {
        $('#add_groups_to_user').click(function(){

            var groups = [];
            $("input[id^='groupid_']").each(function() {
                groups.push($(this).val());
            });
            $.post("?command=administrator&subcommand=group_list", null, function (data) {
                if (data.groups.length > 0) {
                    var html = "";
                    var heading = '<table class="table">'
                    + '<thead>'
                    + '<tr>'
                    + '<th><input type="checkbox" id="checkall"></th>'
                    + '<th>Name</th>'
                    + '<th>Description</th>'
                    + '</tr>'
                    + '</thead>'
                    + '<tbody>';
                    for (var key in data.groups) {
                        if (jQuery.inArray(data.groups[key].id, groups) == -1) {
                            console.log(data.groups[key]);

                            html += '<tr>'
                            + '<td><input type="checkbox" name="groupadd_'+data.groups[key].id+'" id="groupadd_'+data.groups[key].id+'" value="'+data.groups[key].id+'"></td>'
                            + '<td>' + data.groups[key].label
                            + '<input type="hidden" name="groupaddlabel_'+data.groups[key].id+'" id="groupaddlabel_'+data.groups[key].id+'" value="'+data.groups[key].label+'">'
                            + '<input type="hidden" name="groupadddesc_'+data.groups[key].id+'" id="groupadddesc_'+data.groups[key].id+'" value="'+data.groups[key].description+'">'
                            + '</td>';
                            html += "<td>"+data.groups[key].description+"</td>";
                            html += '</tr>';
                        }
                    }
                    var footing = '</tbody></table>';

                    if (html == "") {
                        $("#addGroupsPaneContent").html("<p class='text-center'>There are no groups to add</p>");
                    } else {
                        $("#addGroupsPaneContent").html(heading + html + footing);
                        $("#checkall").click(function(event) {checkAllGroupAdd(this, event);});
                    }

                } else {
                    $("#addGroupsPaneContent").html("<p class='text-center'>There are no groups to add</p>");
                }
            });

            // don't reload the page
            //return false;
        });
    }


    if($('#addSelectedGroupsButton').exists()) {
        $('#addSelectedGroupsButton').click(function(){
            $("input[id^='groupadd_']").each(function() {
                if ($(this).is(":checked")) {
                    var id = $(this).val();
                    var html = "<tr id='grouprow_"+id+"'>"
                    + "<td>" + $("#groupaddlabel_"+id).val()
                    + " <input type=\"hidden\" name=\"groupid_"+id+"\" id=\"groupid_"+id+"\" value=\""+id+"\">"
                    + "</td>"
                    + "<td>"+$("#groupadddesc_"+id).val()+"</td>"
                    + "<td><a href=\"#\" class=\"btn btn-danger\" id=\"removeGroup_"+id+"\"><span class=\"fa fa-minus\" aria-hidden=\"true\"></span></a></td>"
                    + "</tr>";
                    $("#groups-tablebody").append(html);
                    $("#removeGroup_"+id).click(function(event) {removeGroupFromUser(id)});
                }
            });

            $("#addGroupsPaneContent").html("<p class='text-center'>Loading...</p>");

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
                    var id = $(this).val();
                    var html = "<tr id='userrow_"+id+"'>"
                        + "<td>" + $("#useraddname_"+id).val()
                            + " <input type=\"hidden\" name=\"userid_"+id+"\" id=\"userid_"+id+"\" value=\""+id+"\">"
                        + "</td>"
                        + "<td>"+$("#useraddusername_"+id).val()+"</td>"
                        + "<td>"+$("#useraddaff_"+id).val()+"</td>"
                        + "<td><a href=\"#\" class=\"btn btn-danger\" id=\"removeUser_"+id+"\"><span class=\"fa fa-minus\" aria-hidden=\"true\"></span></a></td>"
                       + "</tr>";
                    $("#users-tablebody").append(html);
                    $("#removeUser_"+id).click(function(event) {removeUserFromGroup(id)});
                }
            });

            $("#addUsersPaneContent").html("<p class='text-center'>Loading...</p>");

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
                    var heading = '<table class="table">'
                                + '<thead>'
                            + '<tr>'
                                + '<th><input type="checkbox" id="checkall"></th>'
                                + '<th>Name</th>'
                                + '<th>User Name</th>'
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
                                    + '<input type="hidden" name="useraddusername_'+data.users[key].userid+'" id="useraddusername_'+data.users[key].userid+'" value="'+data.users[key].userName+'">'
                                    + '<input type="hidden" name="useraddname_'+data.users[key].userid+'" id="useraddname_'+data.users[key].userid+'" value="'+data.users[key].fullName+'">'
                                    + '<input type="hidden" name="useraddaff_'+data.users[key].userid+'" id="useraddaff_'+data.users[key].userid+'" value="'+affil+'">'
                                + '</td>';
                            html += "<td>"+data.users[key].userName+"</td>";
                            html += "<td>"+affil+"</td>";
                            html += '</tr>';
                        }
                    }
                    var footing = '</tbody></table>';

                    if (html == "") {
                        $("#addUsersPaneContent").html("<p class='text-center'>There are no users to add</p>");
                    } else {
                        $("#addUsersPaneContent").html(heading + html + footing);
                        $("#checkall").click(function(event) {checkAllUserAdd(this, event);});
                    }
                } else {
                    $("#addUsersPaneContent").html("<p class='text-center'>There are no users to add</p>");
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
