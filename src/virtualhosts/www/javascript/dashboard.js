/**
 * Dashboard JS
 *
 * Interactions with the Dashboard
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

function updateSettingsBox(id, version, nameEntry) {

    $("#settings-name").text(nameEntry);

    var html = "";

    // Edit
    html += "<a href='?command=edit&constellationid="+id+"&version="+version+"' class='list-group-item list-group-item-info'>";
    html += "   <span class='glyphicon glyphicon-pencil'></span> Edit this Constellation";
    html += '   <span class="pull-right glyphicon glyphicon-question-sign" title="Help" data-content="Open this constellation in editing mode, continuing where you left off." data-toggle="popover" data-placement="right"></span>';
    html += "</a>";

    // Preview
    html += "<a href='?command=view&constellationid="+id+"&version="+version+"&preview=1' class='list-group-item list-group-item-success'>";
    html += "   <span class='glyphicon glyphicon-eye-open'></span> Preview this Constellation";
    html += '   <span class="pull-right glyphicon glyphicon-question-sign" title="Help" data-content="Preview the current state of this constellation in the view mode." data-toggle="popover" data-placement="right"></span>';
    html += "</a>";

    // Send for Review
    html += "<a href='?command=review&constellationid="+id+"&version="+version+"' class='list-group-item list-group-item-review'>";
    html += "   <span class='glyphicon glyphicon-send'></span> Send this Constellation for Review";
    html += '   <span class="pull-right glyphicon glyphicon-question-sign" title="Help" data-content="Send your saved changes to a reviewer." data-toggle="popover" data-placement="right"></span>';
    html += "</a>";

    // Publish
    if (permissions.Publish) {
        html += "<a href='?command=publish&constellationid="+id+"&version="+version+"' class='list-group-item list-group-item-warning'>";
        html += "   <span class='glyphicon glyphicon-upload'></span> Publish this Constellation";
        html += '   <span class="pull-right glyphicon glyphicon-question-sign" title="Help" data-content="Publish your saved changes, making them publicly available." data-toggle="popover" data-placement="right"></span>';
        html += "</a>";
    }

    // Delete
    /*
    html += "<a href='?command=delete&constellationid="+id+"&version="+version+"' class='list-group-item list-group-item-danger'";
    html += "       onClick=\"return window.confirm('Are your sure you want to delete "+nameEntry+"?');\">";
    html += "   <span class='glyphicon glyphicon-trash'></span> Delete this Constellation";
    html += '   <span class="pull-right glyphicon glyphicon-question-sign" title="Help" data-content="Delete this constellation from SNAC." data-toggle="popover" data-placement="right"></span>';
    html += "</a>";
    */

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

$(document).ready(function() {
    if (getParam("message")) {
        $('#status-message').html("<p>"+getParam("message")+"</p>");
        $('#status-message').slideDown();
        setTimeout(function(){
            $('#status-message').slideUp();
        }, 7000);

    }
});
