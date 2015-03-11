<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\configure\TravisToDrupalCIDefinition
 *
 * Given a travisCI job definition file, generates the DrupalCI equivalent.
 */

namespace DrupalCI\Plugin\configure;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("travis_to_drupalci_definition")
 */
class TravisToDrupalCIDefinition extends PluginBase {

  /**
   * {@inheritdoc}
   */
  public function run() {
    echo 'run configure travis_to_drupalci_definition';
  }
}