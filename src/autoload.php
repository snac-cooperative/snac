<?php
// SNAC Autoload function
function snac_autoload ($pClassName) {
    include("" . str_replace("\\", "/", $pClassName) . ".php");
}
spl_autoload_register("snac_autoload");
