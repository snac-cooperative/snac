     function setRelationSearchPosition(start) {
         $('#relation-search-start').val(start);
     }

     function searchAndUpdate() {
         if ($("#relation-searchbox").val() == "" || $("#relation-searchbox").val().length < 2) {
             $("#relation-results-box").html("");
         } else {
             $.post("?command=quicksearch", $("#relation_search_form").serialize(), function (data) {
                 //var previewWindow = window.open("", "Preview");
                 //previewWindow.document.write(data);

                 var html = "";
                 html += "<h4 class='text-left'>Search Results</h4><div class='list-group text-left' style='margin-bottom:0px'>";
                 if (data.results.length > 0) {
                     for (var key in data.results) {
                    	 //html += "<div class='input-group'><span class='input-group-addon'><input type='radio'></span><p class='form-static'>Blah</p><span class='input-group-button'><button class='btn btn-default' type='button'>View</button></span></div>";
                         html += "<div class='list-group-item'><div class='row'>";
                         html += "<div class='col-xs-1'><input type='radio' name='relationChoice' id='relationChoice' value='"+data.results[key].id+"'></div>";
                         html += "<div class='col-xs-10'><h4 class='list-group-item-heading'>"+data.results[key].nameEntries[0].original+"</h4>";
                         html += "<p class='list-group-item-text'>"+data.results[key].ark+" <a class='label label-info' target='_blank' href='?command=view&constellationid="+data.results[key].id+"'>View</a></p></div>";
                         html += "<input type='hidden' id='relationChoice_nameEntry_"+data.results[key].id+"' value='"+data.results[key].nameEntries[0].original.replace("'", "&#39;")+"'/>";
                         var arkID = "";
                         if (data.results[key].ark != null)
                             arkID = data.results[key].ark;
                         html += "<input type='hidden' id='relationChoice_arkID_"+data.results[key].id+"' value='"+arkID+"'/>";
                         html += "<input type='hidden' id='relationChoice_entityType_"+data.results[key].id+"' value='"+data.results[key].entityType.id+"'/>";
                         html += "</div></div>";
                     }
                 } else {
                     html += "<a href='#' class='list-group-item list-group-item-danger'>No results found.</a>";
                 }
                 html += "</div>";

                 // Have pagination (total number of pages) and page (current page number) in data
                 // ... use them to help stepping through the search for multiple pages.

                 if (data.results.length > 0 && data.results.length < data.total) {
                     var start = $('#relation-search-start').val();
                     var count = $('#relation-search-count').val();
                     var prev = (data.page - 1) * count;
                     var next = (data.page + 1) * count;
                     html += "<nav><ul class='pagination'>";
                     var disabled = "";
                     var goScript = " onClick='setRelationSearchPosition("+prev+");searchAndUpdate();'";
                     if (data.page == 0) {
                         disabled = " class='disabled'";
                         goScript = "";
                     }
                     html += "<li"+disabled+"><a href='#' aria-label='Previous'"+goScript+"><span aria-hidden='true'>&laquo;</span></a></li>";
                     for (var i = 0; i < data.pagination; i++) {
                         var active = '';
                         var goScript = " onClick='setRelationSearchPosition("+(i * count)+");searchAndUpdate();'";
                         if (i == data.page) {
                             active = " class='active'";
                             goScript = "";
                         }
                         html += "<li"+active+"><a href='#'"+goScript+">"+(i+1)+"</a></li>";
                     }
                     disabled = "";
                     goScript = " onClick='setRelationSearchPosition("+next+");searchAndUpdate();'";
                     if (data.page == data.pagination - 1) {
                         disabled = " class='disabled'";
                         goScript = "";
                     }
                     html += "<li"+disabled+"><a href='#' aria-label='Next'"+goScript+"><span aria-hidden='true'>&raquo;</span></a></li>";
                     html += "</ul></nav>";
                 }
                 $("#relation-results-box").html(html);
             });
         }
     }


     /**
      * Only load this script once the document is fully loaded
      */

     $(document).ready(function() {
    	 /**
        $(function() {
            $( "#searchbox" ).autocomplete({
                source: "?command=search",
                minLength: 2,
                select: function( event, ui ) {
                }
            });
        });**/



        var timeoutID = null;


        $('#relation-searchbox').keyup(function() {
          clearTimeout(timeoutID);
          var $target = $(this);
          timeoutID = setTimeout(function() { setRelationSearchPosition(0); searchAndUpdate(); }, 500);
        });
     });
