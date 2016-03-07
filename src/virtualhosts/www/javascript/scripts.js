var biogHistEditor = null;
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
                    "</select>");
            vocab_select_replace($("#"+short+"_"+name+"_id_"+i), i, vocabtype, minlength);
        }
    });
    $("#" + short + "_operation_" + i).val("update");
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
    $("#" + short + "_operation_" + i).val("update");
    return false;
}

function setDeleted(short, i) {
    return false;
}
