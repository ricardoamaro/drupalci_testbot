<?php
/**
 * @file
 * Base Job class for DrupalCI.
 */

namespace DrupalCI\Console\Jobs;

use Symfony\Component\Process\Process;

class JobBase extends ContainerBase {

  // Defines the job type
  public $jobtype = 'base';

  // Defines argument variable names which are valid for this job type
  protected $available_arguments = array();

  // Defines the default arguments which are valid for this job type
  protected $default_arguments = array();

  // Holds the compiled argument list for this particular job
  public $arguments;

  // Holds build variables which need to be persisted between build steps
  protected $build_vars;

  // Stores the calling command's output buffer
  protected $output;

  // Sets the output buffer
  public function setOutput($output) {
    $this->output = $output;
  }

  // Defines the default build_steps for this job type
  public function build_steps() {
    return array(
      'configure',
      'validate',
      'environment',
      'setup',
      'install',
      'validate_install',
      'execute',
      'complete',
      'success',
      'failure'
    );
  }

  // Wrapper function to get the argument list for this job
  public function get_arguments() {
    return $this->arguments;
  }

  // Wrapper function to get the build variables for this job
  public function get_buildvars() {
    return $this->build_vars;
  }

  // Individual functions for each build step
  public function configure() {
    return;
  }

  public function validate() {
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