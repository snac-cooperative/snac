/**
 * Generic SNAC Scripts
 *
 * Collection of generic scripts used throughout the interface.
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

jQuery.fn.exists = function(){return this.length>0;}

// Global map
var geoMapView = null;
var impliedRelationsLoaded = false;

// Reservations for Edit
var reservedForEdit = false;

/**
 * Open the GeoPlace display
 *
 * @return boolean false to play nice with the browser
 */
function openGeoPlaceViewer(id) {

    $("#geoPlaceInfo").html("<p class='text-center'>Loading...</p>");
    $("#geoPlaceInfoPane").modal();

    $.get("?command=vocabulary&subcommand=read&type=geoPlace&id="+id, null, function (data) {
        if (data.result && data.result == "success" && data.term) {
            // Remove the old map
            if (geoMapView != null) {
                geoMapView.remove();
                geoMapView = null;
                $("#geoPlaceMap").html("");
            }

            // Add a slight delay to the map viewing so that the modal window has time to load
            setTimeout(function() {
                // Create the Map and add it
                geoMapView = L.map('geoPlaceMap').setView([data.term.latitude, data.term.longitude], 6);
                L.tileLayer('http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                }).addTo(geoMapView);
                var marker = L.marker([data.term.latitude, data.term.longitude]).addTo(geoMapView);

                // Add the data about the GeoTerm
                var html = "<h3>" + data.term.name + "</h3>" +
                            "<p>" +
                                "<strong>URI:</strong> " + data.term.uri + " <a class=\"label label-info\" target=\"_blank\" href=\"" + data.term.uri + "\">View</a>" + "<br/>" +
                                "<strong>Administration Code:</strong> " + data.term.administrationCode + "<br/>" +
                                "<strong>Country Code:</strong> " + data.term.countryCode + "<br/>" +
                                "<strong>Location:</strong> (" + data.term.latitude + ", " + data.term.longitude + ")" +
                            "</p>";
                $("#geoPlaceInfo").html(html);
            }, 200);
        }

    });

    return false;
}

$(document).ready(function() {
// Check that we're on the view page to add these:
if ($('#relatedPeopleImpliedLoad').exists()){
    var loadFunction = function() {
        // don't load a second time
        if (impliedRelationsLoaded)
            return;
        impliedRelationsLoaded = true;

        var loadingHTML = "<div class=\"text-center\">" +
                        "<p><i class=\"fa fa-spinner fa-pulse fa-3x fa-fw\"></i></p>" +
                        "<p>Loading ...</p>" +
                        "</div>";
        // Replace the HTML with the loading symbol
        $('#relatedPeopleImplied').html(loadingHTML);
        $('#relatedFamiliesImplied').html(loadingHTML);
        $('#relatedOrganizationsImplied').html(loadingHTML);

        $.get("?command=relations&constellationid="+$('#constellationid').val()+"&version="+$('#version').val(), null, function (data) {
            var peopleHTML = "";
            var familiesHTML = "";
            var organizationsHTML = "";
            if (data.in) {
                for (var key in data.in) {
                    if (data.in[key].constellation.entityType.term == "person") {
                            peopleHTML += "<div class=\"person\">" +
                                "<a href=\"?command=view&constellationid=" + data.in[key].constellation.id + "\">" +
                                data.in[key].constellation.nameEntries[0].original + "</a> " +
                                " <span class=\"arcrole\">" + data.in[key].relation.type.term + "</span>" +
                                "<div></div>" +
                            "</div>";
                    } else if (data.in[key].constellation.entityType.term == "corporateBody") {
                            organizationsHTML += "<div class=\"corporateBody\">" +
                                "<a href=\"?command=view&constellationid=" + data.in[key].constellation.id + "\">" +
                                data.in[key].constellation.nameEntries[0].original + "</a> " +
                                " <span class=\"arcrole\">" + data.in[key].relation.type.term + "</span>" +
                                "<div></div>" +
                            "</div>";
                    } else if (data.in[key].constellation.entityType.term == "family") {
                            familiesHTML += "<div class=\"family\">" +
                                "<a href=\"?command=view&constellationid=" + data.in[key].constellation.id + "\">" +
                                data.in[key].constellation.nameEntries[0].original + "</a> " +
                                " <span class=\"arcrole\">" + data.in[key].relation.type.term + "</span>" +
                                "<div></div>" +
                            "</div>";
                    }
                }
            }
            $('#relatedPeopleImplied').html(peopleHTML);
            $('#relatedFamiliesImplied').html(familiesHTML);
            $('#relatedOrganizationsImplied').html(organizationsHTML);
        });
        return false;

    };
    $('#relatedPeopleImpliedLoad').click(loadFunction);
    $('#relatedFamiliesImpliedLoad').click(loadFunction);
    $('#relatedOrganizationsImpliedLoad').click(loadFunction);
}

// Check that we're on the detailed view page to add these:
if ($('#impliedRelationsTab').exists()){
    function updatePictureTitle(shortName, i, newValue) {
        $('#'+shortName+'_relationPictureTitle_'+i).text(newValue);
    }

    function updatePictureIcon(shortName, i, entityType) {
        var html = "";
        if (entityType == 'person')
            html = '<i class="fa fa-user" aria-hidden="true"></i><br/>';
        else if (entityType == 'corporateBody')
            html = '<i class="fa fa-building" aria-hidden="true"></i><br/>';
        else if (entityType == 'family')
            html = '<i class="fa fa-users" aria-hidden="true"></i><br/>';
        $('#'+shortName+'_relationPictureIcon_'+i).html(html);
    }

    function updatePictureArrow(shortName, i, newValue) {
        $('#'+shortName+'_relationPictureArrow_'+i).text(newValue);
    }

    
    var loadFunction = function() {
        // don't load a second time
        if (impliedRelationsLoaded)
            return;
        impliedRelationsLoaded = true;

        var loadingHTML = "<div class=\"text-center\">" +
                        "<p><i class=\"fa fa-spinner fa-pulse fa-3x fa-fw\"></i></p>" +
                        "<p>Loading ...</p>" +
                        "</div>";
        // Replace the HTML with the loading symbol
        $('#impliedRelations').html(loadingHTML);

        $.get("?command=relations&constellationid="+$('#constellationid').val()+"&version="+$('#version').val(), null, function (data) {
            var finalHtml = "";
            if (data.in) {
                $('#impliedRelations').html("");
                var i = 0;
                for (var key in data.in) {
                    var text = $('#constellationRelation_template').clone();
                    var html = text.html().replace(/ZZ/g, "implied_"+i);
                    $('#impliedRelations').append(html);
                    $("#impliedRelations #constellationRelation_contentText_implied_"+i).text(data.in[key].constellation.nameEntries[0].original);
                    $("#impliedRelations #constellationRelation_targetArkIDText_implied_"+i).text(data.in[key].constellation.ark);
                    $("#impliedRelations #constellationRelation_typeText_implied_"+i).text(data.in[key].relation.type.term);
                    $("#impliedRelations #constellationRelation_noteText_implied_"+i).text(data.in[key].relation.note);
                    updatePictureIcon('constellationRelation', "implied_"+i, data.in[key].constellation.entityType.term);
                    updatePictureTitle('constellationRelation', "implied_"+i, data.in[key].constellation.nameEntries[0].original);
                    updatePictureArrow('constellationRelation', "implied_"+i, data.in[key].relation.type.term);
                    /*
                    finalHtml += "<div class=\"person\">" +
                        "<a href=\"?command=view&constellationid=" + data.in[key].constellation.id + "\">" +
                        data.in[key].constellation.nameEntries[0].original + "</a> " +
                        " <span class=\"arcrole\">" + data.in[key].relation.type.term + "</span>" +
                        "<div></div>" +
                    "</div>";
                    */
                    i++;
                }
            }
            //$('#impliedRelations').html(finalHtml);
        });
        return;

    };
    $('#impliedRelationsTab').click(loadFunction);
}



if ($('#reserveForEdit').exists()){
    var reserveEditFunction = function() {
        $("#reserveForEdit").addClass("disabled");
        if (!reservedForEdit) {
            $.get("?command=checkout&constellationid="+$('#constellationid').val()+"&version="+$('#version').val(), null, function (data) {
                if (data.result == 'success') {
                    bootbox.alert({
                        title: "Reserved",
                        message: "Constellation successfully reserved for edit."
                    });

                    $("#reserveForEditText").text("Reserved");
                    reservedForEdit = true;
                } else {
                    bootbox.alert({
                        title: "Error",
                        message: "Constellation could not be reserved.  You may have already reserved or edited this Constellation, or another user has it checked out."
                    });
                    $("#reserveForEditText").text("Non-Reservable");
                    reservedForEdit = true;
                }
            });
        }
        // Keep the page from changing
        return false;
    };

    $('#reserveForEdit').click(reserveEditFunction);
};


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
});
