/**
 * Feedback Message Sender
 *
 * Adds a Feedback button and modal to the page DOM.  On click, it takes a javascript screenshot and
 * allows the user to send feedback along with the screenshot.
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2017 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

var feedbackButtonHTML = "<div style=\"z-index: 999; position: fixed; right: 15px; bottom: 15px; padding: 0px; background:transparent;\">"
	+ "    <a id=\"feedbackButton\" href=\"#\" title=\"Send feedback\">"
	+ "        <span class=\"fa-stack fa-lg text-center\">"
	+ "            <i class=\"fa fa-comment fa-flip-horizontal fa-stack-2x text-info\"></i>"
	+ "            <i class=\"fa fa-question fa-stack-1x fa-inverse\"></i>"
	+ "        </span>"
	+ "    </a>"
    + "</div>";

var feedbackPaneHTML = "<div class=\"modal fade\" id=\"feedback_pane\" tabindex=\"-1\" role=\"dialog\" aria-labelledby=\"feedback_pane\">"
	+ "    <div class=\"modal-dialog\" role=\"document\">"
	+ "        <div class=\"modal-content\">"
	+ "            <div class=\"modal-header primary\">"
	+ "                <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button>"
	+ "                <h4 class=\"modal-title\" id=\"feedback_title\">Send Feedback</h4>"
	+ "            </div>"
	+ "            <div class=\"modal-body\" id=\"feedback_content\">"
	+ "                <div class=\"row\">"
	+ "                    <div class=\"alert alert-01\" id=\"feedback_status_message\" style=\"display: none\">"
	+ "                    </div><!-- end alert -->"
	+ "                </div>"
	+ "                <p>Please add comments or notes to our development staff in the box below.  The feedback system will automatically attach a screenshot of this page, the page URL, and referrer.</p>"
	+ "                <form id=\"feedback_form\">"
	+ "                    <div class=\"form-horizontal\">"
	+ "                        <div class=\"form-group\">"
	+ "                            <div class=\"col-xs-12\">"
	+ "                                <textarea id=\"feedback_body\" class=\"form-control\" placeholder=\"Compose your feedback here... (required)\"></textarea>"
	+ "                            </div>"
	+ "                        </div>"
	+ "                        <div class=\"form-group\">"
	+ "                            <label class=\"control-label col-xs-3\">Name</label>"
	+ "                            <div class=\"col-xs-9\">"
	+ "                                <input type=\"text\" placeholder=\"Your Name (required)\" class=\"form-control\" id=\"feedback_name\"></p>"
	+ "                            </div>"
	+ "                        </div>"
	+ "                        <div class=\"form-group\">"
	+ "                            <label class=\"control-label col-xs-3\">Email</label>"
	+ "                            <div class=\"col-xs-9\">"
	+ "                                <input type=\"text\" placeholder=\"Your Email (required)\" class=\"form-control\" id=\"feedback_email\"></p>"
	+ "                            </div>"
	+ "                        </div>"
	+ "                        <div class=\"form-group\">"
	+ "                            <label class=\"control-label col-xs-3\">URL</label>"
	+ "                            <div class=\"col-xs-9\">"
	+ "                                <p class=\"form-control-static\">"+ window.location.href + "</p>"
	+ "                                <textarea id=\"feedback_page_url\" class=\"form-control\" style=\"display:none;\">"+ window.location.href + "</textarea>"
	+ "                            </div>"
	+ "                        </div>"
	+ "                        <div class=\"form-group\">"
	+ "                            <label class=\"control-label col-xs-3\">Referrer</label>"
	+ "                            <div class=\"col-xs-9\">"
	+ "                                <p class=\"form-control-static\">" + document.referrer + "</p>"
	+ "                                <textarea id=\"feedback_page_referrer\" class=\"form-control\" style=\"display:none;\">" + document.referrer + "</textarea>"
	+ "                            </div>"
	+ "                        </div>"
	+ "                        <div class=\"form-group\">"
	+ "                            <label class=\"control-label col-xs-3\">Screenshot</label>"
	+ "                            <div class=\"col-xs-9\">"
	+ "                                <p class=\"form-control-static\" id=\"feedback_pane_screenshot\">Loading...</p>"
	+ "                                <input type=\"hidden\" id=\"feedback_screenshot\"/>"
	+ "                            </div>"
	+ "                        </div>"
	+ "                        <div class=\"form-group\">"
	+ "                            <label class=\"control-label col-xs-3\"></label>"
	+ "                            <div class=\"col-xs-9\">"
	+ "                                <div class=\"g-recaptcha\" data-sitekey=\"6LdjGCwUAAAAAJSU24QBnENdvbcX6oHJbjTzO1jN\"></div>"
	+ "                            </div>"
	+ "                        </div>"
	+ "                    </div>"
	+ "                </form>"
	+ "            </div>"
	+ "            <div class=\"modal-footer\">"
	+ "                <button type=\"button\" class=\"btn btn-danger\" data-dismiss=\"modal\"><i class=\"fa fa-times-circle-o\" aria-hidden=\"true\"></i>"
	+ " Cancel</button>"
	+ "                <button type=\"button\" class=\"btn btn-primary\" id=\"send_feedback\"><i class=\"fa fa-paper-plane-o\" aria-hidden=\"true\"></i>"
	+ " Send</button>"
	+ "            </div>"
	+ "        </div>"
	+ "    </div>"
	+ "</div>"


var feedbackScreenshot = null;

function feedbackTakeScreenshot() {
    html2canvas(document.body, {
      //allowTaint: true,
      useCORS: true,
      letterRendering: true,
      onrendered: function(canvas) {
          feedbackScreenshot = canvas;
          $("#feedback_pane_screenshot").html("");
          var dataURL = canvas.toDataURL();
          $("#feedback_pane_screenshot").append($('<img>',{src:dataURL}).css("max-width", "200px").css("max-height", "200px").css("border", "1px solid #000"));
          $("#feedback_screenshot").val(dataURL);
          $("#send_feedback").click(sendFeedback).prop("disabled", false).removeClass("disabled");
        //document.body.appendChild(canvas);
      }
    });
}

function sendFeedback() {
    // Check for required information
    if ($("#feedback_body").val() == "" || $("#feedback_name").val() == "" || $("#feedback_email").val() == "") {
        $("#feedback_status_message").removeClass("alert-success").removeClass("alert-warning").addClass("alert-danger").html("<p>Please include all information.  Name, Email, and Message are required.</p>");
        $('#feedback_status_message').slideDown();

        // After 2 seconds, we'll start doing things:
        setTimeout(function() {
            // close the alert
            $('#feedback_status_message').slideUp();
            $('#feedback_status_message').removeClass("alert-danger").html("");
        }, 2000);
        
        return false;
    }


    // hide the button
    $("#send_feedback").off("click").prop("disabled", true).addClass("disabled");
    $("#send_feedback").html("<i class=\"fa fa-spinner fa-pulse fa-fw\"></i> Sending...");


    var feedbackBody = {
        "subject" : "SNAC Feedback",
        "name" : $("#feedback_name").val(),
        "email" : $("#feedback_email").val(),
        "body" : "<p>" + $("#feedback_body").val() + "</p>" +
                    "<p><strong>Page:</strong> " + $(document).find("title").text() + "<br>" +
                    "<strong>URL:</strong> " + $("#feedback_page_url").val() + "<br>" +
                    "<strong>Referer</strong>:" + $("#feedback_page_referrer").val() + "</p>" +
                    "<p><strong>Contact Information:</strong> " + $("#feedback_name").val() + " (" + $("#feedback_email").val() + ")</p>",
        "screenshot" : $("#feedback_screenshot").val(),
        "token" : $("#g-recaptcha-response").val()
    };

    $.post(snacUrl+"/feedback", feedbackBody, function (data) {
        if (data.result == "success") {
            // show success alert
            $("#send_feedback").html("<i class=\"fa fa-paper-plane-o\" aria-hidden=\"true\"></i> Send");
            $("#feedback_status_message").removeClass("alert-warning").removeClass("alert-danger").addClass("alert-success").html("<p>Feedback sent successfully.</p>");
            $('#feedback_status_message').slideDown();

            // After 2 seconds, we'll start doing things:
            setTimeout(function() {
                // close the alert
                $('#feedback_status_message').slideUp();
                $('#feedback_status_message').removeClass("alert-success").html("");

                // Hide the modal window
                $("#feedback_pane").modal("hide");
            }, 2000);

        } else {
            // show an error alert
            $("#send_feedback").html("<i class=\"fa fa-paper-plane-o\" aria-hidden=\"true\"></i> Send");
            $("#send_feedback").click(sendFeedback).prop("disabled", false).removeClass("disabled");
            $("#feedback_status_message").removeClass("alert-success").removeClass("alert-danger").addClass("alert-warning").html("<p>Error: "+data.message+"</p>");
            $('#feedback_status_message').slideDown();

            // close the alert after 10 seconds
            setTimeout(function() {
                $('#feedback_status_message').slideUp();
                $('#feedback_status_message').removeClass("alert-warning").html("");
            }, 10000);
        }
    });
}

$(document).ready(function() {

    $("body").append(feedbackButtonHTML).append(feedbackPaneHTML);

        $("#feedbackButton").click(function() {
            $.getScript('https://www.google.com/recaptcha/api.js', function() {
                feedbackTakeScreenshot();
                $("#send_feedback").off("click").prop("disabled", true).addClass("disabled");
                $("#feedback_pane").modal("show");
            });
            return false;
        });

});
