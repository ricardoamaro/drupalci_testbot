<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\environment\PhpEnvironment
 *
 * Processes "environment: php:" parameters from within a job definition,
 * ensures appropriate Docker container images exist, and defines the
 * appropriate execution container for communication back to JobBase.
 */

namespace DrupalCI\Plugin\setup;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("php_environment")
 */
class PhpEnvironment extends PluginBase {

  /**
   * {@inheritdoc}
   */
  public function run() {
    echo 'run php_environment';
  }

  // TODO: Grab checkout source code from DrupalCI/Console/Job/Component/EnvironmentValidator.php
}