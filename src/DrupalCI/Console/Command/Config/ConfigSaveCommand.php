<?php

/**
 * @file
 * Command class for config:save.
 */

namespace DrupalCI\Console\Command\Config;

use DrupalCI\Console\Command\DrupalCICommandBase;
use DrupalCI\Console\Helpers\ConfigHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 *   save <configset>  Saves the current set of local testing default overrides
 *                       as a new configuration set with the provided name,
 *                       storing the result in ~/.drupalci/configs/<configset>.
 */
class ConfigSaveCommand extends DrupalCICommandBase {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('config:save')
      ->setDescription('Save a config set.')
      ->addArgument('configset_name', InputArgument::REQUIRED, 'Config set.');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output) {
    $config_name = $input->getArgument('configset_name');
    $helper = new ConfigHelper();
    $configsets = $helper->getAllConfigSets();
    // Ensure we have a 'current' config
    $current = $helper->getCurrentConfigSetParsed();
    if (empty($current)) {
      $output->writeln("<error>Unable to save an empty configuration set.</error>");
      $output->writeln("<info>Use the <option=bold>'drupalci config:set [variablename]=[value]'</option=bold> command to set some configuration defaults before attempting to save a new config set.</info>");
      return;
    }
    // Check if configset name already exists
    if (in_array($config_name, array_keys($configsets))) {
      // Prompt the user that this will overwrite the existing configuration setting file
      $qhelper = $this->getHelper('question');
      $output->writeln("<error>The <option=bold>$config_name</option=bold> config set already exists.</error>");
      $output->writeln("<info>Continuing will overwrite the existing file with the current configuration values.</info>");
      $message = "<question>Are you sure you wish to continue? (yes/no)</question> ";
      $question = new ConfirmationQuestion($message, false);
      if (!$qhelper->ask($input, $output, $question)) {
        $output->writeln("<comment>Action cancelled.</comment>");
        return;
      }
    }
    $helper->saveCurrentConfig($config_name);
  }
}
