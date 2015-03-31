<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\Buildsteps\generic\Drush
 *
 * Processes "[build_step]: drush:" instructions from within a job definition.
 */

namespace DrupalCI\Plugin\Buildsteps\generic;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("drush")
 */
class Drush extends Command {

  /**
   * {@inheritdoc}
   */
  public function run($input) {
    $cmd = $this->buildDrushCommand($input);
    parent::run($cmd);
  }

  protected function buildDrushCommand($input) {
    // TODO: Code to interpret the input and output an actual valid drush command
  }

}
