/**
 * Messaging Scripts
 *
 * Scripts used in the messaging cener page
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

var currentMessage = null;
var messageList = null;

function showMessage(messageID) {
    $.get("?command=message_read&messageid="+messageID, null, function (data) {
        if (data.result == "success") {
            var messageDate = new Date(data.message.timestamp);
            data.message.timestamp = messageDate.toLocaleString();

            var messageBody = "This message has no content";
            if (typeof data.message.body !== 'undefined')
                messageBody = (data.message.body).replace(/(?:\r\n|\r|\n)/g, '<br />');

            var text = $('#message_template').clone();
            var html = text.html().replace(/MESSAGE_SUBJECT/g, data.message.subject)
                                .replace(/MESSAGE_BODY/g, messageBody)
                                .replace(/MESSAGE_TIMESTAMP/g, data.message.timestamp);
            if (data.message.fromString) {
                html = html.replace(/MESSAGE_FROM/g, data.message.fromString);
            } else {
                html = html.replace(/MESSAGE_FROM/g, data.message.fromUser.fullName + " ("
                                        + data.message.fromUser.userName +")");
            }
            currentMessage = data.message;
            $('#message_view_pane').html(html);

            $("#reply_message").removeAttr("disabled").removeClass("disabled");
            $("#forward_message").removeAttr("disabled").removeClass("disabled");
            $("#delete_message").removeAttr("disabled").removeClass("disabled");
        }
    });
}

function sendMessage() {
    $.post("?command=message_send", $("#new_message_form").serialize(), function (data) {
        if (data.result == "success") {
            // show success alert
            $("#send_status_message").addClass("alert-success").html("<p>Message sent successfully.</p>");
            $('#send_status_message').slideDown();

            // After 2 seconds, we'll start doing things:
            setTimeout(function() {
                // close the alert
                $('#send_status_message').slideUp();
                $('#send_status_message').removeClass("alert-success").html("");

                // Hide the modal window
                $("#new_message_pane").modal("hide");

                // clear the form
                $("#new_message_form").find('input:text, input:password, input:file, select, textarea').val('');
                $("#new_message_form").find('input:radio, input:checkbox')
                     .removeAttr('checked').removeAttr('selected');
            }, 2000);

        } else {
            // show an error alert
            $("#send_status_message").addClass("alert-warning").html("<p>Error: "+data.message+"</p>");
            $('#send_status_message').slideDown();

            // close the alert after 10 seconds
            setTimeout(function() {
                $('#send_status_message').slideUp();
                $('#send_status_message').removeClass("alert-warning").html("");
            }, 10000);
        }
    });
}

function replyMessage() {
    // Set up the new message box
    $("#to_user").val(currentMessage.fromUser.userName);

    var subject = "RE: " + currentMessage.subject;
    if (currentMessage.subject.substring(0, 3) === "RE:")
        subject = currentMessage.subject;

    $("#subject").val(subject);
    $("#body").val("\n\n-- On " + currentMessage.timestamp + ", "
                                + currentMessage.fromUser.fullName
                                + " (" + currentMessage.fromUser.userName + ")"
                                + " wrote:\n\n" + currentMessage.body);

    // Open the new message modal window
    $("#new_message_pane").modal("show");
}

function forwardMessage() {
    // Set up the new message box

    var subject = "FW: " + currentMessage.subject;
    if (currentMessage.subject.substring(0, 3) === "FW:")
        subject = currentMessage.subject;


    $("#subject").val(subject);
    $("#body").val("\n\n-- On " + currentMessage.timestamp + ", "
                                + currentMessage.fromUser.fullName
                                + " (" + currentMessage.fromUser.userName + ")"
                                + " wrote:\n\n" + currentMessage.body);

    // Open the new message modal window
    $("#new_message_pane").modal("show");
}

function cancelMessage() {
    // Hide the modal window
    $("#new_message_pane").modal("hide");

    // clear the form
    $("#new_message_form").find('input:text, input:password, input:file, select, textarea').val('');
    $("#new_message_form").find('input:radio, input:checkbox')
         .removeAttr('checked').removeAttr('selected');
}

function deleteMessage() {
    var messageID = currentMessage.id;
    bootbox.confirm({
        title: "Confirm Deletion",
        message: "Are you sure you want to delete this message?",
        buttons: {
            cancel: {
                label: '<i class="fa fa-times"></i> Cancel'
            },
            confirm: {
                label: '<i class="fa fa-trash-o"></i> Delete'
            }
        },
        callback: function (result) {
            if (result) {
                $.post("?command=message_delete", { messageid: messageID }, function (data) {
                    if (data.result == "success") {
                        $("#reply_message").attr("disabled", true).addClass("disabled");
                        $("#forward_message").attr("disabled", true).addClass("disabled");
                        $("#delete_message").attr("disabled", true).addClass("disabled");
                        $("#message_view_pane").text("No Message selected");
                        messageList
                            .row($("#message_list_"+messageID))
                            .remove()
                            .draw();
                        //$("#message_list_"+messageID).remove();
                    }
                });
            }
        }
    });
}

$(document).ready(function() {
    // Load the table into a datatable
    messageList = $('#message_list').DataTable( {
        "language": {
            "emptyTable":     "No Messages Available"
        }
    });

    $("#message_list tbody").delegate("tr", "click", function() {
        var messageID = $("td:first input.messageid", this).val();
        $("td:first span.readflag", this).text("");
        $("#message_list tr").each(function() {$(this).removeClass("viewing");});
        $(this).addClass("viewing");
        showMessage(messageID);
    });

    $("#send_message").click(sendMessage);
    $("#cancel_message").click(cancelMessage);
    $("#reply_message").click(replyMessage);
    $("#forward_message").click(forwardMessage);
    $("#delete_message").click(deleteMessage);

});

