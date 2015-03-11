<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\postinstall\Command
 *
 * Processes "postinstall: command:" instructions from within a job definition.
 */

namespace DrupalCI\Plugin\PostInstall;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("postinstall_command")
 */
class Command extends CommandBase {

  /**
   * {@inheritdoc}
   */
  public function run() {
    echo 'run postinstall_command';
  }

}
