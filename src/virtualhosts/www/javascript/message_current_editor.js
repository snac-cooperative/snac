/**
 * Message Current Editor
 *
 * Scripts to message current editor of a constellation
 *
 * @author Joseph Glass
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

var tinymceInstance = null;

tinymce.init({
    selector:"#body",
    min_height: 250,
    menubar: false,
    statusbar: false,
    plugins: [
        "advlist autolink lists link image charmap print preview anchor",
        "searchreplace visualblocks code fullscreen",
        "insertdatetime contextmenu paste code"
    ],
    toolbar: "undo redo | bold italic | bullist numlist outdent indent | link",
    setup: function (editor) {
        editor.on("change", function () {
            editor.save();
        });
        tinymceInstance = editor;
    }
});

function sendEditorMessage() {
    $.post(snacUrl+"/message_send", $("#new_message_form").serialize(), function (data) {
        if (data.result === "success") {
            // show success alert
            $("#send_status_message").addClass("alert-success").html("<p>Message sent successfully.</p>");
            $("#send_status_message").slideDown();

            // After 2 seconds, we'll start doing things:
            setTimeout(function() {
                // close the alert
                $("#send_status_message").slideUp();
                $("#send_status_message").removeClass("alert-success").html("");

				closeMessage();
                }, 2000);

        } else {
            // show an error alert
            $("#send_status_message").addClass("alert-warning").html("<p>Error: "+data.result+"</p>");
            $("#send_status_message").slideDown();

            // close the alert after 10 seconds
            setTimeout(function() {
                $("#send_status_message").slideUp();
                $("#send_status_message").removeClass("alert-warning").html("");
            }, 10000);
        }
    });
}


function closeMessage() {
    // Hide the modal window
    $("#new_message_pane").modal("hide");

    // clear the form
    $("#new_message_form").find("#subject, textarea").val("");
    $("#new_message_form").find("input:radio, input:checkbox")
         .removeAttr("checked").removeAttr("selected");
    tinymceInstance.load();
}


$(document).ready(function() {

    $("#send_message").click(sendEditorMessage);
    $("#cancel_message").click(closeMessage);
    $("#cancel_message_close").click(closeMessage);

});
