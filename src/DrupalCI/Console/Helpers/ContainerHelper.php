<?php

/**
 * @file
 * DrupalCI Container helper class.
 */

namespace DrupalCI\Console\Helpers;

use DrupalCI\Console\Helpers\DrupalCIHelperBase;
use Symfony\Component\Process\Process;

class ContainerHelper extends DrupalCIHelperBase {

  /**
   * {@inheritdoc}
   */
  public function getContainers($type){
    // TODO: Make sure we're starting from the drupalci root
    $option = array();
    $containers = glob('containers/'.$type.'/*', GLOB_ONLYDIR);
    foreach ($containers as $container) {
      $option['drupalci/' . explode('/', $container)[2]] = $container;
    }
    return $option;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllContainers() {
    $options = $this->getDBContainers() + $this->getWebContainers() + $this->getBaseContainers();
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

  /**
   * {@inheritdoc}
   */
  public function getBaseContainers() {
    return $this->getContainers('base');
  }

  /**
   * {@inheritdoc}
   */
  public function containerExists($container) {
    $containers = $this->getAllContainers();
    return in_array($container, array_keys($containers));
  }

  /**
   * {@inheritdoc}
   */
  public function startContainer($container) {
    $containers = $this->getAllContainers();
    $name = 'drupalci/' . explode('/', $container)[1];
    $dir = $containers[$name];
    $cmd = "cd " . $dir . " && sudo ./run-server.sh";
    $process = new Process($cmd);

    try {
      $process->mustRun();
      echo $process->getOutput();
    } catch (ProcessFailedException $e) {
      echo $e->getMessage();
    }
    return;
  }
}
