<?php
/**
 * Created by PhpStorm.
 * User: Jeremy
 * Date: 3/7/15
 * Time: 11:42 AM
 */

namespace DrupalCI\Jobs\Component;


class SetupComponent {

  protected $working_dir;

  public function execute($job) {
    // If no setup definition, we bail
    if (empty($job->job_definition['setup'])) {
      return;
    }
    $setup = $job->job_definition['setup'];
    foreach ($setup as $step => $details) {
      $func = "setup_" . $step;
      if (!isset($details[0])) {
        // Non-numeric array found ... assume we have only one iteration.
        // We wrap this in an array in order to handle both singletons and
        // arrays with the same code.
        $details = array($details);
      }
      foreach ($details as $iteration => $detail) {
        $this->$func($detail, $job);
        // Handle errors encountered during sub-function execution.
        if ($job->error_status != 0) {
          echo "Received failed return code from function $func.";
          return;
        }
      }
    }
    return;
  }

  protected function setup_checkout($details, $job) {
    $job->output->writeln("<info>Entering setup_checkout().</info>");
    // TODO: Ensure $details contains all required parameters
    $protocol = isset($details['protocol']) ? $details['protocol'] : 'git';
    $func = "setup_checkout_" . $protocol;
    return $this->$func($details, $job);
  }

  protected function setup_checkout_local($details, $job) {
    $job->output->writeln("<info>Entering setup_checkout_local().</info>");
    $srcdir = isset($details['srcdir']) ? $details['srcdir'] : './';
    $workingdir = $job->working_dir;
    $checkoutdir = isset($details['checkout_dir']) ? $details['checkout_dir'] : $workingdir;
    // TODO: Ensure we don't end up with double slashes
    // Validate target directory.  Must be within workingdir.
    if (!($directory = $this->validate_directory($checkoutdir, $job))) {
      // Invalidate checkout directory
      $job->error_output("Error", "The checkout directory <info>$directory</info> is invalid.");
      return;
    }
    $job->output->write("<comment>Copying files from <options=bold>$srcdir</options=bold> to the local checkout directory <options=bold>$directory</options=bold> ... </comment>");
    exec("cp -r $srcdir/* $directory", $cmdoutput, $result);
    if (is_null($result)) {
      $job->error_output("Failed", "Error encountered while attempting to copy code to the local checkout directory.");
      return;
    }
    $job->output->writeln("<comment>DONE</comment>");
  }

  protected function setup_checkout_git($details, $job) {
    $job->output->writeln("<info>Entering setup_checkout_git().</info>");
    $repo = isset($details['repo']) ? $details['repo'] : 'git://drupalcode.org/project/drupal.git';
    $gitbranch = isset($details['branch']) ? $details['branch'] : 'master';
    $gitdepth = isset($details['depth']) ? $details['depth'] : NULL;
    $workingdir = $job->working_dir;

    $checkoutdir = isset($details['checkout_dir']) ? $details['checkout_dir'] : $workingdir;
    // TODO: Ensure we don't end up with double slashes
    // Validate target directory.  Must be within workingdir.
    if (!($directory = $this->validate_directory($checkoutdir, $job))) {
      // Invalid checkout directory
      $job->error_output("Error", "The checkout directory <info>$directory</info> is invalid.");
      return;
    }
    $job->output->writeln("<comment>Performing git checkout of $repo $gitbranch branch to $directory.</comment>");

    $cmd = "git clone -b $gitbranch $repo $directory";
    if (!is_null($gitdepth)) {
      $cmd .=" --depth=$gitdepth";
    }
    exec($cmd, $cmdoutput, $result);
    if ($result !==0) {
      // Git threw an error.
      $job->error_output("Checkout failed", "The git checkout returned an error.");
      // TODO: Pass on the actual return value for the git checkout
      return;
    }
    $job->output->writeln("<comment>Checkout complete.</comment>");
  }

  protected function validate_directory($dir, $job) {
    // Validate target directory.  Must be within workingdir.
    $working_dir = $job->working_dir;
    $true_dir = realpath($dir);
    if (!empty($true_dir)) {
      if ($true_dir == realpath($working_dir)) {
        // Passed directory is the root working directory.
        return $true_dir;
      }
      // Passed directory is different than working directory. Check whether working directory included in path.
      elseif (strpos($true_dir, realpath($working_dir)) === 0) {
        // Passed directory is a subdirectory within the working path.
        return $true_dir;
      }
    }
    // Assume the Passed directory is a subdirectory of the working, without the working prefix.  Construct the full path.
    $directory = realpath($working_dir . "/" . $dir);
    // TODO: Ensure we don't have double slashes

    // Check whether this is a pre-existing directory
    if ($directory === FALSE) {
      // Directory doesn't exist. Create and then validate.
      mkdir($working_dir . "/" . $dir, 0777, TRUE);
      $directory = realpath($working_dir . "/" . $dir);
    }
    // Validate that resulting directory is still within the working directory path.
    if (!strpos(realpath($directory), realpath($working_dir)) === 0) {
      // Invalid checkout directory
      $job->error_output("Error", "The checkout directory <info>$directory</info> is invalid.");
      return;
    }

    // Return the updated directory value.
    return $directory;
  }

  protected function setup_fetch($details, $job) {
    $job->output->writeln("<info>Entering setup_fetch().</info>");
    // URL and target directory
    // TODO: Ensure $details contains all required parameters
    if (empty($details['url'])) {
      $job->error_output("Error", "No valid target file provided for fetch command.");
      return;
    }
    $url = $details['url'];
    $workingdir = realpath($job->working_dir);
    $fetchdir = (!empty($details['fetch_dir'])) ? $details['fetch_dir'] : $workingdir;
    if (!($directory = $this->validate_directory($fetchdir, $job))) {
      // Invalid checkout directory
      $job->error_output("Error", "The fetch directory <info>$directory</info> is invalid.");
      return;
    }
    $info = pathinfo($url);
    $destfile = $directory . "/" . $info['basename'];
    $contents = file_get_contents($url);
    if ($contents === FALSE) {
      $job->error_output("Error", "An error was encountered while attempting to fetch <info>$url</info>.");
      return;
    }
    if (file_put_contents($destfile, $contents) === FALSE) {
      $job->error_output("Error", "An error was encountered while attempting to write <info>$url</info> to <info>$directory</info>");
      return FALSE;
    }
    $job->output->writeln("<comment>Fetch of <options=bold>$url</options=bold> to <options=bold>$destfile</options=bold> complete.</comment>");
  }



  protected function setup_patch($details, $job) {
    $job->output->writeln("<info>Entering setup_patch().</info>");
    if (empty($details['patch_file'])) {
      $job->error_output("Error", "No valid patch file provided for the patch command.");
      return;
    }
    $workingdir = realpath($this->working_dir);
    $patchfile = $details['patch_file'];
    $patchdir = (!empty($details['patch_dir'])) ? $details['patch_dir'] : $workingdir;
    // Validate target directory.
    if (!($directory = $this->validate_directory($patchdir, $job))) {
      // Invalid checkout directory
      $job->error_output("Error", "The patch directory <info>$directory</info> is invalid.");
      return;
    }
    $cmd = "patch -p1 -i $patchfile -d $directory";

    exec($cmd, $cmdoutput, $result);
    if ($result !==0) {
      // The command threw an error.
      $job->error_output("Patch failed", "The patch attempt returned an error.");
      // TODO: Pass on the actual return value for the patch attempt
      return;
    }
    $job->output->writeln("<comment>Patch <options=bold>$patchfile</options=bold> applied to directory <options=bold>$directory</options=bold></comment>");
  }
}