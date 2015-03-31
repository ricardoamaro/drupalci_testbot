<?php

/**
 * @file
 * Command class for run.
 */

namespace DrupalCI\Console\Command;

use DrupalCI\Plugin\PluginManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class RunCommand extends DrupalCICommandBase {

  /**
   * @var \DrupalCI\Plugin\PluginManagerInterface
   */
  protected $buildStepsPluginManager;

  /**
   * @var \DrupalCI\Plugin\PluginManagerInterface
   */
  protected $jobPluginManager;

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

    /** @var $job \DrupalCI\Jobs\JobBase */
    $job = $this->jobPluginManager()->getPlugin($job_type, $job_type);
    // Link our $output variable to the job, so that jobs can display their work.
    $job->setOutput($output);
    // TODO: Create hook to allow for jobtype-specific pre-configuration.
    // We'll need this if we want to (as an example) convert travisci
    // definitions to drupalci definitions.
    // Load the job definition, environment defaults, and any job-specific configuration steps which need to occur
    foreach (['compile_definition', 'validate_definition', 'setup_directories'] as $step) {
      $this->buildstepsPluginManager()->getPlugin('configure', $step)->run($job, NULL);
    }
    if ($job->error_status != 0) {
      $output->writeln("<error>Job halted due to an error while configuring job.</error>");
      return;
    }
    // The job should now have a fully merged job definition file, including
    // any local or drupalci defaults not otherwise defined in the passed job
    // definition, located in $job->job_definition
    $definition = $job->job_definition;
    foreach ($definition as $build_step => $step) {
      foreach ($step as $plugin => $data) {
        $this->buildstepsPluginManager()->getPlugin($build_step, $plugin)->run($job, $data);
        if ($job->error_status != 0) {
          // Step returned an error.  Halt execution.
          // TODO: Graceful handling of early exit states.
          $output->writeln("<error>Job halted.</error>");
          $output->writeln("<comment>Exiting job due to an invalid return code during job build step: <options=bold>'$build_step=>$plugin'</options=bold></comment>");
          break 2;
        }
      }
    }
  }

  /**
   * @return \DrupalCI\Plugin\PluginManagerInterface
   */
  protected function buildstepsPluginManager() {
    if (!isset($this->buildStepsPluginManager)) {
      $this->buildStepsPluginManager = new PluginManager('BuildSteps');
    }
    return $this->buildStepsPluginManager;
  }

    /**
   * @return \DrupalCI\Plugin\PluginManagerInterface
   */
  protected function jobPluginManager() {
    if (!isset($this->jobPluginManager)) {
      $this->jobPluginManager = new PluginManager('Jobs');
    }
    return $this->jobPluginManager;
  }

}
