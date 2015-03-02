<?php
/**
 * Created by PhpStorm.
 * User: Jeremy
 * Date: 3/1/15
 * Time: 11:05 PM
 */

namespace DrupalCI\Console\Jobs\Job\Component;


use DrupalCI\Console\Helpers\ContainerHelper;

class EnvironmentValidator {

  public function build_container_names($job) {
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

  protected function env_containers_from_env($job) {
    $containers = array();
    $job->output->writeln("<comment>Parsing environment variables to determine required containers.</comment>");
    // Retrieve environment-related variables from the job arguments
    $dbtype = $job->build_vars['DCI_DBTYPE'];
    $dbver = $job->build_vars['DCI_DBVER'];
    $phpversion = $job->build_vars['DCI_PHPVERSION'];
    $containers['php'][$phpversion] = "drupalci/php-$phpversion";
    $job->output->writeln("<info>Adding container: <options=bold>drupalci/php-$phpversion</options=bold></info>");
    $containers['db'][$dbtype . "-" . $dbver] = "drupalci/$dbtype-$dbver";
    $job->output->writeln("<info>Adding container: <options=bold>drupalci/$dbtype-$dbver</options=bold></info>");
    return $containers;
  }

  protected function env_containers_from_file($job) {
    $config = $job->job_definition['environment'];
    $job->output->writeln("<comment>Evaluating container requirements as defined in job definition file ...</comment>");
    $containers = array();

    // Determine required php containers
    if (!empty($config['php'])) {
      // May be a string if one version required, or array if multiple
      if (is_array($config['php'])) {
        foreach ($config['php'] as $phpversion) {
          // TODO: Make the drupalci prefix a variable (overrideable to use custom containers)
          $containers['php']["$phpversion"] = "drupalci/php-$phpversion";
          $job->output->writeln("<info>Adding container: <options=bold>drupalci/php-$phpversion</options=bold></info>");
        }
      }
      else {
        $phpversion = $config['php'];
        $containers['php']["$phpversion"] = "drupalci/php-$phpversion";
        $job->output->writeln("<info>Adding container: <options=bold>drupalci/php-$phpversion</options=bold></info>");
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
          $job->output->writeln("<info>Adding container: <options=bold>drupalci/$dbversion</options=bold></info>");
        }
      }
      else {
        $dbversion = $config['db'];
        $containers['db']["$dbversion"] = "drupalci/$dbversion";
        $job->output->writeln("<info>Adding container: <options=bold>drupalci/$dbversion</options=bold></info>");
      }
    }
    return $containers;
  }

  public function validate_container_names($job) {
    // Verify that the appropriate container images exist
    $job->output->writeln("<comment>Ensuring appropriate container images exist.</comment>");
    $helper = new ContainerHelper();
    foreach ($job->build_vars['DCI_Container_Images'] as $type => $containers) {
      foreach ($containers as $key => $image) {
        if (!$helper->containerExists($image)) {
          // Error: No such container image
          $job->error_output("Failed", "Required container image <options=bold>'$image'</options=bold> does not exist.");
          // TODO: Robust error handling.
          return;
        }
      }
    }
    return TRUE;
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

}