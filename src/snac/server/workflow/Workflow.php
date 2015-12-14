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
use \snac\Config as Config;
namespace snac\server\workflow;

/**
  This is the Workflow class. It should be instantiated, then the run()
  method called to run the machine based on user input and the system's current state
  
  @author Tom Laudeman
*/
class Workflow 
{
    /**
     *  
     * The main state table. This is a list of lists, essentially a 2D table with columns edge, test, func, and next.
     *
     * @var string[][] table List of associative list with keys: edge, test, func, next
     */

    private $table = array();

    /**
     *
     * A list of states that exist in the state table. These are the unique values from column 'edge'.  This
     * list is used primarily for sanity checking and user interaction. I'm pretty sure it isn't necessary for
     * running the engine.
     *
     * @var string[]
     */
    private $knownStates = array(); // assoc.

    /**
     * The starting state for traversing the state table. It has been 'login', but will probably be instance
     * specific.
     *
     * @var string='login' The starting state for table traversal. Defaults to 'login'.
     *
     */
    private $default_state = 'login';

    /**
     * A message we return to the calling code.
     *
     *
     * @var string A message we return.
     * 
     */
    private $msg = '';
    
    /**
     * Verbose output if true.
     *
     * @var boolean If true (in any sense of 'true') then do verbose output.
     */
    private $verbose = 0;
    
    /**
     * A stack used by jump and return. Push to this stack on jump, and pop on return.
     */
    private $stateStack = array();
    
    /**
     * Push onto the state stack. Probaly used for jump and return.
     * @var string The state to push
     */
    private function pushState($arg)
    {
        array_push($stateStack, $arg);
    }
    
    /**
     * Pop the state stack, return the state.
     * @return string The popped state.
     *
     */
    private function popState()
    {
        return array_pop($stateStack);
    }

    /**
     * Constructor. Currently known values for $tableAlias: 'client', 'server'. See Config.php and
     * Config_dist.php. Assume (at least for now) that the state tables are in the same directory as this
     * file.
     * 
     * @param string $tableAlias An alias name for the state table to read.
     */
    public function __construct($tableAlias) {
        if (! $tableAlias || ! (isset(Config::$stateTableAlias[$tableAlias])))
        {
            $tableAlias = 'server';
        }
        $fileName = Config::$stateTableAlias[$tableAlias];
        readStateDate($fileName);
    }

    /*
      Run Method

      Runs the workflow engine. Or something.
    */
    public function run() 
    {
        // run the state table
        return;
    }
    
    
    /**
     *
     * Turn the state table into a graphviz .gv graphic file. It reads the $table and writes file $file.
     *
     *
     */
    public function makeGraph ()
    {
        $file = "graphviz_states.gv";
        $out;
        if (! ($out = fopen($file, 'w')))
        {
            printf("Cannot open $file for output\n");
            exit(1);
        }
        
        fprintf($out, "digraph States \{\n"); 
        
        // $hr was "hashref" but php doesn't have refs, so this is a tableRow or something.
        foreach ($table as $hr)
        {
            $trans = '';
            if ($hr['test'] != '' && $hr['func'] != '')
            {
                $trans = sprintf("%s\n%s", $hr['test'], $hr['func']);
            }
            elseif ($hr['test'] != '')
            {
                $trans = $hr['test'];
            }
            elseif ($hr['func'] != '') 
            {
                // isn't ($hr['func']) more clear and robust? Any concept of 'true' is ok here.
                $trans = $hr['func'];
            }
        
            $next = $hr['next'];
            if (preg_match('/^wait/', $hr['func']))
            {
                $next = $hr['func'];
            }
            fprintf($out, "\t\"%s\" -> \"%s\" [label=\"%s\"];\n", $hr['edge'], $next, $trans);
        }
        
        fprintf($out, "\}\n");
        fclose($out);
        // Really exit? That seems like a very bad idea inside a method.
        printf("Exiting from function make_graph\n");
        exit();
    }

    /**
     * Used by the walk-through web site to make a list of checkboxes. This works based on a convention of
     * test function naming.
     *
     * @param string $currState the current state we are in
     *
     * @return string $html the html for the checkboxes (I think) 
     */ 
    public function optionsCheckboxes($currState)
    {
        $currState = $_[0];
        $html = "";
        $unique = array();
        $allTests;
        // First we need a list of unique tests.
        foreach ($table as $hr)
        {
            if ($hr['test'] && ! isset($unique[$hr['test']]))
            {
                $unique[$hr['test']] = 1;
                array_push($allTests, $hr['test']);
            }
        }

        $currStateTest = $currState;
        // $currStateTest =~ s/(.*)\-input/if-page-$1/;
        // Change $currStateTest in place.
        preg_replace('/(.*)\-input/','if-page-$1', $currStateTest);
    
        // foreach over the sorted list so the order is always the same.
        sort($allTests);
        foreach ($allTests as $test)
        {
            $checked = '';
            $autoMsg = '';
            $disabled = '';

            // If a checkbox is checked, and it isn't an "if-page-x" test, then keep it checked.  Else if the
            // matches the current states if-page-x, set the check, else unchecked. dashboard-input causes
            // if-page-dashboard to be true.

            // if (checkDemoCGI($test) && $test !~ m/if\-page/)
            if (checkDemoCGI($test) && ! (preg_match('/if\-page/', $test)))
            {
                $checked = 'checked';
            }
            elseif ($test == $currStateTest)
            {
                $checked = 'checked';
                $autoMsg = "(auto-checked)";
            }

            // if ($test =~ m/if\-not\-/)
            if (preg_match('/if\-not\-/', $test))
            {
                $notTest = $test;
                // $notTest =~ s/if\-not\-(.*)/if-$1/;
                // Note: $notTest modified in place
                preg_replace('/if\-not\-(.*)/', '/if-$1/', $notTest);
                $autoMsg = "(disabled, depends on $notTest)";
                $disabled = "disabled";
            }

            // Always uncheck if-go-x because presumably we went there. Users need to say where to do on each
            // request, so we don't want these properties to carry over.

            if ((preg_match('/if\-go\-/', $test)) || (preg_match('/if\-do\-/', $test)))
            {
                $checked = '';
                $autoMsg = "(auto-cleared)";
            }
            // I wonder if this contatenation and string interpolation will work identically to Perl?
            $html .= "$test <input type=\"checkbox\" name=\"options\" value=\"$test\" $checked $disabled> $autoMsg <br>\n";
        }
        return $html;
    }
    
    /**
     * Private method to concatenate values to the private var $msg. Makes a nicer interface than depending on
     * assigning to a global-to-this-class class variable.
     *
     * @param string $msg
     *
     * @return void
     *
     */
    private function msg($msg)
    {
        $msg .= "$msg<br>\n";
    }
    
    // This is normally first called with $default_state, and state table traversal goes from there. 
    public function traverse($currState)
    {
        $msg = '';
        $waitNext = '';
        $lastFlag = 0;
        $doNext = 1;
        
        // In the old days, when we came out of wait, we ran the wait_next state. Now we start at the beginning,
        // and we have an if-test to get us back to a state that will match the rest of the input in the http
        // request.
        
        $xx = 0;
        while ($doNext)
        {
            msg("<span style=\"background-color:lightblue;\">Going into state: $currState</span>");
            $lastFlag = 0;
            // if ($waitNext)
            // {
            //     $currState = $waitNext;
            // }
            // $waitNext = '';
            foreach ($table as $hr)
            {
                if ($hr['edge'] == $currState)
                {
                    if ((dispatch($hr, 'test')) ||
                        ($hr['test'] == 'true') ||
                        ($hr['test'] == ''))
                    {
                        // Defaulting to the function as the choice makes sense most of the time, but not with return()
                        // $choice = $hr['func'];
                        $lastFlag = 1;
                        
                        // Unless we hit a wait function, we continue with the next state.
                        $doNext = 1;
                        
                        if ($hr['func'] == 'null' || $hr['func'] == '')
                        {
                            $currState = $hr['next'];
                            // Do nothing.
                        }
                        elseif ((preg_match('/^jump\((.*)\)/', $hr['func'], $matches)))
                        {
                            /*
                             * This is there jump() happens.
                             *
                             * Get the state we're jumping to from the regex match above. 
                             * 
                             * Push the state we will transition to when we return.
                             *
                             * Change $currState to the new state. All done. 
                             *
                             * Note: Capture inside literal parens is weird looking (above), but it is exactly
                             * what we want.
                             */
                            $jumpToState = $matches[1];
                            pushState($hr['next']);
                            $currState = $jumpToState;
                        }
                        elseif ((preg_match('/^return[\(\)]*/', $hr['func'])))
                        {
                            /* 
                             * Is $currState really correct for the automatic choice when doing return()?
                             * $hr['func'] is not correct, btw.
                             */
                            $currState = popState();
                        }
                        elseif ((preg_match('/^wait/', $hr['func'])))
                        {
                            /* 
                             * Wait is really exit. In the old, continuously running model, wait was simply a
                             * pause for user input.
                             *
                             * Up above, this should cause all choices to become available.  We could get back
                             * pretty much any input from the user, but depending on the wait state, only
                             * certain other states will be acceptable. At one point, we jumped back to the
                             * default state, but I think that is for a semi-continuous mode of operation:
                             * $waitNext = $default_state;
                             */
                            $waitNext = $hr['next'];
                            $doNext = 0;
                        }
                        else
                        {
                            msg(sprintf("<span style='background-color:lightgreen;'>Dispatch function: %s</span>", $hr['func']));
                            
                            /* 
                             * Eventually, the state table will be sanity checked, and perhaps munged so that nothing
                             * bad can happen. For now do a little sanity checking right here.
                             * 
                             * Is $returnValue used? If not, then this (apparantly historical) variable should
                             * be removed.
                             */
                            $returnValue = dispatch($hr, 'func');
                            if ($hr['next'])
                            {
                                $currState = $hr['next'];
                            }
                            else
                            {
                                $lastFlag = 0;
                            }
                            /* else, the $currState is unchanged, iterate */
                        }
                    }
                    elseif ($hr['test'] && $verbose)
                    {
                        msg(sprintf("If: %s is false,", $hr['test']));
                        if ($hr['func'])
                        {
                            msg(sprintf("not running func: %s, ", $hr['func']));
                        }
                        msg(sprintf("not going to state: %s", $hr['next']));
                    }
                }
                else
                {
                    // msg("$hr['edge'] is not $currState last_flag: $lastFlag");
                }
                if ($lastFlag)
                {
                    last;
                }
            }
            $xx++;
            if ($xx > 30)
            {
                msg("Error: inf loop catcher!");
                last;
            }
        }
        // traverse results        
        $tResults = array();
        $tResults['waitNext']= $waitNext;
        $tResults['msg'] = $msg;
        return $tResults;
    } // end traverse


    /**
     * Turn the state table variable into and html representation.
     *
     * @return string $html The html fragment that is a table element of the workflow state table.
     *
     */

    public function tableToHtml()
    {
        $html = "<table border=\"1\">\n";
        $html .= "<tr bgcolor='lightgray'>\n";
        foreach (array('State', 'Test', 'Func', 'Next-state') as  $head)
        {
            $html .= "<td>$head</td>\n";
        }
        $html .= "</tr>\n";
        
        foreach ($table as $hr)
        {
            $html .= "<tr>\n";
            foreach (array('edge', 'test', 'func', 'next') as $key)
            {
                $html .= sprintf("<td>%s</td>\n", $hr['$key']);
            }
            $html .= "</tr>\n";
        }
        $html .= "</table>\n";
        return $html;
    }

    /**
     * This is a simply regex html substitution template function. If it needs anything more interesting than
     * this, we should migrate is to a real template system like twig.
     *
     * @param string $options Options as html checkboxes
     *
     * @param string $currState The name of the current state
     *
     * @param string $msg A message for the user giving the status of the state machine
     *
     * @param $optionListStr A list of options as a string.
     *
     */

    public function render($options, $currState, $msg, $optionsListStr)
    {
        $table = table_to_html();
        
        // print "Content-type: text/plain\n\n";
        // print "Current state: $currState<br>\n";
        // print $options;
        
        $template = read_file('index.html');
        preg_replace('/\$optionsListStr/smg', '$optionsListStr', $template);
        preg_replace('/\$options/smg', '$options', $template);
        preg_replace('/\$currState/smg', '$currState', $template);
        preg_replace('/\$msg/smg', '$msg', $template);
        preg_replace('/\$table/smg', '$table', $template);
        
        print "Content-type: text/html\n\n";
        print $template;
    }
    
    /* 
     * Quick shortcut to check if- functions from the CGI input. If the CGI key exists and is true, then return
     * true, else return false
     *
     *
     * @param string $key The key we are checking against the CGI params
     *
     * @return boolean True or false, depending on whether the key was found.
     */
    
    public function checkDemoCGI($key)
    {
        // I think these are CGI params. Pull them out using the standard php idiom.
        $opts = array();
        
        $notComplementKey = $key;
        preg_replace('/if\-not\-(.*)/', 'if-$1', $notComplementKey);
        
        if ((preg_match('/^if\-not\-/', $key)))
        {
            if ($opts[$notComplementKey])
            {
                return true;
            }
            else
            {
                return false;
            }
        }
        elseif ((preg_match('/^if\-/', $key)) && $opts[$key])
        {
            return true;
        }
        return false;
    }
    
    /**
     *
     * Dispatch a function. This calls the functions that do work specified by the workflow.
     *
     *
     *
     *
     */
    private function dispatch($hr, $key)
    {
        // Sanity check and default
        if ($key != 'func' && $key != 'test')
        {
            $key = 'func';
        }
        
        /* 
         * The state value true is always true, empty (no test/function) is true as well because empty is
         * considered a "this is a default" or something. State table functions return explicit true or false,
         * and not having a function is assumed to be true. Put another way, if you don't have a function,
         * then you expected the state to transtion.
         */
        if ($hr['$key'] == 'true' || $hr['$key'] == '')
        {
            return 1;
        }

        // auto-clear some options based one the function 'logout' or any if-do-x test option.

        if ($hr['$key'] == 'logout')
        {
            // Yikes. A bit crude but should work. Will leave \0\0 in the options string.
            
            printf("Error: need to re-implement the concept of logging out\n");
            //preg_replace('/if\-logged\-in/', '', $ch{options});
        }
        
        /* 
         * Auto clear if-go-x and if-do-x, search auto-cleared in this file.
         * 
         * Look up test values from the if- checkboxes, aka options handled by checkDemoCGI().
         */
        if ($key == 'test')
        {
            $val = checkDemoCGI($hr['$key']);
            $val_text = 'false';
            if ($val)
            {
                $val_text = 'true';
            }
            msg(sprintf("checking: %s result: $val_text<br>\n", $hr['$key'] ));
            return $val;
        }
        else
        {
            return true;
        }
    }


    /**
     *
     * Read the state table, populate var $table.
     *
     * @param $dataFile Filename of the state table to read.
     *
     */
    private function readStateData($dataFile)
    {
        $fields = array();
        
        $logFlag = false;
        
        if (! $fd = fopen($dataFile, 'r'))
        {
            if (! $logFlag)
            {
                print ("Error: Can't open $dataFile for reading\n");
                $logFlag = true; // Why are we setting this to true here? Odd.
            }
        }
        else
        {
            /* 
             * Start by pulling off the first line of the file. We assume that the first line is column
             * headings that humans use to remind themselves which column is which.
             */
            fgets($fd);
            while (($temp = fgets($fs)))
            {
                $newList = array();
                
                // Remove the leading | and optional whitespace. 
                preg_replace('/^\|\s*/', '', $temp);
                
                if ((preg_match('/^\s*#/', $temp)))
                {
                    // We have a comment, ignore this line.
                    next;
                }
                
                if ((preg_match('/^\-\-/', $temp)))
                {
                    // We have a separator line, ignore. org-mode tables 
                    next;
                }
                
                // Make sure there is a terminal \n which makes the regex both simpler and more robust.
                
                if (preg_match('/\n$/', $temp))
                {
                    $temp .= "\n";
                }
                
                // Get all the fields before we start so the code below is cleaner, and we want all the line
                // splitting regex to happen here so we can swap between tab-separated, whitespace-separated, and
                // whatever.
                
                $hasValues = 0;
                $fields = array();
                $myMatches = array();
                /*
                 * Do a while loop over the input line, trimming off a column by replacing the column data
                 * with '', and then process the captured ($myMatch[1]) column. While this looks exciting in
                 * php, while-replace-and-keep is idiomatic in Perl where the syntax is natural. It can be
                 * idiomatic in php, albeit with a few extra lines of code. Using preg_replace_callback()
                 * saves doing a preg_match() followed by preg_replace(), and is therefore more robust (no
                 * duplicated regex on two separate lines of code).
                 *
                 * php can't return the matches from preg_replace() so we have to use preg_replace_callback()
                 * and make the callback function write into a local variable (declared as a global) as a side
                 * effect. The main purpose of the callback is to supply the replacement value. We replace the
                 * matched (and captured) value with the empty string ''.
                 *
                 */ 
                while ((preg_replace_callback('/^(.*?)(?:\s*\|\s+|\n)/smg', 
                                              function($matches) 
                                              {
                                                  global $myMatches;
                                                  $myMatches = $matches;
                                                  return '';
                                              },
                                              $temp)))
                {
                    /* 
                     * Clean up "$var" and "func()" to be "var" and "func".
                     * Remove () from func() and $ from $var
                     */
                    $raw = $myMatches[1];
                    preg_replace('/\(\)/', '', $raw);
                    preg_replace('/^\$/', '', $raw);
                            
                    /*
                     * Trim whitespace from values. This probably only occurs when there aren't | chars on
                     * the line. Why not just use trim()?
                     */
                    preg_replace('/^\s+(.*)\s+$/', '$1', $raw);
                    if ($raw != '')
                    {
                        $hasValues = 1;
                    }
                    array_push($fields, $raw);
                }
                
                /*
                 * Note that the column names are hard coded. Changes here would required changes everywhere. 
                 */ 
                if ($hasValues)
                {
                    $newList = array('edge' => $fields[0],
                                     'test' => $fields[1],
                                     'func' => $fields[2],
                                     'next' => $fields[3]);
                    array_push($table, $newList);
                }
            }
        }
        fclose($fp);
    }



    public function sanityCheckStates()
    {
        $ok = true; // Things are ok.
        $nextStates = array();
        
        /* 
         * Capture non-empty states.
         * 
         * jump() is a way of doing next state, so record those as well. Remember that unlike everything else
         * in the state table, jump() has an argument which is the state to jump to.
         *
         */
        foreach ($table as $hr)
        {
            if ($hr['edge'])
            {
                $knownStates{$hr['edge']}++;
            }
            if ($hr['next'])
            {
                $nextStates{$hr['next']}++;
            }
            if (preg_match('/jump\((.*)\)/', $hr['func'], $matches))
            {
                $nextStates{$matches[1]}++;
            }
        }
    
        /*
         * Check for unknown states in next.
         */ 
        foreach ($table as $hr)
        {
            if ($hr['next'] && ! isset($knownStates{$hr['next']}))
            {
                if  (preg_match('/return/', $hr['func']))
                {
                    msg("Warning: unknown state following return");
                }
                else
                {
                    msg(sprintf("Error: unknown state %s\n%s", $hr['next'], var_export($hr,1)));
                    $ok = false;
                }
            }
        }
        
        /*
         * Check for states which can never be reached due to no next.
         */
        foreach ($knownStates as $state => $value)
        {
            if (! exists($nextStates{$state}))
            {
                msg("No next-state for: $state");
                $ok = 0;
            }
        }
        
        if (! $ok)
        {
            msg("Failed state table sanity check (unknown or unreachable states)");
            return false;
        }
        return true;
    }
    
}

