<?php

/**
 * @file
 * Command class for init.
 */

namespace DrupalCI\Console\Command\Init;

//use Symfony\Component\Console\Command\Command as SymfonyCommand;
use DrupalCI\Console\Command\DrupalCICommandBase;
use DrupalCI\Console\Helpers\DockerHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class InitDockerCommand extends DrupalCICommandBase {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('init:docker')
      ->setDescription('Validate and/or setup the local Docker installation')
    ;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output) {
    # Check if Docker is installed
    $output->writeln("<info>Executing init:docker</info>");
    $docker = new DockerHelper();
    if ($bin = $docker->locateBinary()) {
      $output->writeln("<comment>Docker binary located at $bin</comment>");
      $docker->getStatus($input, $output);
    }
    else {
      # If not, attempt to install docker
      $output->writeln('<comment>Docker binary not found.</comment>');
      $helper = $this->getHelperSet()->get('question');
      $question = new ConfirmationQuestion('<fg=cyan;bg=blue>DrupalCI will now attempt to install Docker on your system.  Continue (y/n)?</fg=cyan;bg=blue>', FALSE);
      if (!$helper->ask($input, $output, $question)) {
        return;
      }
      $docker->installDocker($output);
    }
  }

}
