<?php

/**
 * @file
 * Command class for run.
 */

namespace DrupalCI\Console\Command\Config;

use DrupalCI\Console\Command\DrupalCICommandBase;
use DrupalCI\Console\Helpers\ConfigHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 *   list              Outputs a list of available configuration sets, which
 *                       can be passed to the 'load' command. Includes both
 *                       pre-defined and custom sets. (Output is the list of
 *                       files from ~/.drupalci/configs/*)
 */
class ConfigListCommand extends DrupalCICommandBase {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('config:list')
      ->setDescription('List config sets.');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output) {
    $helper = new ConfigHelper();
    $configsets = array_keys($helper->getAllConfigSets());
    $output->writeln("<comment>Available config sets:</comment>");
    foreach ($configsets as $set) {
      $output->writeln("<info>$set</info>");
    }
  }
}
