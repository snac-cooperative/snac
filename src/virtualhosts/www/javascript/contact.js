/**
 * Contact Message Sender
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2017 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

function sendContactForm() {

    var feedbackBody = {
        "subject" : $("#subject").val() ? $("#subject").val() : "Contact Form Submission",
        "name" : $("#name").val(),
        "email" : $("#email").val(),
        "body" : "<p>" + tinymce.get("message").getContent() + "</p>" +
                    "<p><strong>URL:</strong> " + window.location.href + "<br>" +
                    "<strong>Referer</strong>:" + document.referrer + "</p>",
        "token" : $("#g-recaptcha-response").val()
    };

    $("#send_comment").prop("disabled", true).addClass("disabled");
    $("#send_comment").html("<i class=\"fa fa-spinner fa-pulse fa-fw\"></i> Sending...");

    $.post(snacUrl+"/feedback", feedbackBody, function (data) {
        if (data.result == "success") {
            // show success alert
            $("#comment_status_message").addClass("alert-success").html("<p>Message sent successfully.</p>");
            $('#comment_status_message').slideDown();

            $("#send_comment").html("<i class=\"fa fa-paper-plane-o\" aria-hidden=\"true\"></i> Send");
            $("#send_comment").prop("disabled", false).removeClass("disabled");
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
        if ($('#contact-topic-select').val() === '') {
            alert("Please select message topic.");
            return
        }
        sendContactForm();
        $("#send_comment").off("click").prop("disabled", true).addClass("disabled");
        return false;
    });

});
