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
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 *   clear               Used to remove a configuration variable from the
 *                       current configuration set.
 */
class ConfigClearCommand extends DrupalCICommandBase {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('config:clear')
      ->setDescription('Reset/remove a single config variable.')
      ->addArgument('variable', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Variable name to remove from the config.');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output) {
    // Retrieve passed argument
    $arguments = $input->getArgument('variable');
    $helper = new ConfigHelper();

    // Retrieve current config
    $config = $helper->getCurrentConfigSetParsed();

    foreach ($arguments as $argument) {
      // Check that the variable exists
      if (!array_key_exists($argument, $config)) {
        $output->writeln("<info>The <option=bold>$argument</option=bold> variable does not exist.  No action taken.");
      }
      else {
        // Prompt the user that this will overwrite the existing setting
        $qhelper = $this->getHelper('question');
        $output->writeln("<info>This will remove the <option=bold>$argument</option=bold> variable from your current configuration set.</info>");
        $message = "<question>Are you sure you wish to continue? (yes/no)</question> ";
        $question = new ConfirmationQuestion($message, false);
        if (!$qhelper->ask($input, $output, $question)) {
          $output->writeln("<comment>Action cancelled.</comment>");
          return;
        }
        $helper->clearConfigVariable($argument);
        $output->writeln("<comment>The <info>$argument</info> variable has been deleted from the current config set.</comment>");
      }
    }
  }
}