<?php

/**
 * Shell Stage Class File
 *
 * Abstract class file for IR Stages needing to run shell commands
 *
 * License:
 *
 *
 * @author Robbie Hott
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */
namespace snac\server\identityReconciliation\stages\helpers;


/**
 * Shell Abstract Class
 *
 * Abstract class that allows a subclass to easily call and report values from
 * a shell script.  This makes it easy for a stage writer to use another
 * language or technology to create the stage.  Once written, a future stage
 * may inherit this class and define the shell script to be called.  Values
 * will be properly passed to the shell script defined by the $script_name
 * field.
 *
 * The shell script output must be of the form:
 * ```
 * ##.### identity string
 * ##.## identity string
 * ...
 * ```
 *
 * @author Robbie Hott
 */
abstract class Shell implements Stage {

    /**
     * @var string Unique Name of this stage
     *
     * Should be overwritten with the proper name.
     */
    private $name = "Abstract Shell Stage";

    /**
     * @var string Shell script to run.
     *
     * This is the script path and name, including parameters.  It should be of
     * the form:
     *
     * `/path/to/script [options] STRING`
     *
     * Where `STRING` is a literal, which will be replaced by the string passed
     * to the run function below.
     */
    protected $scriptName;


    /**
     * Combining function
     *
     * This function defines which parts of the identity object should be used
     * as command line argument to the shell script. It takes an identity
     * object, comines the interested parts of the identity together, then
     * returns the resulting string.
     *
     * @param \identity $identity The identity to parse.
     * @return string The combined string that will be sent to the shell script.
     */
    protected abstract function combineString($identity);

    /**
     * Run function
     *
     * Runs the function defined in the $script_name field, replacing `STRING`
     * with the string to be considered.  Takes the return value from the
     * defined output method and returns that to the caller. The string will be
     * escaped and wrapped with single quotes.
     *
     * @param \identity $search The identity to be evaluated.
     * @param \identity[] $list A list of identities to evaluate against.  This
     * may be null.
     * @return array An array of matches and strengths,
     * `{"id":identity, "strength":float}`.
     * the stage's definition
     *
     */
    public function run($search, $list) {

        // Assume list is null.  We're not using it now

        // Result list
        $results = array();

        // if search is null, then don't complete
        if ($search == null) {
            return $results;
        }

        // Buffers for output and return value
        $output = array();
        $retval = PHP_INT_MAX;

        // Clean up the string
        $cleaned = escapeshellarg($this->combineString($search));

        // Replace STRING with the cleaned string
        $toexec = str_replace('STRING', $cleaned, $this->scriptName);

        // Execute the shell script
        exec($toexec, $output, $retval);

        // Handle the output
        foreach ($output as $line) {
            // Break the output into "float rest"
            list($value, $idstr) = explode(" ", $line, 2);
            //TODO: Update to new engine
            array_push($results, array( "id"=> new \reconciliation_engine\identity\identity($idstr),
                "strength"=>floatval($value)));
        }

        // Return the results
        return $results;
    }

    /**
     * Return the name of the stage
     *
     * @return string Name of the stage
     */
    public function getName() {
        return $this->name;
    }
}
