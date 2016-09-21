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
            +"<a href=\"?command=dashboard\" type=\"button\" class=\"btn btn-danger\">"+
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
 * Add sub-Date GUI object
 *
 * Adds a Date GUI object to the Date modal for the given "short" type of data indexed by i
 *
 * @param string short The short name of the data object, such as "nameEntry" or "occupation"
 * @param string|int i The index of the object to add a date object to.
 * @returns boolean false to keep the browser from redrawing the page
 */
function addDateEntry(short, i){
	//next_date_{{short}}_{{i}}_j
	var j = parseInt($('#next_date_'+short+'_'+i+'_j').text());
	var id = j + "_" + i;
    somethingHasBeenEdited = true;
	var text = $('#sub_date_template').clone();
    var html = text.html().replace(/ZZ/g, i).replace(/XX/g, j).replace(/SHORT/g, short);
    $('#add_date_'+short+'_'+i+'_div').after(html);
    $('#next_date_'+short+'_'+i+'_j').text(j + 1);
    turnOnButtons(short+"_date"+j, i);
    turnOnTooltips(short+"_date_"+j, i);
    makeEditable(short + "_date_"+j, i);
    return false;
}

/**
 * Add sub-Date GUI object
 *
 * Adds a Date GUI object to the Date modal for the given "short" type of data indexed by i
 *
 * @param string short The short name of the data object, such as "nameEntry" or "occupation"
 * @param string|int i The index of the object to add a date object to.
 * @returns boolean false to keep the browser from redrawing the page
 */
function addDateRangeEntry(short, i){
	//next_date_{{short}}_{{i}}_j
	var j = parseInt($('#next_date_'+short+'_'+i+'_j').text());
	var id = j + "_" + i;
    somethingHasBeenEdited = true;
	var text = $('#sub_dateRange_template').clone();
    var html = text.html().replace(/ZZ/g, i).replace(/XX/g, j).replace(/SHORT/g, short);
    $('#add_date_'+short+'_'+i+'_div').after(html);
    $('#next_date_'+short+'_'+i+'_j').text(j + 1);
    turnOnButtons(short+"_date"+j, i);
    turnOnTooltips(short+"_date_"+j, i);
    makeEditable(short + "_date_"+j, i);
    return false;
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
	var id = j + "_" + i;
    somethingHasBeenEdited = true;
	var text = $('#scm_template').clone();
    var html = text.html().replace(/ZZ/g, i).replace(/YY/g, j).replace(/SHORT/g, short);
    $('#add_scm_'+short+'_'+i+'_div').after(html);
    $('#next_scm_'+short+'_'+i+'_j').text(j + 1);
    turnOnSCMButtons(short, i, j);
    turnOnTooltips("scm_"+short, id);
    makeSCMEditable(short, i, j);
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
    turnOnTooltips(short,i);
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
    turnOnTooltips("scm_"+short, id);
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

            vocab_select_replace($("#"+shortName+"_"+name+"_id_"+idStr), "_"+idStr, vocabtype, minlength);

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
    $("#" + short + "_editbutton_" + i).html("<span class=\"fa fa-undo\"></span> Undo");
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

    var idstr = "_" + i;

    $("#"+short+"_datapart_" + i + " button[id^='"+short+"_']").each(function() {
        var obj = $(this);
        if(obj.attr('id').endsWith(idstr) && !obj.attr('id').endsWith("ZZ")) {
            obj.removeAttr("disabled").removeClass("snac-hidden");
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
        textToDate(short, i);
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


    inputToText(shortName, i);
    textAreaToText(shortName, i);
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
        dateToText(shortName,i);
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
    $("#" + shortName + "_editbutton_" + i).html("<span class=\"fa fa-pencil-square-o\"></span> Edit");
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
function makeSCMEditable(shortName, i, j) {
	var id = j + "_" + i;
    var scmShortName = "scm_" + shortName;

    // No editing if it's already in edit mode
    if ($("#" + scmShortName + "_operation_" + id).val() == "update")
        return false;
    // If it's deleted, then you better undelete it first
    if ($("#" + scmShortName + "_operation_" + id).val() == "delete")
        setSCMDeleted(shortName, i, j);

    $("#" + scmShortName + "_editbutton_" + id).removeClass("list-group-item-info").addClass("list-group-item-warning");
    $("#" + scmShortName + "_editbutton_" + id).html("<span class=\"fa fa-undo\"></span>");
    $("#" + scmShortName + "_editbutton_" + id).off('click').on("click", function() {
    	undoSCMEdit(shortName, i, j);
    });
    $("#" + scmShortName + "_deletebutton_" + id).removeClass("list-group-item-danger").addClass("disabled");
    $("#" + scmShortName + "_deletebutton_" + id).off('click').on("click", function() {
        return false;
    });

    $("#" + scmShortName + "_panel_" + id).removeClass("panel-default").addClass("alert-info").addClass("edited-component");

    return subMakeEditable(scmShortName, id);
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
function makeSCMUneditable(shortName, i, j) {

	// Make inputs read-only
    var idstr = j + "_" + i;

    subMakeUneditable('scm_'+shortName, idstr);

    // restore the edit button
    $("#scm_" + shortName + "_editbutton_" + idstr).addClass("list-group-item-info").removeClass("list-group-item-warning");
    $("#scm_" + shortName + "_editbutton_" + idstr).html("<span class=\"fa fa-pencil-square-o\"></span>");
    $("#scm_" + shortName + "_editbutton_" + idstr).off('click').on("click", function() {
    	makeSCMEditable(shortName, i, j);
    });

    // restore the delete button
    $("#scm_" + shortName + "_deletebutton_" + idstr).addClass("list-group-item-danger").removeClass("disabled");
    $("#scm_" + shortName + "_deletebutton_" + idstr).off('click').on("click", function() {
       setSCMDeleted(shortName, i, j);
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
        $("#" + short + "_deletebutton_" + i).html("<span class=\"fa fa-undo\"></span> Undo");

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
        $("#" + short + "_deletebutton_" + i).html("<span class=\"fa fa-trash-o\"></span> Trash");

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
        $("#scm_" + short + "_deletebutton_" + id).html("<span class=\"fa fa-undo\"></span>");

        // disable edit button
        $("#scm_" + short + "_editbutton_" + id).removeClass("list-group-item-info").addClass("disabled");
        $("#scm_" + short + "_editbutton_" + id).off('click').on("click", function() {
           return false;
        });

    } else {
    	// set undelete
        $("#scm_" + short + "_deletebutton_" + id).removeClass("list-group-item-warning").addClass("list-group-item-danger");
        $("#scm_" + short + "_deletebutton_" + id).html("<span class=\"fa fa-trash-o\"></span>");

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

/**
 * Create a new Address Line object on page
 *
 * Puts a new Place Address Line object DIV on the page and attaches it correctly to the DOM and javascript.
 *
 * @param  int     i    The index on the page of the place to add this component to
 * @return boolean      false to play nice with the browser.
 */
function newAddressLine(i) {
	var nextid = 1;
	if ($('#place_address_next_j_'+i).exists()) {
	    nextid = parseInt($('#place_address_next_j_'+i).text());
	}
	console.log("Creating new address line for place " + i + " with id: " + nextid);
    somethingHasBeenEdited = true;
    var text = $('#addressline_template').clone();
    var html = text.html().replace(/ZZ/g, i).replace(/YY/g, nextid);
    $('#place_address_add_div_'+i).before(html);

    $('#place_address_' + nextid + '_operation_' + 1).val("insert");
    subMakeEditable("place_address_" + nextid, i);

    vocab_select_replace($("#place_address_"+nextid+"_type_id_"+i), "_"+i, 'address_part', 0);

    // Put the updated version number back in the DOM
    $('#place_address_next_j_'+i).text(++nextid);

    return false;
}

/**
 * Create a new Name Entry Component object on page
 *
 * Puts a new Name Entry component object DIV on the page and attaches it correctly to the DOM and javascript.
 *
 * @param  int     i    The index on the page of the nameEntry to add this component to
 * @return boolean      false to play nice with the browser.
 */
function newNameEntryComponent(i) {
	var nextid = 1;
	if ($('#nameEntry_component_next_j_'+i).exists()) {
	    nextid = parseInt($('#nameEntry_component_next_j_'+i).text());
	}
	console.log("Creating new name entry component for nameEntry " + i + " with id: " + nextid);
    somethingHasBeenEdited = true;
    var text = $('#component_template').clone();
    var html = text.html().replace(/ZZ/g, i).replace(/YY/g, nextid);
    $('#nameEntry_component_add_div_'+i).before(html);

    $('#nameEntry_component_' + nextid + '_operation_' + 1).val("insert");
    subMakeEditable("nameEntry_component_" + nextid, i);

    vocab_select_replace($("#nameEntry_component_"+nextid+"_type_id_"+i), "_"+i, 'name_component', 0);

    // Put the updated version number back in the DOM
    $('#nameEntry_component_next_j_'+i).text(++nextid);

    return false;
}

function updateNameEntryHeading(i) {
    var text = "";

    $("#nameEntry_panel_"+i+" div[id^='nameEntry_component_']").each(function() {
        var obj = $(this);
        if (!obj.hasClass("deleted-component") && obj.attr('id').endsWith("_panel_" + i)
                && !obj.attr('id').endsWith("ZZ")) {
            var j = obj.attr('id').replace("nameEntry_component_", "").replace("_panel_"+i, "");
            text += $("#nameEntry_component_"+j+"_text_"+i).val() + " ";
        }
    });

    $("#nameEntry_heading_"+i).text(text.trim());
    $("#nameEntry_original_"+i).val(text.trim());
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
    turnOnTooltips("nameEntry_contributor_" + nextid, i);
    subMakeEditable("nameEntry_contributor_" + nextid, i);

    // Put the updated version number back in the DOM
    $('#nameEntry_contributor_next_j_'+i).text(++nextid);

    return false;
}

/**
 * Parse a date string into parts
 */
function parseDate(dateString) {
   var pieces = dateString.split("-");
   if (pieces.length == 3)
      return {
          year : parseInt(pieces[0]),
          month : parseInt(pieces[1]),
          day : parseInt(pieces[2])
      };
   else if (pieces.length == 2)
      return {
          year : parseInt(pieces[0]),
          month : parseInt(pieces[1]),
          day : ''
      };
   else if (pieces.length == 1 && pieces[0] != '')
      return {
          year : parseInt(pieces[0]),
          month : '',
          day : ''
      };
   else return {
       year : '', month : '', day : ''
   }
}

/**
 * Change date input divs to select and boxes
 *
 * Changes all div's with id "date_" for a given data object (shortName, idStr) from a list of
 * inputs defining the parameters (view mode) to a inputs and a select (edit mode).  It then
 * calls the select2 function to replace the select with one matching the rest of the page.
 *
 * Note: idStr must not have the "_" pre-appended
 *
 * @param string shortName The short name of the data object, such as "nameEntry" or "occupation"
 * @param string|int idStr The index within the edit page of the object.
 */
function textToDate(shortName, idStr) {
    $("div[id^='date_"+shortName+"']").each(function() {
        var cont = $(this);
        if(cont.attr('id').endsWith("_"+idStr) && !cont.attr('id').endsWith("ZZ")) {
            // remove the short name and "select_" from the string we're parsing
            var divStr = cont.attr('id').replace("date_", "").replace(shortName + "_", "");
            // remove the idstr to receive the name of this element
            var regex = new RegExp("\_"+idStr+"$", "g");
            var name = divStr.replace(regex, "");
            var dateStr = $("#"+shortName+"_"+name+"_"+idStr).val();

            var dateParts = parseDate(dateStr);

            var html = "<input type='text' size='4' style='width:20%;display:inline;' placeholder='YYYY' id='"+shortName+"_"+name+"_year_"+idStr+"' class='form-control' value='"+dateParts.year+"'>";
            html += "<span class='form-control-static'> - </span>";
            html += "<select id='"+shortName+"_"+name+"_month_"+idStr+"' class='form-control' data-placeholder='Month' style='width: 57%; margin-bottom: 5px; display: inline-block;'>"+
                    "<option></option>";
            var months = ["January", "February", "March", "April", "May",
                            "June", "July", "August", "September", "October", "November", "December"];

            months.forEach(function(value, key) {
                var mInt = key + 1;

                if (mInt == dateParts.month)
                    html += "<option value=\""+mInt+"\" selected>"+value+"</option>";
                else
                    html += "<option value=\""+mInt+"\">"+value+"</option>";
            });
            html += "<select> ";
            html += "<span class='form-control-static'> - </span>";
            html += "<input type='text' style='width:14%;display:inline;' size='2' placeholder='DD' id='"+shortName+"_"+name+"_day_"+idStr+"' class='form-control' value='"+dateParts.day+"'> ";
            html += "<input type='hidden' id='"+shortName+"_"+name+"_"+idStr+"' name='"+shortName+"_"+name+"_"+idStr+"' value='"+dateStr+"'>";
            cont.html(html);

            $("#"+shortName+"_"+name+"_month_"+idStr).select2({
                    width: '57%',
                    allowClear: true,
                    theme: 'bootstrap',
                    placeholder: 'Month'
                });

        }
    });
}

/**
 * Pad an integer
 *
 * This is a helper function to pad an integer with 0s for display.  This is useful to pad
 * a month or day with a leading 0.
 *
 * @param int|string num The number to pad
 * @param int size The total width of the desired output
 * @return string A string containing a size-wide integer representation, 0-padded
 */
function pad(num, size) {
    var s = num+"";
    while (s.length < size) s = "0" + s;
    return s;
}

/**
 * Updates the standard date input field
 *
 * If the date for the data object (shortName, idStr) has been turned into a 3-field edit
 * area, then this function will update the hidden standard date (YYYY-MM-DD) field with the
 * newest values from the human-enterable field.
 *
 * Note: idStr must not have the "_" pre-appended
 *
 * @param string shortName The short name of the data object, such as "nameEntry" or "occupation"
 * @param string|int idStr The index within the edit page of the object.
 */
function updateDate(shortName, idStr) {
    $("div[id^='date_"+shortName+"']").each(function() {
        var cont = $(this);
        if(cont.attr('id').endsWith("_"+idStr) && !cont.attr('id').endsWith("ZZ")) {
            // remove the short name and "select_" from the string we're parsing
            var divStr = cont.attr('id').replace("date_", "").replace(shortName + "_", "");
            // remove the idstr to receive the name of this element
            var regex = new RegExp("\_"+idStr+"$", "g");
            var name = divStr.replace(regex, "");

            if ($("#"+shortName+"_"+name+"_year_"+idStr).exists()) {
                var year = $("#"+shortName+"_"+name+"_year_"+idStr).val();
                var day = $("#"+shortName+"_"+name+"_day_"+idStr).val();
                var month = $("#"+shortName+"_"+name+"_month_"+idStr+ " option:selected").val();

                var dateStr = "";
                if (year != "") {
                    dateStr += pad(year, 4);
                    if (month != "") {
                        dateStr += "-" + pad(month,2);
                        if (day != "") {
                            dateStr += "-" + pad(day,2);
                        }
                    }
                }

                $("#"+shortName+"_"+name+"_"+idStr).val(dateStr);
            }

        }
    });
}

/**
 * Return editable date area back to text
 *
 * If the date for the data object (shortName, idStr) has been turned into a 3-field edit
 * area, then this function will return the editable area back to the view mode text, replacing
 * the edit boxes with a paragraph containing the computed standard date string (YYYY-MM-DD).
 *
 * Note: idStr must not have the "_" pre-appended
 *
 * @param string shortName The short name of the data object, such as "nameEntry" or "occupation"
 * @param string|int idStr The index within the edit page of the object.
 */
function dateToText(shortName, idStr) {
    $("div[id^='date_"+shortName+"']").each(function() {
        var cont = $(this);
        if(cont.attr('id').endsWith("_"+idStr) && !cont.attr('id').endsWith("ZZ")) {
            // remove the short name and "select_" from the string we're parsing
            var divStr = cont.attr('id').replace("date_", "").replace(shortName + "_", "");
            // remove the idstr to receive the name of this element
            var regex = new RegExp("\_"+idStr+"$", "g");
            var name = divStr.replace(regex, "");

            updateDate(shortName, idStr);


            var dateStr = $("#"+shortName+"_"+name+"_"+idStr).val();

            var html = "<p class='form-control-static'>"+dateStr+"</p>";
            html += "<input type='hidden' id='"+shortName+"_"+name+"_"+idStr+"' name='"+shortName+"_"+name+"_"+idStr+"' value='"+dateStr+"'>";
            cont.html(html);

        }
    });
}


// TODO: If we want to show a DIV of edited and deleted components, we should use the following two functions
// to get copies of the edited/deleted components from the page.  We can then insert those copies onto a modal
// dialog box with a "Save" or "Continue" button at the top and bottom.
//
// All the pieces shown in the dialog should be disabled.  One way to do this would be using the following
// method to place a semi-transparent div on top of the editable pieces, making them appear to be grayed out
// and with the not permitted cursor.
//
// .append('<div style="position:absolute; top:0; left:0; width:100%; height:100%; background:#f3f3f3; z-index:500; cursor:not-allowed;opacity:0.4;filter: alpha(opacity = 50)"></div>');
//
// Note: this modal should NOT be inside the constellation form so that we don't submit the values twice.

/**
 * Get all the edited components
 */
function getEdited() {
    var html = "";

    $("#constellation_form div.edited-component").each(function() {
        var cont = $(this);
        console.log(cont.attr('id'));
        var pieces = cont.attr('id').split("_panel_");
        if (pieces.length == 2) {
            html += "<div class='panel panel-body edited-component'>" + $("#"+pieces[0] + "_datapart_" + pieces[1]).html() + "</div>";
        }
    });

    return html;
}

/**
 * Get all the deleted components
 */
function getDeleted() {
    var html = "";

    $("#constellation_form div.deleted-component").each(function() {
        var cont = $(this);
        console.log(cont.attr('id'));
        var pieces = cont.attr('id').split("_panel_");
        if (pieces.length == 2) {
            html += "<div class='panel panel-body deleted-component'>" + $("#"+pieces[0] + "_datapart_" + pieces[1]).html() + "</div>";
        }
    });

    return html;
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
        } else if (pieces.length == 5 && pieces[0] == "scm") {
            var short = pieces[1];
            var i = pieces[4];
            var j = pieces[3];
            obj.on("click", function() {
                makeSCMEditable(short, i, j);
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
        } else if (pieces.length == 5 && pieces[0] == "scm") {
            var short = pieces[1];
            var i = pieces[4];
            var j = pieces[3];
            obj.on("click", function() {
                setSCMDeleted(short, i, j);
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
/**
 * Things to do when the page finishes loading
 */
$(document).ready(function() {


    // If the constellation is in "insert" mode, then we should automatically set "somethingHasBeenEdited"
    // to be true...
    if ($('#operation').val() == 'insert')
        somethingHasBeenEdited = true;

    turnOnEditDeleteButtons();

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
    
    // Name Entry doesn't get any AJAX, since it is pre-loaded
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
            turnOnTooltips("nameEntry", nameEntryid);
            makeEditable("nameEntry", nameEntryid);
	        nameEntryid = nameEntryid + 1;
	        return false;
		});
	}


	// Attach functions to each of the "+ Add New _______" buttons

	// Code to handle adding new genders to the page
	var genderid = 1;
    var genderOpen = false;
	if ($('#genderstab').exists()){
		$('#genderstab').click(function(){
            // Don't open a second time
            if (genderOpen)
                return;

            $.get("?command=edit_part&part=genders&constellationid="+$('#constellationid').val()+"&version="+$('#version').val(), null, function (data) {
                genderOpen = true;
                $('#genders').html(data);

                turnOnEditDeleteButtons("genders");
                
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
                        turnOnTooltips("gender", genderid);
                        makeEditable("gender", genderid);
                        genderid = genderid + 1;
                        return false;
                    });
                }
                
                turnOnTooltipsForTab("genders");
            });
        });
    }

	// Code to handle adding new genders to the page
	var existid = 1;
    var existOpen = false;
	if ($('#existstab').exists()){
		$('#existstab').click(function(){
            // Don't open a second time
            if (existOpen)
                return;

            $.get("?command=edit_part&part=dates&constellationid="+$('#constellationid').val()+"&version="+$('#version').val(), null, function (data) {
                existOpen = true;
                $('#dates').html(data);

                turnOnEditDeleteButtons("dates");
                
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
                        turnOnTooltips("exist", existid);
                        makeEditable("exist", existid);
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
                        turnOnTooltips("exist", existid);
                        makeEditable("exist", existid);
                        existid = existid + 1;
                        return false;
                    });
                }
                
                turnOnTooltipsForTab("dates");
            });
        });
    }

	var sameAsid = 1;
    var sameAsOpen = false;
	if ($('#sameAstab').exists()){
		$('#sameAstab').click(function(){
            // Don't open a second time
            if (sameAsOpen)
                return;

            $.get("?command=edit_part&part=sameAs&constellationid="+$('#constellationid').val()+"&version="+$('#version').val(), null, function (data) {
                sameAsOpen = true;
                $('#sameAs').html(data);

                turnOnEditDeleteButtons("sameAs");
                
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
                        turnOnTooltips("sameAs", sameAsid);
                        makeEditable("sameAs", sameAsid);
                        sameAsid = sameAsid + 1;
                        return false;
                    });
                }
                
                turnOnTooltipsForTab("sameAs");
            });
        });
    }


	var entityIDid = 1;
    var entityIDOpen = false;
	if ($('#entityIDtab').exists()){
		$('#entityIDtab').click(function(){
            // Don't open a second time
            if (entityIDOpen)
                return;

            $.get("?command=edit_part&part=entityID&constellationid="+$('#constellationid').val()+"&version="+$('#version').val(), null, function (data) {
                entityIDOpen = true;
                $('#entityID').html(data);

                turnOnEditDeleteButtons("entityID");
                
                if ($('#next_entityID_i').exists()) {
                    entityIDid = parseInt($('#next_entityID_i').text());
                }
                console.log("Next entityID ID: " + entityIDid);
                if ($('#btn_add_entityID').exists()){
                    $('#btn_add_entityID').click(function(){
                        somethingHasBeenEdited = true;
                        var text = $('#entityID_template').clone();
                        var html = text.html().replace(/ZZ/g, entityIDid);
                        $('#add_entityID_div').after(html);
                        turnOnButtons("entityID", entityIDid);
                        turnOnTooltips("entityID", entityIDid);
                        makeEditable("entityID", entityIDid);
                        entityIDid = entityIDid + 1;
                        return false;
                    });
                }
                
                turnOnTooltipsForTab("entityID");
            });
        });
    }

	var sourceid = 1;
    var sourceOpen = false;
	if ($('#sourcestab').exists()){
		$('#sourcestab').click(function(){
            // Don't open a second time
            if (sourceOpen)
                return;

            $.get("?command=edit_part&part=sources&constellationid="+$('#constellationid').val()+"&version="+$('#version').val(), null, function (data) {
                sourceOpen = true;
                $('#sources').html(data);

                turnOnEditDeleteButtons("sources");
                
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
                        turnOnTooltips("source", sourceid);
                        makeEditable("source", sourceid);
                        sourceid = sourceid + 1;
                        return false;
                    });
                }
                
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

            $.get("?command=edit_part&part=resourceRelations&constellationid="+$('#constellationid').val()+"&version="+$('#version').val(), null, function (data) {
                resourceRelationOpen = true;
                $('#resourceRelations').html(data);

                turnOnEditDeleteButtons("resourceRelations");
                
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
                        turnOnTooltips("resourceRelation", resourceRelationid);
                        makeEditable("resourceRelation", resourceRelationid);
                        resourceRelationid = resourceRelationid + 1;
                        return false;
                    });
                }
                
                turnOnTooltipsForTab("resourceRelations");
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

            $.get("?command=edit_part&part=constellationRelations&constellationid="+$('#constellationid').val()+"&version="+$('#version').val(), null, function (data) {
                constellationRelationOpen = true;
                $('#constellationRelations').html(data);

                turnOnEditDeleteButtons("constellationRelations");
                
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
                            turnOnTooltips("constellationRelation", constellationRelationid);
                            makeEditable("constellationRelation", constellationRelationid);

                            constellationRelationid = constellationRelationid + 1;

                            return true;

                        }


                        return false;
                    });
                }
                
                turnOnTooltipsForTab("constellationRelations");
            });
        });
    }
	
	var languageid = 1;
    var languageOpen = false;
	if ($('#languagesUsedtab').exists()){
		$('#languagesUsedtab').click(function(){
            // Don't open a second time
            if (languageOpen)
                return;

            $.get("?command=edit_part&part=languagesUsed&constellationid="+$('#constellationid').val()+"&version="+$('#version').val(), null, function (data) {
                languageOpen = true;
                $('#languagesUsed').html(data);

                turnOnEditDeleteButtons("languagesUsed");
                
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
                        turnOnTooltips("language", languageid);
                        makeEditable("language", languageid);
                        languageid = languageid + 1;
                        return false;
                    });
                }
                
                turnOnTooltipsForTab("languagesUsed");
            });
        });
    }

	var subjectid = 1;
    var subjectOpen = false;
	if ($('#subjectstab').exists()){
		$('#subjectstab').click(function(){
            // Don't open a second time
            if (subjectOpen)
                return;

            $.get("?command=edit_part&part=subjects&constellationid="+$('#constellationid').val()+"&version="+$('#version').val(), null, function (data) {
                subjectOpen = true;
                $('#subjects').html(data);

                turnOnEditDeleteButtons("subjects");
                
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
                        turnOnTooltips("subject", subjectid);
                        makeEditable("subject", subjectid);
                        subjectid = subjectid + 1;
                        return false;
                    });
                }
                
                turnOnTooltipsForTab("subjects");
            });
        });
    }

	var nationalityid = 1;
    var nationalityOpen = false;
	if ($('#nationalitiestab').exists()){
		$('#nationalitiestab').click(function(){
            // Don't open a second time
            if (nationalityOpen)
                return;

            $.get("?command=edit_part&part=nationalities&constellationid="+$('#constellationid').val()+"&version="+$('#version').val(), null, function (data) {
                nationalityOpen = true;
                $('#nationalities').html(data);

                turnOnEditDeleteButtons("nationalities");
                
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
                        turnOnTooltips("nationality", nationalityid);
                        makeEditable("nationality", nationalityid);
                        nationalityid = nationalityid + 1;
                        return false;
                    });
                }
                
                turnOnTooltipsForTab("nationalities");
            });
        });
    }

	var functionid = 1;
    var functionOpen = false;
	if ($('#functionstab').exists()){
		$('#functionstab').click(function(){
            // Don't open a second time
            if (functionOpen)
                return;

            $.get("?command=edit_part&part=functions&constellationid="+$('#constellationid').val()+"&version="+$('#version').val(), null, function (data) {
                functionOpen = true;
                $('#functions').html(data);

                turnOnEditDeleteButtons("functions");
                
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
                        turnOnTooltips("function", functionid);
                        makeEditable("function", functionid);
                        functionid = functionid + 1;
                        return false;
                    });
                }
                
                turnOnTooltipsForTab("functions");
            });
        });
    }

	var occupationid = 1;
    var occupationOpen = false;
	if ($('#occupationstab').exists()){
		$('#occupationstab').click(function(){
            // Don't open a second time
            if (occupationOpen)
                return;

            $.get("?command=edit_part&part=occupations&constellationid="+$('#constellationid').val()+"&version="+$('#version').val(), null, function (data) {
                occupationOpen = true;
                $('#occupations').html(data);

                turnOnEditDeleteButtons("occupations");
                
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
                        turnOnTooltips("occupation", occupationid);
                        makeEditable("occupation", occupationid);
                        occupationid = occupationid + 1;
                        return false;
                    });
                }
                
                turnOnTooltipsForTab("occupations");
            });
        });
    }

	var legalStatusid = 1;
    var legalStatusOpen = false;
	if ($('#legalStatusestab').exists()){
		$('#legalStatusestab').click(function(){
            // Don't open a second time
            if (legalStatusOpen)
                return;

            $.get("?command=edit_part&part=legalStatuses&constellationid="+$('#constellationid').val()+"&version="+$('#version').val(), null, function (data) {
                legalStatusOpen = true;
                $('#legalStatuses').html(data);

                turnOnEditDeleteButtons("legalStatuses");
                
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
                        turnOnTooltips("legalStatus", legalStatusid);
                        makeEditable("legalStatus", legalStatusid);
                        legalStatusid = legalStatusid + 1;
                        return false;
                    });
                }
                
                turnOnTooltipsForTab("legalStatuses");
            });
        });
    }

	var placeid = 1;
    var placeOpen = false;
	if ($('#placestab').exists()){
		$('#placestab').click(function(){
            // Don't open a second time
            if (placeOpen)
                return;

            $.get("?command=edit_part&part=places&constellationid="+$('#constellationid').val()+"&version="+$('#version').val(), null, function (data) {
                placeOpen = true;
                $('#places').html(data);

                turnOnEditDeleteButtons("places");
                
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
                        turnOnTooltips("place", placeid);
                        makeEditable("place", placeid);
                        placeid = placeid + 1;
                        return false;
                    });
                }
                
                turnOnTooltipsForTab("places");
            });
        });
    }

	var conventionDeclarationid = 1;
    var conventionDeclarationOpen = false;
	if ($('#conventionDeclarationstab').exists()){
		$('#conventionDeclarationstab').click(function(){
            // Don't open a second time
            if (conventionDeclarationOpen)
                return;

            $.get("?command=edit_part&part=conventionDeclarations&constellationid="+$('#constellationid').val()+"&version="+$('#version').val(), null, function (data) {
                conventionDeclarationOpen = true;
                $('#conventionDeclarations').html(data);

                turnOnEditDeleteButtons("conventionDeclarations");
                
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
                        turnOnTooltips("conventionDeclaration", conventionDeclarationid);
                        makeEditable("conventionDeclaration", conventionDeclarationid);
                        conventionDeclarationid = conventionDeclarationid + 1;
                        return false;
                    });
                }
                
                turnOnTooltipsForTab("conventionDeclarations");
            });
        });
    }

	var generalContextid = 1;
    var generalContextOpen = false;
	if ($('#generalContextstab').exists()){
		$('#generalContextstab').click(function(){
            // Don't open a second time
            if (generalContextOpen)
                return;

            $.get("?command=edit_part&part=generalContexts&constellationid="+$('#constellationid').val()+"&version="+$('#version').val(), null, function (data) {
                generalContextOpen = true;
                $('#generalContexts').html(data);

                turnOnEditDeleteButtons("generalContexts");
                
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
                        turnOnTooltips("generalContext", generalContextid);
                        makeEditable("generalContext", generalContextid);
                        generalContextid = generalContextid + 1;
                        return false;
                    });
                }
                
                turnOnTooltipsForTab("generalContexts");
            });
        });
    }

	var structureOrGenealogyid = 1;
    var structureOrGenealogyOpen = false;
	if ($('#structureOrGenealogiestab').exists()){
		$('#structureOrGenealogiestab').click(function(){
            // Don't open a second time
            if (structureOrGenealogyOpen)
                return;

            $.get("?command=edit_part&part=structureOrGenealogies&constellationid="+$('#constellationid').val()+"&version="+$('#version').val(), null, function (data) {
                structureOrGenealogyOpen = true;
                $('#structureOrGenealogies').html(data);

                turnOnEditDeleteButtons("structureOrGenealogies");
                
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
                        turnOnTooltips("structureOrGenealogy", structureOrGenealogyid);
                        makeEditable("structureOrGenealogy", structureOrGenealogyid);
                        structureOrGenealogyid = structureOrGenealogyid + 1;
                        return false;
                    });
                }
                
                turnOnTooltipsForTab("structureOrGenealogies");
            });
        });
    }

	var mandateid = 1;
    var mandateOpen = false;
	if ($('#mandatestab').exists()){
		$('#mandatestab').click(function(){
            // Don't open a second time
            if (mandateOpen)
                return;

            $.get("?command=edit_part&part=mandates&constellationid="+$('#constellationid').val()+"&version="+$('#version').val(), null, function (data) {
                mandateOpen = true;
                $('#mandates').html(data);

                turnOnEditDeleteButtons("mandates");
                
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
                        turnOnTooltips("mandate", mandateid);
                        makeEditable("mandate", mandateid);
                        mandateid = mandateid + 1;
                        return false;
                    });
                }
                
                turnOnTooltipsForTab("mandates");
            });
        });
    }

	var biogHistid = 1;
    var biogHistOpen = false;
	if ($('#biogHiststab').exists()){
		$('#biogHiststab').click(function(){
            // Don't open a second time
            if (biogHistOpen)
                return;

            $.get("?command=edit_part&part=biogHists&constellationid="+$('#constellationid').val()+"&version="+$('#version').val(), null, function (data) {
                biogHistOpen = true;
                $('#biogHists').html(data);

                turnOnEditDeleteButtons("biogHists");
                
                if ($('#next_biogHist_i').exists()) {
                    biogHistid = parseInt($('#next_biogHist_i').text());
                }
                console.log("Next biogHist ID: " + biogHistid);
                if ($('#btn_add_biogHist').exists()){
                    $('#btn_add_biogHist').click(function(){
                        somethingHasBeenEdited = true;
                        var text = $('#biogHist_template').clone();
                        var html = text.html().replace(/ZZ/g, biogHistid);
                        $('#add_biogHist_div').after(html);
                        turnOnButtons("biogHist", biogHistid);
                        turnOnTooltips("biogHist", biogHistid);
                        makeEditable("biogHist", biogHistid);
                        biogHistid = biogHistid + 1;
                        return false;
                    });
                }
                
                turnOnTooltipsForTab("biogHists");
            });
        });
    }

    turnOnTooltipsForTab();

});