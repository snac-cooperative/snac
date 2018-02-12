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


$(document).ready(function() {
    // Use select2 to display the select dropdowns
    // rather than the HTML default
    $('select').each(function() {
        $(this).select2({
            minimumResultsForSearch: Infinity,
            allowClear: false,
            theme: 'bootstrap'
        });
    });

    var $resourceForm = $('#resource-form');


    $('#save-resource-btn').click(function(){
        $('#notification-message').html("<p>Saving Resource... Please wait.</p>");
        $('#notification-message').slideDown();

        $("input, textarea").each(function() {
            $(this).val($.trim($(this).val()));
        });

        $.post(snacUrl + "/save_resource", $resourceForm.serialize())
            .done(function(data) {
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
    });
    
    if($('#vocab_dashboard_cancel').exists()) {
        $('#vocab_dashboard_cancel').click(function(){

            if (!confirm('Are you sure you want to cancel?')) {
                // Don't want to cancel, so exit!
                return;
            }

            $('#notification-message').html("<p>Cancelling...</p>");
            $('#notification-message').slideDown();
            setTimeout(function(){

                window.location.href = snacUrl+"/vocab_administrator";
            }, 1500);
            return false;
        });
    }

    loadVocabSelectOptions($('#resource-type-select'), "document_type");
    // loadVocabSelectOptions($('#select_language_code_0'), "language_code")
    // loadVocabSelectOptions($('#select_language_script_0'), "script_code")  
});
