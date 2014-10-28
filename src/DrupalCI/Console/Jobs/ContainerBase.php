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

  public function startContainer($container) {
    // Validate container name
    $helper = new ContainerHelper();
    if (!($helper->containerExists($container))) {
      // TODO: Invalid container.  Throw an exception.
      return FALSE;
    }
    // Start container
    $output = $helper->startContainer($container);
    return $output;
  }

}