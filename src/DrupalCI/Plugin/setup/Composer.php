<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\setup\Composer
 *
 * Processes "setup: composer:" instructions from within a job definition.
 */

namespace DrupalCI\Plugin\setup;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("setup_composer")
 */
class Composer extends ComposerBase {

  /**
   * {@inheritdoc}
   */
  public function run() {
    echo 'run setup_composer';
  }

}
