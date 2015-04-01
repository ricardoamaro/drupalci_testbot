<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\Buildsteps\environment\DbEnvironment
 *
 * Processes "environment: db:" parameters from within a job definition,
 * ensures appropriate Docker container images exist, and launches any new
 * database service containers as required.
 */

namespace DrupalCI\Plugin\Buildsteps\environment;
use DrupalCI\Plugin\PluginBase;
use DrupalCI\Plugin\Buildsteps\environment\EnvironmentBase;
use Docker\Container;

/**
 * @PluginID("db")
 */
class DbEnvironment extends EnvironmentBase {

  /**
   * {@inheritdoc}
   */
  public function run($job, $data) {
    // Data format: 'mysql-5.5' or array('mysql-5.5', 'pgsql-9.3')
    // $data May be a string if one version required, or array if multiple
    // Normalize data to the array format, if necessary
    $data = is_array($data) ? $data : [$data];
    $job->getOutput()->writeln("<comment>Parsing required container image names ...</comment>");
    $containers = $this->buildImageNames($data, $job);
    $valid = $this->validateImageNames($containers, $job);
    if (!empty($valid)) {
      $job->service_containers['db'] = $containers;
      $job->startServiceContainerDaemons('db');
    }
  }

  public function buildImageNames($data, $job) {
    $db_containers = array();
    foreach ($data as $key => $db_version) {
      $images["db-$db_version"]['image'] = "drupalci/db-$db_version";
      $job->getOutput()->writeln("<info>Adding image: <options=bold>drupalci/db-$db_version</options=bold></info>");
    }
    return $images;
  }

/*

  public function build_db_container_names($job) {

    // Determine whether to use environment variables or definition file to determine what containers are needed
    if (empty($job->job_definition['environment'])) {
      $containers = $this->env_containers_from_env($job);
    }
    else {
      $containers = $this->env_containers_from_file($job);
    }
    if (!empty($containers)) {
      $job->build_vars['DCI_Container_Images'] = $containers;
    }
  }

  protected function env_containers_from_file($job) {
    $config = $job->job_definition['environment'];
    $job->getOutput()->writeln("<comment>Evaluating container requirements as defined in job definition file ...</comment>");
    $containers = array();

    // Determine required php containers
    if (!empty($config['php'])) {
      // May be a string if one version required, or array if multiple
      if (is_array($config['php'])) {
        foreach ($config['php'] as $phpversion) {
          // TODO: Make the drupalci prefix a variable (overrideable to use custom containers)
          $containers['php']["$phpversion"] = "drupalci/php-$phpversion";
          $job->getOutput()->writeln("<info>Adding container: <options=bold>drupalci/php-$phpversion</options=bold></info>");
        }
      }
      else {
        $phpversion = $config['php'];
        $containers['php']["$phpversion"] = "drupalci/php-$phpversion";
        $job->getOutput()->writeln("<info>Adding container: <options=bold>drupalci/php-$phpversion</options=bold></info>");
      }
    }
    else {
      // We assume will always need at least one default PHP container
      $containers['php']['5.5'] = "drupalci/php-5.5";
    }

    // Determine required database containers
    if (!empty($config['db'])) {
      // May be a string if one version required, or array if multiple
      if (is_array($config['db'])) {
        foreach ($config['db'] as $dbversion) {
          $containers['db']["$dbversion"] = "drupalci/$dbversion";
          $job->getOutput()->writeln("<info>Adding container: <options=bold>drupalci/$dbversion</options=bold></info>");
        }
      }
      else {
        $dbversion = $config['db'];
        $containers['db']["$dbversion"] = "drupalci/$dbversion";
        $job->getOutput()->writeln("<info>Adding container: <options=bold>drupalci/$dbversion</options=bold></info>");
      }
    }
    return $containers;
  }

  public function start_service_containers($job) {
    // We need to ensure that any service containers are started.
    $helper = new ContainerHelper();
    if (empty($job->build_vars['DCI_Container_Images']['db'])) {
      // No service containers required.
      return;
    }
    foreach ($job->build_vars['DCI_Container_Images']['db'] as $image) {
      // Start an instance of $image.
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
  }
  // TODO: Grab checkout source code from DrupalCI/Console/Job/Component/EnvironmentValidator.php

  */
}
