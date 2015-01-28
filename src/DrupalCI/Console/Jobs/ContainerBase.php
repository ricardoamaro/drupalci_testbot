<?php
/**
 * @file
 * Base Container class for DrupalCI.
 *
 * Contains base container functions, intended for consumption within the
 * JobBase container.  These are within their own class simply for ease of
 * administration, and to maintain functional segregation.
 */

namespace DrupalCI\Console\Jobs;

use DrupalCI\Console\Helpers\ContainerHelper;

class ContainerBase {

  protected $helper = NULL;

  public function startContainer($container) {
    if (isNull($this->helper)) {
      $this->helper = new ContainerHelper();
    }
    // Validate container name
    if (!($this->validateContainerName($container))) {
      // TODO: Invalid container.  Throw an exception.
      return FALSE;
    }
    // Start container
    $output = $this->helper->startContainer($container);
    return $output;
  }

  public function stopContainer($container) {
    if (isNull($this->helper)) {
      $this->helper = new ContainerHelper();
    }
    // TODO: Stop Container logic
    $output = $this->helper->stopContainer($container);
    return $output;
  }

  protected function validateContainerName($container) {
    if (isNull($this->helper)) {
      $this->helper = new ContainerHelper();
    }
    return $this->helper->containerExists($container);
  }

}