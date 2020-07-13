/**
 * Dashboard JS
 *
 * Interactions with the Dashboard
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

var reviewPayload = {
    id : null,
    version : null,
    reviewer : null,
    reviewmessage : null
}

function sendForReviewModal(id, version) {
    // Close the settings modal// Close this modal and open the new modal
    $("#constellation-settings").modal("hide");

    // Set the ID/Version to send
    reviewPayload.id = id;
    reviewPayload.version = version;

    // Set a 500ms timeout to give the hidden search pane time to fully close
    setTimeout(function() {$("#sendReviewPane").modal("show");}, 500);

    // Play nice and don't let the browser reload
    return false;
}

function sendReview() {
    // Pull the message text
    reviewPayload.reviewmessage = $("#sendReviewMessage").val();

    // Send the browser to the review page (which will then redirect them back to the dashboard)
    window.location.href = snacUrl+"/review/"+reviewPayload.id+"/"+reviewPayload.version+"?reviewer="+reviewPayload.reviewer+"&reviewmessage="+encodeURIComponent(reviewPayload.reviewmessage);
}

function updateSettingsBox(id, version, nameEntry) {

    $("#settings-name").text(nameEntry);

    var html = "";

    // Edit
    html += `<a href="${snacUrl}/edit/${id}/${version}" class="list-group-item list-group-item-info">
                <span class="glyphicon glyphicon-pencil"></span> Edit this Constellation
                <span class="pull-right glyphicon glyphicon-question-sign" title="Help"
                    data-content="Open this constellation in editing mode, continuing where you left off." data-toggle="popover" data-placement="right">
                </span>
            </a>`;

    // Preview
    html += `<a href="${snacUrl}/view/${id}/${version}?preview=1" class="list-group-item list-group-item-success">
                <span class="glyphicon glyphicon-eye-open"></span> Preview this Constellation
                <span class="pull-right glyphicon glyphicon-question-sign" title="Help"
                    data-content="Preview the current state of this constellation in the view mode." data-toggle="popover" data-placement="right">
                </span>
            </a>`;

    // Send for Review
    html += `<a href="#" onClick="sendForReviewModal(${id},${version});" class="list-group-item list-group-item-review">
                <span class="glyphicon glyphicon-send"></span> Send this Constellation for Review
                <span class="pull-right glyphicon glyphicon-question-sign" title="Help"
                    data-content="Send your saved changes to a reviewer." data-toggle="popover" data-placement="right">
                </span>
            </a>`;

    // Publish
    if (permissions.Publish) {
        html += `<a href="${snacUrl}/publish/${id}/${version}" class="list-group-item list-group-item-warning">
                    <span class="glyphicon glyphicon-upload"></span> Publish this Constellation
                    <span class="pull-right glyphicon glyphicon-question-sign" title="Help"
                        data-content="Publish your saved changes, making them publicly available." data-toggle="popover" data-placement="right">
                    </span>
                </a>`;
    }

    // Delete
    if (permissions.Delete) {
        html += `<a href="${snacUrl}/delete/${id}/${version}" class="list-group-item list-group-item-danger"
                       onClick="return window.confirm('Are your sure you want to delete ${nameEntry}?')">
                    <span class="glyphicon glyphicon-trash"></span> Delete this Constellation
                    <span class="pull-right glyphicon glyphicon-question-sign" title="Help"
                        data-content="Delete this constellation from SNAC." data-toggle="popover" data-placement="right">
                    </span>
                </a>`;
    }

    $("#settings-actions").html(html);

    // Load tooltips
    $(function () {
          $('[data-toggle="tooltip"]').tooltip()
    })

    // Load popovers
    $(function () {
          $('[data-toggle="popover"]').popover({
                trigger: 'hover',
                container: 'body'
          })
    })
}

function getParam(val) {
    var result = false,
        tmp = [];
    var items = location.search.substr(1).split("&");
    for (var index = 0; index < items.length; index++) {
        tmp = items[index].split("=");
        if (tmp[0] === val) result = decodeURIComponent(tmp[1]);
    }
    return result;
}

function collapsePanel(event) {
    var $panel = $(event.target).closest('.panel');
    $panel.find('.panel-body').slideToggle();
    $panel.find('.hideicon').toggle();
}

$(document).ready(function() {
    if (getParam("message")) {
        $('#status-message').html("<p>"+getParam("message")+"</p>");
        $('#status-message').slideDown();
        setTimeout(function(){
            $('#status-message').slideUp();
        }, 7000);
    }

    // Turn on the reviewer buttons
    $("#save_and_review_touser").click(function() {
        reviewPayload.reviewer = $("#reviewersearchbox").val();
        sendReview();
    });
    $("#save_and_review_general").click(function() {
        reviewPayload.reviewer = null;
        sendReview();
    });


    $(".accordion-panel").click(function(event) {
        collapsePanel(event);
    });

});
