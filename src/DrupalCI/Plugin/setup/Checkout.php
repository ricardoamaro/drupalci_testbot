<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\setup\Checkout
 *
 * Processes "setup: checkout:" instructions from within a job definition.
 */

namespace DrupalCI\Plugin\setup;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("checkout")
 */
class Checkout extends PluginBase {

  /**
   * {@inheritdoc}
   */
  public function run() {
    echo 'run git checkout';
  }

  // TODO: Grab checkout source code from DrupalCI/Console/Job/Component/SetupComponent.php
}
