/**
 * Upload and Parse Actions
 *
 * Contains code that handles what happens in the GUI when
 * the user uploads files to SNAC
 *
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2020 the Rector and Visitors of the University of Virginia
 */

$(document).ready( function() {

    // When the Validate EAD button is pushed, the form is submitted
    // This provides an AJAX call that expects JSON as a return
    // It displays and fills the well of progress
	$("#upload_form").submit(function(){
		var formData = new FormData($(this)[0]);
		$("#submit").prop('disabled', true).addClass('disabled');
		$("#parse").prop('disabled', true).addClass('disabled');
		$("#progress-div").css('visibility', 'visible');
		$('.progress-bar').css('width', '50%').attr('aria-valuenow', '10');
		$('.progress-text').text("Uploading and Validating EAD");
		$('#errors').text("");
		$.ajax({
			url:$(this).attr("action"),
			type: 'POST',
			data: formData,
			async: false,
			success: function (data) {
				if (data.result == "success") {
					// Validation is complete, now parse
					$('.progress-bar').css('width', '100%').attr('aria-valuenow', '50');
					$('.progress-text').text("Validation Complete, No Errors Detected");
				} else { // validation or other errors
					$('.progress-text').text("Validation Errors");
					if (typeof data.errors != "undefined") {
						data.errors.forEach(function(error) {
							$('#errors').append("<p><b>"+error.filename+":"+error.line+": </b> "+error.message);
						});
					} else {
						$('#errors').append("<p>"+data.error.message+"</p>");
					}
				}
                $("#submit").removeAttr('disabled').removeClass('disabled');
                $("#parse").removeAttr('disabled').removeClass('disabled');
			},
			cache: false,
			contentType: false,
			processData: false
		});
		return false;
	});
    

    // When the Parse EAD to TSV button is pushed, this makes an ajax call using XHR
    // This provides an AJAX call that expects either a zip or JSON as return.
    // It displays and fills the well of progress, then also auto-loads the zip for download.
    var parseEAD = function () {
		var formData = new FormData($("#upload_form")[0]);
		$("#submit").prop('disabled', true).addClass('disabled');
		$("#parse").prop('disabled', true).addClass('disabled');
		$("#progress-div").css('visibility', 'visible');
		$('.progress-bar').css('width', '50%').attr('aria-valuenow', '10');
		$('.progress-text').text("Uploading and Parsing EAD");
		$('#errors').text("");
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'parse_ead' , true);
        xhr.responseType = 'arraybuffer';
        xhr.onload = function () {
            if (this.status === 200) {
                $('.progress-bar').css('width', '100%').attr('aria-valuenow', '100');
                $('.progress-text').text("Parsing Complete");
                // this will be zip file
                var filename = "";
                var disposition = xhr.getResponseHeader('Content-Disposition');
                if (disposition && disposition.indexOf('attachment') !== -1) {
                    var filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                    var matches = filenameRegex.exec(disposition);
                    if (matches != null && matches[1]) filename = matches[1].replace(/['"]/g, '');
                }

                var type = xhr.getResponseHeader('Content-Type');

                var blob;
                if (typeof File === 'function') {
                    try {
                        blob = new File([this.response], filename, { type: type });
                    } catch (e) { /* Edge */ }
                }
                if (typeof blob === 'undefined') {
                    blob = new Blob([this.response], { type: type });
                }
                if (typeof window.navigator.msSaveBlob !== 'undefined') {
                    // IE workaround for "HTML7007: One or more blob URLs were revoked by closing the blob for which they were created. These URLs will no longer resolve as the data backing the URL has been freed."
                    window.navigator.msSaveBlob(blob, filename);
                } else {
                    var URL = window.URL || window.webkitURL;
                    var downloadUrl = URL.createObjectURL(blob);

                    if (filename) {
                        // use HTML5 a[download] attribute to specify filename
                        var a = document.createElement("a");
                        // safari doesn't support this yet
                        if (typeof a.download === 'undefined') {
                            window.location = downloadUrl;
                        } else {
                            a.href = downloadUrl;
                            a.download = filename;
                            document.body.appendChild(a);
                            a.click();
                        }
                    } else {
                        window.location = downloadUrl;
                    }
                    setTimeout(function () { URL.revokeObjectURL(downloadUrl); }, 100); // cleanup
                }

            } else {
                $('.progress-text').text("Parsing Errors");
                if (typeof data.errors != "undefined") {
                    // Actual well-formed validation errors
                    data.errors.forEach(function(error) {
                            $('#errors').append("<p><b>"+error.filename+":"+error.line+": </b> "+error.message);
                            });
                } else {
                    // Likely SAXON errors through Exception
                    $('#errors').append("<p>"+data.error.message+"</p>");
                }
            }
            $("#submit").removeAttr('disabled').removeClass('disabled');
            $("#parse").removeAttr('disabled').removeClass('disabled');
        };
        xhr.send(formData);
    };


    // Attach the function to the button
    $("#parse").on("click", function() {
        parseEAD();
        return false;
    });

});



