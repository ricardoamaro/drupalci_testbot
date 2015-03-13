<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\environment\EnvironmentBase
 */

namespace DrupalCI\Plugin\environment;
use Docker\Container;
use DrupalCI\Plugin\PluginBase;
use DrupalCI\Console\Helpers\ContainerHelper;

/**
 * Base class for 'environment' plugins.
 */
abstract class EnvironmentBase extends PluginBase {

  public function validateContainerNames($containers, $job) {
    // Verify that the appropriate container images exist
    $job->output->writeln("<comment>Ensuring appropriate container images exist.</comment>");
    // TODO: Remove ContainerHelper and use docker-php to verify container exists.
    $helper = new ContainerHelper();
    foreach ($containers as $key => $image) {
      if (!$helper->containerExists($image)) {
        // Error: No such container image
        $job->error_output("Failed", "Required container image <options=bold>'$image'</options=bold> does not exist.");
        // TODO: Robust error handling.
        return;
      }
    }
    return TRUE;
  }

  protected function generateContainer($job, $container) {
    $docker = $job->getDocker();
    $instance = new Container(['Image' => $container]);
    echo "Generating container $container";
    // $docker->getContainerManager()->run($instance);
  }
} 