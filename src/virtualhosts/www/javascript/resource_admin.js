/**
 * Resource Admin Actions
 *
 * Contains code that handles the resource editing under vocab dashboard
 *
 * @author Joseph Glass
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

function saveResource() {
    $('#notification-message').slideDown().html("<p>Saving Resource... Please wait.</p>");

    // Remove leading and trailing whitespace
    $("input, textarea").each(function() {
        $(this).val($.trim($(this).val()));
    });

    // Ignore deleted new languages, set deleted existing languages to delete
    var $ignoredLanguages = $("#resource-languages .component-deleted").not(':has(input[id])');
    var $deletedLanguages = $("#resource-languages .component-deleted").has('input[id]');
    setOperations($ignoredLanguages, "");
    setOperations($deletedLanguages, "delete");

    $.post(snacUrl + "/save_resource", $('#resource-form').serialize())
        .done(function(data) {
            createdResource = data;
            $('#notification-message').slideUp();
            $('#success-message').slideDown();
            setTimeout(function() {
                window.location.replace(snacUrl + "/vocab_administrator/resources");
            }, 1500);
        })
        .fail(function() {
            $('#error-message').slideDown();
        });
    return false;
}

/**
 * New Resource Language
 * Copies the resource template DIV on the page and attaches it correctly to the DOM.
 * Tracks language index using $('#language-template').data('languageCount')
 * 
 */
function newResourceLanguage() {
    event.preventDefault();
    var $newLanguage = $('#language-template').clone();
    var langCount = $('#language-template').data('languageCount');
    var newLanguageID = 'language_' + langCount;
    $newLanguage.attr('id', newLanguageID);
    $newLanguage.find('.operation').val('insert');

    //update input names with new langCount
    $newLanguage.find('input, select').attr('name', function(i, name) {
        return name.replace('ZZ', langCount);
    });

    console.log('Adding new resource language with id: ', newLanguageID);
    $newLanguage.toggle();
    $('#add-resource-language').before($newLanguage);
    enableLanguageSelect($newLanguage);
    
    langCount++;
    $('#language-template').data("languageCount", (langCount));
}

/**
 * Delete or Undo Language
 * 
 * Toggles component-deleted class, and btn classes for delete and undo.
 * Does not change operations. 
 * 
 */
function deleteOrUndoLanguage() {
    event.preventDefault();
    var btn = event.currentTarget;
    $(btn).toggleClass('btn-danger btn-warning');
    $(btn).find(':only-child').toggleClass('fa-minus-circle fa-undo');
    var $language = $(btn).closest('.language');
    $language.toggleClass('alert-danger component-deleted');
}


function setOperations($elements, operation) {
    $elements.find('.operation').each(function() {
        $(this).val(operation);
    });
}

/**
 * Mark Edited Resource Fields
 * 
 * Adds edited-field class to altered inputs. Sets altered resource language to update. 
 * @param jqueryObject $resourceForm jQuery object to modify
 * 
 */
function markEditedResourceFields($resourceForm) {
    $resourceForm.find("input, select, textarea").on("change", function(e) {
        $(e.target).addClass('edited-field');

        if ($(e.target).closest('.language').length) {
            setOperations($(e.target).closest('.language'), "update");
        }
    });
}

function enableVocabularySelect(selectItem, type) {
    selectItem.select2({
        ajax: {
            url: snacUrl + "/vocabulary?type=" + type,
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term,
                    page: params.page
                };
            },
            processResults: function(data, page) {
                return {
                    results: data.results
                };
            },
            cache: true
        },
        width: '100%',
        minimumInputLength: 0,
        allowClear: true,
        theme: 'bootstrap',
        placeholder: 'Select'
    });
}

function enableLanguageSelect($language) {
    enableVocabularySelect($language.find("select:first"), 'language_code');
    enableVocabularySelect($language.find("select:last"), 'script_code');
}

$(document).ready(function() {
    if ($('#vocab_dashboard_cancel').exists()) {
        $('#vocab_dashboard_cancel').click(function() {

            if (!confirm('Are you sure you want to cancel?')) {
                return;
            }

            $('#notification-message').html("<p>Cancelling...</p>");
            $('#notification-message').slideDown();
            setTimeout(function() {
                window.location.href = snacUrl + "/vocab_administrator";
            }, 1500);
            return false;
        });
    }

    loadVocabSelectOptions($("#resource-type-select"), "document_type", "Resource Type");
    $('#resource-form').on('submit', saveResource);
    $("#new-resource-language-btn").on("click", newResourceLanguage);
    $('#resource-form').find(".language").each(function() {
        enableLanguageSelect($(this));
    });
});
