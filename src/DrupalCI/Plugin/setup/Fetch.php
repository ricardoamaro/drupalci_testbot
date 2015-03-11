<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\setup\Fetch
 *
 * Processes "setup: fetch:" instructions from within a job definition.
 */

namespace DrupalCI\Plugin\setup;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("fetch")
 */
class Fetch extends PluginBase {

  /**
   * {@inheritdoc}
   */
  public function run() {
    echo 'run fetch';
  }

  // TODO: Grab fetch source code from DrupalCI/Console/Job/Component/SetupComponent.php
}
