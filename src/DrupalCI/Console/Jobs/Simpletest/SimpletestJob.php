<?php

/**
 * @file
 * Job class for SimpleTest jobs on DrupalCI.
 */

namespace DrupalCI\Console\Jobs\Simpletest;

use DrupalCI\Console\Helpers\ContainerHelper;
use DrupalCI\Console\Jobs\JobBase;
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

  protected $default_arguments = array(
    'DCI_DBTYPE' => 'mysql',
    'DCI_DBVER' => '5.5',
    'DCI_PHPVERSION' => '5.4',
  );

  public function build_steps() {
    return array(
      'configure',
      'environment',
      'setup',
      'install',
      'validate',
      'execute',
      'complete',
      'success',
      'failure'
    );
  }

  protected $variables = array();

  /**
   * Populate initial job properties
   */
  public function configure() {
    // Simpletest testing via the bash script pulls it's configuration from a
    // combination of default environment variables, local defaults defined in
    // ~/.drupalci/config, and test definition parameters passed in with the
    // test request.

    // Presumably, there will be a test definition file created with all of the
    // required configuration information; which will be sourced by the bash
    // script.  As such, no configuration logic needs to be performed within
    // this class, other than to ensure that all mandatory job properties are
    // established with appropriate defaults; and loading the test definition
    // parameters into this class so that they may be referenced by the rest of
    // the code while processing the job.

    // Get default arguments
    $defaults = $this->default_arguments;

    // Get job arguments
    $definition = array();   // TODO: Where do we retrieve the test definition from for a simpletest run?

    // Merge defaults and job specific arguments
    $job_arguments = array_merge($defaults, $definition);

    if (!empty($job_arguments)) {
      $this->arguments = $job_arguments;
    }

    // Load any initial build_vars
    // $this->build_vars = array('foo'=>'bar');

    return;
  }

  public function environment() {
    // Retrieve environment-related variables from the job arguments
    $dbtype = $this->arguments['DCI_DBTYPE'];
    $dbver = $this->arguments['DCI_DBVER'];
    $phpversion = $this->arguments['DCI_PHPVERSION'];

    // Determine the web container name
    $this->build_vars['images'][] = array(
      'name' => 'drupalci/web-' . $phpversion,
      'type' => 'execute',
    );

    // Determine the database container name
    $this->build_vars['images'][] = array(
      'name' => 'drupalci/' . $dbtype . '-' . $dbver,
      'type' => 'prereq',
    );

    // Validate the environmental variables from the above list
    // Verify that the appropriate container images exist
    $helper = new ContainerHelper();
    foreach ($this->build_vars['images'] as $image) {
      if (!($helper->containerExists($image['name']))) {
        // Error: No such container
      }
    }
    return;
  }

  public function setup() {
    // Start up any linked containers that need to be running, if they are not
    // running already.
    $output = '';
    foreach ($this->build_vars['images'] as $image) {
      if ($image['type'] == 'prereq') {
        // Start an instance of $image['name'].
        $helper = new ContainerHelper();
        // TODO: Ensure container is not already running!
        $helper->startContainer($image['name']);
        $need_sleep = TRUE;
      }
    }
    // Pause to allow any container services (e.g. mysql) to start up.
    // TODO: This currently pauses even if the container was already found.  Do we need the
    // start_container.sh script to throw an error return code?
    if ($need_sleep) {
      echo "Sleeping 10 seconds to allow container services to start.\n";
      sleep(10);
    }
    return;
  }

  public function install() {
    // Installation is handled by the bash script.
    return;
  }

  public function validate() {
    // Validate that any required linked containers are actually running.
    return;
  }

  public function execute() {
    // Execute the simpletest testing bash script
    $this->shell_command('sudo ./containers/web/run.sh');
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
