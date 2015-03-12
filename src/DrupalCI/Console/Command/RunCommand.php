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

    // Get the list of job types.
    $jobs = $this->discoverJobs();

    // Validate the passed job type.
    if (!isset($jobs[$job_type])) {
      $output->writeln("The job type '$job_type' does not exist.");
      return;
    }

    $job = $this->getJob($job_type, $job_type);

    // Link the job to our $output variable, so that jobs can display their work.
    $job->setOutput($output);

    // Load the job definition, environment defaults, and any job-specific configuration steps which need to occur
    // TODO: If passed a job definition source file as a command argument, pass it in to the configure function
    $job->configure();
    if ($job->error_status != 0) {
      // Step returned an error.  Halt execution.
      // TODO: Graceful handling of early exit states.
      $output->writeln("<error>Job halted.</error>");
      $output->writeln("<comment>Exiting job due to an invalid return code during job build step: <options=bold>'configure'</options=bold></comment>");
      return;
    }

    $build_steps = $job->build_steps();

    foreach ($build_steps as $step) {
      $job->{$step}();
      if ($job->error_status != 0) {
        // Step returned an error.  Halt execution.
        // TODO: Graceful handling of early exit states.
        $output->writeln("<error>Job halted.</error>");
        $output->writeln("<comment>Exiting job due to an invalid return code during job build step: <options=bold>'$step'</options=bold></comment>");
        break;
      }
    }
  }

  /**
   * Discovers the list of available jobs.
   */
  protected function discoverJobs() {
    $dir = 'src/DrupalCI/Jobs';
    $job_definitions = [];
    foreach (new \DirectoryIterator($dir) as $file) {
      if ($file->isDir() && !$file->isDot()) {
        $job_type = $file->getFilename();
        $job_namespaces = ["DrupalCI\\Jobs\\$job_type" => ["$dir/$job_type"]];
        $discovery  = new AnnotatedClassDiscovery($job_namespaces, 'Drupal\Component\Annotation\PluginID');
        $job_definitions[$job_type] = $discovery->getDefinitions();
      }
    }
    return $job_definitions;
  }

  /**
   * @return \DrupalCI\Plugin\PluginBase
   */
  protected function getJob($type, $plugin_id, $configuration = []) {
    if (!isset($this->pluginDefinitions)) {
      $this->pluginDefinitions = $this->discoverJobs();
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

}
