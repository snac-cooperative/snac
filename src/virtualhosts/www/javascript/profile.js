/**
 * Profile Page Actions
 *
 * Contains code that handles what happens in the GUI when
 * the user clicks any save button.
 *
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

jQuery.fn.exists = function(){return this.length>0;}


function makeProfileEditable() {

    $(".editable").each(function() {
        var obj = $(this);
        var id = obj.attr('id').replace('Div','');
        var value = $("#"+id).val();

        var html = "<input type=\"text\" class=\"form-control\" value=\""+value+"\" name=\""+id+"\" id=\""+id+"\"/>";
        obj.html(html);
    });

    $("#edit").attr("style", "display:none");
    $("#update").attr("style", "display:auto");
}

function makeProfileUneditable() {

    $(".editable").each(function() {
        var obj = $(this);
        var id = obj.attr('id').replace('Div','');
        var value = $("#"+id).val();
        var html = "<p class='form-control-static'>"+value+"</p>";
        html += "<input type=\"hidden\" value=\""+value+"\" name=\""+id+"\" id=\""+id+"\"/>";
        obj.html(html);
    });
    $("#edit").attr("style", "display:auto");
    $("#update").attr("style", "display:none");

}


/**
 * Only load this script once the document is fully loaded
 */
$(document).ready(function() {

    // Save and Continue button
    if($('#edit').exists()) {
        $('#edit').click(function(){
            makeProfileEditable();

        });
    };

    // Save and Continue button
    if($('#update').exists()) {
        $('#update').click(function(){

            // Send the data back by AJAX call
            $.post(snacUrl+"/update_profile", $("#profile_form").serialize(), function (data) {
                // Check the return value from the ajax. If success, then alert the
                // user and make appropriate updates.
                if (data.result == "success") {
                    makeProfileUneditable();
                }
            });

        });
    };
});
