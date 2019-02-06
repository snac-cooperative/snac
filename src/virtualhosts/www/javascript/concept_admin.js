/**
 * Concept Admin Actions
 *
 * Contains code that handles Concept and Concept Term creation and editing
 *
 * @author Joseph Glass
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2018 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

// function enableVocabularySelect(selectItem, type) {
//     selectItem.select2({
//         ajax: {
//             url: snacUrl + "/vocabulary?type=" + type,
//             dataType: 'json',
//             delay: 250,
//             data: function(params) {
//                 return {
//                     q: params.term,
//                     page: params.page
//                 };
//             },
//             processResults: function(data, page) {
//                 return {
//                     results: data.results
//                 };
//             },
//             cache: true
//         },
//         width: '25%',
//         minimumInputLength: 0,
//         allowClear: true,
//         theme: 'bootstrap',
//         placeholder: 'Select'
//     });
// }

/**
 * Mark Edited Fields
 *
 * Adds edited-field class to altered inputs. Sets altered resource language to update.
 * @param jqueryObject $resourceForm jQuery object to modify
 *
 */
function markEditedFields($resourceForm) {
    $resourceForm.find("input, select, textarea").on("change", function(e) {
        $(e.target).addClass('edited-field');

        if ($(e.target).closest('.language').length) {
            setOperations($(e.target).closest('.language'), "update");
        }
    });
}


function convertToInputField(event) {
    $element = $(event.target);
    $element.addClass('edited-field');
    var inputField = "<input "+ $element[0].outerHTML.slice(2, $element[0].outerHTML.indexOf('>') +1);
    $element.replaceWith(inputField);
}
// jQuery.fn.visibilityToggle = function() {
//     return this.css('visibility', function(i, visibility) {
//         return (visibility == 'visible') ? 'hidden' : 'visible';
//     });
// };


function deleteTerm(event) {
    if (!confirm('Are you sure you want to delete this term?')) { return; }

    var id = { "term-id" : $("#term-input").data("termId") };
    $.post(snacUrl + "/vocab_administrator/delete_concept_term", id)
        .done(function(data) {
            createdTerm = data;
            if (data.result !== "success")  {
                $('#error-message').slideDown();
                return false;
            }
            $('#notification-message').slideUp();
            $('#success-message').slideDown();
            $('#term-modal').modal('hide');
            setTimeout(function() {
                window.location.reload()
            }, 500);
        })
        .fail(function() {
            $('#error-message').slideDown();
        });

    console.log("deleting term: ", id);

}

function buildTermForm(event) {
    var $term = $(event.target).closest('.form-group').find('.term');
    console.log($term.data('termId'));
    console.log($term.data('isPreferred'));
    console.log($term.data('termValue'));

    // var title = $("#term-modal-label").text();
    $("#term-modal-label").text("Edit Term: " + $term.data('termValue'));

    $termInput = $("#term-input");
    $termInput.data("termId", $term.data('termId'));
    $termInput.data("isPreferred", $term.data('isPreferred'));
    $termInput.val($term.data('termValue'));
    var checkboxStatus = $term.data("isPreferred") === "t" ? "on" : "off";
    $("#is-preferred").bootstrapToggle(checkboxStatus);
    if ($term.data('termId')) {
        $('#term-delete-btn').attr("disabled", false);
    }
}

// clean up form on post or close
function resetTermForm() {
    $("#term-modal-label").text("Add Term");
    $termInput = $("#term-input");
    $termInput.removeData();
    $termInput.val("");
    $('#is-preferred').bootstrapToggle('off');
    $('#term-delete-btn').attr("disabled", true);
}




function saveTermForm() {
    //validate required fields
    if ($('#is-preferred:checked').length) {
        if (!confirm('Are you sure you want to set this as the sole preferred term for this concept?')) { return; }
    }

    var serialized = $("#term-form").serialize();

    // edit path
    if ($("#term-input").data("termId")) {
        serialized += "&term-id=" + $("#term-input").data("termId");
    }

    // new term path
    // Post form and reload page
    console.log(serialized);
    $.post(snacUrl + "/vocab_administrator/save_concept_term", serialized)
        .done(function(data) {
            createdTerm = data;
            if (data.result !== "success")  {
                $('#error-message').slideDown();
                return false;
            }
            $('#term-modal').modal('hide');
            $('#notification-message').slideUp();
            $('#success-message').slideDown();
            setTimeout(function() {
                window.location.reload()
            }, 500);
        })
        .fail(function() {
            $('#error-message').slideDown();
        });
    return false;
}


function deleteConceptRelationship() {
    if (!confirm( "Are you sure you want to delete this relationship?")) { return; }
    var conceptID = $("#concept-id").val();
    var narrowerID = "";
    var broaderID = "";
    var endpoint = "delete_broader_concepts";
    var $secondConcept = $(event.target.parentElement);
    var secondID = $secondConcept.data("conceptId");

    // broader/narrower/related





    // if broader, conceptID is narrower
    if ($secondConcept.hasClass('narrower_concept')) {
        narrowerID = secondID;
        broaderID = conceptID;
    }
    // if narrower, conceptID is narrower
    if ($secondConcept.hasClass('broader_concept')) {
        narrowerID = secondID;
        broaderID = conceptID;
    }

    var params = `?narrower_id=${narrowerID}&broader_id=${broaderID}`;
    // if related, related url, id1, id2
    if ($secondConcept.hasClass('related_concept')) {
        // id1, id2, delete related concept
        var relatedID = secondID;
        endpoint = "delete_related_concepts";
        var params = `?id1=${conceptID}&id2=${relatedID}`;    
    }


    // var id = { "term-id" : $("#term-input").data("termId") };
    $.post(snacUrl + "/vocab_administrator/" + endpoint + params)
        .done(function(data) {
            createdTerm = data;
            if (data.result !== "success")  {
                $('#error-message').slideDown();
                return false;
            }
            $('#notification-message').slideUp();
            $('#success-message').slideDown();
            $('#term-modal').modal('hide');
            setTimeout(function() {
                window.location.reload()
            }, 500);
        })
        .fail(function() {
            $('#error-message').slideDown();
        });

    console.log("deleting relationship with concept id ", secondID );

}


function searchResourceIMeanTerm() {
    if (!$("#concept-searchbox").val().trim().length) { return false; }

    $("#concept-results-box").html("<p style='text-align: center'>Loading...</p>");
    $.post(snacUrl+"/vocab_administrator/search_concepts", $("#concept-search-form").serialize(), function (data) {
    // $.post("http://localhost/~josephglass/snac/www/vocab_administrator/search_concepts?q=Flight&json=true", function(data) {

        var html = "";
        html += "<h4 class='text-left'>Search Results</h4><div class='list-group text-left' style='margin-bottom:0px'>";
        if (data.concepts.length) {
            var concepts = data.concepts;

            html += "<p class='search-info'>Showing " + concepts.length + " results.</p>";
            for (var i = 0; i < concepts.length; i++) {
                var conceptUrl = "<a href='"+snacUrl+ "/vocab_administrator/concepts/"+concepts[i].id+"'>" + concepts[i].value + "</a>";
                html += "<div class='list-group-item'><div class='row'>";
                html += "<div class='col-xs-1'><input type='radio' name='conceptChoice' data-concept-id='"+concepts[i].id+"'></div><div class='col-xs-10'>";
                html += "<h4 class='list-group-item-heading'>"+ conceptUrl + "</h4></div></div></div>";

            }
        } else {
            html += "<a href='#' class='list-group-item list-group-item-danger'>No results found.</a>";
        }
        $("#concept-results-box").html(html);
    });
}

function relateConcepts() {

}


function describeRelation() {
    "broader than"
    "narrower than"
    "related to"
}


$('document').ready( function() {
    $('.select').each(function() {
        $(this).select2({
            minimumResultsForSearch: Infinity,
            allowClear: false,
            theme: 'bootstrap',
            width: '25%'
        });
    });
    //
    // $("#term-relationship-type-select").select2({
    //     minimumResultsForSearch: Infinity,
    //     allowClear: false,
    //     theme: 'bootstrap',
    //     width: '25%',
    // });

    // $("#term-modal").modal()

    // $('#term-modal').on('show.bs.modal', function (event) {
    //   var button = $(event.relatedTarget) // Button that triggered the modal
    //   var recipient = button.data('whatever') // Extract info from data-* attributes
    //   // If necessary, you could initiate an AJAX request here (and then do the updating in a callback).
    //   // Update the modal's content. We'll use jQuery here, but you could use a data binding library or other methods instead.
    //   var modal = $(this)
    //
    //   var term = $(event.target).closest('term');
    //   console.log(term.data('id'));
    //   console.log(term.data('is-preferred'));
    //   console.log(term.data('value'));
    //   console.log("hello");
    //   modal.find('.modal-title').text('New message to ' + recipient);
    //   modal.find('.modal-body input').val(recipient);
    // })


    $('#term-modal').on('hide.bs.modal', function (event) {
            resetTermForm();
    });
});
