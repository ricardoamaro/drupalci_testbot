<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\environment\WebEnvironment
 *
 * Processes "environment: web:" parameters from within a job definition,
 * ensures appropriate Docker container images exist, and defines the
 * appropriate execution container for communication back to JobBase.
 */

namespace DrupalCI\Plugin\environment;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("web")
 */
class WebEnvironment extends PhpEnvironment {

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
      $job->executable_containers['web'] = $containers;
      // Actual creation and configuration of the executable containers will occur in the 'execute' plugin.
    }
  }

  public function buildImageNames($data, $job) {
    $php_containers = array();
    foreach ($data as $key => $php_version) {
      $images["web-$php_version"]['image'] = "drupalci/web-$php_version";
      $job->output->writeln("<info>Adding image: <options=bold>drupalci/web-$php_version</options=bold></info>");
    }
    return $images;
  }

}