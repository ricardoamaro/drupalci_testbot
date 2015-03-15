<?php

/**
 * @file
 * Command class for run.
 */

namespace DrupalCI\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Yaml\Yaml;
use Drupal\Component\Annotation\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;

class RunCommand extends DrupalCICommandBase {

  /**
   * {@inheritdoc}
   *
   * Options:
   *   Will probably be a combination of things taken from environment variables
   *   and job specific options.
   *   TODO: Sort out how to define job-specific options, and be able to import
   *   them into the drupalci command. (Imported from a specially named file in
   *   the job directory, perhaps?) Will need syntax to define required versus
   *   optional options, and their defaults if not specified.
   */
  protected function configure() {
    $this
      ->setName('run')
      ->setDescription('Execute a given job run.')
      ->addArgument('job', InputArgument::REQUIRED, 'Job definition.');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output) {
    // Determine what job type is being run.
    $job_type = $input->getArgument('job');
    $job = $this->getJob($job_type, $job_type);
    // Link our $output variable to the job, so that jobs can display their work.
    $job->setOutput($output);
    // TODO: Create hook to allow for jobtype-specific pre-configuration.
    // We'll need this if we want to (as an example) convert travisci
    // definitions to drupalci definitions.
    // Load the job definition, environment defaults, and any job-specific configuration steps which need to occur
    foreach (['compile_definition', 'validate_definition'] as $step) {
      $this->getPlugin('configure', $step)->run($job, NULL);
    }
    if ($job->error_status != 0) {
      $output->writeln("<error>Job halted due to an error while parsing the job definition file.</error>");
      return;
    }
    // The job should now have a fully merged job definition file, including
    // any local or drupalci defaults not otherwise defined in the passed job
    // definition, located in $job->job_definition
    $definition = $job->job_definition;
    foreach ($definition as $build_step => $step) {
      foreach ($step as $plugin => $data) {
        $this->getPlugin($build_step, $plugin)->run($job, $data);
        if ($job->error_status != 0) {
          // Step returned an error.  Halt execution.
          // TODO: Graceful handling of early exit states.
          $output->writeln("<error>Job halted.</error>");
          $output->writeln("<comment>Exiting job due to an invalid return code during job build step: <options=bold>'$build_step=>$plugin'</options=bold></comment>");
          break;
        }
      }
    }
  }

  /**
   * Discovers the list of available plugins.
   */
  protected function discoverPlugins($dir = 'src/DrupalCI/Plugin', $namespace = 'Plugin') {
    $plugin_definitions = [];
    foreach (new \DirectoryIterator($dir) as $file) {
      if ($file->isDir() && !$file->isDot()) {
        $plugin_type = $file->getFilename();
        $plugin_namespaces = ["DrupalCI\\$namespace\\$plugin_type" => ["$dir/$plugin_type"]];
        $discovery  = new AnnotatedClassDiscovery($plugin_namespaces, 'Drupal\Component\Annotation\PluginID');
        $plugin_definitions[$plugin_type] = $discovery->getDefinitions();
      }
    }
    return $plugin_definitions;
  }

  /**
   * Discovers the list of available job types.
   */
  protected function discoverJobs() {
    return $this->discoverPlugins('src/DrupalCI/Jobs', 'Jobs');
  }

  /**
   * @return \DrupalCI\Plugin\PluginBase
   */
  protected function getPlugin($type, $plugin_id, $configuration = []) {
    if (!isset($this->pluginDefinitions)) {
      $this->pluginDefinitions = $this->discoverPlugins();
    }
    if (!isset($this->plugins[$type][$plugin_id])) {
      if (isset($this->pluginDefinitions[$type][$plugin_id])) {
        $plugin_definition = $this->pluginDefinitions[$type][$plugin_id];
      }
      elseif (isset($this->pluginDefinitions['generic'][$plugin_id])) {
        $plugin_definition = $this->pluginDefinitions['generic'][$plugin_id];
      }
      else {
        throw new PluginNotFoundException("Plugin type $type plugin id $plugin_id not found.");
      }
      $this->plugins[$type][$plugin_id] = new $plugin_definition['class']($configuration, $plugin_id, $plugin_definition);
    }
    return $this->plugins[$type][$plugin_id];
  }

  /**
   * @return \DrupalCI\Plugin\PluginBase
   */
  protected function getJob($type, $plugin_id, $configuration = []) {
    if (!isset($this->jobDefinitions)) {
      $this->jobDefinitions = $this->discoverJobs();
    }
    if (!isset($this->jobs[$type][$plugin_id])) {
      if (isset($this->jobDefinitions[$type][$plugin_id])) {
        $job_definition = $this->jobDefinitions[$type][$plugin_id];
      }
      else {
        throw new PluginNotFoundException("Job type $type plugin id $plugin_id not found.");
      }
      $this->jobs[$type][$plugin_id] = new $job_definition['class']($configuration, $plugin_id, $job_definition);
    }
    return $this->jobs[$type][$plugin_id];
  }
}
