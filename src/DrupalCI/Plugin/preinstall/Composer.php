<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\preinstall\Composer
 *
 * Processes "preinstall: composer:" instructions from within a job definition.
 */

namespace DrupalCI\Plugin\preinstall;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("preinstall_composer")
 */
class Composer extends ComposerBase {

  /**
   * {@inheritdoc}
   */
  public function run() {
    echo 'run preinstall_composer';
  }

}
