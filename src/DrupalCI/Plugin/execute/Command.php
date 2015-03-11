<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\execute\ExecuteCommand
 *
 * Processes "execute: command:" instructions from within a job definition.
 */

namespace DrupalCI\Plugin\execute;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("execute_command")
 */
class Command extends CommandBase {

  /**
   * {@inheritdoc}
   */
  public function run() {
    echo 'run execute_command';
  }

}
