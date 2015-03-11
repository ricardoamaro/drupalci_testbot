<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\setup\Checkout
 */

namespace DrupalCI\Plugin\setup;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("checkout")
 */
class Checkout extends PluginBase {

  /**
   * {@inheritdoc}
   */
  public function run() {
    echo 'run git checkout';
  }
}
