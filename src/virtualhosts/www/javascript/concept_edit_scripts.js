/**
 * Concept Edit Scripts
 *
 * Scripts used in the concept edit page
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

// Has anything been edited on this page?
var somethingHasBeenEdited = false;

function setEditedFlag(val) {
    if (val == true) {
       $("#save_and_continue").addClass("save-active");
    } else {
       $("#save_and_continue").removeClass("save-active");
    }
    somethingHasBeenEdited = val;
}


// Global Undo Set
var undoSet = new Array();

var defaults = {
    language: {
        id: 130,
        term: "eng"
    },
    script: {
        id: 586,
        term: "Latn"
    }
};



/**
 * Display Error message
 *
 * Displays the error message box to the user with the given error message
 *
 * @param string|object err The error message (string) or error object containing a message and type string
 */
function displayErrorMessage(err, data) {
    var errorMsg = "";
    if (typeof err === 'undefined')
        errorMsg = "an unknown problem occurred";
    else if ((typeof err) == "string")
        errorMsg = err;
    else if (err.message)
        errorMsg = err.message;
    else if (err.type)
        errorMsg = err.type;
    else
        errorMsg = "an unknown problem occurred";

    $('#error-message').html("<h4>Oops</h4><p>"+errorMsg+"</p>"
            +"<p class=\"text-right\">"
            +"<button type=\"button\" class=\"btn btn-warning\" aria-label=\"Close\" onClick=\"$('#error-message').slideUp()\">"+
            "Stay Here"
            +"</button> "
            +"<a href=\""+snacUrl+"/dashboard\" type=\"button\" class=\"btn btn-danger\">"+
            "Go to Dashboard"
            +"</a>"
            +"</p>");
    setTimeout(function(){
        $('#error-message').slideDown();
    }, 500);

    // For reference, put the server response in the console
    console.log(data);
}

/**
 * Undo Edit
 *
 * Returns the html for the (short, i) panel back to its original state, i.e. removes
 * the edit.  Before doing this, to clean up the page, it first makes the edited version
 * uneditable.
 *
 * @param string short The short name of the data object, such as "nameEntry" or "occupation"
 * @param string|int i The index within the edit page of the object.
 */
function undoEdit(short, i) {
	makeUneditable(short, i);

	// restore the old content
	$("#" + short + "_datapart_" + i).replaceWith(undoSet[short+"-"+i]);
    turnOnTooltips(short,i);
    $("#" + short + "_datapart_" + i + " input[type='checkbox']").each(function() {
        var obj = $(this);
        obj.bootstrapToggle();
    });
}

/**
 * Change vocabulary input divs to selects
 *
 * Changes all div's with id "select_" for a given data object (shortName, idStr) from a list of
 * inputs defining the parameters to a select (view mode) to a select box (edit mode).  It then
 * calls the select2 function to replace the select with an AJAX-compatible select.
 *
 * This function handles VOCABULARY select boxes ONLY.
 *
 * Note: idStr must not have the "_" pre-appended
 *
 * @param string shortName The short name of the data object, such as "nameEntry" or "occupation"
 * @param string|int idStr The index within the edit page of the object.
 */
function textToSelect(shortName, idStr) {
    $("#"+shortName+"_datapart_" + idStr + " div[id^='select_"+shortName+"']").each(function() {
        var cont = $(this);
        if(cont.attr('id').endsWith("_"+idStr) && !cont.attr('id').endsWith("ZZ")) {
            // remove the short name and "select_" from the string we're parsing
            var divStr = cont.attr('id').replace("select_", "").replace(shortName + "_", "");
            // remove the idstr to receive the name of this element
            var regex = new RegExp("\_"+idStr+"$", "g");
            var name = divStr.replace(regex, "");
            var id = $("#"+shortName+"_"+name+"_id_"+idStr).val();
            var term = $("#"+shortName+"_"+name+"_term_"+idStr).val();
            var vocabtype = $("#"+shortName+"_"+name+"_vocabtype_"+idStr).val();
            var minlength = $("#"+shortName+"_"+name+"_minlength_"+idStr).val();
            var placeholder = "Select";
            if ($("#"+shortName+"_"+name+"_placeholder_"+idStr).exists()) {
                placeholder = $("#"+shortName+"_"+name+"_placeholder_"+idStr).val();
            }
            var options = "";
            if ($("#"+shortName+"_"+name+"_defaultOptions_"+idStr).exists()) {
                options = $("#"+shortName+"_"+name+"_defaultOptions_"+idStr).val();
            }

            cont.html("<select id='"+shortName+"_"+name+"_id_"+idStr+"' name='"+shortName+"_"+name+"_id_"+idStr+"' class='form-control' data-placeholder='"+placeholder+"'>"+
                    "<option></option>"+
                    "<option value=\""+id+"\" selected>"+term+"</option>"+ options +
                    "</select>"+
                    "<input type=\"hidden\" id=\""+shortName+"_"+name+"_vocabtype_"+idStr+"\" " +
                        "name=\""+shortName+"_"+name+"_vocabtype_"+idStr+"\" value=\""+vocabtype+"\"/>" +
                    "<input type=\"hidden\" id=\""+shortName+"_"+name+"_minlength_"+idStr+"\" " +
                        "name=\""+shortName+"_"+name+"_minlength_"+idStr+"\" value=\""+minlength+"\"/>");

            if (name == "citation")
                scm_source_select_replace($("#"+shortName+"_"+name+"_id_"+idStr), "_"+idStr);
            else if (shortName == "sameAs" && name == "baseuri") {
                //The following block handles the specific case of Same As External Resource association form
                var loadPromise = loadVocabSelectOptions($("#"+shortName+"_"+name+"_id_"+idStr), "external_sameas_domain", "Base URI", true);
                loadPromise.then(function(result){
                    var currentURI = $("#"+shortName+"_uri_"+idStr).val();
                    if (currentURI) {
                        var found = false;
                        $("#"+shortName+"_"+name+"_id_"+idStr+" option").each(function(index,op){
                            if( found || (!op.hasAttribute("value") || !op.value)) {
                                return;
                            }
                            var uriComponents = op.value.split(/{id}/);
                            if(currentURI.indexOf(uriComponents[0]) == 0) {
                                var currOption = op.value;
                                var currId = currentURI.replace(uriComponents[0],"");
                                if(!!uriComponents[1]) {
                                    if(currentURI.indexOf(uriComponents[1]) != -1) {
                                        currId = currId.replace(/uriComponents[1]$/,"");
                                    }
                                }
                                found = true;
                                $("#sameAs_baseuri_id_"+idStr).val(currOption);
                                $("#sameAs_baseuri_id_"+idStr).trigger("change");
                                $("#sameAs_uriid_"+idStr).val(currId);
                                $("#sameAs_uri_"+idStr).val(currOption.replace(/{id}/,currId));
                            }
                        });
                    }
                    $("#"+shortName+"_uri_"+idStr).prop("readonly", true);
                });
                // If dealing with subject, function, or occupation term, query Concept Vocab system
            } else if (shortName == 'activity' || shortName == 'subject' || shortName =='occupation') {
                conceptVocabSelectReplace($("#"+shortName+"_"+name+"_id_"+idStr), "_"+idStr, vocabtype, minlength);
            } else
                vocab_select_replace($("#"+shortName+"_"+name+"_id_"+idStr), "_"+idStr, vocabtype, minlength);

        }
    });
}

function textToCheckbox(shortName, idStr) {
    $("#"+shortName+"_datapart_" + idStr + " div[id^='checkbox_"+shortName+"']").each(function() {
        var cont = $(this);
        if(cont.attr('id').endsWith("_"+idStr) && !cont.attr('id').endsWith("ZZ")) {
            // remove the short name and "select_" from the string we're parsing
            var divStr = cont.attr('id').replace(/^checkbox_/, "").replace(shortName + "_", "");
            // remove the idstr to receive the name of this element
            var regex = new RegExp("\_"+idStr+"$", "g");
            var name = divStr.replace(regex, "");
            var value = $("#"+shortName+"_"+name+"_"+idStr).val();
            var placeholderOn = "";
            if ($("#"+shortName+"_"+name+"_placeholderOn_"+idStr).exists()) {
                placeholderOn = $("#"+shortName+"_"+name+"_placeholderOn_"+idStr).val();
            }
            var placeholderOff = "";
            if ($("#"+shortName+"_"+name+"_placeholderOff_"+idStr).exists()) {
                placeholderOff = $("#"+shortName+"_"+name+"_placeholderOff_"+idStr).val();
            }

            var html = "<input id='"+shortName+"_"+name+"_"+idStr+"' name='"+shortName+"_"+name+"_"+
                    idStr+"' class='form-control' type='checkbox' value=\"checked\""+
                    "data-on=\""+placeholderOn+"\" data-off=\""+placeholderOff+"\"";
            if (value == 'checked')
                html += " checked";
            html += "/>";
            if (placeholderOn != "") {
                html += "<input type=\"hidden\" id=\""+shortName+"_"+name+"_placeholderOn_"+idStr+"\" " +
                "value=\""+placeholderOn+"\"/>";
            }
            if (placeholderOff != "") {
                html += "<input type=\"hidden\" id=\""+shortName+"_"+name+"_placeholderOff_"+idStr+"\" " +
                "value=\""+placeholderOff+"\"/>";
            }

            cont.html(html);
            $("#"+shortName+"_"+name+"_"+idStr).bootstrapToggle();
        }
    });


}


function checkboxToText(shortName, idStr) {
    $("#"+shortName+"_datapart_" + idStr + " div[id^='checkbox_"+shortName+"']").each(function() {
        var cont = $(this);
        if(cont.attr('id').endsWith("_"+idStr) && !cont.attr('id').endsWith("ZZ")) {
            // remove the short name and "select_" from the string we're parsing
            var divStr = cont.attr('id').replace(/^checkbox_/, "").replace(shortName + "_", "");
            // remove the idstr to receive the name of this element
            var regex = new RegExp("\_"+idStr+"$", "g");
            var name = divStr.replace(regex, "");
            var value = $("#"+shortName+"_"+name+"_"+idStr).val();
            var checked = $("#"+shortName+"_"+name+"_"+idStr).prop('checked');
            var placeholderOn = "";
            if ($("#"+shortName+"_"+name+"_placeholderOn_"+idStr).exists()) {
                placeholderOn = $("#"+shortName+"_"+name+"_placeholderOn_"+idStr).val();
            }
            var placeholderOff = "";
            if ($("#"+shortName+"_"+name+"_placeholderOff_"+idStr).exists()) {
                placeholderOff = $("#"+shortName+"_"+name+"_placeholderOff_"+idStr).val();
            }


            var html = "<input id='"+shortName+"_"+name+"_"+idStr+"' name='"+shortName+"_"+name+"_"+
                    idStr+"' type='hidden' value=\"";
            if (checked)
                html += "checked";
            html +="\"/>";

            html += "<p class='form-control-static'>";
            if (checked) {
                if (placeholderOn != "")
                   html += placeholderOn;
                else
                   html += value;
            } else {
                if (placeholderOff != "")
                   html += placeholderOff;
            }
            html += "</p>";
            if (placeholderOn != "") {
                html += "<input type=\"hidden\" id=\""+shortName+"_"+name+"_placeholderOn_"+idStr+"\" " +
                "value=\""+placeholderOn+"\"/>";
            }
            if (placeholderOff != "") {
                html += "<input type=\"hidden\" id=\""+shortName+"_"+name+"_placeholderOff_"+idStr+"\" " +
                "value=\""+placeholderOff+"\"/>";
            }

            $("#"+shortName+"_"+name+"_"+idStr).bootstrapToggle("destroy");
            cont.html(html);
        }
    });


}

function textToInput(shortName, idStr) {
    $("#"+shortName+"_datapart_" + idStr + " div[id^='text_"+shortName+"']").each(function() {
        var cont = $(this);
        if(cont.attr('id').endsWith("_"+idStr) && !cont.attr('id').endsWith("ZZ")) {
            // remove the short name and "select_" from the string we're parsing
            var divStr = cont.attr('id').replace(/^text_/, "").replace(shortName + "_", "");
            // remove the idstr to receive the name of this element
            var regex = new RegExp("\_"+idStr+"$", "g");
            var name = divStr.replace(regex, "");
            var value = $("#"+shortName+"_"+name+"_"+idStr).val();
            var size = 0;
            var sizeStr = "";
            if ($("#"+shortName+"_"+name+"_size_"+idStr).exists()) {
                size = parseInt($("#"+shortName+"_"+name+"_size_"+idStr).val());
                sizeStr = " size='" + size +"' ";
            }
            var placeholder = "";
            if ($("#"+shortName+"_"+name+"_placeholder_"+idStr).exists()) {
                placeholder = $("#"+shortName+"_"+name+"_placeholder_"+idStr).val();
            }

            var onKeyUp = "";
            if ($("#"+shortName+"_"+name+"_onKeyUp_"+idStr).exists()) {
                onKeyUp = $("#"+shortName+"_"+name+"_onKeyUp_"+idStr).val();
            }
            var onKeyUpStr = "";
            if (onKeyUp != "") {
                onKeyUpStr = " onKeyUp='"+onKeyUp+"' ";
            }

            var html = "<input id='"+shortName+"_"+name+"_"+idStr+"' name='"+shortName+"_"+name+"_"+
                    idStr+"' class='form-control' type='text' value=\""+ value +"\""+sizeStr + onKeyUpStr +
                    "placeholder=\""+placeholder+"\"/>";
            if (size != 0) {
                    html += "<input type=\"hidden\" id=\""+shortName+"_"+name+"_size_"+idStr+"\" " +
                        "value=\""+size+"\"/>";
            }
            if (placeholder != "") {
                html += "<input type=\"hidden\" id=\""+shortName+"_"+name+"_placeholder_"+idStr+"\" " +
                "value=\""+placeholder+"\"/>";
            }
            if (onKeyUp != "") {
                html += "<input type=\"hidden\" id=\""+shortName+"_"+name+"_onKeyUp_"+idStr+"\" " +
                "value=\""+onKeyUp+"\"/>";
            }

            cont.html(html);
        }
    });


}


function inputToText(shortName, idStr) {
    $("#"+shortName+"_datapart_" + idStr + " div[id^='text_"+shortName+"']").each(function() {
        var cont = $(this);
        if(cont.attr('id').endsWith("_"+idStr) && !cont.attr('id').endsWith("ZZ")) {
            // remove the short name and "select_" from the string we're parsing
            var divStr = cont.attr('id').replace(/^text_/, "").replace(shortName + "_", "");
            // remove the idstr to receive the name of this element
            var regex = new RegExp("\_"+idStr+"$", "g");
            var name = divStr.replace(regex, "");
            var value = $("#"+shortName+"_"+name+"_"+idStr).val();
            var size = 0;
            if ($("#"+shortName+"_"+name+"_size_"+idStr).exists()) {
                size = parseInt($("#"+shortName+"_"+name+"_size_"+idStr).val());
            }
            var placeholder = "";
            if ($("#"+shortName+"_"+name+"_placeholder_"+idStr).exists()) {
                placeholder = $("#"+shortName+"_"+name+"_placeholder_"+idStr).val();
            }
            var onKeyUp = "";
            if ($("#"+shortName+"_"+name+"_onKeyUp_"+idStr).exists()) {
                onKeyUp = $("#"+shortName+"_"+name+"_onKeyUp_"+idStr).val();
            }

            var html = "<input id='"+shortName+"_"+name+"_"+idStr+"' name='"+shortName+"_"+name+"_"+
                    idStr+"' type='hidden' value=\""+ value +"\"/>";
            html += "<p class='form-control-static'>" + value + "</p>";
            if (size != 0) {
                    html += "<input type=\"hidden\" id=\""+shortName+"_"+name+"_size_"+idStr+"\" " +
                        "value=\""+size+"\"/>";
            }
            if (placeholder != "") {
                html += "<input type=\"hidden\" id=\""+shortName+"_"+name+"_placeholder_"+idStr+"\" " +
                "value=\""+placeholder+"\"/>";
            }
            if (onKeyUp != "") {
                html += "<input type=\"hidden\" id=\""+shortName+"_"+name+"_onKeyUp_"+idStr+"\" " +
                "value=\""+onKeyUp+"\"/>";
            }

            cont.html(html);
        }
    });


}



function textToTextArea(shortName, idStr) {
    $("#"+shortName+"_datapart_" + idStr + " div[id^='textarea_"+shortName+"']").each(function() {
        var cont = $(this);
        if(cont.attr('id').endsWith("_"+idStr) && !cont.attr('id').endsWith("ZZ")) {
            // remove the short name and "select_" from the string we're parsing
            var divStr = cont.attr('id').replace(/^textarea_/, "").replace(shortName + "_", "");
            // remove the idstr to receive the name of this element
            var regex = new RegExp("\_"+idStr+"$", "g");
            var name = divStr.replace(regex, "");
            var value = $("#"+shortName+"_"+name+"_"+idStr).val();

            var html = "<textarea id='"+shortName+"_"+name+"_"+idStr+"' name='"+shortName+"_"+name+"_"+
                    idStr+"' class='form-control' style='width: 100%;'>"+ value +"</textarea>";

            cont.html(html);
        }
    });
}


function textAreaToText(shortName, idStr) {
    $("#"+shortName+"_datapart_" + idStr + " div[id^='textarea_"+shortName+"']").each(function() {
        var cont = $(this);
        if(cont.attr('id').endsWith("_"+idStr) && !cont.attr('id').endsWith("ZZ")) {
            // remove the short name and "select_" from the string we're parsing
            var divStr = cont.attr('id').replace(/^textarea_/, "").replace(shortName + "_", "");
            // remove the idstr to receive the name of this element
            var regex = new RegExp("\_"+idStr+"$", "g");
            var name = divStr.replace(regex, "");
            var value = $("#"+shortName+"_"+name+"_"+idStr).val();

            //var html = "<input type='hidden' id='"+shortName+"_"+name+"_"+idStr+"' name='"+shortName+"_"+name+"_"+
            //        idStr+"' value=\""+ value +"\"/>";
            var html = "<textarea style='display:none;' id='"+shortName+"_"+name+"_"+idStr+"' name='"+shortName+"_"+name+"_"+
                    idStr+"'>"+ value +"</textarea>";
            html += "<div class='form-control-static'>" + value + "</div>";

            cont.html(html);
        }
    });
}

/**
 * Change vocabulary selects to divs of inputs
 *
 * Changes all div's with id "select_" for a given data object (shortName, idStr) from a select
 * box (edit mode) to a list of inputs defining the parameters to a select (view mode).
 *
 * This function handles VOCABULARY select boxes ONLY.
 *
 * Note: idStr must not have the "_" pre-appended
 *
 * @param string shortName The short name of the data object, such as "nameEntry" or "occupation"
 * @param string|int idStr The index within the edit page of the object.
 */
function selectToText(shortName, idStr) {
    $("div[id^='select_"+shortName+"']").each(function() {
        var cont = $(this);
        if(cont.attr('id').endsWith("_"+idStr) && !cont.attr('id').endsWith("ZZ")) {
            // remove the short name and "select_" from the string we're parsing
            var divStr = cont.attr('id').replace("select_", "").replace(shortName + "_", "");
            // remove the idstr to receive the name of this element
            var regex = new RegExp("\_"+idStr+"$", "g");
            var name = divStr.replace(regex, "");
            var id = $("#"+shortName+"_"+name+"_id_"+idStr).val();
            var term = $("#"+shortName+"_"+name+"_id_"+idStr+ " option:selected").text();
            var vocabtype = $("#"+shortName+"_"+name+"_vocabtype_"+idStr).val();
            var minlength = $("#"+shortName+"_"+name+"_minlength_"+idStr).val();

            var additionalStyle = "";
            var postText = "";
            if (vocabtype == "name_component") {
                additionalStyle = "name-component-type";
                postText = " :";
            }

            cont.html("<input type=\"hidden\" id=\""+shortName+"_"+name+"_id_"+idStr+"\" " +
                    "name=\""+shortName+"_"+name+"_id_"+idStr+"\" value=\""+id+"\"/>" +
                    "<input type=\"hidden\" id=\""+shortName+"_"+name+"_term_"+idStr+"\" " +
                    "name=\""+shortName+"_"+name+"_term_"+idStr+"\" value=\""+term+"\"/>" +
                    "<input type=\"hidden\" id=\""+shortName+"_"+name+"_vocabtype_"+idStr+"\" " +
                        "name=\""+shortName+"_"+name+"_vocabtype_"+idStr+"\" value=\""+vocabtype+"\"/>" +
                    "<input type=\"hidden\" id=\""+shortName+"_"+name+"_minlength_"+idStr+"\" " +
                        "name=\""+shortName+"_"+name+"_minlength_"+idStr+"\" value=\""+minlength+"\"/>" +
                        "<p class=\"form-control-static "+additionalStyle+"\">"+term+postText+"</p>");

        }
    });
}


var geoPlaceLoadResults = null;

function textToGeoPlaceSelect(shortName, idStr) {
    $("#"+shortName+"_datapart_" + idStr + " div[id^='selectGeo_"+shortName+"']").each(function() {
        var cont = $(this);
        if(cont.attr('id').endsWith("_"+idStr) && !cont.attr('id').endsWith("ZZ")) {
            // remove the short name and "select_" from the string we're parsing
            var divStr = cont.attr('id').replace("selectGeo_", "").replace(shortName + "_", "");
            // remove the idstr to receive the name of this element
            var regex = new RegExp("\_"+idStr+"$", "g");
            var name = divStr.replace(regex, "");
            var id = $("#"+shortName+"_"+name+"_id_"+idStr).val();
            var term = $("#"+shortName+"_"+name+"_term_"+idStr).val();
            var placeholder = "Select Geo Place Term";

            var confirmed = $("#"+shortName+"_confirmed_" + idStr).val() == "true" ? true : false;
            var firstOptionSelect = "";
            var secondOptionSelect = " selected";
            if (!confirmed) {
                firstOptionSelect = " selected";
                secondOptionSelect = "";
            }

            cont.html("<select id='"+shortName+"_"+name+"_id_"+idStr+"' name='"+shortName+"_"+name+"_id_"+idStr+"' class='form-control' data-placeholder='"+placeholder+"'>"+
                    "<option"+firstOptionSelect+"></option>"+
                    "<option value=\""+id+"\""+secondOptionSelect+">"+term+"</option>"+
                    "</select>");

            geovocab_select_replace($("#"+shortName+"_"+name+"_id_"+idStr), "_"+idStr);

        }
    });
}

function geoPlaceSelectToText(shortName, idStr) {
    $("div[id^='selectGeo_"+shortName+"']").each(function() {
        var cont = $(this);
        if(cont.attr('id').endsWith("_"+idStr) && !cont.attr('id').endsWith("ZZ")) {
            // remove the short name and "select_" from the string we're parsing
            var divStr = cont.attr('id').replace("selectGeo_", "").replace(shortName + "_", "");
            // remove the idstr to receive the name of this element
            var regex = new RegExp("\_"+idStr+"$", "g");
            var name = divStr.replace(regex, "");
            var id = $("#"+shortName+"_"+name+"_id_"+idStr).val();
            var term = $("#"+shortName+"_"+name+"_id_"+idStr+ " option:selected").text();

            cont.html("<input type=\"hidden\" id=\""+shortName+"_"+name+"_id_"+idStr+"\" " +
                    "name=\""+shortName+"_"+name+"_id_"+idStr+"\" value=\""+id+"\"/>" +
                    "<input type=\"hidden\" id=\""+shortName+"_"+name+"_term_"+idStr+"\" " +
                    "name=\""+shortName+"_"+name+"_term_"+idStr+"\" value=\""+term+"\"/>");

        }
    });
}

/**
 * Make a data object editable
 *
 * Make the GUI pane for a given constellation object (short, i) editable.  Sets up the edit and delete
 * buttons for first-order data objects.
 *
 * @param string shortName The short name of the data object, such as "nameEntry" or "occupation"
 * @param string|int i     The index within the edit page of the object.
 * @return boolean         False to play nice with the browser
 */
function makeEditable(short, i) {
    // No editing if it's already in edit mode
    if ($("#" + short + "_operation_" + i).val() == "update")
        return false;
    // If it's deleted, then you better undelete it first
    if ($("#" + short + "_operation_" + i).val() == "delete")
        setDeleted(short, i);

    $("#" + short + "_editbutton_" + i).removeClass("list-group-item-info").addClass("list-group-item-warning");
    $("#" + short + "_editbutton_" + i).html("<span class=\"fa fa-2x fa-undo\"></span><br>Undo");
    $("#" + short + "_editbutton_" + i).off('click').on("click", function() {
    	undoEdit(short, i);
    });
    $("#" + short + "_deletebutton_" + i).removeClass("list-group-item-danger").addClass("disabled");
    $("#" + short + "_deletebutton_" + i).off('click').on("click", function() {
        return false;
    });

    $("#" + short + "_panel_" + i).removeClass("panel-default").addClass("alert-info").addClass("edited-component");

    return subMakeEditable(short, i);
}

/**
 * Make a data object editable
 *
 * Make the GUI pane for a given constellation object (short, i) editable.  Handles removing the read-only
 * statuses and changing divs into selects.
 *
 * @param string shortName The short name of the data object, such as "nameEntry" or "occupation"
 * @param string|int i     The index within the edit page of the object.
 * @return boolean         False to play nice with the browser
 */
function subMakeEditable(short, i) {
    // No editing if it's already in edit mode
    if ($("#" + short + "_operation_" + i).val() == "update")
        return false;

    // Add to the undo set
    undoSet[short + "-" + i] = $("#"+short+"_datapart_" + i).clone();


    textToInput(short, i);
    textToTextArea(short, i);
    textToCheckbox(short, i);

    var idstr = "_" + i;

    // Enable buttons
    $("#"+short+"_datapart_" + i + " button[id^='"+short+"_']").each(function() {
        var obj = $(this);
        if(obj.attr('id').endsWith(idstr) && !obj.attr('id').endsWith("ZZ")) {
            obj.removeAttr("disabled").removeClass("snac-hidden");
        }
    });
    // Enable buttons
    $("#"+short+"_datapart_" + i + " a.label").each(function() {
        $(this).removeClass("snac-hidden");
    });

    // Enable checkboxes
    $("#"+short+"_datapart_" + i + " input[type='checkbox']").each(function() {
        var obj = $(this);
        if(obj.attr('id').endsWith(idstr) && !obj.attr('id').endsWith("ZZ")) {
            obj.bootstrapToggle('enable');
        }
    });

    // Turn on CodeMirror Editors
    $("#"+short+"_datapart_" + i + " textarea[id^='"+short+"_']").each(function() {
        var obj = $(this);
        if(obj.attr('id').endsWith(idstr)
            && !obj.attr('id').endsWith("ZZ")) {
            // remove the short name from the string we're parsing
            var idStr = obj.attr('id').replace(short, "");
            if (idStr.indexOf('_text_') != -1 || idStr.indexOf('_source_') != -1) {
                obj.get(0).CodeMirror = CodeMirror.fromTextArea(obj.get(0), {
                  lineNumbers: true,
                  lineWrapping: true,
                  viewportMargin: Infinity,
                  mode: {name: "xml"}
                });
            }
        }
    });
    var sawSelect = false;
    $("#"+short+"_datapart_" + i + " select[id^='"+short+"_']").each(function() {
        var obj = $(this);
        if(obj.attr('id').endsWith(idstr) && !obj.attr('id').endsWith("ZZ")) {
            sawSelect = true;
        }
    });

    if (!sawSelect) {
        textToSelect(short, i);
    }

    // Make things re-orderable if something exists
    // $( "#nameEntry_component_ui_0" ).sortable({  // id of the container containing sortable things
    //      items       : '.name_component'         // class of the things that are sortable.  They MUST have ids
    // });
    $("#"+short+"_datapart_" + i + " span.move-handle").each(function() {
        $(this).removeClass("snac-hidden");
    });
    $( "#"+short+"_reorderable_"+i ).sortable({
          items       : '.reorderable',
          opacity     : 0.5,
          update      : function( event, ui ) {
              var neworder = $( "#"+short+"_reorderable_"+i ).sortable("toArray");
              console.log(neworder);
              neworder.forEach(function(orderedID, index) {
                    $("#"+orderedID + " input.order-index").val(index);
              });
              if (short == "nameEntry")
                    updateNameEntryHeading(i);
          }
    });

    // Make the relation pictures update appropriately
    if (short == 'constellationRelation') {
        // make the role dropdown affect the picture
        $('#'+short+'_type_id_'+i).change(function() {
            updatePictureArrow(short, i,
                $('#'+short+'_type_id_'+i+' option:selected').text());
        });

    }
    if (short == 'resourceRelation') {
        // make the role dropdown affect the picture
        $('#'+short+'_role_id_'+i).change(function() {
            updatePictureArrow(short, i,
                $('#'+short+'_role_id_'+i+' option:selected').text());
        });
    }
    // Places should update the place heading
    if (short == 'place') {
        // If there is a value pre-set, then automatically confirm and update
        if ($('#'+short+'_geoplace_id_'+i).val() != null && $('#'+short+'_geoplace_id_'+i).val() != "") {
            updatePlaceHeading(short, i, $('#'+short+'_geoplace_id_'+i).val());
        }
        // make the role dropdown affect the picture
        $('#'+short+'_geoplace_id_'+i).change(function() {
            updatePlaceHeading(short, i,
                $('#'+short+'_geoplace_id_'+i).val());
        });
    }
    // Same As add on change functions
    if (short == 'sameAs') {
        $("#sameAs_baseuri_id_"+i).change(updateSameAsURI);
        $("#sameAs_baseuri_container_"+i).css("display","block");
        $("#sameAs_uriid_"+i).on("input", updateSameAsURI);
        $("#sameAs_uriid_container_"+i).css("display","block");
    }

    // add parser btn if nameEntry is a computed name, entity is person, and if no btn or extra name components already exist
    if (short === 'nameEntry' && ($("#entityType").val() === "700") &&
        ($("#nameEntry_component_0_panel_" + i).find('select:first').text() === "Name") &&
        (!$("#nameEntry_panel_" + i).find('.name-parser').length &&
            $("#nameEntry_component_1_panel_" + i).length === 0)) {
        $('#nameEntry_component_add_' + i).after('<button class="btn btn-primary name-parser" id="nameEntry_parse_' + i +
            '" style="margin-left:5px;"> <i class="fa fa-magic" aria-hidden="true"></i> Parse </button>');
    }

    // Set this data's operation value appropriately
    if ($("#" + short + "_id_" + i).val() != "")
    	$("#" + short + "_operation_" + i).val("update");
    else
    	$("#" + short + "_operation_" + i).val("insert");

    // Asked to edit something, so make it globally known
    setEditedFlag(true);
    //somethingHasBeenEdited = true;

    return false;
}

/**
 * Make a data object uneditable
 *
 * Make each object in the GUI page for the given piece uneditable by turning them back to text.  Also
 * takes the color away from the pane and removes the operation flag.
 *
 * @param string shortName The short name of the data object, such as "nameEntry" or "occupation"
 * @param string|int i     The index within the edit page of the object.
 * @return boolean         False to play nice with the browser
 */
function subMakeUneditable(shortName, i) {

	// Make inputs read-only
    var idstr = "_" + i;


    // Turn off the reordering js
    $("#"+shortName+"_datapart_" + i + " span.move-handle").each(function() {
        $(this).addClass("snac-hidden");
    });
    if ($( "#"+shortName+"_reorderable_"+i ).hasClass("ui-sortable"))
        $( "#"+shortName+"_reorderable_"+i ).sortable("destroy");

    // Remove CodeMirror editors
    $("#"+shortName+"_datapart_" + i + " textarea[id^='"+shortName+"_']").each(function() {
        var obj = $(this);
        if(obj.attr('id').endsWith(idstr)
            && !obj.attr('id').endsWith("ZZ")
            && (obj.attr('id').indexOf('_text_') != -1 || obj.attr('id').indexOf('_source_') != -1)) {

            if (obj.get(0).CodeMirror) {
                obj.get(0).CodeMirror.toTextArea();
            }
            //(document.getElementById(obj.attr('id'))).CodeMirror.toTextArea();
                //obj.get(0).CodeMirror.toTextArea();
        }
    });
    // Disable buttons
    $("#"+shortName+"_datapart_" + i + " button[id^='"+shortName+"_']").each(function() {
        var obj = $(this);
        if(obj.attr('id').endsWith(idstr) && !obj.attr('id').endsWith("ZZ")) {
            obj.attr("disabled", "true").addClass("snac-hidden");
        }
    });

    // Disable buttons
    $("#"+shortName+"_datapart_" + i + " a.label").each(function() {
        $(this).addClass("snac-hidden");
    });

    // Disable checkboxes
    $("#"+shortName+"_datapart_" + i + " input[type='checkbox']").each(function() {
        var obj = $(this);
        if(obj.attr('id').endsWith(idstr) && !obj.attr('id').endsWith("ZZ")) {
            obj.bootstrapToggle("disable");
        }
    });

    inputToText(shortName, i);
    textAreaToText(shortName, i);
    checkboxToText(shortName, i);
    // Check for a select box
    var sawSelect = false;
    $("#"+shortName+"_datapart_" + i + " select[id^='"+shortName+"_']").each(function() {
        var obj = $(this);
        if(obj.attr('id').endsWith(idstr) && !obj.attr('id').endsWith("ZZ")) {
            sawSelect = true;
        }
    });
    // If a select box was seen, undo it
    if (sawSelect) {
        selectToText(shortName, i);
    }


    // Clear the operation flags
    //$("#" + shortName + "_operation_" + i).val("");
    $("#"+shortName+"_datapart_" + i + " input[id^='"+shortName+"_']").each(function() {
        var obj = $(this);
        if(obj.attr('id').endsWith("_operation" + idstr) && !obj.attr('id').endsWith("ZZ")) {
            obj.val("");
        }
    });

	$("#" + shortName + "_panel_" + i).addClass("panel-default").removeClass("alert-info").removeClass("edited-component");



}

/**
 * Make a data object uneditable
 *
 * Make the GUI pane for a given constellation object (short, i) un-editable.  Sets up the edit and delete
 * buttons for first-order data objects, and calls the function to turn the elements back to text.
 *
 * @param string shortName The short name of the data object, such as "nameEntry" or "occupation"
 * @param string|int i     The index within the edit page of the object.
 * @return boolean         False to play nice with the browser
 */
function makeUneditable(shortName, i) {
	// Make inputs read-only
    var idstr = "_" + i;

    subMakeUneditable(shortName, i);

    // restore the edit button
    $("#" + shortName + "_editbutton_" + i).addClass("list-group-item-info").removeClass("list-group-item-warning");
    $("#" + shortName + "_editbutton_" + i).html("<span class=\"fa fa-2x fa-pencil-square-o\"></span><br>Edit");
    $("#" + shortName + "_editbutton_" + i).off('click').on("click", function() {
    	makeEditable(shortName, i);
    });

    // restore the delete button
    $("#" + shortName + "_deletebutton_" + i).addClass("list-group-item-danger").removeClass("disabled");
    $("#" + shortName + "_deletebutton_" + i).off('click').on("click", function() {
       setDeleted(shortName, i);
    });

    return false;
}

/**
 * Set a Contributor Object as deleted
 *
 * Sets the contributor object (shortName, i) as deleted or undeleted and makes the appropriate changes.
 *
 * @param string shortName The short name of the contributor object.
 * @param string|int i     The index within the edit page of the object.
 */
function setRepeatedDataDeleted(shortName, i) {
    if ($("#" + shortName + "_operation_" + i).val() != "delete") {
        $("#" + shortName + "_remove_" + i).removeClass("btn-danger").addClass("btn-warning");
        $("#" + shortName + "_remove_" + i).html("<i class=\"fa fa-undo\" aria-hidden=\"true\"></i>");
    } else {
        $("#" + shortName + "_remove_" + i).removeClass("btn-warning").addClass("btn-danger");
        $("#" + shortName + "_remove_" + i).html("<span class=\"glyphicon glyphicon-minus-sign\"></span>");
    }

    return subSetDeleted(shortName, i);
}

/**
 * Set a first-order data object as deleted
 *
 * Sets a first-order data object (short, i) as deleted or undeleted and makes the appropriate changes throughout the page.
 *
 * @param string shortName The short name of the data object, such as "nameEntry" or "occupation"
 * @param string|int i     The index within the edit page of the object.
 */
function setDeleted(short, i) {
    if ($("#" + short + "_operation_" + i).val() != "delete") {
    	// set deleted
        $("#" + short + "_deletebutton_" + i).removeClass("list-group-item-danger").addClass("list-group-item-warning");
        $("#" + short + "_deletebutton_" + i).html("<span class=\"fa fa-2x fa-undo\"></span><br>Undo");

        // disable edit button
        $("#" + short + "_editbutton_" + i).removeClass("list-group-item-info").addClass("disabled");
        $("#" + short + "_editbutton_" + i).off('click').on("click", function() {
           return false;
        });

        // disable the Date button
        $("#" + short + "_datebutton_" + i).removeClass("list-group-item-success").addClass("disabled").prop('disabled', true);

        // disable the SCM button
        $("#" + short + "_scmbutton_" + i).removeClass("list-group-item-success").addClass("disabled").prop('disabled', true);

    } else {
    	// set undelete
        $("#" + short + "_deletebutton_" + i).removeClass("list-group-item-warning").addClass("list-group-item-danger");
        $("#" + short + "_deletebutton_" + i).html("<span class=\"fa fa-2x fa-trash-o\"></span><br>Trash");

        // restore edit button
        $("#" + short + "_editbutton_" + i).addClass("list-group-item-info").removeClass("disabled");
        $("#" + short + "_editbutton_" + i).off('click').on("click", function() {
           makeEditable(short, i);
        });

        // restore the Date button
        $("#" + short + "_datebutton_" + i).addClass("list-group-item-success").removeClass("disabled").prop('disabled', false);

        // restore the SCM button
        $("#" + short + "_scmbutton_" + i).addClass("list-group-item-success").removeClass("disabled").prop('disabled', false);

    }

    return subSetDeleted(short, i);
}

/**
 * Make the delete/undelete happen
 *
 * Actually performs the changes to the object, affecting the operation and panel color.
 *
 * @param string shortName The short name of the data object, such as "nameEntry" or "occupation"
 * @param string|int i     The index within the edit page of the object.
 * @return boolean         False to play nice with the browser
 */
function subSetDeleted(short, i) {
    if ($("#" + short + "_operation_" + i).val() != "delete") {
    	// set deleted
    	$("#" + short + "_panel_" + i).removeClass("panel-default").addClass("alert-danger").addClass("deleted-component");

        $("#" + short + "_operation_" + i).val("delete");
    } else {
    	// set undelete
    	$("#" + short + "_panel_" + i).removeClass("alert-danger").addClass("panel-default").removeClass("deleted-component");

        // If this thing was deleted but is supposed to be an update, then return it back to update status
        var sawSelect = false;
        $("select[id^='"+short+"_']").each(function() {
            var obj = $(this);
            if(obj.attr('id').endsWith("_" + i)  && !obj.attr('id').endsWith("ZZ")) {
                sawSelect = true;
            }
        });
        if (sawSelect) {
    	    if ($("#" + short + "_id_" + i).val() != "")
    	    	$("#" + short + "_operation_" + i).val("update");
    	    else
    	    	$("#" + short + "_operation_" + i).val("insert");
        } else {
        	$("#" + short + "_operation_" + i).val("");
        }

    }

    // Asked to delete something, so make it globally known
    setEditedFlag(true);
    //somethingHasBeenEdited = true;

    return false;
}

/**
 * Turn on the Edit/Delete buttons for an object
 *
 * @param string shortName The short name of the data object, such as "nameEntry" or "occupation"
 * @param string|int i     The index within the edit page of the object.
 */
function turnOnButtons(shortName, i) {

    // Turn on the edit button
    $("#"+shortName+"_editbutton_"+i).on("click", function() {
        makeEditable(shortName, i);
    });

    // Turn on the delete buttons
    $("#"+shortName+"_deletebutton_"+i).on("click", function() {
        setDeleted(shortName, i);
    });
}

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

/**
 * Turn on the Edit/Delete buttons for an SCM object
 *
 * @param string shortName The short name of the data object, such as "nameEntry" or "occupation"
 * @param string|int i     The index within the edit page of the object.
 * @param string|int j      The index within the data object SCM list on the edit page of the SCM object.
 */
function turnOnSCMButtons(shortName, i, j) {

    // Turn on the edit button
    $("#scm_"+shortName+"_editbutton_"+j+"_"+i).on("click", function() {
        makeSCMEditable(shortName, i, j);
    });

    // Turn on the delete buttons
    $("#scm_"+shortName+"_deletebutton_"+j+"_"+i).on("click", function() {
        setSCMDeleted(shortName, i, j);
    });
}


function turnOnEditDeleteButtons(part='') {
    var divID = '';
    if (part != '')
        divID = "#" + part + " ";
    // Turn on the edit buttons
    $(divID + "a[id*='editbutton']").each(function() {
        var obj = $(this);
        var pieces = obj.attr('id').split("_");

        if (pieces.length == 3) {
            var short = pieces[0];
            var i = pieces[2];
            obj.on("click", function() {
                makeEditable(short, i);
            });
        } else if (pieces.length == 5) {
            var short = pieces[0] + "_" + pieces[1] + "_" + pieces[2];
            var i = pieces[4];
            obj.on("click", function() {
                makeEditable(short, i);
            });
        }
    });

    // Turn on the delete buttons
    $(divID + "a[id*='deletebutton']").each(function() {
        var obj = $(this);
        var pieces = obj.attr('id').split("_");

        if (pieces.length == 3) {
            var short = pieces[0];
            var i = pieces[2];
            obj.on("click", function() {
                setDeleted(short, i);
            });
        } else if (pieces.length == 5) {
            var short = pieces[0] + "_" + pieces[1] + "_" + pieces[2];
            var i = pieces[4];
            obj.on("click", function() {
                setDeleted(short, i);
            });
        }
    });
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

function magicDefaultFill(selectID, vocabType) {
   if (typeof(defaults[vocabType]) !== undefined) {

       var data = {
           id: defaults[vocabType].id,
           text: defaults[vocabType].term
       };

       // If the selected item exists, then select it. Else, add a new option
       // and select it.
       if ($('#'+selectID).find("option[value='" + data.id + "']").length) {
               $('#'+selectID).val(data.id).trigger('change');
       } else {
           var newOption = new Option(data.text, data.id, false, true);
           $('#'+selectID).append(newOption).trigger('change');
       }
   }

}


/**
 * Things to do when the page finishes loading
 */
$(document).ready(function() {

    // If the constellation is in "insert" mode, then we should automatically set "somethingHasBeenEdited"
    // to be true...
    if ($('#operation').val() == 'insert')
        setEditedFlag(true);
        //somethingHasBeenEdited = true;

    turnOnEditDeleteButtons();

    // Name Entry doesn't get any AJAX, since it is pre-loaded
	var nextTerm = 1;
	if ($('#next_term_i').exists()) {
	    nextTerm = parseInt($('#next_term_i').text());
	}
	console.log("Next Term ID: " + nextTerm);
	if ($('#btn_add_term').exists()){
		$('#btn_add_term').click(function(){
            setEditedFlag(true);
			var text = $('#term_template').clone();
	        var html = text.html().replace(/ZZ/g, nextTerm).replace(/YY/g, 'preferred');
	        $('#add_term_div').after(html);
            turnOnButtons("term", nextTerm);
            turnOnTooltips("term", nextTerm);
            makeEditable("term", nextTerm);
	        nextTerm = nextTerm + 1;
	        return false;
		});
	}
	if ($('#btn_add_altterm').exists()){
		$('#btn_add_altterm').click(function(){
            setEditedFlag(true);
			var text = $('#term_template').clone();
	        var html = text.html().replace(/ZZ/g, nextTerm).replace(/YY/g, '');
	        $('#add_altterm_div').after(html);
            turnOnButtons("term", nextTerm);
            turnOnTooltips("term", nextTerm);
            makeEditable("term", nextTerm);
	        nextTerm = nextTerm + 1;
	        return false;
		});
	}


	// Attach functions to each of the "+ Add New _______" buttons

	var relationid = 1;
    if ($('#next_relation_i').exists()) {
        relationid = parseInt($('#next_relation_i').text());
    }
    console.log("Next relation ID: " + relationid);
    if ($('#btn_add_relation').exists()){
        $('#btn_add_relation').click(function(){
            setEditedFlag(true);
            //somethingHasBeenEdited = true;
            var text = $('#relation_template').clone();
            var html = text.html().replace(/ZZ/g, relationid);
            $('#add_relation_div').after(html);
            turnOnButtons("relation", relationid);
            turnOnTooltips("relation", relationid);
            makeEditable("relation", relationid);
            relationid = relationid + 1;
            return false;
        });
    }

	var categoryid = 1;
    if ($('#next_category_i').exists()) {
        categoryid = parseInt($('#next_category_i').text());
    }
    console.log("Next category ID: " + categoryid);
    if ($('#btn_add_category').exists()){
        $('#btn_add_category').click(function(){
            setEditedFlag(true);
            //somethingHasBeenEdited = true;
            var text = $('#category_template').clone();
            var html = text.html().replace(/ZZ/g, categoryid);
            $('#add_category_div').after(html);
            turnOnButtons("category", categoryid);
            turnOnTooltips("category", categoryid);
            makeEditable("category", categoryid);
            categoryid = categoryid + 1;
            return false;
        });
    }

	var sourceid = 1;
    if ($('#next_source_i').exists()) {
        sourceid = parseInt($('#next_source_i').text());
    }
    console.log("Next source ID: " + sourceid);
    if ($('#btn_add_source').exists()){
        $('#btn_add_source').click(function(){
            setEditedFlag(true);
            //somethingHasBeenEdited = true;
            var text = $('#source_template').clone();
            var html = text.html().replace(/ZZ/g, sourceid);
            $('#add_source_div').after(html);
            turnOnButtons("source", sourceid);
            turnOnTooltips("source", sourceid);
            makeEditable("source", sourceid);
            sourceid = sourceid + 1;
            return false;
        });
    }

    turnOnTooltipsForTab();

});

