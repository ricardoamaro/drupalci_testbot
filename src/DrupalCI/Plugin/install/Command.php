<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\install\Command
 *
 * Processes "install: command:" instructions from within a job definition.
 */

namespace DrupalCI\Plugin\install;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("install_command")
 */
class Command extends CommandBase {

  /**
   * {@inheritdoc}
   */
  public function run() {
    echo 'run install_command';
  }

}
