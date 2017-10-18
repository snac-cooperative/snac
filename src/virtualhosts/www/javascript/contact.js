/**
 * Contact Message Sender
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2017 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

	+ "                                <p class=\"form-control-static\">"+ window.location.href + "</p>"
	+ "                                <p class=\"form-control-static\">" + document.referrer + "</p>"

function sendContactForm() {
    var feedbackBody = {
        "subject" : "SNAC Web Contact",
        "body" : "<p>" + tinymce.get("message").getContent() + "</p>" +
                    "<p><strong>Contact Information:</strong> " + $("#name").val() + " (" + $("#email").val() + ")</p>" +
                    "<p><strong>URL:</strong> " + window.location.href + "<br>" +
                    "<strong>Referer</strong>:" + document.referrer + "</p>",
        "token" : $("#g-recaptcha-response").val()
    };

    $.post(snacUrl+"/feedback", feedbackBody, function (data) {
        if (data.result == "success") {
            // show success alert
            $("#comment_status_message").addClass("alert-success").html("<p>Message sent successfully.</p>");
            $('#comment_status_message').slideDown();
        } else {
            // show an error alert
            $("#comment_status_message").addClass("alert-warning").html("<p>Error: "+data.message+"</p>");
            $('#comment_status_message').slideDown();

            // close the alert after 10 seconds
            setTimeout(function() {
                $('#comment_status_message').slideUp();
                $('#comment_status_message').removeClass("alert-warning").html("");
            }, 10000);
        }
    });
}

$(document).ready(function() {

    $("#send_comment").click(function() {
        sendContactForm();
        $("#send_comment").off("click").prop("disabled", true).addClass("disabled");
        return false;
    });

});
