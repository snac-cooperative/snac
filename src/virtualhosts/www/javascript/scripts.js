// Global map
var geoMapView = null;

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


