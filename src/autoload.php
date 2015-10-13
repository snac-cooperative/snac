<?php
/**
 * Autoload Script to pre-load classes into php
 *
 * @author Robbie Hott
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */


/**
 * Autoload function, used by php for loading classes dynamically
 * 
 * @param string $pClassName class name to load
 */
function snac_autoload($pClassName) {

    include ("" . str_replace("\\", "/", $pClassName) . ".php");
}
spl_autoload_register("snac_autoload");
