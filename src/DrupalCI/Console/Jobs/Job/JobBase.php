<?php
/**
 * @file
 * Base Job class for DrupalCI.
 */

namespace DrupalCI\Console\Jobs\Job;

use DrupalCI\Console\Helpers\ConfigHelper;
use Symfony\Component\Process\Process;
use DrupalCI\Console\Jobs\ContainerBase;
use DrupalCI\Console\Jobs\Definition\JobDefinition;

class JobBase extends ContainerBase {

  // Defines the job type
  public $jobtype = 'base';

  // Defines argument variable names which are valid for this job type
  protected $available_arguments = array();

  // Defines the default arguments which are valid for this job type
  protected $default_arguments = array();

  // Defines the required arguments which are necessary for this job type
  protected $required_arguments = array();

  // Holds the compiled argument list for this particular job
  public $arguments;

  // Retrieve the argument list for this job
  public function get_arguments() {
    return $this->arguments;
  }

  // Set the argument list for this job
  public function set_arguments($arguments) {
    $this->arguments = $arguments;
  }

  // Merge the passed arguments into the existing argument list
  public function merge_arguments($arguments) {
    $existing = $this->arguments;
    $this->arguments = $arguments + $existing;
  }

  // Holds build variables which need to be persisted between build steps
  protected $build_vars;

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
  protected $output;

  // Sets the output buffer
  public function setOutput($output) {
    $this->output = $output;
  }

  // Individual functions for each build step
  public function configure($source = NULL) {
    // Get and parse test definitions
    // DrupalCI jobs are controlled via a hierarchy of configuration settings, which define the behaviour of the platform while running DrupalCI jobs.  This hierarchy is defined as follows, which each level overriding the previous:
    // 1. Out-of-the-box DrupalCI defaults
    // 2. Local overrides defined in ~/.drupalci/config
    // 3. 'DCI_' namespaced environment variable overrides
    // 4. Test-specific overrides passed inside a DrupalCI test definition (e.g. .drupalci.yml)
    // 5. Custom overrides located inside a test definition defined via the $source variable when calling this function.

    $confighelper = new ConfigHelper();

    // Load job defaults
    $default_args = $this->default_arguments;
    if (!empty($default_args)) {
      $this->output->writeln("<comment>Loading default test parameters for this job type.</comment>");
    }

    // Load DrupalCI local config overrides
    $local_args = $confighelper->getCurrentConfigSetParsed();
    if (!empty($local_args)) {
      $this->output->writeln("<comment>Loading test parameters from DrupalCI local config overrides.</comment>");
    }

    // Load "DCI_ namespaced" environment variable overrides
    $environment_args = $confighelper->getCurrentEnvVars();
    if (!empty($environment_args)) {
      $this->output->writeln("<comment>Loading test parameters from namespaced environment variable overrides.</comment>");
    }

    $config = $environment_args + $local_args + $default_args;

    // Retrieve test definition file
    if (isset($source)) {
      $config['explicit_source'] = $source;
    }
    $definition_file = $this->getDefinitionFile($config);

    $definition_args = array();

    // Load test definition file
    if (!empty($definition_file)) {
      $this->output->writeln("<comment>Loading test parameters from build file: </comment><info>$definition_file</info>");
      $job = new JobDefinition();
      $result = $job->load($definition_file);
      if ($result == -1) {
        // Error loading definition file.
        $this->output->writeln("<error>FAILED:</error> <info>Unable to parse build file.</info>");
        // TODO: Robust error handling
        return -1;
      };
      $definition_args = $job->getParameters();
      if (empty($definition_args)) {
        $definition_args = array();
      }
    }

    // Load command line arguments
    // TODO: Routine for loading command line arguments.
    // TODO: How do we pull arguments off the drupalci command, when in a job class?
    // $cli_args = $somehelper->loadCLIargs();
    $cli_args = array();
    if (!empty($environment_args)) {
      $this->output->writeln("<comment>Loading test parameters from command line arguments.</comment>");
    }

    $config = $cli_args + $definition_args + $environment_args + $local_args + $default_args;
    $this->arguments = $config;

    // TODO: Load any initial build_vars
    // $this->build_vars = array('foo'=>'bar');

    return;
  }

  protected function getDefinitionFile($config) {
    $definition_file = "";

    // DrupalCI file-based test definition overrides can come from a number of sources:
    // 1. A file location explicitly passed into the config function
    if (!empty($config['explicit_source'])) {
      // TODO: Validate passed filename
      $definition_file = $config['explicit_source'];
    }
    // 2. A .drupalci.yml file located in the current directory
    elseif (file_exists('.drupalci.yml')) {
      $definition_file = ".drupalci.yml";
    }
    // 3. A file location stored in the 'DCI_BuildFile' environment variable
    elseif (!empty($config['DCI_BuildFile'])) {
      $definition_file = $config['DCI_BuildFile'];
    }
    return $definition_file;
  }


  // Defines the default build_steps for this job type
  public function build_steps() {
    return array(
      'validate',
      //'checkout',
      //'environment',
      //'setup',
      //'install',
      //'validate_install',
      //'execute',
      //'complete',
      //'success',
      //'failure'
    );
  }

  public function validate() {
    $this->output->write("<comment>Validating test parameters ... </comment>");
    // TODO: Ensure that all 'required' arguments are defined
    foreach ($this->required_arguments as $arg) {
      if (empty($this->arguments[$arg])) {
        $this->output->writeln("<error>FAILED</error>");
        $this->output->writeln("<info>Required test parameter <options=bold>'$arg'</options=bold> not found.</info>");
        // TODO: Graceful handling of failed exit states
        return -1;
      }
    }
    // TODO: Strip out arguments which are not defined in the 'Available' arguments array
    $this->output->writeln("<info>PASSED</info>");
    return;
  }

  public function environment() {
    return;
  }

  public function setup() {
    return;
  }

  public function install() {
    return;
  }

  public function validate_install() {
    return;
  }

  public function execute() {
    return;
  }

  public function complete() {
    return;
  }

  public function success() {
    return;
  }

  public function failure() {
    return;
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