<?php

/**
 * Workflow Class File
 *
 * Contains the main REST interface class that instantiates the REST UI
 *
 * License:

 * @author Tom Laudeman
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\client\workflow;

use \snac\client\util\ServerConnect as ServerConnect;

/*
  This is the Workflow class. It should be instantiated, then the run()
  method called to run the machine based on user input and the system's current state
  
  @author Tom Laudeman
*/
class Workflow {

    
    
    // Constructor
    // 
    // Requires the input to the server as an associative array
    // @param array $input Input to the server

    public function __construct($input) {
        // read the state table
    }

    /*
      Run Method

      Runs the server
    */
    public function run() {
        // run the state table
        return;
    }

}

