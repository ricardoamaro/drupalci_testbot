<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\generic\Command
 *
 * Processes "[build_step]: command:" instructions from within a job definition.
 */

namespace DrupalCI\Plugin\generic;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("command")
 */
class Command extends PluginBase {

  /**
   * {@inheritdoc}
   */
  public function run() {
    echo 'run generic_command';
  }

  protected function doCommand($cmd) {
    // TODO: Code to execute given command
  }

  protected function runScript($script) {
    // TODO: Code to execute given script
  }

  // TODO: Other possible functions relating to the command return code, error processing, etc. ?

}
