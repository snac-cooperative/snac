
function select_replace(selectItem, idMatch, type) {
        if(selectItem.attr('id').indexOf(idMatch) != -1
            && selectItem.attr('id').indexOf("ZZ") == -1) {
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
                width: '600px',
                minimumInputLength: 4,
                allowClear: false
                });
            }
}


$(document).ready(function() {

    // Use select2 to display the select dropdowns
    // rather than the HTML default
    $("select").each(function() {
        
        // Replace the subject selects
        select_replace($(this), "subject_", "subject");
        
        // Replace the function selects
        select_replace($(this), "function_", "function");
        
        // Replace the occupation selects
        select_replace($(this), "occupation_", "occupation");
    });
});

