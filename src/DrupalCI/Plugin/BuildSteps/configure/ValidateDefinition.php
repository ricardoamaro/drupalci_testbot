<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\Buildsteps\configure\ValidateDefinition
 *
 * Validates a compiled job definition against the job type definition:
 * 1. Verifies all 'mandatory' parameters present
 * 2. (TODO) Strips out any parameters not specified in the 'allowed' list for that job type
 */

namespace DrupalCI\Plugin\Buildsteps\configure;
use DrupalCI\Plugin\JobTypes\JobInterface;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("validate_definition")
 */
class ValidateDefinition extends PluginBase {

  /**
   * {@inheritdoc}
   */
  public function run(JobInterface $job, $data = NULL) {
    // TODO: Ensure that all 'required' arguments are defined
    $definition = $job->getDefinition();
    $failflag = FALSE;
    foreach ($job->getRequiredArguments() as $env_var => $yaml_loc) {
      if (!empty($job->getBuildVars()[$env_var])) {
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
      $job->errorOutput("Failed", "Required test parameter <options=bold>'$env_var'</options=bold> not found in environment variables, and <options=bold>'$yaml_loc'</options=bold> not found in job definition file.");
      // TODO: Graceful handling of failed exit states
      return FALSE;
    }
    // TODO: Strip out arguments which are not defined in the 'Available' arguments array
    return TRUE;
  }

}
