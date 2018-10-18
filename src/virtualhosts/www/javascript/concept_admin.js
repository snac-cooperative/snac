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

// function saveConcept(event) {
//     event.preventDefault();
//     var $form = $(event.target);
//
//     if ($form.find("#resource-url").val() == "") {
//         if (!confirm('Are you sure you want to save without an HREF?')) {
//             return;
//         }
//     }
//     $('#notification-message').slideDown().html("<p>Saving Concept... Please wait.</p>");
//
//     // Remove leading and trailing whitespace
//     $form.find("input, textarea").each(function() {
//         $(this).val($.trim($(this).val()));
//     });
//
//     setDeletedConceptLanguageOperations($form);
//
//     $.post(snacUrl + "/save_resource", $form.serialize())
//         .done(function(data) {
//             createdConcept = data;
//             if (typeof(data.resource) === 'undefined')  {
//                 $('#error-message').slideDown();
//                 return false;
//             }
//             $('#notification-message').slideUp();
//             $('#success-message').slideDown();
//             setTimeout(function() {
//                 window.location = (snacUrl + "/vocab_administrator/resource/" + data.resource.id);
//             }, 1000);
//         })
//         .fail(function() {
//             $('#error-message').slideDown();
//         });
//     return false;
// }
//
//
// function setDeletedConceptLanguageOperations($form) {
//     // Set deleted new languages to null, set deleted existing languages to delete
//     var $deletedLanguages = $form.find("#resource-languages .component-deleted").has('input[id]');
//     var $ignoredLanguages = $form.find("#resource-languages .component-deleted.new-language");
//     setOperations($deletedLanguages, "delete");
//     setOperations($ignoredLanguages, "");
// }
//
// function cancelConcept() {
//     if (!confirm('Are you sure you want to cancel?')) {
//         return;
//     }
//
//     $('#notification-message').html("<p>Cancelling...</p>");
//     $('#notification-message').slideDown();
//     setTimeout(function() {
//         window.location.href = snacUrl + "/vocab_administrator";
//     }, 1000);
//     return false;
// }
//
// /**
//  * New Concept Language
//  * Copies the resource template DIV on the page and attaches it correctly to the DOM.
//  * Tracks language index using $('#language-template').data('languageCount')
//  *
//  */
// function newConceptLanguage(event) {
//     event.preventDefault();
//     var $newLanguage = $('#resource-language-template').find(".language").clone();
//     var data = $('#resource-language-template').data();
//     var newLanguageID = 'language_' + data.languageCount;
//     $newLanguage.attr('id', newLanguageID);
//     $newLanguage.find('.operation').val('insert');
//     $newLanguage.addClass('new-language');
//
//     //update input names with new data.languageCount
//     $newLanguage.find('input, select').attr('name', function(i, name) {
//         return name.replace('YY', data.languageCount);
//     });
//
//     console.log('Adding new resource language with id: ', newLanguageID);
//     $newLanguage.toggle();
//     // selects last to avoid conflict on multiple clones
//     $('.add-resource-language:last').before($newLanguage);
//     enableLanguageSelect($newLanguage);
//
//     data.languageCount++;
//     return $newLanguage;
// }
//
// /**
//  * Delete or Undo Language
//  *
//  * Toggles component-deleted class, and btn classes for delete and undo.
//  * Does not change operations.
//  *
//  */
// function deleteOrUndoLanguage(event) {
//     event.preventDefault();
//     var $btn = $(event.currentTarget);
//     $btn.toggleClass('btn-danger btn-warning');
//     $btn.find(':only-child').toggleClass('fa-minus-circle fa-undo');
//     var $language = $btn.closest('.language');
//     $language.toggleClass('alert-danger component-deleted');
// }
//
//
// function setOperations($elements, operation) {
//     $elements.find('.operation').each(function() {
//         $(this).val(operation);
//     });
// }
//
//
//
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
//         width: '100%',
//         minimumInputLength: 0,
//         allowClear: true,
//         theme: 'bootstrap',
//         placeholder: 'Select'
//     });
// }
//
// function enableLanguageSelect($language) {
//     enableVocabularySelect($language.find("select:first"), 'language_code');
//     enableVocabularySelect($language.find("select:last"), 'script_code');
// }
//
//
// function magicNewConceptLanguage(event) {
//     var $newLanguage = newConceptLanguage(event);
//     var defaultLanguage = new Option(defaults.language.term, defaults.language.id, false, true);
//     var defaultScript = new Option(defaults.script.term, defaults.script.id, false, true);
//     $newLanguage.find("select:first").append(defaultLanguage).trigger('change');
//     $newLanguage.find("select:last").append(defaultScript).trigger('change');
// }
//
// function selectHoldingRepository(event) {
//     event.preventDefault();
//     var name = event.target.innerHTML;
//     var id = event.target.href.split('/').pop();
//     var selectedRepo = new Option(name, id, false, true);
//     $(".resource-repo:last").append(selectedRepo).trigger('change');
//     $("#search_form").slideToggle();
//     $("#search-results-box").html("");
//     $("#searchbox").val("");
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


function deleteOrUndoLanguage(event) {
    event.preventDefault();
    var $btn = $(event.currentTarget);
    $btn.toggleClass('btn-danger btn-warning');
    $btn.find(':only-child').toggleClass('fa-minus-circle fa-undo');
    var $language = $btn.closest('.language');
    $language.toggleClass('alert-danger component-deleted');
    // $(event.target).closest('.term').toggleClass('alert-danger component-deleted');
    $(event.target).closest('.term').find('input[type=text]').toggleClass('alert-danger component-deleted');
}

jQuery.fn.visibilityToggle = function() {
    return this.css('visibility', function(i, visibility) {
        return (visibility == 'visible') ? 'hidden' : 'visible';
    });
};

// $('input[type=checkbox]:not(:checked)')

$('document').ready( function() {
    // markEditedFields($('#concept-form'))
    $('.term-edit').on('click', function(event) {
        console.log(event)
        $(event.target).closest('.term').toggleClass('well well-sm edited-field').find('input[type=text]').removeAttr('readonly')
        x = event
        // convertToInputField(event)
    })

    $('.select').each(function() {
        $(this).select2({
            minimumResultsForSearch: Infinity,
            allowClear: false,
            theme: 'bootstrap',
            width: ''
        });
    });

    $('#exampleModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget) // Button that triggered the modal
      var recipient = button.data('whatever') // Extract info from data-* attributes
      // If necessary, you could initiate an AJAX request here (and then do the updating in a callback).
      // Update the modal's content. We'll use jQuery here, but you could use a data binding library or other methods instead.
      var modal = $(this)
      console.log("hello")
      modal.find('.modal-title').text('New message to ' + recipient)
      modal.find('.modal-body input').val(recipient)
    })

});
