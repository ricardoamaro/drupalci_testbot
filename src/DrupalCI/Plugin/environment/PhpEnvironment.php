<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\environment\PhpEnvironment
 *
 * Processes "environment: php:" parameters from within a job definition,
 * ensures appropriate Docker container images exist, and defines the
 * appropriate execution container for communication back to JobBase.
 */

namespace DrupalCI\Plugin\environment;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("php")
 */
class PhpEnvironment extends EnvironmentBase {

  /**
   * {@inheritdoc}
   */
  public function run($job, $data=NULL) {
    echo 'run php_environment';
  }

  // TODO: Grab checkout source code from DrupalCI/Console/Job/Component/EnvironmentValidator.php
}