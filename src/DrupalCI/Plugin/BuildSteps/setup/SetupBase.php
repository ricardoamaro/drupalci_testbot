<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\Buildsteps\setup\Checkout
 *
 * Processes "setup: checkout:" instructions from within a job definition.
 */

namespace DrupalCI\Plugin\Buildsteps\setup;

use DrupalCI\Plugin\JobTypes\JobInterface;
use DrupalCI\Plugin\PluginBase;

abstract class SetupBase extends PluginBase {

  protected function validate_directory(JobInterface $job, $dir) {
    // Validate target directory.  Must be within workingdir.
    $working_dir = $job->getWorkingDir();
    $true_dir = realpath($dir);
    if (!empty($true_dir)) {
      if ($true_dir == realpath($working_dir)) {
        // Passed directory is the root working directory.
        return $true_dir;
      }
      // Passed directory is different than working directory. Check whether working directory included in path.
      elseif (strpos($true_dir, realpath($working_dir)) === 0) {
        // Passed directory is an existing subdirectory within the working path.
        return $true_dir;
      }
    }
    // Assume the Passed directory is a subdirectory of the working, without the working prefix.  Construct the full path.
    if (!(strpos($dir, realpath($working_dir)) === 0)) {
      $dir = $working_dir . "/" . $dir;
    }
    $directory = realpath($dir);
    // TODO: Ensure we don't have double slashes
    // Check whether this is a pre-existing directory
    if ($directory === FALSE) {
      // Directory doesn't exist. Create and then validate.
      mkdir($dir, 0777, TRUE);
      $directory = realpath($dir);
    }
    // Validate that resulting directory is still within the working directory path.
    if (!strpos(realpath($directory), realpath($working_dir)) === 0) {
      // Invalid checkout directory
      $job->errorOutput("Error", "The checkout directory <info>$directory</info> is invalid.");
      return FALSE;
    }

    // Return the updated directory value.
    return $directory;
  }

}
