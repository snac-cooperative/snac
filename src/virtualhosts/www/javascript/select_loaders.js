
function vocab_select_replace(selectItem, idMatch, type, minLength) {
    if (minLength === undefined) {
        minLength = 4;
    }

        if(selectItem.attr('id').endsWith(idMatch)
            && !selectItem.attr('id').endsWith("ZZ")) {
                selectItem.select2({
                    ajax: {
                        url: "?command=vocabulary&type="+type,
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return {
                                q: params.term,
                                page: params.page
                            };
                        },
                        processResults: function (data, page) {
                            return { results: data.results };
                        },
                        cache: true
                    },
                    width: '100%',
                    minimumInputLength: minLength,
                    allowClear: true,
                    theme: 'classic',
                    placeholder: 'Select'
                });
            }
}


function scm_source_select_replace(selectItem, idMatch) {
        if(selectItem.attr('id').indexOf(idMatch) != -1
            && selectItem.attr('id').indexOf("ZZ") == -1) {
        		var icid = $("#constellationid").val();
        		var icversion = $("#version").val();
                selectItem.select2({
                    ajax: {
                        url: "?command=vocabulary&type=ic_sources&id="+icid+"&version="+icversion,
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return {
                                q: params.term,
                                page: params.page
                            };
                        },
                        processResults: function (data, page) {
                            return { results: data.results };
                        },
                        cache: true
                    },
                    width: '100%',
                    minimumInputLength: 0,
                    allowClear: true,
                    theme: 'classic',
                    placeholder: 'Select'
                });
            }
}


$(document).ready(function() {

    // Use select2 to display the select dropdowns
    // rather than the HTML default
    $("select").each(function() {

        // Replace the subject selects
        vocab_select_replace($(this), "language_language_", "language_code", 1);

        // Replace the subject selects
        vocab_select_replace($(this), "language_script_", "script_code", 1);

        // Replace the subject selects
        vocab_select_replace($(this), "subject_", "subject", 4);

        // Replace the function selects
        vocab_select_replace($(this), "function_", "function", 4);

        // Replace the occupation selects
        vocab_select_replace($(this), "occupation_", "occupation", 4);

        // Replace the entityType select
        vocab_select_replace($(this), "entityType", "entity_type", 0);
    });
});
