<?php

/**
 * @file
 * Command class for config:set.
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
 *   set <setting=value>
 *                     Sets a default override for a particular configuration
 *                       setting, establishing the appropriate DrupalCI
 *                       namespaced environment variable
 */
class ConfigSetCommand extends DrupalCICommandBase {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('config:set')
      ->setDescription('Set a config variable.')
      ->addArgument('assignment', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Value assignment, such as: name=value');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output) {
    // Retrieve passed argument
    $arguments = $input->getArgument('assignment');
    $helper = new ConfigHelper();

    // Retrieve current config
    $config = $helper->getCurrentConfigSetParsed();

    foreach ($arguments as $argument) {
      // Parse key => value
      $parsed = explode('=', $argument);
      if (count($parsed) != 2) {
        $output->writeln("<error>Unable to parse argument.</error>");
        $output->writeln("<comment>Please provide both a variable name and value formatted as <options=bold>variable_name=variable_value</options=bold></comment>");
        return;
      }
      // TODO: Validate key against a list of allowed variables
      $key = trim($parsed[0]);
      $value = trim($parsed[1]);

      // Check if replacing an existing environment variable
      if ($existing = getenv($key)) {
        // Prompt the user that an existing environment variable exists and can not be overwritten
        $output->writeln("<error>The <option=bold>$key</option=bold> setting has been set via an environment variable and can not be set via the console.</error>");
        $output->writeln("<comment>To override this value, provide it on the command line as part of the drupalci invocation.");
        $output->writeln("<comment>Example: </comment> $key=$value ./drupalci [command]");
      }
      // Check if replacing a value from 'config'
      elseif (in_array($key, array_keys($config))) {
        // Prompt the user that this will overwrite the existing setting
        $qhelper = $this->getHelper('question');
        $output->writeln("<info>The <option=bold>$key</option=bold> variable already exists.</info>");
        $message = "<question>Are you sure you wish to override it? (yes/no)</question> ";
        $question = new ConfirmationQuestion($message, false);
        if (!$qhelper->ask($input, $output, $question)) {
          $output->writeln("<comment>Action cancelled.</comment>");
          return;
        }
        $helper->setConfigVariable($key, $value);
      }
      else {
        // Set the var and provide feedback
        $output->writeln("<info>Setting the value of the <option=bold>$key</option=bold> variable to <option=bold>$value</option=bold>");
        $helper->setConfigVariable($key, $value);
      }
    }
  }

}
