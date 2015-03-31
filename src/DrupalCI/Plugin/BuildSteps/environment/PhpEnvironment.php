<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\Buildsteps\environment\PhpEnvironment
 *
 * Processes "environment: php:" parameters from within a job definition,
 * ensures appropriate Docker container images exist, and defines the
 * appropriate execution container for communication back to JobBase.
 */

namespace DrupalCI\Plugin\Buildsteps\environment;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("php")
 */
class PhpEnvironment extends EnvironmentBase {

  /**
   * {@inheritdoc}
   */
  public function run($job, $data) {
    // Data format: '5.5' or array('5.4', '5.5')
    // $data May be a string if one version required, or array if multiple
    // Normalize data to the array format, if necessary
    $data = is_array($data) ? $data : [$data];
    $job->output->writeln("<comment>Parsing required container image names ...</comment>");
    $containers = $this->buildImageNames($data, $job);
    $valid = $this->validateImageNames($containers, $job);
    if (!empty($valid)) {
      $job->executable_containers['php'] = $containers;
      // Actual creation and configuration of the executable containers will occur in the 'execute' plugin.
    }
  }

  public function buildImageNames($data, $job) {
    $php_containers = array();
    foreach ($data as $key => $php_version) {
      $images["php-$php_version"]['image'] = "drupalci/php-$php_version";
      $job->output->writeln("<info>Adding image: <options=bold>drupalci/php-$php_version</options=bold></info>");
    }
    return $images;
  }
}
