<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\Buildsteps\environment\EnvironmentBase
 */

namespace DrupalCI\Plugin\Buildsteps\environment;

use Docker\Exception\ImageNotFoundException;
use DrupalCI\Plugin\PluginBase;

/**
 * Base class for 'environment' plugins.
 */
abstract class EnvironmentBase extends PluginBase {

  public function validateImageNames($containers, $job) {
    // Verify that the appropriate container images exist
    $job->output->writeln("<comment>Validating container images exist</comment>");
    $docker = $job->getDocker();
    $manager = $docker->getImageManager();
    foreach ($containers as $key => $image_name) {
      $name = $image_name['image'];
      try {
        $image = $manager->find($name);
      }
      catch (ImageNotFoundException $e) {
        $job->error_output("Failed", "Required container image <options=bold>'$name'</options=bold> not found.");
        // TODO: Robust error handling.
        return FALSE;
      }
      $id = substr($image->getID(), 0, 8);
      $job->output->writeln("<comment>Found image <options=bold>$name</options=bold> with ID <options=bold>$id</options=bold></comment>");
    }
    return TRUE;
  }
/*
  protected function startServiceContainerDaemons($type, &$job) {
    $docker = $job->getDocker();
    $manager = $docker->getContainerManager();
    $instances = array();
    foreach ($manager->findAll() as $running) {
      $repo = $running->getImage()->getRepository();
      $id = substr($running->getID(), 0, 8);
      $instances[$repo] = $id;
    };
    foreach ($job->service_containers[$type] as $key => $image) {
      if (in_array($image['image'], array_keys($instances))) {
        // TODO: Determine service container ports, id, etc, and save it to the job.
        $job->output->writeln("<comment>Found existing <options=bold>${image['image']}</options=bold> service container instance.</comment>");
        // TODO: Load up container parameters
        $container = $manager->find($instances[$image['image']]);
        $container_id = $container->getID();
        $container_name = $container->getName();
        $job->service_containers[$type][$key]['id'] = $container_id;
        $job->service_containers[$type][$key]['name'] = $container_name;
        continue;
      }
      // Container not running, so we'll need to create it.
      $job->output->writeln("<comment>No active <options=bold>${image['image']}</options=bold> service container instances found. Generating new service container.</comment>");
      // Instantiate container
      $container = new Container(['Image' => $image['image']]);
      // Get container configuration, which defines parameters such as exposed ports, etc.
      $config = $job->getContainerConfiguration($image['image']);
      // TODO: Allow classes to modify the default configuration before processing
      // Configure the container
      $this->configureContainer($container, $config[$image['image']]);
      // Create the docker container instance, running as a daemon.
      // TODO: Ensure there are no stopped containers with the same name (currently throws fatal)
      $manager->run($container, function($output, $type) {
          fputs($type === 1 ? STDOUT : STDERR, $output);
      }, [], true);
      $container_id = $container->getID();
      $container_name = $container->getName();
      $job->service_containers[$type][$key]['id'] = $container_id;
      $job->service_containers[$type][$key]['name'] = $container_name;
      $short_id = substr($container_id, 0, 8);
      $job->output->writeln("<comment>Created new <options=bold>${image['image']}</options=bold> container instance with ID <options=bold>$short_id</options=bold></comment>");
    }
  }

  protected function configureContainer($container, $config) {
    if (!empty($config['name'])) {
      $container->setName($config['name']);
    }
    if (!empty($config['exposed_ports'])) {
      $ports = new PortCollection($config['exposed_ports']);
      $container->setExposedPorts($ports);
    }
    // TODO: Process Tmpfs configuration
  }
  */
}
