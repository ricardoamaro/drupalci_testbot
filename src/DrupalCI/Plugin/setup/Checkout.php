<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\setup\Checkout
 *
 * Processes "setup: checkout:" instructions from within a job definition.
 */

namespace DrupalCI\Plugin\setup;
use DrupalCI\Plugin\setup\SetupBase;

/**
 * @PluginID("checkout")
 */
class Checkout extends SetupBase {

  /**
   * {@inheritdoc}
   */
  public function run($job, $data) {
    // Data format:
    // i) array('protocol' => 'local', 'srcdir' => '/tmp/drupal', ['checkout_dir' => '/tmp/checkout'])
    // or
    // ii) array('protocol' => 'git', 'repo' => 'git://code.drupal.org/drupal.git', 'branch' => '8.0.x', ['depth' => 1])
    // or
    // iii) array(array(...), array(...))
    // Normalize data to the third format, if necessary
    $data = (count($data) == count($data, COUNT_RECURSIVE)) ? [$data] : $data;

    $job->output->writeln("<info>Entering setup_checkout().</info>");
    foreach ($data as $key => $details ) {
      // TODO: Ensure $details contains all required parameters
      $protocol = isset($details['protocol']) ? $details['protocol'] : 'git';
      $func = "setup_checkout_" . $protocol;
      $this->$func($job, $details);
      if ($job->error_status != 0) { break; }
    }
    return;
  }

  protected function setup_checkout_local($job, $details) {
    $job->output->writeln("<info>Entering setup_checkout_local().</info>");
    $srcdir = isset($details['srcdir']) ? $details['srcdir'] : './';
    $workingdir = $job->working_dir;
    $checkoutdir = isset($details['checkout_dir']) ? $details['checkout_dir'] : $workingdir;
    // TODO: Ensure we don't end up with double slashes
    // Validate source directory
    $source = realpath($srcdir);
    if (empty($source)) {
      $job->error_output("Error", "The source directory <info>$srcdir</info> does not exist.");
      return;
    }
    // Validate target directory.  Must be within workingdir.
    if (!($directory = $this->validate_directory($job, $checkoutdir))) {
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

  protected function setup_checkout_git($job, $details) {
    $job->output->writeln("<info>Entering setup_checkout_git().</info>");
    $repo = isset($details['repo']) ? $details['repo'] : 'git://drupalcode.org/project/drupal.git';
    $gitbranch = isset($details['branch']) ? $details['branch'] : 'master';
    $gitdepth = isset($details['depth']) ? $details['depth'] : NULL;
    $workingdir = $job->working_dir;

    $checkoutdir = isset($details['checkout_dir']) ? $details['checkout_dir'] : $workingdir;
    // TODO: Ensure we don't end up with double slashes
    // Validate target directory.  Must be within workingdir.
    if (!($directory = $this->validate_directory($job, $checkoutdir))) {
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

}
