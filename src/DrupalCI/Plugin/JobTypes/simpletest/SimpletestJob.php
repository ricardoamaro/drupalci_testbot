<?php

/**
 * @file
 * Job class for SimpleTest jobs on DrupalCI.
 */

namespace DrupalCI\Plugin\JobTypes\simpletest;

use DrupalCI\Plugin\JobTypes\Component\EnvironmentValidator;
use DrupalCI\Plugin\JobTypes\JobBase;

/**
 * @PluginID("simpletest")
 */

class SimpletestJob extends JobBase {

  /**
   * @var string
   */
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
  public $availableArguments = array(
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

  public $defaultArguments = array();

  public $requiredArguments = array(
    'DCI_DBTYPE' => 'environment:db',
    'DCI_DBVER' => 'environment:db',
    'DCI_PHPVERSION' => 'environment:php',
  );

  public function buildSteps() {
    // TODO: buildSteps() is now legacy, left over from before the March 2015 refactoring.
    // To be replaced with a default job definition array.
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


  public function compatibility_bridge() {
    // TODO: This is legacy, from before the March 2015 refactoring.  Initial purpose was to maintain backwards compatibility with the Proof of Concept implementation scripts.

    // Loads items from the job definition file into environment variables in
    // order to remain compatible with the simpletest run.sh script.
    // TODO: At some point, we should deprecate non "drupalci run simpletest"
    // methods of kicking off execution of the script, which will allow us to
    // remove the validation code from the bash script itself (in favor of
    // validate step within the job classes.
    // TODO: This presumes only one db type; but may need to be expanded for multiple.
    if (empty($this->jobDefinition)) {
      return;
    }
    $definition = $this->jobDefinition['environment'];
    // We need to set a number of parameters on the command line in order to
    // prevent the bash script from overriding them
    $cmd_prefix = "";
    if (!empty($definition['db'])) {
      $dbtype = explode("-", $definition['db'][0]);
      $cmd_prefix = "DCI_DBTYPE=" . $dbtype[0] . " DCI_DBVER=" . $dbtype[1];
    }
    else {
      $cmd_prefix = "DCI_DBTYPE= DCI_DBVER= ";
    }

    $phpver = (!empty($definition['php'])) ? $definition['php'][0] : "";

    $cmd_prefix .= (!empty($phpver)) ? " DCI_PHPVERSION=$phpver " : " DCI_PHPVERSION= ";

    if (!empty($this->jobDefinition['variables'])) {
      $buildvars = $this->jobDefinition['variables'];
      foreach ($buildvars as $key => $value) {
        $cmd_prefix .= "$key=$value ";
      }
    }

    // Set working directory
    if (!empty($this->workingDirectory)) {
      $cmd_prefix .= " DCI_WORKSPACE=" . $this->workingDirectory . " ";
    }

    $this->cmd_prefix = $cmd_prefix;



  }

  protected $cmd_prefix = "";

  public function execute() {
    // TODO: This is legacy, from before the March 2015 refactoring.  Leftover from the Proof of Concept implementation.
    $cmd = "sudo " . $this->cmd_prefix . "./containers/web/run.sh";
    // Execute the simpletest testing bash script
    $this->shellCommand($cmd);
    return;
  }

}
