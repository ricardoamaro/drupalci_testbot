<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\preinstall\Command
 *
 * Processes "preinstall: command:" instructions from within a job definition.
 */

namespace DrupalCI\Plugin\PreInstall;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("preinstall_command")
 */
class Command extends CommandBase {

  /**
   * {@inheritdoc}
   */
  public function run() {
    echo 'run preinstall_command';
  }

}
