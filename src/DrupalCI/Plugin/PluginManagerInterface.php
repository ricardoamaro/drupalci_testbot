<?php

/**
 * @file
 * Contains \DrupalCI\Plugin\PluginManagerInterface.
 */

namespace DrupalCI\Plugin;

interface PluginManagerInterface {

  /**
   * @param $type
   * @param $plugin_id
   * @param array $configuration
   * @return PluginBase
   */
  public function getPlugin($type, $plugin_id, $configuration = []);

}
