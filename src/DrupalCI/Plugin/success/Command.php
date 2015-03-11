<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\success\Command
 *
 * Processes "success: command:" instructions from within a job definition.
 */

namespace DrupalCI\Plugin\success;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("success_command")
 */
class Command extends CommandBase {

  /**
   * {@inheritdoc}
   */
  public function run() {
    echo 'run success_command';
  }

}
