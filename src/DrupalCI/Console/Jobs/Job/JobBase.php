<?php
/**
 * @file
 * Base Job class for DrupalCI.
 */

namespace DrupalCI\Console\Jobs\Job;


use DrupalCI\Console\Jobs\Job\Component\Configurator;
use DrupalCI\Console\Jobs\Job\Component\EnvironmentValidator;
use DrupalCI\Console\Jobs\Job\Component\ParameterValidator;
use DrupalCI\Console\Jobs\Job\Component\SetupComponent;
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
    $presetup = new SetupDirectoriesComponent();
    $presetup->setup_codebase($this);
    $presetup->setup_working_dir($this);
    // Run through the job definition file setup stages.
    $setup = new SetupComponent();
    $setup->execute($this);
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

  public function error_output($type = 'Error', $message = 'DrupalCI has encountered an error.') {
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