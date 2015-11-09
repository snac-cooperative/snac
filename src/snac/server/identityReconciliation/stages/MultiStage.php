<?php
namespace snac\server\identityReconciliation\stages;

/**
 * Multi-Stage Class
 *
 * This class is used to daisy-chain some stages together.  It will issue the
 * stages in order, but pass the results from one stage into the next stage
 * (ignoring the strengths/scores from the previous stage.
 *
 * The result list from the first stage will be passed in as the list to
 * search for the next stage, which will return a list of results to pass to
 * the next stage, and so on.  The result set from the final stage will be
 * returned as the results from this stage.
 *
 * @author Robbie Hott
 */

class MultiStage implements helpers\Stage {

    /**
     * @var array Stages to run (instantiated by constructor)
     */
    private $stages;

    /**
     * Constructor
     *
     * Must take a list of text-based stages to instantiate
     *
     * @param helpers\stage $stage .. stages to instantiate
     */
    public function __construct() {
        $this->stages = array();
        foreach (func_get_args() as $stage) {
            $stage_full = "snac\\server\\identityReconciliation\\stages\\".$stage;
            array_push($this->stages, new $stage_full);
        }
    }

    /**
     * Get Name
     *
     * Returns the name of this stage.  It will combine the list of all
     * stages run as its official name. 
     *
     * @return string Name of the stage.
     */
    public function getName() {
        $retval = "MultiStage";
        foreach ($this->stages as $stage)
            $retval .= ":" . $stage->getName();
        return $retval;
    }

   

    /**
     * Run function
     *
     * Runs each of the stages in order.  The first stage is not given a list
     * of results to test against (unless provided), however, after the first
     * stage, each subsequent stage is given the former stage's result set as
     * the list.
     *
     * @param \snac\data\Constellation $search The constellation to be evaluated.
     * @param \snac\data\ReconiciliationResult[] $list A list of results to evaluate against.  This
     * may be null.  
     * @return array An array of results from the final stage that has been
     * run or an empty array.
     *
     */
    public function run($search, $list) {

        $nextList = $list;
        foreach ($this->stages as $stage) {
            // Run the stage with the current list of IDs
            $results = $stage->run($search, $nextList);

            // Empty out the list of next ids
            $nextList = $results;
        } 
        
        return $results;
    }
}
