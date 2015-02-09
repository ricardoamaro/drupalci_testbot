<?php
/**
 * @file
 * Job class for PrivateTravis jobs on DrupalCI.
 */

namespace DrupalCI\Console\Jobs\Job\Travis;

use DrupalCI\Console\Jobs\Job\JobBase;
use DrupalCI\Console\Jobs\Definition\JobDefinition;
use PrivateTravis\Permutation;

class TravisJob extends JobBase {

  protected $namespace = "privatetravis";

  public function set_namespace($namespace) {
    $this->namespace = $namespace;
  }

  public function get_namespace() {
    return $this->namespace;
  }

  protected $travisfile = ".travisci.yml";

  public function set_travisfile($travisfile) {
    $this->travisfile = $travisfile;
  }

  public function get_travisfile() {
    return $this->travisfile;
  }

  protected $commands = array(
    'env',
    'before_script',
    'script',
  );

  public function set_commands($commands) {
    $this->commands = $commands;
  }

  public function get_commands($commands) {
    return $this->commands;
  }

  // Placeholder to store the parsed .travisci.yml file contents
  protected $travis_parsed = NULL;

  // Placeholder to store the auto-generated bootstrap script
  protected $script = "";

  protected $allowed_arguments = array(
    'DCI_TravisFile',
    'DCI_Namespace',
    'DCI_TravisCommands',
    'DCI_FastFail',
    'DCI_Privileged',
  );

  protected $default_arguments = array(
    'DCI_TravisFile' => '.travisci.yml',
  );

  protected $required_arguments = array(
    'DCI_TravisFile',
  );

  public function build_steps() {
    return array(
      'validate',
      'environment',
      'setup',
      //'install',
      //'validate_install',
      'execute',
      //'complete',
      //'success',
      //'failure'
    );
  }

  public function environment() {

    // Load and parse travis file
    $travis_file = $this->arguments['DCI_TravisFile'];
    $this->output->writeln("<comment>Loading test build parameters from travis file: </comment><info>$travis_file</info>");
    $build = new JobDefinition();
    $result = $build->load($travis_file);
    if ($result == -1) {
      // Error loading definition file.
      $this->output->writeln("<error>FAILED:</error> <info>Unable to parse travis file.</info>");
      // TODO: Robust error handling
      return -1;
    };
    $travis = $build->getParameters();

    // Store the parsed contents so we can reference them in later build steps
    $this->travis_parsed = $travis;

    $namespace = $this->namespace;
    $command = implode(' ', $this->commands);

    // Evaluate the parsed travis file
    $language = !empty($travis['language']) ? $travis['language'] : '';
    $language_versions = !empty($travis[$language]) ? $travis[$language] : array();
    $services = !empty($travis['services']) ? $travis['services'] : array();

    // TODO: Add Fast Fail logic

    // Get the permutations
    foreach ($language_versions as $language_version) {
      $this->output->writeln("<info>### Building permutation <options=bold>'$language$language_version'</options=bold> ###</info>");

      $permutation = new Permutation();
      $permutation->setNamespace($namespace);
      $permutation->setLanguage($language . ':' . $language_version);
      $permutation->setCommand($command);
      if (!empty($this->arguments['DCI_Privileged'])) {
        $permutation->setPrivileged(true);
      }
      $permutation->addServices($services);

      // Print.
      $lines = $permutation->build();
      foreach ($lines as $line) {
        $this->output->writeln($line);
        if (!empty($this->script)) {
          $this->script .= " && ";
        }
        $this->script .= $line;
      }
    }
  }

  public function setup() {
    // Generate a local copy of the codebase to be tested
    // Check if we have an environment variable specifying the codebase location (Jenkins)
    // Check if we have a DCI config variable specifying the test directory location
    // Generate a copy of the codebase (either via git checkout, or local copy
      // Do we need to have a variable that determines whether you operate on local codebase or not?
      //


  }

  public function execute() {
    // Execute Script
    //foreach ($this->script as $cmd) {
      $this->shell_command($this->script);
    //}
  }

}