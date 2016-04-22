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

?><!DOCTYPE html>
<html>
<!-- JQuery -->
<script src="//code.jquery.com/jquery-2.1.4.min.js"></script>

<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css" integrity="sha512-dTfge/zgoMYpP7QbHy4gWMEGsbsdZeCXz7irItjcC3sPUFtf0kuFbDz/ixG7ArTxmDjLXDmezHubeNikyKGVyQ==" crossorigin="anonymous">

<!-- Optional theme -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css" integrity="sha384-aUGj/X2zp5rLCbBxumKTCw2Z50WgIr1vs/PFN4praOTvYXWlVyh2UtNUU0KAUhAX" crossorigin="anonymous">

<!-- Latest compiled and minified JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js" integrity="sha512-K1qjQ+NcF2TYO/eI3M6v8EiNYZfA95pQumfvcVrTHtwQVDG+aHRqLi/ETn2uB+1JqwYqVG3LIvdm9lj6imS/pQ==" crossorigin="anonymous"></script>

<!-- Helper Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.7.5/js/bootstrap-select.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.7.5/css/bootstrap-select.min.css">

<!-- Tiny MCE text editor
<script src="//cdn.tinymce.com/4/tinymce.min.js"></script>
<script>
//tinymce.init({selector:'#biogHist', plugins:'code', min_height: 300});
</script>
-->

<!-- CodeMirror XML editor -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.9.0/codemirror.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.9.0/codemirror.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.9.0/mode/xml/xml.js"></script>

<!-- Select Upgrades -->
<link href="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.2-rc.1/css/select2.min.css" rel="stylesheet" />
<script src="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.2-rc.1/js/select2.min.js"></script>
<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">

<!-- SNAC Javascript -->
<script src="javascript/save_actions.js"></script>
<script src="javascript/select_loaders.js"></script>
<script src="javascript/scripts.js"></script>
<script src="javascript/relation_search.js"></script>

<script>
$.fn.modal.Constructor.prototype.enforceFocus = $.noop;
</script>

<style>
body {
    padding-top: 70px;
    padding-bottom: 30px;
}

.theme-dropdown .dropdown-menu {
    position: static;
    display: block;
    margin-bottom: 20px;
}

.snac > p > .btn {
    margin: 5px 0;
}

.snac .navbar .container {
    width: auto;
}
.tab-pane {

    border-left: 1px solid #ddd;
    border-right: 1px solid #ddd;
    border-bottom: 1px solid #ddd;
    border-radius: 0px 0px 5px 5px;
    padding: 10px;
}

.nav-tabs {
    margin-bottom: 0;
}
</style>
</head>
<body>
<h1>System Cleaning</h1>
<p>This script cleans out your session variables</p>
<pre>
<?php
echo "Old Session Variables\n";
print_r($_SESSION);

// Destroy the old session
session_destroy();
// Restart the session
session_name("SNACWebUI");
session_start();
$_SESSION = array();

echo "Session Destroyed.\nNew Session Variables:\n";

print_r($_SESSION);
?>
</pre>
<p class="text-center">
<a href="index.php" class="btn btn-primary">Continue to SNAC</a>
</p>
</body>
</html>
