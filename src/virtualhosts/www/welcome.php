<?php
/**
 * Session destruction and Script reload page
 *
 * This file removes and refreshes the session information, including destroying the user variables.  It
 * also loads all the scripts used in the edit page, so it provides a way to shift+click reload the scripts
 * used in editing.
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

/**
 * Actual script below
 */
?><!DOCTYPE html>
<html>
<title>SNAC Prototype</title>
<!-- JQuery -->
<script src="//code.jquery.com/jquery-2.1.4.min.js"></script>

<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css" integrity="sha512-dTfge/zgoMYpP7QbHy4gWMEGsbsdZeCXz7irItjcC3sPUFtf0kuFbDz/ixG7ArTxmDjLXDmezHubeNikyKGVyQ==" crossorigin="anonymous">

<!-- Optional theme -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css" integrity="sha384-aUGj/X2zp5rLCbBxumKTCw2Z50WgIr1vs/PFN4praOTvYXWlVyh2UtNUU0KAUhAX" crossorigin="anonymous">

<!-- Latest compiled and minified JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js" integrity="sha512-K1qjQ+NcF2TYO/eI3M6v8EiNYZfA95pQumfvcVrTHtwQVDG+aHRqLi/ETn2uB+1JqwYqVG3LIvdm9lj6imS/pQ==" crossorigin="anonymous"></script>

<!-- CodeMirror XML editor -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.9.0/codemirror.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.9.0/codemirror.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.9.0/mode/xml/xml.js"></script>

<!-- Select Upgrades -->
<link href="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.2-rc.1/css/select2.min.css" rel="stylesheet" />
<script src="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.2-rc.1/js/select2.min.js"></script>
<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">

<script>
$.fn.modal.Constructor.prototype.enforceFocus = $.noop;
</script>
</head>
<?php

/**
 * Session management operations
 */
    // Destroy the old session
    session_destroy();
    // Restart the session
    session_name("SNACWebUI");
    session_start();
    $_SESSION = array();
?>
<script>

    var scripts = [
        ];

    function updateProgress(percent, statusText) {
        $('#progress-bar').css("width", percent+"%");
        $('#progress-text').text(percent+"%");
        $('#status').text(statusText);

    }

    function somethingWrong(reason) {

        $('#progress-bar').addClass("progress-bar-danger");
        $('#status').text("Failure: " + reason + "could not load. Please refresh.");

    }

    function openSNACButton() {
        setTimeout(function(){
            updateProgress(100, "Done");
            $('#continue').css("display", "");
        }, 5000);

    }

    function loadScripts() {
        setTimeout(function(){
            var requests = [];

            for (var i = 0; i < scripts.length; i++) {
                requests.push($.ajax({
                    url : scripts[i],
                        //cache : false,
                        dataType : "text",
                    complete: function(xhr, success) {
                        if (success != "success")
                            somethingWrong(scripts[i]);
                    }
                }));
            }

            ($.when.apply($,requests)).then(function() {
                updateProgress(75, "Preparing SNAC...");
                openSNACButton();
            }, function() {
                somethingWrong("Scripts");
            });

        }, 5000);

    }
    /**
     * Only load this script once the document is fully loaded
     */
    $(document).ready(function() {

        setTimeout(function(){
            updateProgress(75, "Preparing SNAC...");
            openSNACButton();
        }, 5000);


    });
</script>

<body role="document">
    <div class="container snac" role="main">
        <h1>Welcome to the SNAC Prototypical Prototype</h1>
        <p>This welcome page serves to ensure that you have the most recent version of SNAC and that your sessions are prepared appropriately.</p>
        <div class="well well-lg">
            <div class="progress">
                <div class="progress-bar progress-bar-striped active" id="progress-bar"
                        role="progressbar" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"
                        style="width: 30%">
                    <span class="sr-only"><span id="progress-text">30%</span> Complete</span>
                </div>
            </div>
            <div class="row text-center" id="status">
                Cleaning out old sessions...
            </div>
        </div>
        <div id="continue" style="display:none">
            <p class="text-center">
                <a href="index.php" class="btn btn-primary">Continue to SNAC</a>
            </p>
        </div>
    </div>
</body>
</html>
