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
    // send a DELETE request to Server.
    // $.ajax({
    //    url: '/',
    //    type: 'DELETE',
    //    success: function(response) {
    //      //...
    //    }
    // });
    var id = $("#term-input").data("termId");
    console.log("deleting term: ", id);

}

function buildTermForm(event) {
    var term = $(event.target).closest('.form-group').find('.term');
    console.log(term.data('termId'));
    console.log(term.data('isPreferred'));
    console.log(term.data('termValue'));

    // var title = $("#term-modal-label").text();
    $("#term-modal-label").text("Edit Term: " + term.data('termValue'));

    $termInput = $("#term-input");
    $termInput.data("termId", term.data('termId'));
    $termInput.data("isPreferred", term.data('isPreferred'));
    $termInput.val(term.data('termValue'));
    var checkboxStatus = term.data("isPreferred") === "t" ? "on" : "off";
    $("#is-preferred").bootstrapToggle(checkboxStatus);
}

// clean up form on post or close
function resetTermForm() {
    $("#term-modal-label").text("Add Term");
    $termInput = $("#term-input");
    $termInput.removeData();
    $termInput.val('');
    $('#is-preferred').bootstrapToggle('off');
}




function postTermForm() {
    //validate required fields
    if ($('#is-preferred:checked').length) {
        if (!confirm('Are you sure you want to set this as the sole preferred term for this concept?')) { return; }
    }


// Post form and reload page
    var serialized = $("#term-form").serialize() + "&term-id=" + $("#term-input").data("termId");
    console.log(serialized);
    // $.post(snacUrl + "/", serialized)
    //     .done(function(data) {
    //         createdResource = data;
    //         if (typeof(data.resource) === 'undefined')  {
    //             $('#error-message').slideDown();
    //             return false;
    //         }
    //         $('#notification-message').slideUp();
    //         $('#success-message').slideDown();
    //         setTimeout(function() {
    //             window.location.reload()
    //         }, 1000);
    //     })
    //     .fail(function() {
    //         $('#error-message').slideDown();
    //     });
    return false;
}

$('document').ready( function() {
    // $('.select').each(function() {
    //     $(this).select2({
    //         minimumResultsForSearch: Infinity,
    //         allowClear: false,
    //         theme: 'bootstrap',
    //         width: '25%'
    //     });
    // });
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
