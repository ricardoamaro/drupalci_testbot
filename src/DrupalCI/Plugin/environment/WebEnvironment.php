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
class WebEnvironment extends EnvironmentBase {
  // TODO: Do we want to extend PHPEnvironment here?

  /**
   * {@inheritdoc}
   */
  public function run() {
    echo 'run web_environment';
  }

  // TODO: Grab checkout source code from DrupalCI/Console/Job/Component/EnvironmentValidator.php and SimpletestJob.php
}