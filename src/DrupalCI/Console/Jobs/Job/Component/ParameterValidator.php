<?php
/**
 * Created by PhpStorm.
 * User: Jeremy
 * Date: 2/26/15
 * Time: 9:56 PM
 */

namespace DrupalCI\Console\Jobs\Job\Component;


class ParameterValidator {

  public function validate($job) {
    // TODO: Ensure that all 'required' arguments are defined
    $definition = $job->job_definition;
    $failflag = FALSE;
    foreach ($job->required_arguments as $env_var => $yaml_loc) {
      if (!empty($job->build_vars[$env_var])) {
        continue;
      }
      else {
        // Look for the appropriate array structure in the job definition file
        // eg: environment:db
        $keys = explode(":", $yaml_loc);
        $eval = $definition;
        foreach ($keys as $key) {
          if (!empty($eval[$key])) {
            // Check if the next level contains a numeric [0] key, indicating a
            // nested array of parameters.  If found, skip this level of the
            // array.
            if (isset($eval[$key][0])) {
              $eval = $eval[$key][0];
            }
            else {
              $eval=$eval[$key];
            }
          }
          else {
            // Missing a required key in the array key chain
            $failflag = TRUE;
            break;
          }
        }
        if (!$failflag) {
          continue;
        }
      }
      // If processing gets to here, we're missing a required variable
      $job->error_output("Failed", "Required test parameter <options=bold>'$env_var'</options=bold> not found in environment variables, and <options=bold>'$yaml_loc'</options=bold> not found in job definition file.");
      // TODO: Graceful handling of failed exit states
      return;
    }
    // TODO: Strip out arguments which are not defined in the 'Available' arguments array
    return TRUE;
  }
}