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
	+ "    <a id=\"feedbackButton\" href=\"#\" title=\"Send feedback to SNAC\">"
	+ "        <span class=\"fa-stack fa-lg text-center\">"
	+ "            <i class=\"fa fa-comment fa-stack-2x text-info\"></i>"
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
	+ "                    <div class=\"alert alert-01\" id=\"send_status_message\" style=\"display: none\">"
	+ "                    </div><!-- end alert -->"
	+ "                </div>"
	+ "                <p>Please add comments or notes to our development staff in the box below.  The feedback system will automatically attach a screenshot of this page, the page URL, and referrer.</p>"
	+ "                <form id=\"feedback_form\">"
	+ "                    <div class=\"form-horizontal\">"
	+ "                        <div class=\"form-group\">"
	+ "                            <div class=\"col-xs-12\">"
	+ "                                <textarea id=\"body\" name=\"body\" class=\"form-control\" placeholder=\"Message Body\"></textarea>"
	+ "                            </div>"
	+ "                        </div>"
	+ "                        <div class=\"form-group\">"
	+ "                            <label class=\"control-label col-xs-3\">URL</label>"
	+ "                            <div class=\"col-xs-9\">"
	+ "                                <p class=\"form-control-static\">"+ window.location.href + "</p>"
	+ "                                <textarea id=\"page_url\" name=\"page_url\" class=\"form-control\" style=\"display:none;\">"+ window.location.href + "</textarea>"
	+ "                            </div>"
	+ "                        </div>"
	+ "                        <div class=\"form-group\">"
	+ "                            <label class=\"control-label col-xs-3\">Referrer</label>"
	+ "                            <div class=\"col-xs-9\">"
	+ "                                <p class=\"form-control-static\">" + document.referrer + "</p>"
	+ "                                <textarea id=\"page_referrer\" name=\"page_referrer\" class=\"form-control\" style=\"display:none;\">" + document.referrer + "</textarea>"
	+ "                            </div>"
	+ "                        </div>"
	+ "                        <div class=\"form-group\">"
	+ "                            <label class=\"control-label col-xs-3\">Screenshot</label>"
	+ "                            <div class=\"col-xs-9\">"
	+ "                                <p class=\"form-control-static\" id=\"feedback_pane_screenshot\">Loading...</p>"
	+ "                                <input type=\"hidden\" id=\"screenshot\" name=\"screenshot\"/>"
	+ "                            </div>"
	+ "                        </div>"
	+ "                    </div>"
	+ "                </form>"
	+ "            </div>"
	+ "            <div class=\"modal-footer\">"
	+ "                <button type=\"button\" class=\"btn btn-danger\" data-dismiss=\"modal\"><i class=\"fa fa-times-circle-o\" aria-hidden=\"true\"></i>"
	+ " Cancel</button>"
	+ "                <button type=\"button\" class=\"btn btn-primary\" id=\"send_feedback\"><i class=\"fa fa-paper-plane-o\" aria-hidden=\"true\"></i>"
	+ "Send</button>"
	+ "            </div>"
	+ "        </div>"
	+ "    </div>"
	+ "</div>"


var screenshot = null;

function takeScreenshot() {
    html2canvas(document.body, {
      //allowTaint: true,
      useCORS: true,
      letterRendering: true,
      onrendered: function(canvas) {
          screenshot = canvas;
          $("#feedback_pane_screenshot").html("");
          var dataURL = canvas.toDataURL();
          $("#feedback_pane_screenshot").append($('<img>',{src:dataURL}).css("max-width", "200px").css("max-height", "200px").css("border", "1px solid #000"));
          $("#screenshot").val(dataURL);
          $("#send_feedback").click(sendFeedback).prop("disabled", false).removeClass("disabled");
        //document.body.appendChild(canvas);
      }
    });
}

function sendFeedback() {
    var feedbackBody = {
        "subject" : "SNAC Feedback",
        "body" : "<p>" + $("#body").val() + "</p>" +
                    "<p><strong>Page:</strong> " + $(document).find("title").text() + "<br>" +
                    "<strong>URL:</strong> " + $("#page_url").val() + "<br>" +
                    "<strong>Referer</strong>:" + $("#page_referrer").val() + "</p>",
        "screenshot" : $("#screenshot").val()
    };

    $.post("?command=feedback", feedbackBody, function (data) {
        if (data.result == "success") {
            // show success alert
            $("#send_status_message").addClass("alert-success").html("<p>Feedback sent successfully.</p>");
            $('#send_status_message').slideDown();

            // After 2 seconds, we'll start doing things:
            setTimeout(function() {
                // close the alert
                $('#send_status_message').slideUp();
                $('#send_status_message').removeClass("alert-success").html("");

                // Hide the modal window
                $("#feedback_pane").modal("hide");
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

$(document).ready(function() {

    $("body").append(feedbackButtonHTML).append(feedbackPaneHTML);

    $("#feedbackButton").click(function() {
        takeScreenshot();
        $("#send_feedback").off("click").prop("disabled", true).addClass("disabled");
        $("#feedback_pane").modal("show");
        return false;
    });

});

