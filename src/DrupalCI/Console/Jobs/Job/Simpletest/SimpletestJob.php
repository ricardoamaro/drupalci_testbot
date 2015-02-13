<?php

/**
 * @file
 * Job class for SimpleTest jobs on DrupalCI.
 */

namespace DrupalCI\Console\Jobs\Job\Simpletest;

use DrupalCI\Console\Helpers\ContainerHelper;
use DrupalCI\Console\Jobs\Job\JobBase;
use Symfony\Component\Finder\Tests\Iterator\DateRangeFilterIteratorTest;

class SimpletestJob extends JobBase {

  public $jobtype = 'simpletest';

  /**
   * Return a list of argument variables which are relevant to this job type.
   *
   *  DCI_PATCH:         Local or remote Patches to be applied.
   *       Format: patch_location,apply_dir;patch_location,apply_dir;...
   *  DCI_DEPENDENCIES:  Contrib projects to be downloaded & patched.
   *       Format: module1,module2,module2...
   *  DCI_DEPENDENCIES_GIT  Format: gitrepo1,branch;gitrepo2,branch;...
   *  DCI_DEPENDENCIES_TGZ  Format: module1_url.tgz,module1_url.tgz,...
   *  DCI_DRUPALBRANCH:  Default is '8.0.x'
   *  DCI_DRUPALVERSION: Default is '8'
   *  DCI_TESTGROUPS:    Tests to run. Default is '--class NonDefaultBlockAdmin'
   *       A list is available at the root of this project.
   *  DCI_VERBOSE:       Default is 'false'
   *  DCI_DBTYPE:        Default is 'mysql-5.5' from mysql/sqlite/pgsql
   *  DCI_DBVER:         Default is '5.5'.  Used to override the default version for a given database type.
   *  DCI_ENTRYPOINT:    Default is none. Executes other funcionality in the container prepending CMD.
   *  DCI_CMD:           Default is none. Normally use '/bin/bash' to debug the container
   *  DCI_INSTALLER:     Default is none. Try to use core non install tests.
   *  DCI_UPDATEREPO:    Force git pull of Drupal & Drush. Default is 'false'
   *  DCI_IDENTIFIER:    Automated Build Identifier. Only [a-z0-9-_.] are allowed
   *  DCI_REPODIR:       Default is 'HOME/testbotdata'
   *  DCI_DRUPALREPO:    Default is 'http://git.drupal.org/project/drupal.git'
   *  DCI_DRUSHREPO:     Default is 'https://github.com/drush-ops/drush.git'
   *  DCI_BUILDSDIR:     Default is  equal to DCI_REPODIR
   *  DCI_WORKSPACE:     Default is 'HOME/testbotdata/DCI_IDENTIFIER/'
   *  DCI_DBUSER:        Default is 'drupaltestbot'
   *  DCI_DBPASS:        Default is 'drupaltestbotpw'
   *  DCI_DBCONTAINER:   Default is 'drupaltestbot-db-mysql-5.5'
   *  DCI_PHPVERSION:    Default is '5.4'
   *  DCI_CONCURRENCY:   Default is '4'  #How many cpus to use per run
   *  DCI_RUNSCRIPT:     Command to be executed
   */
  protected $available_arguments = array(
    'DCI_PATCH',
    'DCI_DEPENDENCIES',
    'DCI_DEPENDENCIES_GIT',
    'DCI_DEPENDENCIES_TGZ',
    'DCI_DRUPALBRANCH',
    'DCI_DRUPALVERSION',
    'DCI_TESTGROUPS',
    'DCI_VERBOSE',
    'DCI_DBTYPE',
    'DCI_DBVER',
    'DCI_ENTRYPOINT',
    'DCI_CMD',
    'DCI_INSTALLER',
    'DCI_UPDATEREPO',
    'DCI_IDENTIFIER',
    'DCI_REPODIR',
    'DCI_DRUPALREPO',
    'DCI_DRUSHREPO',
    'DCI_BUILDSDIR',
    'DCI_WORKSPACE',
    'DCI_DBUSER',
    'DCI_DBPASS',
    'DCI_DBCONTAINER',
    'DCI_PHPVERSION',
    'DCI_CONCURRENCY',
    'DCI_RUNSCRIPT',
  );

  protected $default_arguments = array();

  protected $required_arguments = array(
    'DCI_DBTYPE' => 'environment:db',
    'DCI_DBVER' => 'environment:db',
    'DCI_PHPVERSION' => 'environment:php',
  );

  public function build_steps() {
    return array(
      'validate',
      'environment',
      'setup',
      //'install',
      //'validate_install',
      'compatibility_bridge',
      'execute',
      //'complete',
      //'success',
      //'failure'
    );
  }

  protected $variables = array();

  public function environment() {
    $this->build_container_names();
    $containers = $this->build_vars["DCI_Container_Images"];
    foreach ($containers['php'] as $phpversion => $container) {
      // TODO: Fix this after moving to the new container stack
      // $containers['php'][$phpversion] = $container . "-web";
      $containers['php'][$phpversion] = "drupalci/web-" . $phpversion;
    }
    $this->build_vars["DCI_Container_Images"] = $containers;
    if (!$this->validate_container_names()) {
      return -1;
    }
    return;
  }

  public function setup() {
    // Start up any linked service containers that need to be running, if they
    // are not running already.
    $output = '';
    foreach ($this->build_vars['DCI_Container_Images']['db'] as $image) {
      // Start an instance of $image['name'].
      $helper = new ContainerHelper();
      // TODO: Ensure container is not already running!
      $helper->startContainer($image);
      $need_sleep = TRUE;
    }
    // Pause to allow any container services (e.g. mysql) to start up.
    // TODO: This currently pauses even if the container was already found.  Do we need the
    // start_container.sh script to throw an error return code?
    if (!empty($need_sleep)) {
      echo "Sleeping 10 seconds to allow container services to start.\n";
      sleep(10);
    }
    return;
  }

  public function compatibility_bridge() {
    // Loads items from the job definition file into environment variables in
    // order to remain compatible with the simpletest run.sh script.
    // TODO: At some point, we should deprecate non "drupalci run simpletest"
    // methods of kicking off execution of the script, which will allow us to
    // remove the validation code from the bash script itself (in favor of
    // validate step within the job classes.
    if (empty($this->job_definition)) {
      return;
    }
    $definition = $this->job_definition['environment'];
    // We need to set a number of parameters on the command line in order to
    // prevent the bash script from overriding them
    $dbtype = explode("-", $definition['db'][0]);
    $phpver = $definition['php'][0];
    $cmd_prefix = "DCI_DBTYPE=" . $dbtype[0] . " DCI_DBVER=" . $dbtype[1] . " DCI_PHPVERSION=" . $phpver . " ";
    $this->cmd_prefix = $cmd_prefix;

  }

  protected $cmd_prefix = "";

  public function install() {
    // Installation is handled by the bash script.
    return;
  }

  public function validate_install() {
    // Validate that any required linked containers are actually running.
    return;
  }

  public function execute() {
    $cmd = "sudo " . $this->cmd_prefix . "./containers/web/run.sh";
    // Execute the simpletest testing bash script
    $this->shell_command($cmd);
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

}
