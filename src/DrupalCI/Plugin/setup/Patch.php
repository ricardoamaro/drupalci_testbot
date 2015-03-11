<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\setup\Patch
 *
 * Processes "setup: patch:" instructions from within a job definition.
 */

namespace DrupalCI\Plugin\setup;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("patch")
 */
class Patch extends PluginBase {

  /**
   * {@inheritdoc}
   */
  public function run() {
    echo 'run patch';
  }

  // TODO: Grab patch source code from DrupalCI/Console/Job/Component/SetupComponent.php
}
