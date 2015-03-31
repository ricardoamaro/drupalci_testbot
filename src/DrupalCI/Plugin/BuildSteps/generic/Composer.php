<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\Buildsteps\generic\Composer
 *
 * Processes "[build_step]: composer:" instructions from within a job definition.
 */

namespace DrupalCI\Plugin\Buildsteps\generic;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("composer")
 */
class Composer extends Command {

  /**
   * {@inheritdoc}
   */
  public function run($input, $data) {
    // @TODO http://stackoverflow.com/a/25208897/308851
    $cmd = $this->buildComposerCommand($input);
    parent::run($cmd);
  }

  protected function buildComposerCommand($input) {
    // TODO: Code to interpret the input and output an actual valid composer command
  }

}
