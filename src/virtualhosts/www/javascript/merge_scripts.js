/**
 * SNAC Merge Scripts
 *
 * Scripts used in merging and diffing identity constellations in the UI
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

jQuery.fn.exists = function(){return this.length>0;}

var pieceCache = new Array();

// Scripts to run when the page finishes loading...
$(document).ready(function() {
    console.log(pieceCache);

    $("#preview_button").click(function() {
        var form = $("#merged_identity");
        // empty out the form
        form.html("");

        // for each "both" pane, copy it into the form and then submit the form!
        $(".content-both").each(function() {
            var copy = $(this).html();
            form.append(copy);
        });

        form.attr('action', '?command=preview').attr('method', 'post').attr('target', '_blank');
        form.submit();

        return false;
    });


    $("#merge_button").click(function() {
        var form = $("#merged_identity");
        // empty out the form
        form.html("");

        // for each "both" pane, copy it into the form and then submit the form!
        $(".content-both").each(function() {
            var copy = $(this).html();
            form.append(copy);
        });

        // Put the other constellation data into the form
        var copy = $("#constellation_data").html();
        form.append(copy);

        form.attr('action', '?command=merge').attr('method', 'post').attr('target', '_self');
        form.submit();

        return false;
    });

    pieceCache.forEach(function(piece, i) {
        $("#icon_" + i).popover({
                title: piece.title,
                content : $(piece.identifier).html(),
                html: true,
                trigger: 'hover',
                container: 'body',
                placement: 'bottom'
        });

        $("#icon_" + i).on("click", function() {
            $(".data-component").each(function() {
                $(this).removeClass("data-component-selected").removeClass("disabled");
                $(this).popover('enable');
            });
            $(".preview").each(function() {
                $(this).html("");
            })
            $(".move-button-div").each(function() {
                $(this).addClass("move-button-div-disabled");
                $(this).find(".move-button").off("click");
                $(this).find(".split-button").off("click");
            })
            var obj = $(this);
            $(this).popover('disable');
            obj.addClass("data-component-selected").addClass("disabled");

            obj.closest(".diff-content-panel").find(".preview").html($("#data_" + i).html());
            obj.closest(".diff-content-panel").find(".move-button-div").removeClass("move-button-div-disabled");
            obj.closest(".diff-content-panel").find(".move-button").on("click", function() {
                var both = $("#icon_" + i).closest(".tab-pane").find(".merge-panel").find(".data-components");
                var copy = $("#icon_" + i).detach();
                copy.appendTo(both);


                $(".data-component").each(function() {
                    $(this).removeClass("data-component-selected").removeClass("disabled");
                    $(this).popover('enable');
                });
                $(".preview").each(function() {
                    $(this).html("");
                })
                $(".move-button-div").each(function() {
                    $(this).addClass("move-button-div-disabled");
                })

                $(this).closest(".diff-content-panel").find(".move-button").off("click");

            });

            obj.closest(".diff-content-panel").find(".split-button").on("click", function() {
                var innerObj = $(this);
                var move = null;
                if (innerObj.hasClass("split-button-left"))
                    move = $("#icon_" + i).closest(".tab-pane").find(".content-a").find(".data-components");
                else
                    move = $("#icon_" + i).closest(".tab-pane").find(".content-b").find(".data-components");

                if (move != null) {
                    var copy = $("#icon_" + i).detach();
                    copy.appendTo(move);

                    $(".data-component").each(function() {
                        $(this).removeClass("data-component-selected").removeClass("disabled");
                        $(this).popover('enable');
                    });
                    $(".preview").each(function() {
                        $(this).html("");
                    })
                    $(".move-button-div").each(function() {
                        $(this).addClass("move-button-div-disabled");
                    });

                    $(this).closest(".diff-content-panel").find(".split-button").off("click");

                }

            });
        });
    });

    $(".move-all-button").each(function() {
        var button = $(this);
        button.on("click", function() {
            var button = $(this);
            button.closest(".diff-content-panel").find(".data-component").each(function() {
                var both = $(this).closest(".tab-pane").find(".merge-panel").find(".data-components");
                var copy = $(this).detach();
                copy.appendTo(both);
            });
            
            $(".data-component").each(function() {
                $(this).removeClass("data-component-selected").removeClass("disabled");
                $(this).popover('enable');
            });

            $(".preview").each(function() {
                $(this).html("");
            })
            $(".move-button-div").each(function() {
                $(this).addClass("move-button-div-disabled");
            })

            $(this).closest(".diff-content-panel").find(".move-button").off("click");
        })
    });
});
