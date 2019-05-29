/**
 * Resource Admin Actions
 *
 * Contains code that handles Resource creation and editing
 *
 * @author Joseph Glass
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

function saveResource(event) {
    event.preventDefault();
    var $form = $(event.target);

    if ($form.find("#resource-url").val() == "") {
        if (!confirm('Are you sure you want to save without an HREF?')) {
            return;
        }
    }
    $('#notification-message').slideDown().html("<p>Saving Resource... Please wait.</p>");

    // Remove leading and trailing whitespace
    $form.find("input, textarea").each(function() {
        $(this).val($.trim($(this).val()));
    });

    setDeletedResourceLanguageOperations($form);

    $.post(snacUrl + "/save_resource", $form.serialize())
        .done(function(data) {
            createdResource = data;
            if (typeof(data.resource) === 'undefined')  {
                $('#error-message').slideDown();
                return false;
            }
            $('#notification-message').slideUp();
            $('#success-message').slideDown();
            setTimeout(function() {
                window.location = (snacUrl + "/vocab_administrator/resource/" + data.resource.id);
            }, 1000);
        })
        .fail(function() {v
            $('#error-message').slideDown();
        });
    return false;
}


function setDeletedResourceLanguageOperations($form) {
    // Set deleted new languages to null, set deleted existing languages to delete
    var $deletedLanguages = $form.find("#resource-languages .component-deleted").has('input[id]');
    var $ignoredLanguages = $form.find("#resource-languages .component-deleted.new-language");
    setOperations($deletedLanguages, "delete");
    setOperations($ignoredLanguages, "");
}

function cancelResource() {
    if (!confirm('Are you sure you want to cancel?')) {
        return;
    }

    $('#notification-message').html("<p>Cancelling...</p>");
    $('#notification-message').slideDown();
    setTimeout(function() {
        window.location.href = snacUrl + "/vocab_administrator";
    }, 1000);
    return false;
}

/**
 * New Resource Language
 * Copies the resource template DIV on the page and attaches it correctly to the DOM.
 * Tracks language index using $('#language-template').data('languageCount')
 *
 */
function newResourceLanguage(event) {
    event.preventDefault();
    var $newLanguage = $('#resource-language-template').find(".language").clone();
    var data = $('#resource-language-template').data();
    var newLanguageID = 'language_' + data.languageCount;
    $newLanguage.attr('id', newLanguageID);
    $newLanguage.find('.operation').val('insert');
    $newLanguage.addClass('new-language');

    //update input names with new data.languageCount
    $newLanguage.find('input, select').attr('name', function(i, name) {
        return name.replace('YY', data.languageCount);
    });

    console.log('Adding new resource language with id: ', newLanguageID);
    $newLanguage.toggle();
    // selects last to avoid conflict on multiple clones
    $('.add-resource-language:last').before($newLanguage);
    enableLanguageSelect($newLanguage);

    data.languageCount++;
    return $newLanguage;
}

/**
 * Delete or Undo Language
 *
 * Toggles component-deleted class, and btn classes for delete and undo.
 * Does not change operations.
 *
 */
function deleteOrUndoLanguage(event) {
    event.preventDefault();
    var $btn = $(event.currentTarget);
    $btn.toggleClass('btn-danger btn-warning');
    $btn.find(':only-child').toggleClass('fa-minus-circle fa-undo');
    var $language = $btn.closest('.language');
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


function magicNewResourceLanguage(event) {
    var $newLanguage = newResourceLanguage(event);
    var defaultLanguage = new Option(defaults.language.term, defaults.language.id, false, true);
    var defaultScript = new Option(defaults.script.term, defaults.script.id, false, true);
    $newLanguage.find("select:first").append(defaultLanguage).trigger('change');
    $newLanguage.find("select:last").append(defaultScript).trigger('change');
}

function selectHoldingRepository(event) {
    event.preventDefault();
    var name = event.target.innerHTML;
    var id = event.target.href.split('/').pop();
    var selectedRepo = new Option(name, id, false, true);
    $(".resource-repo:last").append(selectedRepo).trigger('change');
    $("#search_form").slideToggle();
    $("#search-results-box").html("");
    $("#searchbox").val("");
}
