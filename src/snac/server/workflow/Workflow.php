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

/**
  This is the Workflow class. It should be instantiated, then the run()
  method called to run the machine based on user input and the system's current state
  
  @author Tom Laudeman
*/
class Workflow 
{
    private $ch = array(); // assoc.

    /**
     *  
     * The main state table. This is a list of lists, essentially a 2D table with columns edge, test, func, and next.
     *
     * @var string[][] table List of associative list with keys: edge, test, func, next
     */

    private $table = array();

    /**
     *
     * A list of states that exist in the state table. These are the unique values from column 'edge'.
     *
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
    
    private $verbose = 0;
    
    /**
     * Constructor
     * 
     * Requires the input to the server as an associative array
     * @param array $input Input to the server
     */
    public function __construct($input) {
        // read the state table
    }

    /*
      Run Method

      Runs the server
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
                if (($hr['edge'] == $currState))
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
                            // Note: Capture inside literal parens is weird looking (above), but it is exactly
                            // what we want.
                            $jumpToState = $matches[1];
                            // Push the state we will transition to when we return.
                            push_state($hr['next']);
                            $currState = $jumpToState;
                        }
                        elseif ((preg_match('/^return[\(\)]*/', $hr['func'])))
                        {
                            $currState = pop_state();
                            // Is $currState really correct for the automatic choice when doing return()? $hr['func']
                            // is not correct, btw.
                            // $choice = $currState
                        }
                        elseif ((preg_match('/^wait/', $hr['func'])))
                        {
                            // Up above, this should cause all choices to become available.  We could get back
                            // pretty much any input from the user, but depending on the wait state, only
                            // certain other states will be acceptable. At one point, we jumped back to the
                            // default state, but I think that is for a semi-continuous mode of operation:
                            // $waitNext = $default_state;
                            $waitNext = $hr['next'];
                            $doNext = 0;
                        }
                        else
                        {
                            msg(sprintf("<span style='background-color:lightgreen;'>Dispatch function: %s</span>", $hr['func']));
                            
                            // Eventually, the state table will be sanity checked, and perhaps munged so that nothing
                            // bad can happen. For now do a little sanity checking right here.
                            
                            // Is $returnValue used? If not, then this (apparantly historical) variable should
                            // be removed.
                            
                            $returnValue = dispatch($hr, 'func');
                            if ($hr['next'])
                            {
                                $currState = $hr['next'];
                            }
                            else
                            {
                                $lastFlag = 0;
                            }
                            // Else, the $currState is unchanged, iterate
                        }
                        }
                    elseif ($hr['test'] && $verbose)
                    {
                        msg("If: $hr['test'] is false,");
                        if ($hr['func'])
                        {
                            msg("not running func: $hr['func'], ");
                        }
                        msg("not going to state: $hr['next']");
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


        my %tresults; // traverse results
        $tresults{wait_next} = $waitNext;
        $tresults{msg} = $msg;
        return %tresults;
        }


my @state_stack = [];

sub push_state
{
    push(@state_stack, $_[0]);
}

sub pop_state
{
    return pop(@state_stack);
}

sub table_to_html
{
    my $html = "<table border=\"1\">\n";
    $html .= "<tr bgcolor='lightgray'>\n";
    foreach my $head ('State', 'Test', 'Func', 'Next-state')
    {
        $html .= "<td>$head</td>\n";
    }
    $html .= "</tr>\n";

    foreach my $hr (@table)
    {
        $html .= "<tr>\n";
        foreach my $key ('edge', 'test', 'func', 'next')
        {
            $html .= "<td>$hr['$key']</td>\n";
        }
        $html .= "</tr>\n";
    }
    $html .= "</table>\n";
}

sub render
{
    my ($args) = @_;
    my $options = $args['options'];
    my $currState = $args['currState'];
    my $msg = $args['msg'];
    my $options_list_str = $args['options_list_str'];

    my $table = table_to_html();

    // print "Content-type: text/plain\n\n";
    // print "Current state: $currState<br>\n";
    // print $options;

    my $template = read_file('index.html');
    $template =~ s/\$options_list_str/$options_list_str/smg;
    $template =~ s/\$options/$options/smg;
    $template =~ s/\$currState/$currState/smg;
    $template =~ s/\$msg/$msg/smg;
    $template =~ s/\$table/$table/smg;
    
    print "Content-type: text/html\n\n";
    print $template;

}

// Quick shortcut to check if- functions from the CGI input. If the CGI key exists and is true, then return
// true, else return false

sub checkDemoCGI
{
    my $key = $_[0];
    my %opts;
    foreach my $opt (split("\0", $ch{options}))
    {
        $opts{$opt} = 1;
    }

    my $not_complement_key = $key;
    $not_complement_key =~ s/if\-not\-(.*)/if-$1/;
    
    if ($key =~ m/^if\-not\-/)
    {
        if ($opts{$not_complement_key})
        {
            return 0;
        }
        else
        {
            return 1;
        }
    }
    elseif ($key =~ m/^if\-/ && $opts{$key})
    {
        return 1;
    }
    return 0;
}

sub dispatch
{
    my $hr = $_[0];
    my $key = $_[1];

    // Sanity check and default
    if ($key ne 'func' && $key ne 'test')
    {
        $key = 'func';
    }

    // true is always true, empty (no test/function) is true as well.
    if ($hr['$key'] == 'true' || $hr['$key'] == '')
    {
        return 1;
    }

    // auto-clear some options based one the function 'logout' or any if-do-x test option.

    if ($hr['$key'] == 'logout')
    {
        // Yikes. A bit crude but should work. Will leave \0\0 in the options string.
        $ch{options} =~ s/if\-logged\-in//;
        // msg("logout options: $ch{options}");
    }

    // Auto clear if-go-x and if-do-x, search auto-cleared in this file.

    // Look up test values from the if- checkboxes, aka options handled by checkDemoCGI().

    if ($key == 'test')
    {
        my $val = checkDemoCGI($hr['$key']);
        my $val_text = 'false';
        if ($val)
        {
            $val_text = 'true';
        }
        msg("checking: $hr['$key'] result: $val_text<br>\n");
        return $val;
    }
    else
    {
        return 1;
    }
}


sub read_state_data
{
    my $data_file = shift(@_);
    my @va = @_; // remaining args are column names, va mnemonic for variables.

    // print "Reading state data file: $data_file\n";
    my($temp);
    my @fields;
    
    my $log_flag = 0;

    if (! open(IN, "<",  $data_file))
    {
        if (! $log_flag)
        {
            print ("Error: Can't open $data_file for reading\n");
            $log_flag = 1;
        }
    }
    else
    {
        my $ignore_first_line = <IN>;
        while ($temp = <IN>)
        {
            my $new_hr;

            // Remove the leading | and optional whitespace. 
            $temp =~ s/^\|\s*//;

            if ($temp =~ m/^\s*#/)
            {
                // We have a comment, ignore this line.
                next;
            }

            if ($temp =~ m/^\-\-/)
            {
                // We have a separator line, ignore.
                next;
            }

            // Don't use split because Perl will truncate the returned array due to an undersireable feature
            // where arrays returned and assigned have null elements truncated.

            // Also, make sure there is a terminal \n which makes the regex both simpler and more robust.

            if ($temp !~ m/\n$/)
            {
                $temp .= "\n";
            }

            // Get all the fields before we start so the code below is cleaner, and we want all the line
            // splitting regex to happen here so we can swap between tab-separated, whitespace-separated, and
            // whatever.

            my $has_values = 0;
            my @fields;
            while ($temp =~ s/^(.*?)(?:\s*\|\s+|\n)//smg)
            {
                // Clean up "$var" and "func()" to be "var" and "func".
                my $raw = $1;
                $raw =~ s/\(\)//;
                $raw =~ s/^\$//;

                // Trim whitespace from values. This probably only occurs when there aren't | chars on the line.
                $raw =~ s/^\s+(.*)\s+$/$1/;
                if ($raw ne '')
                {
                    $has_values = 1;
                }
                push(@fields, $raw);
            }
            
            if ($has_values)
            {
                for (my $xx=0; $xx<=$#va; $xx++)
                {
                    $new_hr['$va[$xx]'] = $fields[$xx];
                    // print "$va[$xx]: $fields[$xx]\n";
                }
                push(@table, $new_hr);
            }
        }
    }
    close(IN);
}



sub sanity_check_states
{
    my $ok = 1; // Things are ok.
    my %next_states;

    // Capture non-empty states.
    foreach my $hr (@table)
    {
        if ($hr['edge'])
        {
            $knownStates{$hr['edge']}++;
        }
        if ($hr['next'])
        {
            $next_states{$hr['next']}++;
        }
        // jump() is a way of doing next state, so record those as well
        if ($hr['func'] =~ m/jump\((.*)\)/)
        {
            $next_states{$1}++;
        }
    }
    
    // Check for unknown states in next.
    foreach my $hr (@table)
    {
        if ($hr['next'] && ! exists($knownStates{$hr['next']}))
        {
            if  ($hr['func'] =~ m/return/)
            {
                msg("Warning: unknown state following return");
            }
            else
            {
                msg("Error: unknown state $hr['next']");
                msg( Dumper($hr) );
                $ok = 0;
            }
        }
    }

    // Check for states which can never be reached due to no next.
    foreach my $state (keys(%known_states))
    {
        if (! exists($next_states{$state}))
        {
            msg("No next-state for: $state");
            $ok = 0;
        }
    }

    if (! $ok)
    {
        msg("Failed state table sanity check (unknown or unreachable states)");
        return 0;
    }
    return 1;
}




}

