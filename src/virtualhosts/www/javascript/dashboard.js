function updateSettingsBox(id, version, nameEntry) {

    $("#settings-name").text(nameEntry);

    var html = "";

    // Edit
    html += "<a href='?command=edit&constellationid="+id+"&version="+version+"' class='list-group-item list-group-item-info'>";
    html += "   <span class='glyphicon glyphicon-pencil'></span> Edit this Constellation";
    html += "</a>";

    // Preview
    html += "<a href='?command=view&constellationid="+id+"&version="+version+"' class='list-group-item list-group-item-success'>";
    html += "   <span class='glyphicon glyphicon-eye-open'></span> Preview this Constellation";
    html += "</a>";

    // Publish
    html += "<a href='?command=publish&constellationid="+id+"&version="+version+"' class='list-group-item list-group-item-warning'>";
    html += "   <span class='glyphicon glyphicon-upload'></span> Publish this Constellation";
    html += "</a>";

    // Delete
    html += "<a href='?command=delete&constellationid="+id+"&version="+version+"' class='list-group-item list-group-item-danger'>";
    html += "   <span class='glyphicon glyphicon-trash'></span> Delete this Constellation";
    html += "</a>";

    $("#settings-actions").html(html);
}
