<?php

/**
 * @file
 * Command class for config:reset.
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
 *   reset <setting>   Clears local default overrides by resetting the value of
 *                       the associated environment variable to the drupalci
 *                       defaults.  Also supports 'ALL'.
 */
class ConfigResetCommand extends DrupalCICommandBase {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('config:reset')
      ->setDescription('Reset environment variables.')
      ->addArgument('setting', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Config set.');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output) {
    // Get available config sets
    $helper = new ConfigHelper();
    $qhelper = $this->getHelper('question');
    $configsets = $helper->getAllConfigSets();
    // Get default config sets
    $defaultsets = $helper->getDefaultConfigSets();
    $homedir = getenv('HOME');
    $configdir = $homedir . "/.drupalci/configs/";
    // TODO: configdir absolute path
    $sourcedir = "./configsets/";
    // Check if passed argument is 'all'
    $names = $input->getArgument('setting');
    if (in_array('all', $names)) {
      $names = array_keys($configsets);
    }
    // Is passed config set valid?
    foreach ($names as $name) {
      // Is passed config one of the default sets?
      if (in_array($name, array_keys($defaultsets))) {
        // TODO: Prompt user (You are about to overwrite the $name configuration set. (Y/N/All)
        $output->writeln("<comment>This action will overwrite any local changes you have made to the <options=bold>$name</options=bold> configuration set.</comment>");
        $question = new ConfirmationQuestion("<question>Do you wish to continue? (yes/no)</question> ", false);
        if (!$qhelper->ask($input, $output, $question)) {
          continue;
        }
        // Copy defaultset from code dir to ~/.drupalci/config
        $output->writeln("<comment>Resetting the <options=bold>$name</options=bold> configuration set.</comment>");
        $file = $sourcedir . $name;
        copy($file, $configdir . $name);
      }
      elseif (in_array($name, array_keys($configsets))) {
        // TODO: Prompt user (This action will delete the $name configuration set
        $output->writeln("<comment>This action will delete the <options=bold>$name</options=bold> configuration set.</comment>");
        $question = new ConfirmationQuestion("<question>Do you wish to continue? (yes/no)</question> ", false);
        if (!$qhelper->ask($input, $output, $question)) {
          continue;
        }
        // Delete configset file
        $file = $configdir . $name;
        unlink($file);
      }
      else {
        // TODO: Prompt user (Invalid configuration set)
        $output->writeln("<error>The '$name' configuration set does not exist.</error></comment>");
      }
    }
  }
}
