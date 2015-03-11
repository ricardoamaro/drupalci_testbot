<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\failure\Command
 *
 * Processes "failure: command:" instructions from within a job definition.
 */

namespace DrupalCI\Plugin\failure;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("failure_command")
 */
class FailureCommand extends CommandBase {

  /**
   * {@inheritdoc}
   */
  public function run() {
    echo 'run failure_command';
  }

}
