<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\CommandBase
 *
 * Abstract base class for plugins which exec custom scripts/commands.
 * Processes "plugintype: command:" instructions from within a job definition.
 */

namespace DrupalCI\Plugin;
use DrupalCI\Plugin\PluginBase;

abstract class CommandBase extends PluginBase {

  public function doCommand($cmd) {
    // TODO: Code to execute given command
  }

  public function runScript($script) {
    // TODO: Code to execute given script
  }

  // TODO: Other possible functions relating to the command return code, error processing, etc. ?

}
