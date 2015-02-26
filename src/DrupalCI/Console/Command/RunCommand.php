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

    $job = $jobs[$job_type];

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
   *
   * @return \Symfony\Component\Console\Command\Command[]
   *   An array of job commands.
   */
  protected function discoverJobs() {
    $path = __DIR__ . '/../Jobs';
    // RecursiveDirectoryIterator recurses into directories and returns an
    // iterator for each directory. RecursiveIteratorIterator then iterates over
    // each of the directory iterators, which consecutively return the files in
    // each directory.
    $directory = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS));

    $jobs = [];
    foreach ($directory as $file) {
      if (!$file->isDir() && $file->isReadable() && $file->getExtension() === 'yml') {
        $job_type = $file->getBasename('.yml');
        // Get the job type definition.
        $job_type_definition = Yaml::parse(file_get_contents($file->getPathname()));

        if (!isset($job_type_definition['class'])) {
          // This does not mean the caller did something wrong, but there is a
          // misconfiguration so throwing an exception seems fine?!
          throw new \Exception("The $job_type definition must specify a class in {$file->getPathname()}");
        }

        // Instantiate the job type class.
        // Pass the job definition to the job constructor.
        $jobs[$job_type] = new $job_type_definition['class']($job_type_definition);
      }
    }
    return $jobs;
  }

}
