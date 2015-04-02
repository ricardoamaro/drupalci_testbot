<?php
/**
 * Created by PhpStorm.
 * User: Jeremy
 * Date: 3/20/15
 * Time: 10:10 PM
 */

namespace DrupalCI\Plugin\BuildSteps\configure;
use DrupalCI\Plugin\JobTypes\JobInterface;

/**
 * @PluginID("setup_directories")
 */
class SetupDirectories {


  // TODO: Setting CodeBase results in $job->definition file being empty.  Look into CompileDefinition->getDefinitionFile()


  /**
   * {@inheritdoc}
   */
  public function run(JobInterface $job, $data = NULL) {
    // Setup codebase and working directories
    $this->setupCodebase($job);
    $this->setupWorkingDir($job);
  }

  public function setupCodebase(JobInterface $job) {
    $arguments = $job->getBuildVars();
    // Check if the source codebase directory has been specified
    if (empty($arguments['DCI_CodeBase'])) {
      // If no explicit codebase provided, assume we are using the code in the local directory.
      $arguments['DCI_CodeBase'] = "./";
      $job->setBuildVars($arguments);
    }
    else {
      $job->getOutput()->writeln("<comment>Using codebase directory defined in DCI_CodeBase: <options=bold>${arguments['DCI_CodeBase']}</options=bold></comment>");
    }
  }

  public function setupWorkingDir(JobInterface $job) {
    $arguments = $job->getBuildVars();
    // Check if the target working directory has been specified.
    if (empty($arguments['DCI_CheckoutDir'])) {
      // If no explicit working directory provided, we generate one in the system temporary directory.
      $tmpdir = $this->create_tempdir($job, sys_get_temp_dir() . '/drupalci/', $job->jobtype . "-");
      if (!$tmpdir) {
        // Error creating checkout directory
        $job->errorOutput("Error", "Failure encountered while attempting to create a local checkout directory");
        return FALSE;
      }
      $job->getOutput()->writeln("<comment>Checkout directory created at <info>$tmpdir</info></comment>");
      $arguments['DCI_CheckoutDir'] = $tmpdir;
      $job->setBuildVars($arguments);
    }
    elseif ($arguments['DCI_CheckoutDir'] != $arguments['DCI_CodeBase']) {
      // We ensure the checkout directory is within the system temporary directory, to ensure
      // that we don't provide access to the entire file system.

      // Create checkout directory
      $result = $this->create_local_checkout_dir($job);
      // Pass through any errors encountered while creating the directory
      if ($result == -1) {
        return -1;
      }
    }
    // Update the checkout directory in the class object
    $job->setWorkingDir($arguments['DCI_CheckoutDir']);
  }

  protected function create_tempdir(JobInterface $job, $dir=NULL,$prefix=NULL) {
    // PHP seems to have trouble creating temporary unique directories with the appropriate permissions,
    // So we create a temp file to get the unique filename, then mkdir a directory in it's place.
    $prefix = empty($prefix) ? "drupalci-" : $prefix;
    $tmpdir = ($dir && is_dir($dir)) ? $dir : sys_get_temp_dir();
    $tempname = tempnam($tmpdir, $prefix);
    if (empty($tempname)) {
      // Unable to create temp filename
      $job->errorOutput("Error", "Unable to create temporary directory inside of $tmpdir.");
      return;
    }
    $tempdir = $tempname;
    unlink($tempname);
    if (mkdir($tempdir)) {
      return $tempdir;
    }
    else {
      // Unable to create temp directory
      $job->errorOutput("Error", "Error encountered while attempting to create temporary directory $tempdir.");
      return;
    }
  }

  protected function create_local_checkout_dir(JobInterface $job) {
    $arguments = $job->getBuildVars();
    $directory = $arguments['DCI_CheckoutDir'];
    $tempdir = sys_get_temp_dir();

    // Prefix the system temp dir on the DCI_CheckoutDir variable if needed
    if (strpos($directory, $tempdir) !== 0) {
      // If not, prefix the system temp directory on the variable.
      if ($directory[0] != "/") {
        $directory = "/" . $directory;
      }
      $arguments['DCI_CheckoutDir'] = $tempdir . $directory;
      $job->setBuildVars($arguments);
    }

    // Check if the DCI_CheckoutDir exists within the /tmp directory, or create it if not
    $path = realpath($arguments['DCI_CheckoutDir']);
    if ($path !== FALSE) {
      // Directory exists.  Check that we're still in /tmp
      if (!$this->validate_checkout_dir($job)) {
        // Something bad happened.  Attempt to transverse out of the /tmp dir, perhaps?
        $job->errorOutput("Error", "Detected an invalid local checkout directory.  The checkout directory must reside somewhere within the system temporary file directory.");
        return;
      }
      else {
        // Directory is within the system temp dir.
        $job->getOutput()->writeln("<comment>Found existing local checkout directory <info>$path</info></comment>");
        return;
      }
    }
    elseif ($path === FALSE) {
      // Directory doesn't exist, so create it.
      $directory = $arguments['DCI_CheckoutDir'];
      mkdir($directory, 0777, true);
      $job->getOutput()->writeln("<comment>Checkout Directory created at <info>$directory</info>");
      // Ensure we are under the system temp dir
      if (!$this->validate_checkout_dir($job)) {
        // Something bad happened.  Attempt to transverse out of the /tmp dir, perhaps?
        $job->errorOutput("Error", "DCI_CheckoutDir must reside somewhere within the system temporary file directory. You may wish to manually remove the directory created above.");
        return;
      }
    }
  }

  public function validate_checkout_dir(JobInterface $job) {
    $arguments = $job->getBuildVars();
    $path = realpath($arguments['DCI_CheckoutDir']);
    $tmpdir = sys_get_temp_dir();
    if (strpos($path, $tmpdir) === 0) {
      return TRUE;
    }
    return FALSE;
  }

}
