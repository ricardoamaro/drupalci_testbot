<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\preexecute\Command
 *
 * Processes "preexecute: command:" instructions from within a job definition.
 */

namespace DrupalCI\Plugin\PreExecute;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("preexecute_command")
 */
class Command extends CommandBase {

  /**
   * {@inheritdoc}
   */
  public function run() {
    echo 'run preexecute_command';
  }

}
