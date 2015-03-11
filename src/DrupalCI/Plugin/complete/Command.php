<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\complete\Command
 *
 * Processes "complete: command:" instructions from within a job definition.
 */

namespace DrupalCI\Plugin\complete;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("complete_command")
 */
class Command extends CommandBase {

  /**
   * {@inheritdoc}
   */
  public function run() {
    echo 'run complete_command';
  }

}
