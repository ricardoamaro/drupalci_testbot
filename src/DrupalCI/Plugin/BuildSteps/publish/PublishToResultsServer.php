<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\BuildSteps\publish\PublishToDrupalCIServer
 *
 * Processes "publish: drupalci_server:" instructions from within a job
 * definition. Gathers the resulting job artifacts and pushes them to a
 * DrupalCI Results server.
 */

namespace DrupalCI\Plugin\BuildSteps\publish;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("publish_to_drupalci_server")
 */
class PublishToDrupalCIServer extends PluginBase {

  /**
   * {@inheritdoc}
   */
  public function run() {
    echo 'run publish_to_drupalci_server';
  }

}
