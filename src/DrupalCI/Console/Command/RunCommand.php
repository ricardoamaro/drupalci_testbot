<?php

/**
 * @file
 * Command class for run.
 */

namespace DrupalCI\Console\Command;

use DrupalCI\Console\Command\DrupalCICommandBase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

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
      ->addArgument('job', InputArgument::REQUIRED, 'Job definition.')
      ->addOption('something', '', InputOption::VALUE_NONE, 'See TODOs.');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output) {
    $this->showArguments($input, $output);

    // Determine what job type is being run (based on passed argument)

    // Validate job type as one of our valid job types
        // Get list of job types (directories from /jobs)
        // Validate passed job type

    // Instantiate the $jobtype class
        // Get the $jobtype config
            // Is this a .yml file, or defined in the class?
        // Parse the $jobtype config
            // Get the container list for that job type
                // Differentiate between mandatory and optional?
            // Load the default build steps for that job type
                // $buildsteps = $jobtype->buildsteps();
                    // Options:
                    // environment (env), pre-install (pre-install), install, pre-execute (pre-script), execute (script), post-success, post-fail, post-execute
                        //  e.g. pre-install might contain container validation, install containing container creation, execute containing the docker command.
                        // We could have a default drupalci_job class which has metadata and other info common to all job types.

    // For each build step:
        // Run $jobtype->buildstep
    // Next
  }
}
