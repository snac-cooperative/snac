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
    private $known_states = array(); // assoc.

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
    public function make_graph ()
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
            elseif ($hr->{func} != '') // isn't ($hr['func']) more clear and robust? Any concept of 'true' is ok here.
            {
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

sub options_checkboxes
{
    my $curr_state = $_[0];
    my $html = "";
    my %unique;
    my @all_tests;
    # First we need a list of unique tests.
    foreach my $hr (@table)
    {
        if ($hr->{test} && ! exists($unique{$hr->{test}}))
        {
            $unique{$hr->{test}} = 1;
            push(@all_tests, $hr->{test});
        }
    }

    my $curr_state_test = $curr_state;
    $curr_state_test =~ s/(.*)\-input/if-page-$1/;
    
    # foreach over the sorted list so the order is always the same.
    foreach my $test (sort(@all_tests))
    {
            my $checked = '';
            my $auto_msg = '';
            my $disabled = '';

            # If a checkbox is checked, and it isn't an "if-page-x" test, then keep it checked.  Else if the
            # matches the current states if-page-x, set the check, else unchecked. dashboard-input causes
            # if-page-dashboard to be true.

            if (check_demo_cgi($test) && $test !~ m/if\-page/)
            {
                $checked = 'checked';
            }
            elsif ($test eq $curr_state_test)
            {
                $checked = 'checked';
                $auto_msg = "(auto-checked)";
            }

            if ($test =~ m/if\-not\-/)
            {
                my $not_test = $test;
                $not_test =~ s/if\-not\-(.*)/if-$1/;
                $auto_msg = "(disabled, depends on $not_test)";
                $disabled = "disabled";
            }

            # Always uncheck if-go-x because presumably we went there. Users need to say where to do on each
            # request, so we don't want these properties to carry over.

            if ($test =~ m/if\-go\-/ || $test =~ m/if\-do\-/)
            {
                $checked = '';
                $auto_msg = "(auto-cleared)";
            }

            $html .= "$test <input type=\"checkbox\" name=\"options\" value=\"$test\" $checked $disabled> $auto_msg <br>\n";
    }
    return $html;
}

sub msg
{
    $msg .= "$_[0]<br>\n";
    # print "$_[0]\n";
}

# This is normally first called with $default_state, and state table traversal goes from there. 
sub traverse
{
    my $curr_state = $_[0];
    my $msg;
    my $wait_next = '';
    my $last_flag = 0;
    my $do_next = 1;

    # In the old days, when we came out of wait, we ran the wait_next state. Now we start at the beginning,
    # and we have an if-test to get us back to a state that will match the rest of the input in the http
    # request.

    my $xx = 0;
    while ($do_next)
    {
        msg("<span style=\"background-color:lightblue;\">Going into state: $curr_state</span>");
        $last_flag = 0;
        # if ($wait_next)
        # {
        #     $curr_state = $wait_next;
        # }
        # $wait_next = '';
        foreach my $hr (@table)
        {
            if (($hr->{edge} eq $curr_state))
            {
                if ((dispatch($hr, 'test')) ||
                    ($hr->{test} eq 'true') ||
                    ($hr->{test} eq ''))
                {
                    # Defaulting to the function as the choice makes sense most of the time, but not with return()
                    # $choice = $hr->{func};
                    $last_flag = 1;

                    # Unless we hit a wait function, we continue with the next state.
                    $do_next = 1;

                    if ($hr->{func} eq 'null' || $hr->{func} eq '')
                    {
                        $curr_state = $hr->{next};
                        # Do nothing.
                    }
                    elsif ($hr->{func} =~ m/^jump\((.*)\)/)
                    {
                        # Ick. Capture inside literal parens is weird looking. (above)
                        my $jump_to_state = $1;
                        # Push the state we will transition to when we return.
                        push_state($hr->{next});
                        $curr_state = $jump_to_state;
                    }
                    elsif ($hr->{func} =~ m/^return[\(\)]*/)
                    {
                        $curr_state = pop_state();
                        # Is $curr_state really correct for the automatic choice when doing return()? $hr->{func}
                        # is not correct, btw.
                        # $choice = $curr_state
                    }
                    elsif ($hr->{func} =~ m/^wait/)
                    {
                        # Up above, this should cause all choices to become available.  We could get back pretty
                        # much any input from the user, but depending on the wait state, only certain other states
                        # will be acceptable.
                        $wait_next = $hr->{next};
                        # $wait_next = $default_state;
                        $do_next = 0;
                    }
                    else
                    {
                        msg("<span style='background-color:lightgreen;'>Dispatch function: $hr->{func}</span>");
                    
                        # Eventually, the state table will be sanity checked, and perhaps munged so that nothing
                        # bad can happen. For now do a little sanity checking right here.
                    
                        my $return_value = dispatch($hr, 'func');
                        if ($hr->{next})
                        {
                            $curr_state = $hr->{next};
                        }
                        else
                        {
                            $last_flag = 0;
                        }
                        # Else, the $curr_state is unchanged, iterate
                    }
                    # msg("end of if curr_state: $curr_state do_next: $do_next last_flag: $last_flag");
                }
                elsif ($hr->{test} && $verbose)
                {
                    msg("If: $hr->{test} is false,");
                    if ($hr->{func})
                    {
                        msg("not running func: $hr->{func}, ");
                    }
                    msg("not going to state: $hr->{next}");
                }
            }
            else
            {
                # msg("$hr->{edge} is not $curr_state last_flag: $last_flag");
            }
            if ($last_flag)
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


    my %tresults; # traverse results
    $tresults{wait_next} = $wait_next;
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
            $html .= "<td>$hr->{$key}</td>\n";
        }
        $html .= "</tr>\n";
    }
    $html .= "</table>\n";
}

sub render
{
    my ($args) = @_;
    my $options = $args->{options};
    my $curr_state = $args->{curr_state};
    my $msg = $args->{msg};
    my $options_list_str = $args->{options_list_str};

    my $table = table_to_html();

    # print "Content-type: text/plain\n\n";
    # print "Current state: $curr_state<br>\n";
    # print $options;

    my $template = read_file('index.html');
    $template =~ s/\$options_list_str/$options_list_str/smg;
    $template =~ s/\$options/$options/smg;
    $template =~ s/\$curr_state/$curr_state/smg;
    $template =~ s/\$msg/$msg/smg;
    $template =~ s/\$table/$table/smg;
    
    print "Content-type: text/html\n\n";
    print $template;

}

# Quick shortcut to check if- functions from the CGI input. If the CGI key exists and is true, then return
# true, else return false

sub check_demo_cgi
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
    elsif ($key =~ m/^if\-/ && $opts{$key})
    {
        return 1;
    }
    return 0;
}

sub dispatch
{
    my $hr = $_[0];
    my $key = $_[1];

    # Sanity check and default
    if ($key ne 'func' && $key ne 'test')
    {
        $key = 'func';
    }

    # true is always true, empty (no test/function) is true as well.
    if ($hr->{$key} eq 'true' || $hr->{$key} eq '')
    {
        return 1;
    }

    # auto-clear some options based one the function 'logout' or any if-do-x test option.

    if ($hr->{$key} eq 'logout')
    {
        # Yikes. A bit crude but should work. Will leave \0\0 in the options string.
        $ch{options} =~ s/if\-logged\-in//;
        # msg("logout options: $ch{options}");
    }

    # Auto clear if-go-x and if-do-x, search auto-cleared in this file.

    # Look up test values from the if- checkboxes, aka options handled by check_demo_cgi().

    if ($key eq 'test')
    {
        my $val = check_demo_cgi($hr->{$key});
        my $val_text = 'false';
        if ($val)
        {
            $val_text = 'true';
        }
        msg("checking: $hr->{$key} result: $val_text<br>\n");
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
    my @va = @_; # remaining args are column names, va mnemonic for variables.

    # print "Reading state data file: $data_file\n";
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

            # Remove the leading | and optional whitespace. 
            $temp =~ s/^\|\s*//;

            if ($temp =~ m/^\s*#/)
            {
                # We have a comment, ignore this line.
                next;
            }

            if ($temp =~ m/^\-\-/)
            {
                # We have a separator line, ignore.
                next;
            }

            # Don't use split because Perl will truncate the returned array due to an undersireable feature
            # where arrays returned and assigned have null elements truncated.

            # Also, make sure there is a terminal \n which makes the regex both simpler and more robust.

            if ($temp !~ m/\n$/)
            {
                $temp .= "\n";
            }

            # Get all the fields before we start so the code below is cleaner, and we want all the line
            # splitting regex to happen here so we can swap between tab-separated, whitespace-separated, and
            # whatever.

            my $has_values = 0;
            my @fields;
            while ($temp =~ s/^(.*?)(?:\s*\|\s+|\n)//smg)
            {
                # Clean up "$var" and "func()" to be "var" and "func".
                my $raw = $1;
                $raw =~ s/\(\)//;
                $raw =~ s/^\$//;

                # Trim whitespace from values. This probably only occurs when there aren't | chars on the line.
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
                    $new_hr->{$va[$xx]} = $fields[$xx];
                    # print "$va[$xx]: $fields[$xx]\n";
                }
                push(@table, $new_hr);
            }
        }
    }
    close(IN);
}



sub sanity_check_states
{
    my $ok = 1; # Things are ok.
    my %next_states;

    # Capture non-empty states.
    foreach my $hr (@table)
    {
        if ($hr->{edge})
        {
            $known_states{$hr->{edge}}++;
        }
        if ($hr->{next})
        {
            $next_states{$hr->{next}}++;
        }
        # jump() is a way of doing next state, so record those as well
        if ($hr->{func} =~ m/jump\((.*)\)/)
        {
            $next_states{$1}++;
        }
    }
    
    # Check for unknown states in next.
    foreach my $hr (@table)
    {
        if ($hr->{next} && ! exists($known_states{$hr->{next}}))
        {
            if  ($hr->{func} =~ m/return/)
            {
                msg("Warning: unknown state following return");
            }
            else
            {
                msg("Error: unknown state $hr->{next}");
                msg( Dumper($hr) );
                $ok = 0;
            }
        }
    }

    # Check for states which can never be reached due to no next.
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

