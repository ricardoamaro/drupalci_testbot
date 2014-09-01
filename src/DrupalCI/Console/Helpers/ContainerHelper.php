<?php

/**
 * @file
 * DrupalCI Container helper class.
 */

namespace DrupalCI\Console\Helpers;

use DrupalCI\Console\Helpers\DrupalCIHelperBase;

class ContainerHelper extends DrupalCIHelperBase {

  /**
   * {@inheritdoc}
   */
  public function getContainers($type){
    // TODO: Make sure we're starting from the drupalci root
    $option = array();
    $containers = glob('containers/'.$type.'/*', GLOB_ONLYDIR);
    foreach ($containers as $container) {
      $option[explode('/', $container)[2]] = $container;
    }
    return $option;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllContainers() {
    // $option = getContainers(base);  // TODO: Enable once testbot_base exists
    $options = $this->getContainers('database') + $this->getContainers('web');
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getDbContainers() {
    return $this->getContainers('database');
  }

  /**
   * {@inheritdoc}
   */
  public function getWebContainers() {
    return $this->getContainers('web');
  }

}