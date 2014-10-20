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
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 *   load <configset>  Clears all drupalci namespaced environment variables and
 *                       establishes new values matching the combination of
 *                       drupalci defaults and overrides from the chosen config
 *                       set, as defined in ~/.drupalci/configs/<configset>.
 */
class ConfigLoadCommand extends DrupalCICommandBase {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('config:load')
      ->setDescription('Load a config set.')
      ->addArgument('configset', InputArgument::OPTIONAL, 'Config set.');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output) {
    $helper = new ConfigHelper();
    $configsets = $helper->getAllConfigSets();
    // Were we passed a configset name?
    $selected = $input->getArgument('configset');

    if (empty($selected)) {
      // If no argument passed, prompt the user for which config set to display
      $qhelper = $this->getHelper('question');
      $message = "<question>Choose the number corresponding to which configuration set to load:</question> ";
      $options = array_keys($configsets);
      $question = new ChoiceQuestion($message, $options, 0);
      $selected = $qhelper->ask($input, $output, $question);
      // TODO: Validate argument is a valid config set
    }

    if (empty($configsets[$selected])) {
      $output->writeln("<error>Unable to load configset. The specified configset does not exist.");
      return;
    }

    $output->writeln("You chose configset: " . $configsets[$selected]);

    $qhelper = $this->getHelper('question');
    $output->writeln("<info>This will wipe out your current DrupalCI defaults and replace them with the values from the <option=bold>$selected</option=bold> configset.</info>");
    $message = "<question>Are you sure you wish to continue? (y/n)</question> ";
    $question = new ConfirmationQuestion($message, false);
    if (!$qhelper->ask($input, $output, $question)) {
      $output->writeln("<comment>Action cancelled.</comment>");
      return;
    }
    $helper->activateConfig($selected);
  }
}
