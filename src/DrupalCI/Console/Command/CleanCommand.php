<?php

/**
 * @file
 * Command class for clean.
 */

namespace DrupalCI\Console\Command;

use DrupalCI\Console\Command\DrupalCICommandBase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class CleanCommand extends DrupalCICommandBase {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('clean')
      ->setDescription('Remove docker images and containers.')
      ->addArgument('type', InputArgument::REQUIRED, 'Type of container to clean.')
      ->addOption('hard', '', InputOption::VALUE_NONE, 'Remove everything, stopping first if neccessary.');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output) {
    $types = array(
      'images', 'containers', 'db', 'web', 'environment',
    );
    $type = $input->getArgument('type');
    if (!in_array($type, $types)) {
      $output->writeln('<error>' . $type . ' is not a legal container type.</error>');
    }
    $this->showArguments($input, $output);
  }

}
