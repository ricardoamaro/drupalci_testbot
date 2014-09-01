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

/**
 *   show <configset>  Outputs the testing default configuration overrides from
 *                       a given ~/.drupalci/configs/<configset> config set, or
 *                       if <configset> is not specified, the current
 *                       configuration (a combination of drupalci defaults,
 *                       config set overrides, and manual overrides established
 *                       via the 'set' command).
 */
class ConfigShowCommand extends DrupalCICommandBase {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('config:show')
      ->setDescription('Output a config set.')
      ->addArgument('setting', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Config set.');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output) {
    // TODO: Ensure that configurations have been initialized

    // Get available config sets
    $helper = new ConfigHelper();
    $configsets = $helper->getAllConfigSets();
    $homedir = getenv('HOME');
    // Check if passed argument is 'all'
    $names = $input->getArgument('setting');
    if (empty($names)) {
      // If no argument passed, prompt the user for which config set to display
      $qhelper = $this->getHelper('question');
      $message = "<question>Choose the number corresponding to which configuration set(s) to display:</question> ";
      $output->writeln($message);
      $message="<comment>Separate multiple values with commas.</comment>";
      $options = array_merge(array_keys($configsets), array('current', 'all'));
      $question = new ChoiceQuestion($message, $options, 0);
      $question->setMultiselect(TRUE);
      $names = $qhelper->ask($input, $output, $question);
    }

    if (in_array('all', $names)) {
      $names = array_keys($configsets);
    }
    // Is passed config set valid?
    foreach ($names as $key => $name) {
      if ($name == 'current') {
        $env_vars = $helper->getCurrentEnvVars();
        $output->writeln("<info>---------------- Start config set: <options=bold>CURRENT DCI ENVIRONMENT</options=bold></info> ----------------</info>");
        $output->writeln("<comment;options=bold>Defined in ~/.drupalci/config:</comment;options=bold>");
        $contents = $helper->getCurrentConfigSetContents();
        foreach ($contents as $line) {
          $parsed = explode("=", $line);
          if (!empty($parsed[0]) && !empty($parsed[1])) {
            $output->writeln("<comment>" . strtoupper($parsed[0]) . ": </comment><info>" . $parsed[1] . "</info>");
          }
        }
        if (!empty($env_vars)) {
          $output->writeln("<comment;options=bold>Defined in Environment Variables:</comment;options=bold>");
          foreach ($env_vars as $key => $value) {
            $output->writeln("<comment>" . strtoupper($key) . ": </comment><info>" . $value . "</info>");
          }
          $output->writeln("<info>------------ End config set: <options=bold>CURRENT DCI ENVIRONMENT</options=bold></info> ----------------</info>");
          $output->writeln('');
        }
      }
      elseif (in_array($name, array_keys($configsets))) {
        $contents = file_get_contents($configsets[$name]);
        $output->writeln("<info>---------------- Start config set: <options=bold>$name</options=bold></info> ----------------</info>");
        $output->writeln($contents);
        $output->writeln("<info>------------ End config set: <options=bold>$name</options=bold></info> ----------------</info>");
        $output->writeln('');
      }
      else {
        $output->writeln("<error>Configuration set '$name' not found.  Skipping.</error>");
      }
    }
  }
}
