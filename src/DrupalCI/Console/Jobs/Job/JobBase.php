<?php
/**
 * @file
 * Base Job class for DrupalCI.
 */

namespace DrupalCI\Console\Jobs\Job;


use DrupalCI\Console\Jobs\Job\Component\Configurator;
use DrupalCI\Console\Jobs\Job\Component\EnvironmentValidator;
use DrupalCI\Console\Jobs\Job\Component\ParameterValidator;
use DrupalCI\Console\Jobs\Job\Component\SetupDirectoriesComponent;
use Symfony\Component\Process\Process;
use DrupalCI\Console\Jobs\ContainerBase;
use DrupalCI\Console\Helpers\ContainerHelper;

class JobBase extends ContainerBase {

  // Defines the job type
  public $jobtype = 'base';

  // Defines argument variable names which are valid for this job type
  public $available_arguments = array();

  // Defines platform defaults which apply for all jobs.  (Can still be overridden by per-job defaults)
  public $platform_defaults = array(
    "DCI_CodeBase" => "./",
    // DCI_CheckoutDir defaults to a random directory in the system temp directory.
  );

  // Defines the default arguments which are valid for this job type
  public $default_arguments = array();

  // Defines the required arguments which are necessary for this job type
  // Format:  array('ENV_VARIABLE_NAME' => 'CONFIG_FILE_LOCATION'), where
  // CONFIG_FILE_LOCATION is a colon-separated nested location for the
  // equivalent var in a job definition file.
  public $required_arguments = array(
    // eg:   'DCI_DBTYPE' => 'environment:db'
  );

  // Placeholder which holds the parsed job definition file for this job
  public $job_definition = NULL;

  // Error status
  public $error_status = 0;

  // Default working directory
  public $working_dir = "./";

  // Holds build variables which need to be persisted between build steps
  public $build_vars = array();

  // Retrieves the build variables for this job
  public function get_buildvars() {
    return $this->build_vars;
  }

  // Sets the build variables for this job
  public function set_buildvars($build_vars) {
    $this->build_vars = $build_vars;
  }

  // Retrieves a single build variable for this job
  public function get_buildvar($build_var) {
    return $this->build_vars[$build_var];
  }

  // Sets a single build variable for this job
  public function set_buildvar($build_var, $value) {
    $this->build_vars[$build_var] = $value;
  }

  // Stores the calling command's output buffer
  public $output;

  // Sets the output buffer
  public function setOutput($output) {
    $this->output = $output;
  }

  // Defines the default build_steps for this job type
  public function build_steps() {
    return array(
      'validate',
      'checkout',
      'environment',
      //'setup',
      //'install',
      //'validate_install',
      //'execute',
      //'complete',
      //'success',
      //'failure'
    );
  }

  // Compile the complete job definition
  // DrupalCI jobs are controlled via a hierarchy of configuration settings, which define the behaviour of the platform while running DrupalCI jobs.  This hierarchy is defined as follows, which each level overriding the previous:
  // 1. Out-of-the-box DrupalCI defaults
  // 2. Local overrides defined in ~/.drupalci/config
  // 3. 'DCI_' namespaced environment variable overrides
  // 4. Test-specific overrides passed inside a DrupalCI test definition (e.g. .drupalci.yml)
  // 5. Custom overrides located inside a test definition defined via the $source variable when calling this function.
  public function configure($source = NULL) {
    $configurator = new Configurator();
    $configurator->configure($this, $source);
  }

  public function validate() {
    $this->output->write("<comment>Validating test parameters ... </comment>");
    $validator = new ParameterValidator();
    $result = $validator->validate($this);
    if ($result) {
      $this->output->writeln("<info>PASSED</info>");
    }
    return;
  }

  public function environment() {
    $this->output->writeln("<comment>Validating environment parameters ...</comment>");
    // The 'environment' step determines which containers are going to be
    // required, validates that the appropriate container images exist, and
    // starts any required service containers.
    $validator = new EnvironmentValidator();
    $validator->build_container_names($this);
    $validator->validate_container_names($this);
    $validator->start_service_containers($this);
  }

  /*
   *  The setup stage will be responsible for:
   * - Perform checkouts (setup_checkout)
   * - Perform fetches  (setup_fetch)
   * - Apply patches  (setup_patch)
   */
  public function setup() {
    // Setup codebase and working directories
    $setup = new SetupDirectoriesComponent();
    $setup->setup_codebase($this);
    $setup->setup_working_dir($this);
    // Bail if we don't have a setup stage in the job definition.
    if (empty($this->job_definition['setup'])) {
      return;
    }
    // Run through the job definition file setup stages.
    $this->do_setup();
  }

  public function do_setup() {
    $setup = $this->job_definition['setup'];
    foreach ($setup as $step => $details) {
      $func = "setup_" . $step;
      if (!isset($details[0])) {
        // Non-numeric array found ... assume we have only one iteration.
        // We wrap this in an array in order to handle both singletons and
        // arrays with the same code.
        $details = array($details);
      }
      foreach ($details as $iteration => $detail) {
        $this->$func($detail);
        // Handle errors encountered during sub-function execution.
        if ($this->error_status != 0) {
          echo "Received failed return code from function $func.";
          return;
        }
      }
    }
    return;
  }



  protected function checkout_local_to_working() {
    // Load arguments
    $arguments = $this->get_buildvars();
    $srcdir = $arguments['DCI_CodeBase'];
    $targetdir = $arguments['DCI_CheckoutDir'];
    // TODO: Prompt for confirmation.
    // TODO: Should we restrict the Working/Checkout variables to local use only?
    // TODO: Additional validation

    $this->output->write("<comment>Copying files to the local checkout directory ... </comment>");
    $result = exec("cp -r $srcdir $targetdir");
    if (is_null($result)) {
      $this->error_output("Failed", "Error encountered while attempting to copy code to the local checkout directory.");
      return;
    }
    $this->output->writeln("<comment>DONE</comment>");
  }

  protected function checkout_git_to_working() {
    // Load arguments
    $arguments = $this->get_buildvars();

    // See if a specific branch has been supplied.  If not, default to 'master'.
    if (empty($arguments['DCI_GitBranch'])) {
      $arguments['DCI_GitBranch'] = "master";
      $this->set_buildvars($arguments);
    }

    $repodir = $arguments['DCI_CodeBase'];
    $targetdir = $arguments['DCI_CheckoutDir'];
    $branch = $arguments['DCI_GitBranch'];

    // TODO: Sanitize the directory and branch parameters to prevent people from adding to the clone command.

    $cmd = "git clone -b $branch $repodir $targetdir";

    $this->output->writeln("<comment>Cloning repository from <info>$repodir</info> ... </comment>");
    exec($cmd, $cmdout, $result);

    if ($result !== 0) {
      $this->error_output("Failed", "Error encountered while attempting to clone remote repository.  Git return code: <info>$result</info>");
      return;
    }
    $this->output->writeln("<comment>Checkout directory populated.</comment>");
  }


  protected function setup_checkout($details) {
    $this->output->writeln("<info>Entering setup_checkout().</info>");
    // TODO: Ensure $details contains all required parameters
    $protocol = isset($details['protocol']) ? $details['protocol'] : 'git';
    $func = "setup_checkout_" . $protocol;
    return $this->$func($details);
  }

  protected function setup_checkout_local($details) {
    $this->output->writeln("<info>Entering setup_checkout_local().</info>");
    $srcdir = isset($details['srcdir']) ? $details['srcdir'] : './';
    $workingdir = $this->working_dir;
    $checkoutdir = isset($details['checkout_dir']) ? $details['checkout_dir'] : $workingdir;
    // TODO: Ensure we don't end up with double slashes
    // Validate target directory.  Must be within workingdir.
    if (!($directory = $this->validate_directory($checkoutdir))) {
      // Invalidate checkout directory
      $this->error_output("Error", "The checkout directory <info>$directory</info> is invalid.");
      return;
    }
    $this->output->write("<comment>Copying files from <options=bold>$srcdir</options=bold> to the local checkout directory <options=bold>$directory</options=bold> ... </comment>");
    exec("cp -r $srcdir/* $directory", $cmdoutput, $result);
    if (is_null($result)) {
      $this->error_output("Failed", "Error encountered while attempting to copy code to the local checkout directory.");
      return;
    }
    $this->output->writeln("<comment>DONE</comment>");
  }

  protected function setup_checkout_git($details) {
    $this->output->writeln("<info>Entering setup_checkout_git().</info>");
    $repo = isset($details['repo']) ? $details['repo'] : 'git://drupalcode.org/project/drupal.git';
    $gitbranch = isset($details['branch']) ? $details['branch'] : 'master';
    $gitdepth = isset($details['depth']) ? $details['depth'] : NULL;
    $workingdir = $this->working_dir;

    $checkoutdir = isset($details['checkout_dir']) ? $details['checkout_dir'] : $workingdir;
    // TODO: Ensure we don't end up with double slashes
    // Validate target directory.  Must be within workingdir.
    if (!($directory = $this->validate_directory($checkoutdir))) {
      // Invalid checkout directory
      $this->error_output("Error", "The checkout directory <info>$directory</info> is invalid.");
      return;
    }
    $this->output->writeln("<comment>Performing git checkout of $repo $gitbranch branch to $directory.</comment>");

    $cmd = "git clone -b $gitbranch $repo $directory";
    if (!is_null($gitdepth)) {
      $cmd .=" --depth=$gitdepth";
    }
    exec($cmd, $cmdoutput, $result);
    if ($result !==0) {
      // Git threw an error.
      $this->error_output("Checkout failed", "The git checkout returned an error.");
      // TODO: Pass on the actual return value for the git checkout
      return;
    }
    $this->output->writeln("<comment>Checkout complete.</comment>");
  }

  protected function setup_fetch($details) {
    $this->output->writeln("<info>Entering setup_fetch().</info>");
    // URL and target directory
    // TODO: Ensure $details contains all required parameters
    if (empty($details['url'])) {
      $this->error_output("Error", "No valid target file provided for fetch command.");
      return;
    }
    $url = $details['url'];
    $workingdir = realpath($this->working_dir);
    $fetchdir = (!empty($details['fetch_dir'])) ? $details['fetch_dir'] : $workingdir;
    if (!($directory = $this->validate_directory($fetchdir))) {
      // Invalid checkout directory
      $this->error_output("Error", "The fetch directory <info>$directory</info> is invalid.");
      return;
    }
    $info = pathinfo($url);
    $destfile = $directory . "/" . $info['basename'];
    $contents = file_get_contents($url);
    if ($contents === FALSE) {
      $this->error_output("Error", "An error was encountered while attempting to fetch <info>$url</info>.");
      return;
    }
    if (file_put_contents($destfile, $contents) === FALSE) {
      $this->error_output("Error", "An error was encountered while attempting to write <info>$url</info> to <info>$directory</info>");
      return FALSE;
    }
    $this->output->writeln("<comment>Fetch of <options=bold>$url</options=bold> to <options=bold>$destfile</options=bold> complete.</comment>");
  }

  protected function setup_patch($details) {
    $this->output->writeln("<info>Entering setup_patch().</info>");
    if (empty($details['patch_file'])) {
      $this->error_output("Error", "No valid patch file provided for the patch command.");
      return;
    }
    $workingdir = realpath($this->working_dir);
    $patchfile = $details['patch_file'];
    $patchdir = (!empty($details['patch_dir'])) ? $details['patch_dir'] : $workingdir;
    // Validate target directory.
    if (!($directory = $this->validate_directory($patchdir))) {
      // Invalid checkout directory
      $this->error_output("Error", "The patch directory <info>$directory</info> is invalid.");
      return;
    }
    $cmd = "patch -p1 -i $patchfile -d $directory";

    exec($cmd, $cmdoutput, $result);
    if ($result !==0) {
      // The command threw an error.
      $this->error_output("Patch failed", "The patch attempt returned an error.");
      // TODO: Pass on the actual return value for the patch attempt
      return;
    }
    $this->output->writeln("<comment>Patch <options=bold>$patchfile</options=bold> applied to directory <options=bold>$directory</options=bold></comment>");
  }

  protected function validate_directory($dir) {
    // Validate target directory.  Must be within workingdir.
    $working_dir = $this->working_dir;
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
      $this->error_output("Error", "The checkout directory <info>$directory</info> is invalid.");
      return;
    }

    // Return the updated directory value.
    return $directory;
  }

  // Note:  After setup has run, the rest of these need to happen on the container ... so instead of
  // executing directly, we store as instructions for the container to execute.  Perhaps we can have the
  // container parse these out of the .drupalci.yml file directly???

  public function install() {
/*
  install:
    command: pre_install_script.php
    composer:
        action: install
    drupal_install:
        action: install
    command: post_install_script.php
*/
    // TODO: Do these go in a scripts directory passed into each container?
    // TODO: Figure out how we can get these executed on the container directly.
    // Change logic to build out a list of instructions on the container side.
    // Essentially, we need to build the contents of start.sh dynamically!


    /*
    // Bail if we don't have an install stage in the job definition.
    if (empty($this->job_definition['install'])) {
      return;
    }

    $install = $this->job_definition['install'];
    foreach ($install as $step => $details) {
      $func = "install_" . $step;
      if (!isset($details[0])) {
        // Non-numeric array found ... assume we have only one iteration.
        // We wrap this in an array in order to handle both singletons and
        // arrays with the same code.
        $details = array($details);
      }
      foreach ($details as $iteration => $detail) {
        //$result = $this->$func($detail);
        $this->$func($detail);
        // Handle errors encountered during sub-function execution.
        if ($this->error_status != 0) {
          echo "Received failed return code from function $func.";
          return;
        }
      }
    }
    */
    return;
  }

  protected function install_command($details) {

  }

  public function validate_install() {
    // Validate that any required linked containers are actually running.
    return;
  }

  public function execute() {
    return;
  }

  public function complete() {
    // Run any post-execute clean-up or notification scripts, as desired.
    return;
  }

  public function success() {
    // Run any post-execute clean-up or notification scripts, which are
    // intended to be run only upon success.
    return;
  }

  public function failure() {
    // Run any post-execute clean-up or notification scripts, which are
    // intended to be run only upon failure.
    return;
  }

  protected function error_output($type = 'Error', $message = 'DrupalCI has encountered an error.') {
    if (!empty($type)) {
      $this->output->writeln("<error>$type</error>");
    }
    if (!empty($message)) {
      $this->output->writeln("<comment>$message</comment>");
    }
    $this->error_status = -1;
  }

  public function shell_command($cmd) {
    $process = new Process($cmd);
    $process->setTimeout(3600*6);
    $process->setIdleTimeout(3600);
    $process->run(function ($type, $buffer) {
        $this->output->writeln($buffer);
    });
   }

}