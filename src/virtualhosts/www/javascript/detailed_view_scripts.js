/**
 * Detailed View Scripts
 *
 * Scripts used in the edit page
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */


function turnOnTooltips(shortName, i) {
    // Load tooltips
    $(function () {
          $('#'+shortName+'_panel_'+ i +' [data-toggle="tooltip"]').tooltip()
    })

    // Load popovers
    $(function () {
          $('#'+shortName+'_panel_'+ i +' [data-toggle="popover"]').popover({
                trigger: 'hover',
                container: 'body'
          })
    })


}

function turnOnTooltipsForTab(part='') {
    var divID = '';
    if (part != '')
        divID = "#" + part + " ";
    // Load tooltips
    $(function () {
          $(divID + '[data-toggle="tooltip"]').tooltip()
    })

    // Load popovers
    $(function () {
          $(divID + '[data-toggle="popover"]').popover({
                trigger: 'hover',
                container: 'body'
          })
    })

}


/**
 * Things to do when the page finishes loading
 */
$(document).ready(function() {

	var sourceid = 1;
    var sourceOpen = false;
	if ($('#sourcestab').exists()){
		$('#sourcestab').click(function(){
            // Don't open a second time
            if (sourceOpen)
                return;

            $.get("?command=details&part=sources&constellationid="+$('#constellationid').val()+"&version="+$('#version').val(), null, function (data) {
                sourceOpen = true;
                $('#sources').html(data);

                turnOnTooltipsForTab("sources");
            });
        });
    }

	var resourceRelationid = 1;
    var resourceRelationOpen = false;
	if ($('#resourceRelationstab').exists()){
		$('#resourceRelationstab').click(function(){
            // Don't open a second time
            if (resourceRelationOpen)
                return;

            $.get("?command=details&part=resourceRelations&constellationid="+$('#constellationid').val()+"&version="+$('#version').val(), null, function (data) {
                resourceRelationOpen = true;
                $('#resourceRelations').html(data);

                turnOnTooltipsForTab("resourceRelations");

                // If there is a display holdings map button and a holdings map on the page, then activate it
                if ($('#displayHoldingsMap').exists() && $('#holdingsMap').exists()){
                    $('#displayHoldingsMap').removeClass('disabled');
                    $('#displayHoldingsMap').click(displayHoldingsMap);
                    // remove the help information
                    $('#collection_locations_help').remove();
                }

            });
        });
    }


	var constellationRelationid = 1;
    var constellationRelationOpen = false;
	if ($('#constellationRelationstab').exists()){
		$('#constellationRelationstab').click(function(){
            // Don't open a second time
            if (constellationRelationOpen)
                return;

            $.get("?command=details&part=constellationRelations&constellationid="+$('#constellationid').val()+"&version="+$('#version').val(), null, function (data) {
                constellationRelationOpen = true;
                $('#constellationRelations').html(data);

                turnOnTooltipsForTab("constellationRelations");

                enableImpliedRelations();
            });
        });
    }


    turnOnTooltipsForTab();

});

function enableImpliedRelations() {
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
}

