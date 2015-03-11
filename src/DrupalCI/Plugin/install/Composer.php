<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\install\Composer
 *
 * Processes "install: composer:" instructions from within a job definition.
 */

namespace DrupalCI\Plugin\install;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("install_composer")
 */
class Composer extends ComposerBase {

  /**
   * {@inheritdoc}
   */
  public function run() {
    echo 'run install_composer';
  }

}
