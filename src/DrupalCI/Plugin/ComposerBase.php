<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\ComposerBase
 *
 * Abstract base class for plugins which exec custom scripts/commands.
 * Processes "plugintype: composer:" instructions from within a job definition.
 */

namespace DrupalCI\Plugin;
use DrupalCI\Plugin\PluginBase;

abstract class ComposerBase extends CommandBase {

  protected function doComposerCommand($input) {
    $cmd = $this->buildComposerCommand($input);
    $this->doCommand($cmd);
  }

  protected function buildComposerCommand($script) {
    // TODO: Code to interpret the input and output an actual valid composer command
  }

}
