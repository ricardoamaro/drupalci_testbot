<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\postexecute\Command
 *
 * Processes "postexecute: command:" instructions from within a job definition.
 */

namespace DrupalCI\Plugin\PostExecute;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("postexecute_command")
 */
class Command extends CommandBase {

  /**
   * {@inheritdoc}
   */
  public function run() {
    echo 'run postexecute_command';
  }

}
