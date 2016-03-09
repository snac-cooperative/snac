var biogHistEditor = null;

// Has anything been edited on this page?
var somethingHasBeenEdited = false;

function updatePage() {
    $('.selectpicker').selectpicker();
    /*
        biogHistEditor = CodeMirror.fromTextArea(document.getElementById("biogHist"), {
              lineNumbers: true,
              lineWrapping: true,
              viewportMargin: Infinity,
              mode: {name: "xml"}
          });*/
}

function updateBiogHist() {
    return; // right now, not doing this
    
    if (biogHistEditor == null) {
        biogHistEditor = CodeMirror.fromTextArea(document.getElementById("biogHist"), {
              lineNumbers: true,
              lineWrapping: true,
              viewportMargin: Infinity,
              mode: {name: "xml"}
              });
    }
    biogHistEditor.setSize("100%", null);
    biogHistEditor.refresh();
}

function makeEditable(short, i) {
    // No editing if it's already in edit mode
    if ($("#" + short + "_operation_" + i).val() == "update")
        return false;
    
    // If it's deleted, then you better undelete it first
    if ($("#" + short + "_operation_" + i).val() == "delete")
        setDeleted(short, i);


    var idstr = "_" + i;
    $("input[id^='"+short+"_']").each(function() {
        var obj = $(this);
        if(obj.attr('id').indexOf(idstr) != -1 && obj.attr('id').indexOf("ZZ") == -1) {
            obj.removeAttr("readonly");
        }
    });
    $("textarea[id^='"+short+"_']").each(function() {
        var obj = $(this);
        if(obj.attr('id').indexOf(idstr) != -1 && obj.attr('id').indexOf("ZZ") == -1) {
            obj.removeAttr("readonly");
        }
    });
    var sawSelect = false;
    $("select[id^='"+short+"_']").each(function() {
        var obj = $(this);
        if(obj.attr('id').indexOf(idstr) != -1 && obj.attr('id').indexOf("ZZ") == -1) {
            sawSelect = true;
        }
    });
    
    if (!sawSelect) {
	    $("div[id^='select_"+short+"']").each(function() {
	        var cont = $(this);
	        if(cont.attr('id').indexOf(idstr) != -1 && cont.attr('id').indexOf("ZZ") == -1) {
	            var split = cont.attr('id').split("_");
	            var name = split[2];
	            var id = $("#"+short+"_"+name+"_id_"+i).val();
	            var term = $("#"+short+"_"+name+"_term_"+i).val();
	            var vocabtype = $("#"+short+"_"+name+"_vocabtype_"+i).val();
	            var minlength = $("#"+short+"_"+name+"_minlength_"+i).val();
		        
	            cont.html("<select id='"+short+"_"+name+"_id_"+i+"' name='"+short+"_"+name+"_id_"+i+"' class='form-control'>"+
	                    "<option></option>"+
	                    "<option value=\""+id+"\" selected>"+term+"</option>"+
	                    "</select>"+
                        "<input type=\"hidden\" id=\""+short+"_"+name+"_vocabtype_"+i+"\" " +
                        	"name=\""+short+"_"+name+"_vocabtype_"+i+"\" value=\""+vocabtype+"\"/>" +
                        "<input type=\"hidden\" id=\""+short+"_"+name+"_minlength_"+i+"\" " +
                        	"name=\""+short+"_"+name+"_minlength_"+i+"\" value=\""+minlength+"\"/>");
	            
	            vocab_select_replace($("#"+short+"_"+name+"_id_"+i), i, vocabtype, minlength);
	            
	        }
	    });
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


function makeUneditable(short, i) {

	// Make inputs read-only
    var idstr = "_" + i;
    $("input[id^='"+short+"_']").each(function() {
        var obj = $(this);
        if(obj.attr('id').indexOf(idstr) != -1 && obj.attr('id').indexOf("ZZ") == -1) {
            obj.attr("readonly", "true");
        }
    });
    // Make textareas read-only
    $("textarea[id^='"+short+"_']").each(function() {
        var obj = $(this);
        if(obj.attr('id').indexOf(idstr) != -1 && obj.attr('id').indexOf("ZZ") == -1) {
            obj.attr("readonly", "true");
        }
    });
    // Check for a select box
    var sawSelect = false;
    $("select[id^='"+short+"_']").each(function() {
        var obj = $(this);
        if(obj.attr('id').indexOf(idstr) != -1 && obj.attr('id').indexOf("ZZ") == -1) {
            sawSelect = true;
        }
    });
    // If a select box was seen, undo it
    if (sawSelect) {
	    $("div[id^='select_"+short+"']").each(function() {
	        var cont = $(this);
	        if(cont.attr('id').indexOf(idstr) != -1 && cont.attr('id').indexOf("ZZ") == -1) {
	            var split = cont.attr('id').split("_");
	            var name = split[2];
	            var id = $("#"+short+"_"+name+"_id_"+i).val();
	            var term = $("#"+short+"_"+name+"_id_"+i+ " option:selected").text();
	            var vocabtype = $("#"+short+"_"+name+"_vocabtype_"+i).val();
	            var minlength = $("#"+short+"_"+name+"_minlength_"+i).val();
	        
	            cont.html("<input type=\"hidden\" id=\""+short+"_"+name+"_id_"+i+"\" " +
                    	"name=\""+short+"_"+name+"_id_"+i+"\" value=\""+id+"\"/>" +
                        "<input type=\"hidden\" id=\""+short+"_"+name+"_term_"+i+"\" " +
                    	"name=\""+short+"_"+name+"_term_"+i+"\" value=\""+term+"\"/>" +
                        "<input type=\"hidden\" id=\""+short+"_"+name+"_vocabtype_"+i+"\" " +
                        	"name=\""+short+"_"+name+"_vocabtype_"+i+"\" value=\""+vocabtype+"\"/>" +
                        "<input type=\"hidden\" id=\""+short+"_"+name+"_minlength_"+i+"\" " +
                        	"name=\""+short+"_"+name+"_minlength_"+i+"\" value=\""+minlength+"\"/>" +
                        	"<p class=\"form-control-static\">"+term+"</p>");
	            
	        }
	    });
    }
    
    // Clear the operation flag
    $("#" + short + "_operation_" + i).val("");
    return false;
}

function makeSCMEditable(short, i, j) {
    // No editing if it's already in edit mode
    if ($("#" + short + "_operation_" + i).val() == "update")
        return false;

    var idstr = "_" + j + "_" + i;
    $("input[id^='"+short+"_']").each(function() {
        var obj = $(this);
        if(obj.attr('id').indexOf(idstr) != -1 && obj.attr('id').indexOf("ZZ") == -1) {
            obj.removeAttr("readonly");
        }
    });
    $("textarea[id^='"+short+"_']").each(function() {
        var obj = $(this);
        if(obj.attr('id').indexOf(idstr) != -1 && obj.attr('id').indexOf("ZZ") == -1) {
            obj.removeAttr("readonly");
        }
    });
    $("div[id^='select_"+short+"']").each(function() {
        var cont = $(this);
        if(cont.attr('id').indexOf(idstr) != -1 && cont.attr('id').indexOf("ZZ") == -1) {
            var split = cont.attr('id').split("_");
            var name = split[3];
            var id = $("#"+short+"_"+name+"_id"+idstr).val();
            var term = $("#"+short+"_"+name+"_term"+idstr).val();
            var vocabtype = $("#"+short+"_"+name+"_vocabtype"+idstr).val();
            var minlength = $("#"+short+"_"+name+"_minlength"+idstr).val();
            cont.html("<select id='"+short+"_"+name+"_id"+idstr+"' name='"+short+"_"+name+"_id"+idstr+"' class='form-control'>"+
                    "<option></option>"+
                    "<option value=\""+id+"\" selected>"+term+"</option>"+
                    "</select>");
            vocab_select_replace($("#"+short+"_"+name+"_id"+idstr), idstr, vocabtype, minlength);
        }
    });
    $("div[id^='selectsource_"+short+"']").each(function() {
        var cont = $(this);
        if(cont.attr('id').indexOf(idstr) != -1 && cont.attr('id').indexOf("ZZ") == -1) {
            var split = cont.attr('id').split("_");
            var name = split[3];
            var id = $("#"+short+"_"+name+"_id"+idstr).val();
            var term = $("#"+short+"_"+name+"_term"+idstr).val();
            cont.html("<select id='"+short+"_"+name+"_id"+idstr+"' name='"+short+"_"+name+"_id"+idstr+"' class='form-control'>"+
                    "<option></option>"+
                    "<option value=\""+id+"\" selected>"+term+"</option>"+
                    "</select>");
            scm_source_select_replace($("#"+short+"_"+name+"_id"+idstr), idstr);
        }
    });
    $("#" + short + "_operation_" + i).val("update");
    return false;
}

function setDeleted(short, i) {
    if ($("#" + short + "_operation_" + i).val() != "delete") {
    	// set deleted
    	$("#" + short + "_panel_" + i).removeClass("panel-default").addClass("alert-danger").addClass("deleted-component");
        $("#" + short + "_deletebutton_" + i).removeClass("list-group-item-danger").addClass("list-group-item-warning");
        $("#" + short + "_deletebutton_" + i).html("<span class=\"glyphicon glyphicon-remove-sign\"></span> Undo");
    	
        $("#" + short + "_operation_" + i).val("delete");
    	
    } else {
    	// set undelete
    	$("#" + short + "_panel_" + i).removeClass("alert-danger").addClass("panel-default").removeClass("deleted-component");
        $("#" + short + "_deletebutton_" + i).removeClass("list-group-item-warning").addClass("list-group-item-danger");
        $("#" + short + "_deletebutton_" + i).html("<span class=\"glyphicon glyphicon-trash\"></span> Trash");
        

        // If this thing was deleted but is supposed to be an update, then return it back to update status
        var sawSelect = false;
        $("select[id^='"+short+"_']").each(function() {
            var obj = $(this);
            if(obj.attr('id').indexOf("_" + i) != -1 && obj.attr('id').indexOf("ZZ") == -1) {
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

// Attach functions to each of the "+ Add New _______" buttons
$(document).ready(function() {

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
	        $('#gender_pane').append(html);
	        genderid = genderid + 1;
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
	        resourceRelationid = resourceRelationid + 1;
	        return false;
		});
	}
	
	var constellationRelationid = 1;
	if ($('#next_constellationRelation_i').exists()) {
	    constellationRelationid = parseInt($('#next_constellationRelation_i').text());
	}
	console.log("Next constellationRelation ID: " + constellationRelationid);
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
	        mandateid = mandateid + 1;
	        return false;
		});
	}
	
	
});

