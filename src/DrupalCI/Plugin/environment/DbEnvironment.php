<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\environment\DbEnvironment
 *
 * Processes "environment: db:" parameters from within a job definition,
 * ensures appropriate Docker container images exist, and launches any new
 * database service containers as required.
 */

namespace DrupalCI\Plugin\setup;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("db_environment")
 */
class DbEnvironment extends PluginBase {

  /**
   * {@inheritdoc}
   */
  public function run() {
    echo 'run db_environment';
  }

  // TODO: Grab checkout source code from DrupalCI/Console/Job/Component/EnvironmentValidator.php
}
