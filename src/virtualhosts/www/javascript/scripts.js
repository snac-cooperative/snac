var biogHistEditor = null;

// Has anything been edited on this page?
var somethingHasBeenEdited = false;

// Global Undo Set
var undoSet = new Array();

/**
 * Display Error message
 *
 * Displays the error message box to the user with the given error message
 *
 * @param string|object err The error message (string) or error object containing a message and type string
 */
function displayErrorMessage(err) {
    var errorMsg = "";
    if ((typeof err) == "string")
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
            +"<a href=\"?command=dashboard\" type=\"button\" class=\"btn btn-danger\">"+
            "Go to Dashboard"
            +"</a>"
            +"</p>");
    setTimeout(function(){
        $('#error-message').slideDown();
    }, 500);
}

/**
 * Add SCM GUI object
 *
 * Adds a GUI SCM object to the SCM modal for the given "short" type of data indexed by i
 *
 * @param string short The short name of the data object, such as "nameEntry" or "occupation"
 * @param string|int i The index of the object to add an SCM object to.
 * @returns boolean false to keep the browser from redrawing the page
 */
function addSCMEntry(short, i){
	//next_scm_{{short}}_{{i}}_j
	var j = parseInt($('#next_scm_'+short+'_'+i+'_j').text());
    somethingHasBeenEdited = true;
	var text = $('#scm_template').clone();
    var html = text.html().replace(/ZZ/g, i).replace(/YY/g, j).replace(/SHORT/g, short);
    $('#add_scm_'+short+'_'+i+'_div').after(html);
    $('#next_scm_'+short+'_'+i+'_j').text(j + 1);
    turnOnSCMButtons(short, i, j);
    return false;
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
}

/**
 * Undo SCM Edit
 *
 * Takes an SCM GUI object (j) for a data object (short, i) and returns it back to its
 * original state (removing the edit).  Before doing this, to clean up the page and JS, it first
 * makes the edited version uneditable, then replaces the HTML.
 *
 * @param string short The short name of the data object, such as "nameEntry" or "occupation"
 * @param string|int i The index within the edit page of the object.
 * @param string|int j The index within the data object SCM list on the edit page of the SCM object.
 */
function undoSCMEdit(short, i, j) {
	var id = j + "_" + i;
	makeSCMUneditable(short, i, j);

	// restore the old content
	$("#scm_" + short + "_datapart_" + id).replaceWith(undoSet["scm_"+short+"-"+id]);
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
    $("div[id^='select_"+shortName+"']").each(function() {
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

            cont.html("<select id='"+shortName+"_"+name+"_id_"+idStr+"' name='"+shortName+"_"+name+"_id_"+idStr+"' class='form-control' placeholder='Select'>"+
                    "<option></option>"+
                    "<option value=\""+id+"\" selected>"+term+"</option>"+
                    "</select>"+
                    "<input type=\"hidden\" id=\""+shortName+"_"+name+"_vocabtype_"+idStr+"\" " +
                        "name=\""+shortName+"_"+name+"_vocabtype_"+idStr+"\" value=\""+vocabtype+"\"/>" +
                    "<input type=\"hidden\" id=\""+shortName+"_"+name+"_minlength_"+idStr+"\" " +
                        "name=\""+shortName+"_"+name+"_minlength_"+idStr+"\" value=\""+minlength+"\"/>");

            vocab_select_replace($("#"+shortName+"_"+name+"_id_"+idStr), "_"+idStr, vocabtype, minlength);

        }
    });
}

/**
 * Change source list input divs to selects
 *
 * Changes all div's with id "selectsource_" for a given data object (shortName, idStr) from a list of
 * inputs defining the parameters to a select (view mode) to a select box (edit mode).  It then
 * calls the select2 function to replace the select with an AJAX-compatible select.
 *
 * This function handles CONSTELLATION SOURCE OBJECT select boxes ONLY.
 *
 * Note: idStr must not have the "_" pre-appended
 *
 * @param string shortName The short name of the data object, such as "nameEntry" or "occupation"
 * @param string|int idStr The index within the edit page of the object.
 */
function sourceTextToSelect(shortName, idStr) {

    $("div[id^='selectsource_"+shortName+"']").each(function() {
        var cont = $(this);
        if(cont.attr('id').endsWith(idStr) && !cont.attr('id').endsWith("ZZ")) {
            // remove the short name and "select_" from the string we're parsing
            var divStr = cont.attr('id').replace("selectsource_", "").replace(shortName + "_", "");
            // remove the idstr to receive the name of this element
            var regex = new RegExp("\_"+idStr+"$", "g");
            var name = divStr.replace(regex, "");
            var id = $("#"+shortName+"_"+name+"_id_"+idStr).val();
            var term = $("#"+shortName+"_"+name+"_term_"+idStr).val();
            cont.html("<select id='"+shortName+"_"+name+"_id_"+idStr+"' name='"+shortName+"_"+name+"_id_"+idStr+"' class='form-control'>"+
                    "<option></option>"+
                    "<option value=\""+id+"\" selected>"+term+"</option>"+
                    "</select>");
            scm_source_select_replace($("#"+shortName+"_"+name+"_id_"+idStr), idStr);
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

            cont.html("<input type=\"hidden\" id=\""+shortName+"_"+name+"_id_"+idStr+"\" " +
                    "name=\""+shortName+"_"+name+"_id_"+idStr+"\" value=\""+id+"\"/>" +
                    "<input type=\"hidden\" id=\""+shortName+"_"+name+"_term_"+idStr+"\" " +
                    "name=\""+shortName+"_"+name+"_term_"+idStr+"\" value=\""+term+"\"/>" +
                    "<input type=\"hidden\" id=\""+shortName+"_"+name+"_vocabtype_"+idStr+"\" " +
                        "name=\""+shortName+"_"+name+"_vocabtype_"+idStr+"\" value=\""+vocabtype+"\"/>" +
                    "<input type=\"hidden\" id=\""+shortName+"_"+name+"_minlength_"+idStr+"\" " +
                        "name=\""+shortName+"_"+name+"_minlength_"+idStr+"\" value=\""+minlength+"\"/>" +
                        "<p class=\"form-control-static\">"+term+"</p>");

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
    $("#" + short + "_editbutton_" + i).html("<span class=\"glyphicon glyphicon-remove-sign\"></span> Undo");
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


    var idstr = "_" + i;
    $("input[id^='"+short+"_']").each(function() {
        var obj = $(this);
        if(obj.attr('id').endsWith(idstr) && !obj.attr('id').endsWith("ZZ")) {
            obj.removeAttr("readonly");
        }
    });
    $("textarea[id^='"+short+"_']").each(function() {
        var obj = $(this);
        if(obj.attr('id').endsWith(idstr) && !obj.attr('id').endsWith("ZZ")) {
            obj.removeAttr("readonly");
        }
    });
    $("button[id^='"+short+"_']").each(function() {
        var obj = $(this);
        if(obj.attr('id').endsWith(idstr) && !obj.attr('id').endsWith("ZZ")) {
            obj.removeAttr("disabled").removeClass("snac-hidden");
        }
    });
    // Turn on CodeMirror Editors
    $("textarea[id^='"+short+"_']").each(function() {
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
    $("select[id^='"+short+"_']").each(function() {
        var obj = $(this);
        if(obj.attr('id').endsWith(idstr) && !obj.attr('id').endsWith("ZZ")) {
            sawSelect = true;
        }
    });

    if (!sawSelect) {
        textToSelect(short, i);
        sourceTextToSelect(short, i);
    }

    // Set this data's operation value appropriately
    if ($("#" + short + "_id_" + i).val() != "")
    	$("#" + short + "_operation_" + i).val("update");
    else
    	$("#" + short + "_operation_" + i).val("insert");

    // Asked to edit something, so make it globally known
    somethingHasBeenEdited = true;

    return false;
}

/**
 * Make a data object uneditable
 *
 * Make the GUI pane for a given constellation object (short, i) un-editable.  Sets up the edit and delete
 * buttons for first-order data objects.
 *
 * @param string shortName The short name of the data object, such as "nameEntry" or "occupation"
 * @param string|int i     The index within the edit page of the object.
 * @return boolean         False to play nice with the browser
 */
function makeUneditable(short, i) {

	// Make inputs read-only
    var idstr = "_" + i;
    $("input[id^='"+short+"_']").each(function() {
        var obj = $(this);
        if(obj.attr('id').endsWith(idstr) && !obj.attr('id').endsWith("ZZ")) {
            obj.attr("readonly", "true");
        }
    });
    // Remove CodeMirror editors
    $("textarea[id^='"+short+"_']").each(function() {
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
    $("button[id^='"+short+"_']").each(function() {
        var obj = $(this);
        if(obj.attr('id').endsWith(idstr) && !obj.attr('id').endsWith("ZZ")) {
            obj.attr("disabled", "true").addClass("snac-hidden");
        }
    });
    // Make textareas read-only
    $("textarea[id^='"+short+"_']").each(function() {
        var obj = $(this);
        if(obj.attr('id').endsWith(idstr) && !obj.attr('id').endsWith("ZZ")) {
            obj.attr("readonly", "true");
        }
    });
    // Check for a select box
    var sawSelect = false;
    $("select[id^='"+short+"_']").each(function() {
        var obj = $(this);
        if(obj.attr('id').endsWith(idstr) && !obj.attr('id').endsWith("ZZ")) {
            sawSelect = true;
        }
    });
    // If a select box was seen, undo it
    if (sawSelect) {
        selectToText(short, i);
    }

    // restore the edit button
    $("#" + short + "_editbutton_" + i).addClass("list-group-item-info").removeClass("list-group-item-warning");
    $("#" + short + "_editbutton_" + i).html("<span class=\"glyphicon glyphicon-pencil\"></span> Edit");
    $("#" + short + "_editbutton_" + i).off('click').on("click", function() {
    	makeEditable(short, i);
    });

    // restore the delete button
    $("#" + short + "_deletebutton_" + i).addClass("list-group-item-danger").removeClass("disabled");
    $("#" + short + "_deletebutton_" + i).off('click').on("click", function() {
       setDeleted(short, i);
    });

    // Clear the operation flags
    //$("#" + short + "_operation_" + i).val("");
    $("input[id^='"+short+"_']").each(function() {
        var obj = $(this);
        if(obj.attr('id').endsWith("_operation" + idstr) && !obj.attr('id').endsWith("ZZ")) {
            obj.val("");
        }
    });

	$("#" + short + "_panel_" + i).addClass("panel-default").removeClass("alert-info").removeClass("edited-component");

    return false;
}

/**
 * Make an SCM data object editable
 *
 * Make the GUI pane for an SCM (j) of a given constellation object (short, i) editable.  Sets up the edit and delete
 * buttons for first-order data objects.
 *
 * @param string shortName  The short name of the data object, such as "nameEntry" or "occupation"
 * @param string|int i      The index within the edit page of the object.
 * @param string|int j      The index within the data object SCM list on the edit page of the SCM object.
 * @return boolean          False to play nice with the browser
 */
function makeSCMEditable(short, i, j) {
	var id = j + "_" + i;
    var scmshort = "scm_" + short;

    // No editing if it's already in edit mode
    if ($("#" + scmshort + "_operation_" + id).val() == "update")
        return false;
    // If it's deleted, then you better undelete it first
    if ($("#" + scmshort + "_operation_" + id).val() == "delete")
        setSCMDeleted(short, i, j);

    $("#" + scmshort + "_editbutton_" + id).removeClass("list-group-item-info").addClass("list-group-item-warning");
    $("#" + scmshort + "_editbutton_" + id).html("<span class=\"glyphicon glyphicon-remove-sign\"></span>");
    $("#" + scmshort + "_editbutton_" + id).off('click').on("click", function() {
    	undoSCMEdit(short, i, j);
    });
    $("#" + scmshort + "_deletebutton_" + id).removeClass("list-group-item-danger").addClass("disabled");
    $("#" + scmshort + "_deletebutton_" + id).off('click').on("click", function() {
        return false;
    });

    $("#" + scmshort + "_panel_" + id).removeClass("panel-default").addClass("alert-info").addClass("edited-component");

    return subMakeEditable(scmshort, id);
}

/**
 * Make an SCM data object un-editable
 *
 * Make the GUI pane for an SCM object (j) for a given constellation object (short, i) un-editable.  Sets up the edit and delete
 * buttons for SCM data objects.
 *
 * @param string shortName The short name of the data object, such as "nameEntry" or "occupation"
 * @param string|int i     The index within the edit page of the object.
 * @param string|int j      The index within the data object SCM list on the edit page of the SCM object.
 * @return boolean         False to play nice with the browser
 */
function makeSCMUneditable(short, i, j) {

	// Make inputs read-only
    var idstr = "_" + j + "_" + i;
    $("input[id^='scm_"+short+"_']").each(function() {
        var obj = $(this);
        if(obj.attr('id').indexOf(idstr) != -1 && obj.attr('id').indexOf("ZZ") == -1) {
            obj.attr("readonly", "true");
        }
    });
    // Make textareas read-only
    $("textarea[id^='scm_"+short+"_']").each(function() {
        var obj = $(this);
        if(obj.attr('id').indexOf(idstr) != -1 && obj.attr('id').indexOf("ZZ") == -1) {
            obj.attr("readonly", "true");
        }
    });
    // Check for a select box
    var sawSelect = false;
    $("select[id^='scm_"+short+"_']").each(function() {
        var obj = $(this);
        if(obj.attr('id').indexOf(idstr) != -1 && obj.attr('id').indexOf("ZZ") == -1) {
            sawSelect = true;
        }
    });
    // If a select box was seen, undo it
    if (sawSelect) {
	    $("div[id^='select_scm_"+short+"']").each(function() {
	        var cont = $(this);
	        if(cont.attr('id').indexOf(idstr) != -1 && cont.attr('id').indexOf("ZZ") == -1) {
	            var split = cont.attr('id').split("_");
	            var name = split[3];
	            var id = $("#scm_"+short+"_"+name+"_id"+idstr).val();
	            var term = $("#scm_"+short+"_"+name+"_id"+idstr+ " option:selected").text();
	            var vocabtype = $("#scm_"+short+"_"+name+"_vocabtype"+idstr).val();
	            var minlength = $("#scm_"+short+"_"+name+"_minlength"+idstr).val();

	            cont.html("<input type=\"hidden\" id=\"scm_"+short+"_"+name+"_id"+idstr+"\" " +
                    	"name=\"scm_"+short+"_"+name+"_id"+idstr+"\" value=\""+id+"\"/>" +
                        "<input type=\"hidden\" id=\"scm_"+short+"_"+name+"_term"+idstr+"\" " +
                    	"name=\"scm_"+short+"_"+name+"_term"+idstr+"\" value=\""+term+"\"/>" +
                        "<input type=\"hidden\" id=\"scm_"+short+"_"+name+"_vocabtype"+idstr+"\" " +
                        	"name=\"scm_"+short+"_"+name+"_vocabtype"+idstr+"\" value=\""+vocabtype+"\"/>" +
                        "<input type=\"hidden\" id=\"scm_"+short+"_"+name+"_minlength"+idstr+"\" " +
                        	"name=\"scm_"+short+"_"+name+"_minlength"+idstr+"\" value=\""+minlength+"\"/>" +
                        	"<p class=\"form-control-static\">"+term+"</p>");

	        }
	    });
	    $("div[id^='selectsource_scm_"+short+"']").each(function() {
	        var cont = $(this);
	        if(cont.attr('id').indexOf(idstr) != -1 && cont.attr('id').indexOf("ZZ") == -1) {
	            var split = cont.attr('id').split("_");
	            var name = split[3];
	            var id = $("#scm_"+short+"_"+name+"_id"+idstr).val();
	            var term = $("#scm_"+short+"_"+name+"_id"+idstr+ " option:selected").text();

	            cont.html("<input type=\"hidden\" id=\"scm_"+short+"_"+name+"_id"+idstr+"\" " +
                    	"name=\"scm_"+short+"_"+name+"_id"+idstr+"\" value=\""+id+"\"/>" +
                        "<input type=\"hidden\" id=\"scm_"+short+"_"+name+"_term"+idstr+"\" " +
                    	"name=\"scm_"+short+"_"+name+"_term"+idstr+"\" value=\""+term+"\"/>" +
                        	"<p class=\"form-control-static\">"+term+"</p>");

	        }
	    });
    }

    // restore the edit button
    $("#scm_" + short + "_editbutton" + idstr).addClass("list-group-item-info").removeClass("list-group-item-warning");
    $("#scm_" + short + "_editbutton" + idstr).html("<span class=\"glyphicon glyphicon-pencil\"></span>");
    $("#scm_" + short + "_editbutton" + idstr).off('click').on("click", function() {
    	makeSCMEditable(short, i, j);
    });

    // restore the delete button
    $("#scm_" + short + "_deletebutton" + idstr).addClass("list-group-item-danger").removeClass("disabled");
    $("#scm_" + short + "_deletebutton" + idstr).off('click').on("click", function() {
       setSCMDeleted(short, i, j);
    });


    // Clear the operation flag
    $("#scm_" + short + "_operation_" + j + "_" + i).val("");

    $("#scm_" + short + "_panel" + idstr).addClass("panel-default").removeClass("alert-info").removeClass("edited-component");
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
function setContributorDeleted(shortName, i) {
    if ($("#" + shortName + "_operation_" + i).val() != "delete") {
        $("#" + shortName + "_remove_" + i).removeClass("btn-danger").addClass("btn-warning");
        $("#" + shortName + "_remove_" + i).html("<span class=\"glyphicon glyphicon-remove-sign\"></span> Undo");
    } else {
        $("#" + shortName + "_remove_" + i).removeClass("btn-warning").addClass("btn-danger");
        $("#" + shortName + "_remove_" + i).html("<span class=\"glyphicon glyphicon-minus-sign\"></span> Remove");
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
        $("#" + short + "_deletebutton_" + i).html("<span class=\"glyphicon glyphicon-remove-sign\"></span> Undo");

        // disable edit button
        $("#" + short + "_editbutton_" + i).removeClass("list-group-item-info").addClass("disabled");
        $("#" + short + "_editbutton_" + i).off('click').on("click", function() {
           return false;
        });

        // disable the SCM button
        $("#" + short + "_scmbutton_" + i).removeClass("list-group-item-success").addClass("disabled").prop('disabled', true);

    } else {
    	// set undelete
        $("#" + short + "_deletebutton_" + i).removeClass("list-group-item-warning").addClass("list-group-item-danger");
        $("#" + short + "_deletebutton_" + i).html("<span class=\"glyphicon glyphicon-trash\"></span> Trash");

        // restore edit button
        $("#" + short + "_editbutton_" + i).addClass("list-group-item-info").removeClass("disabled");
        $("#" + short + "_editbutton_" + i).off('click').on("click", function() {
           makeEditable(short, i);
        });

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
    somethingHasBeenEdited = true;

    return false;
}

/**
 * Set an SCM data object as deleted
 *
 * Sets an SCM object (j) for a constellation object (short, i) as deleted or undeleted and makes the appropriate changes throughout the page.
 *
 * @param string shortName The short name of the data object, such as "nameEntry" or "occupation"
 * @param string|int i     The index within the edit page of the object.
 * @param string|int j      The index within the data object SCM list on the edit page of the SCM object.
 */
function setSCMDeleted(short, i, j) {
	var id = j + '_' + i;
    if ($("#scm_" + short + "_operation_" + id).val() != "delete") {
    	// set deleted
        $("#scm_" + short + "_deletebutton_" + id).removeClass("list-group-item-danger").addClass("list-group-item-warning");
        $("#scm_" + short + "_deletebutton_" + id).html("<span class=\"glyphicon glyphicon-remove-sign\"></span>");

        // disable edit button
        $("#scm_" + short + "_editbutton_" + id).removeClass("list-group-item-info").addClass("disabled");
        $("#scm_" + short + "_editbutton_" + id).off('click').on("click", function() {
           return false;
        });

    } else {
    	// set undelete
        $("#scm_" + short + "_deletebutton_" + id).removeClass("list-group-item-warning").addClass("list-group-item-danger");
        $("#scm_" + short + "_deletebutton_" + id).html("<span class=\"glyphicon glyphicon-trash\"></span>");

        // restore edit button
        $("#scm_" + short + "_editbutton_" + id).addClass("list-group-item-info").removeClass("disabled");
        $("#scm_" + short + "_editbutton_" + id).off('click').on("click", function() {
           makeSCMEditable(short, i, j);
        });

    }

    return subSetDeleted("scm_"+short, id);
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

/**
 * Create a new Name Entry Contributor object on page
 *
 * Puts a new Name Entry contributor object DIV on the page and attaches it correctly to the DOM and javascript.
 *
 * @param  int     i    The index on the page of the nameEntry to add this contributor to
 * @return boolean      false to play nice with the browser.
 */
function newNameEntryContributor(i) {
	var nextid = 1;
	if ($('#nameEntry_contributor_next_j_'+i).exists()) {
	    nextid = parseInt($('#nameEntry_contributor_next_j_'+i).text());
	}
	console.log("Creating new name entry contributor for nameEntry " + i + " with id: " + nextid);
    somethingHasBeenEdited = true;
    var text = $('#contributor_template').clone();
    var html = text.html().replace(/ZZ/g, i).replace(/YY/g, nextid);
    $('#nameEntry_contributor_add_div_'+i).before(html);

    $('#nameEntry_contributor_' + nextid + '_operation_' + 1).val("insert");
    subMakeEditable("nameEntry_contributor_" + nextid, i);

    // Put the updated version number back in the DOM
    $('#nameEntry_contributor_next_j_'+i).text(++nextid);

    return false;
}


/**
 * Things to do when the page finishes loading
 */
$(document).ready(function() {


    // Turn on the edit buttons
    $("a[id*='editbutton']").each(function() {
        var obj = $(this);
        var pieces = obj.attr('id').split("_");

        if (pieces.length == 3) {
            var short = pieces[0];
            var i = pieces[2];
            obj.on("click", function() {
                makeEditable(short, i);
            });
        } else if (pieces.length == 5) {
            var short = pieces[1];
            var i = pieces[4];
            var j = pieces[3];
            obj.on("click", function() {
                makeSCMEditable(short, i, j);
            });
        }
    });

    // Turn on the delete buttons
    $("a[id*='deletebutton']").each(function() {
        var obj = $(this);
        var pieces = obj.attr('id').split("_");

        if (pieces.length == 3) {
            var short = pieces[0];
            var i = pieces[2];
            obj.on("click", function() {
                setDeleted(short, i);
            });
        } else if (pieces.length == 5) {
            var short = pieces[1];
            var i = pieces[4];
            var j = pieces[3];
            obj.on("click", function() {
                setSCMDeleted(short, i, j);
            });
        }
    });

	// Attach functions to the entityType select
	if ($('#entityType').exists()) {
		$('#entityType').change(function() {
            somethingHasBeenEdited = true;
            // If there is an ID, then we need to set this to update
            // Else, the main-level operation should be and remain insert
			if ($('#constellationid').val() != null &&
					$('#constellationid').val() != "") {
				$('#operation').val("update");
			}
		});
	}

	// Attach functions to each of the "+ Add New _______" buttons

	// Code to handle adding new genders to the page
	var genderid = 1;
	if ($('#next_gender_i').exists()) {
	    genderid = parseInt($('#next_gender_i').text());
	}
	console.log("Next Gender ID: " + genderid);
	if ($('#btn_add_gender').exists()){
		$('#btn_add_gender').click(function(){
            somethingHasBeenEdited = true;
			var text = $('#gender_template').clone();
	        var html = text.html().replace(/ZZ/g, genderid);
	        $('#add_gender_div').after(html);
            turnOnButtons("gender", genderid);
	        genderid = genderid + 1;
	        return false;
		});
	}

	// Code to handle adding new genders to the page
	var existid = 1;
	if ($('#next_exist_i').exists()) {
	    existid = parseInt($('#next_exist_i').text());
	}
	console.log("Next Exist Date ID: " + existid);
	if ($('#btn_add_exist_date').exists()){
		$('#btn_add_exist_date').click(function(){
            somethingHasBeenEdited = true;
			var text = $('#exist_date_template').clone();
	        var html = text.html().replace(/ZZ/g, existid);
	        $('#add_exist_div').after(html);
            turnOnButtons("exist", existid);
	        existid = existid + 1;
	        return false;
		});
	}
	if ($('#btn_add_exist_dateRange').exists()){
		$('#btn_add_exist_dateRange').click(function(){
            somethingHasBeenEdited = true;
			var text = $('#exist_dateRange_template').clone();
	        var html = text.html().replace(/ZZ/g, existid);
	        $('#add_exist_div').after(html);
            turnOnButtons("exist", existid);
	        existid = existid + 1;
	        return false;
		});
	}

	var nameEntryid = 1;
	if ($('#next_nameEntry_i').exists()) {
	    nameEntryid = parseInt($('#next_nameEntry_i').text());
	}
	console.log("Next NameEntry ID: " + nameEntryid);
	if ($('#btn_add_nameEntry').exists()){
		$('#btn_add_nameEntry').click(function(){
            somethingHasBeenEdited = true;
			var text = $('#nameEntry_template').clone();
	        var html = text.html().replace(/ZZ/g, nameEntryid);
	        $('#add_nameEntry_div').after(html);
            turnOnButtons("nameEntry", nameEntryid);
	        nameEntryid = nameEntryid + 1;
	        return false;
		});
	}

	var sameAsid = 1;
	if ($('#next_sameAs_i').exists()) {
	    sameAsid = parseInt($('#next_sameAs_i').text());
	}
	console.log("Next sameAs ID: " + sameAsid);
	if ($('#btn_add_sameAs').exists()){
		$('#btn_add_sameAs').click(function(){
            somethingHasBeenEdited = true;
			var text = $('#sameAs_template').clone();
	        var html = text.html().replace(/ZZ/g, sameAsid);
	        $('#add_sameAs_div').after(html);
            turnOnButtons("sameAs", sameAsid);
	        sameAsid = sameAsid + 1;
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
            somethingHasBeenEdited = true;
			var text = $('#source_template').clone();
	        var html = text.html().replace(/ZZ/g, sourceid);
	        $('#add_source_div').after(html);
            turnOnButtons("source", sourceid);
	        sourceid = sourceid + 1;
	        return false;
		});
	}

	var resourceRelationid = 1;
	if ($('#next_resourceRelation_i').exists()) {
	    resourceRelationid = parseInt($('#next_resourceRelation_i').text());
	}
	console.log("Next resourceRelation ID: " + resourceRelationid);
	if ($('#btn_add_resourceRelation').exists()){
		$('#btn_add_resourceRelation').click(function(){
            somethingHasBeenEdited = true;
			var text = $('#resourceRelation_template').clone();
	        var html = text.html().replace(/ZZ/g, resourceRelationid);
	        $('#add_resourceRelation_div').after(html);
            turnOnButtons("resourceRelation", resourceRelationid);
	        resourceRelationid = resourceRelationid + 1;
	        return false;
		});
	}
	var constellationRelationid = 1;

	if ($('#next_constellationRelation_i').exists()) {
	    constellationRelationid = parseInt($('#next_constellationRelation_i').text());
	}
	console.log("Next constellationRelation ID: " + constellationRelationid);
	if ($('#btn_create_constellationRelation').exists()){
		$('#btn_create_constellationRelation').click(function(){
	        var cid = $('input[name=relationChoice]:checked', '#relation_search_form').val()
	        if (cid != null) {
	            somethingHasBeenEdited = true;
				var text = $('#constellationRelation_template').clone();
		        var html = text.html().replace(/ZZ/g, constellationRelationid);
		        $('#add_constellationRelation_div').after(html);
		        $('#constellationRelation_targetID_'+constellationRelationid).val(cid);
		        $('#constellationRelation_content_'+constellationRelationid).val($('#relationChoice_nameEntry_'+cid).val());
		        $('#constellationRelation_targetArkID_'+constellationRelationid).val($('#relationChoice_arkID_'+cid).val());
		        $('#constellationRelation_targetEntityType_'+constellationRelationid).val($('#relationChoice_entityType_'+cid).val());

		        $('#constellationRelation_contentText_'+constellationRelationid).text($('#relationChoice_nameEntry_'+cid).val());
		        $('#constellationRelation_targetArkIDText_'+constellationRelationid).text($('#relationChoice_arkID_'+cid).val());

                turnOnButtons("constellationRelation", constellationRelationid);
		        makeEditable("constellationRelation", constellationRelationid);

		        constellationRelationid = constellationRelationid + 1;

		        return true;

	        }


	        return false;
		});
	}
/**
	if ($('#btn_add_constellationRelation').exists()){
		$('#btn_add_constellationRelation').click(function(){
            somethingHasBeenEdited = true;
			var text = $('#constellationRelation_template').clone();
	        var html = text.html().replace(/ZZ/g, constellationRelationid);
	        $('#add_constellationRelation_div').after(html);
	        constellationRelationid = constellationRelationid + 1;
	        return false;
		});
	}
**/
	var languageid = 1;
	if ($('#next_language_i').exists()) {
	    languageid = parseInt($('#next_language_i').text());
	}
	console.log("Next language ID: " + languageid);
	if ($('#btn_add_language').exists()){
		$('#btn_add_language').click(function(){
            somethingHasBeenEdited = true;
			var text = $('#language_template').clone();
	        var html = text.html().replace(/ZZ/g, languageid);
	        $('#add_language_div').after(html);
            turnOnButtons("language", languageid);
	        languageid = languageid + 1;
	        return false;
		});
	}

	var subjectid = 1;
	if ($('#next_subject_i').exists()) {
	    subjectid = parseInt($('#next_subject_i').text());
	}
	console.log("Next subject ID: " + subjectid);
	if ($('#btn_add_subject').exists()){
		$('#btn_add_subject').click(function(){
            somethingHasBeenEdited = true;
			var text = $('#subject_template').clone();
	        var html = text.html().replace(/ZZ/g, subjectid);
	        $('#add_subject_div').after(html);
            turnOnButtons("subject", subjectid);
	        subjectid = subjectid + 1;
	        return false;
		});
	}

	var nationalityid = 1;
	if ($('#next_nationality_i').exists()) {
	    nationalityid = parseInt($('#next_nationality_i').text());
	}
	console.log("Next nationality ID: " + nationalityid);
	if ($('#btn_add_nationality').exists()){
		$('#btn_add_nationality').click(function(){
            somethingHasBeenEdited = true;
			var text = $('#nationality_template').clone();
	        var html = text.html().replace(/ZZ/g, nationalityid);
	        $('#add_nationality_div').after(html);
            turnOnButtons("nationality", nationalityid);
	        nationalityid = nationalityid + 1;
	        return false;
		});
	}

	var functionid = 1;
	if ($('#next_function_i').exists()) {
	    functionid = parseInt($('#next_function_i').text());
	}
	console.log("Next function ID: " + functionid);
	if ($('#btn_add_function').exists()){
		$('#btn_add_function').click(function(){
            somethingHasBeenEdited = true;
			var text = $('#function_template').clone();
	        var html = text.html().replace(/ZZ/g, functionid);
	        $('#add_function_div').after(html);
            turnOnButtons("function", functionid);
	        functionid = functionid + 1;
	        return false;
		});
	}

	var occupationid = 1;
	if ($('#next_occupation_i').exists()) {
	    occupationid = parseInt($('#next_occupation_i').text());
	}
	console.log("Next occupation ID: " + occupationid);
	if ($('#btn_add_occupation').exists()){
		$('#btn_add_occupation').click(function(){
            somethingHasBeenEdited = true;
			var text = $('#occupation_template').clone();
	        var html = text.html().replace(/ZZ/g, occupationid);
	        $('#add_occupation_div').after(html);
            turnOnButtons("occupation", occupationid);
	        occupationid = occupationid + 1;
	        return false;
		});
	}

	var legalStatusid = 1;
	if ($('#next_legalStatus_i').exists()) {
	    legalStatusid = parseInt($('#next_legalStatus_i').text());
	}
	console.log("Next legalStatus ID: " + legalStatusid);
	if ($('#btn_add_legalStatus').exists()){
		$('#btn_add_legalStatus').click(function(){
            somethingHasBeenEdited = true;
			var text = $('#legalStatus_template').clone();
	        var html = text.html().replace(/ZZ/g, legalStatusid);
	        $('#add_legalStatus_div').after(html);
            turnOnButtons("legalStatus", legalStatusid);
	        legalStatusid = legalStatusid + 1;
	        return false;
		});
	}

	var placeid = 1;
	if ($('#next_place_i').exists()) {
	    placeid = parseInt($('#next_place_i').text());
	}
	console.log("Next place ID: " + placeid);
	if ($('#btn_add_place').exists()){
		$('#btn_add_place').click(function(){
            somethingHasBeenEdited = true;
			var text = $('#place_template').clone();
	        var html = text.html().replace(/ZZ/g, placeid);
	        $('#add_place_div').after(html);
            turnOnButtons("place", placeid);
	        placeid = placeid + 1;
	        return false;
		});
	}

	var conventionDeclarationid = 1;
	if ($('#next_conventionDeclaration_i').exists()) {
	    conventionDeclarationid = parseInt($('#next_conventionDeclaration_i').text());
	}
	console.log("Next conventionDeclaration ID: " + conventionDeclarationid);
	if ($('#btn_add_conventionDeclaration').exists()){
		$('#btn_add_conventionDeclaration').click(function(){
            somethingHasBeenEdited = true;
			var text = $('#conventionDeclaration_template').clone();
	        var html = text.html().replace(/ZZ/g, conventionDeclarationid);
	        $('#add_conventionDeclaration_div').after(html);
            turnOnButtons("conventionDeclaration", conventionDeclarationid);
	        conventionDeclarationid = conventionDeclarationid + 1;
	        return false;
		});
	}

	var generalContextid = 1;
	if ($('#next_generalContext_i').exists()) {
	    generalContextid = parseInt($('#next_generalContext_i').text());
	}
	console.log("Next generalContext ID: " + generalContextid);
	if ($('#btn_add_generalContext').exists()){
		$('#btn_add_generalContext').click(function(){
            somethingHasBeenEdited = true;
			var text = $('#generalContext_template').clone();
	        var html = text.html().replace(/ZZ/g, generalContextid);
	        $('#add_generalContext_div').after(html);
            turnOnButtons("generalContext", generalContextid);
	        generalContextid = generalContextid + 1;
	        return false;
		});
	}

	var structureOrGenealogyid = 1;
	if ($('#next_structureOrGenealogy_i').exists()) {
	    structureOrGenealogyid = parseInt($('#next_structureOrGenealogy_i').text());
	}
	console.log("Next structureOrGenealogy ID: " + structureOrGenealogyid);
	if ($('#btn_add_structureOrGenealogy').exists()){
		$('#btn_add_structureOrGenealogy').click(function(){
            somethingHasBeenEdited = true;
			var text = $('#structureOrGenealogy_template').clone();
	        var html = text.html().replace(/ZZ/g, structureOrGenealogyid);
	        $('#add_structureOrGenealogy_div').after(html);
            turnOnButtons("structureOrGenealogy", structureOrGenealogyid);
	        structureOrGenealogyid = structureOrGenealogyid + 1;
	        return false;
		});
	}

	var mandateid = 1;
	if ($('#next_mandate_i').exists()) {
	    mandateid = parseInt($('#next_mandate_i').text());
	}
	console.log("Next mandate ID: " + mandateid);
	if ($('#btn_add_mandate').exists()){
		$('#btn_add_mandate').click(function(){
            somethingHasBeenEdited = true;
			var text = $('#mandate_template').clone();
	        var html = text.html().replace(/ZZ/g, mandateid);
	        $('#add_mandate_div').after(html);
            turnOnButtons("mandate", mandateid);
	        mandateid = mandateid + 1;
	        return false;
		});
	}

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
