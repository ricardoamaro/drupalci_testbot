<?php

/**
 * @file
 * Base command class for Drupal CI.
 */

namespace DrupalCI\Console\Command;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Docker\Docker;
use Docker\Http\DockerClient as Client;

/**
 * Just some helpful debugging stuff for now.
 */
class DrupalCICommandBase extends SymfonyCommand {

  // Holds our Docker container manager
  protected $docker;

  protected function showArguments(InputInterface $input, OutputInterface $output) {
    $output->writeln('<info>Arguments:</info>');
    $items = $input->getArguments();
    foreach($items as $name=>$value) {
      $output->writeln(' ' . $name . ': ' . print_r($value, TRUE));
    }
    $output->writeln('<info>Options:</info>');
    $items = $input->getOptions();
    foreach($items as $name=>$value) {
      $output->writeln(' ' . $name . ': ' . print_r($value, TRUE));
    }

  }

  public function getDocker()
  {
    $client = Client::createWithEnv();
    if (null === $this->docker) {
      $this->docker = new Docker($client);
    }
    return $this->docker;
  }

}
