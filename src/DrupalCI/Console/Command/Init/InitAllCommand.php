<?php

/**
 * @file
 * Command class for init.
 */

namespace DrupalCI\Console\Command\Init;

//use Symfony\Component\Console\Command\Command as SymfonyCommand;
use DrupalCI\Console\Command\DrupalCICommandBase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;

class InitAllCommand extends DrupalCICommandBase {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('init')
      ->setDescription('Setup the DrupalCI Environment with sane defaults for testing')
      #->addOption(
      #  'dbtype', '', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Database types to support'
      #3)
      #->addOption('php_version', '', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'PHP Versions to support', array('5.4'))
      #->addOption('force', 'f', InputOption::VALUE_NONE, 'Override a previous setup')
    ;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output) {
    # Generate array of general arguments to pass downstream
    $options = array();
    $options['--quiet'] = $input->getOption('quiet');
    $options['--verbose'] = $input->getOption('verbose');
    $options['--ansi'] = $input->getOption('ansi');
    $options['--no-ansi'] = $input->getOption('no-ansi');
    $options['--no-interaction'] = $input->getOption('no-interaction');

    # Validate/Install dependencies
    $cmd = $this->getApplication()->find('init:dependencies');
    $arguments = array(
      'command' => 'init:dependencies',
      '--dbtype' => ($dbtype = $input->getOption('dbtype')) ? $dbtype : NULL,
    );
    $cmdinput = new ArrayInput($arguments + $options);
    $returnCode = $cmd->run($cmdinput, $output);
    # TODO: Error Handling

    # Validate/Install Docker
    $cmd = $this->getApplication()->find('init:docker');
    $cmdinput = new ArrayInput(array('command' => 'init:docker') + $options);
    $returnCode = $cmd->run($cmdinput, $output);
    # TODO: Error Handling

    # Generate Base Containers
    $cmd = $this->getApplication()->find('init:base');
    $cmdinput = new ArrayInput(array('command' => 'init:base') + $options);
    $cmd->run($cmdinput, $output);
    # TODO: Error Handling

    # Generate Database Containers
    $cmd = $this->getApplication()->find('init:database');
    $arguments = array(
      'command' => 'init:database',
      '--dbtype' => ($dbtype = $input->getOption('dbtype')) ? $dbtype : NULL,
    );
    $cmdinput = new ArrayInput($arguments + $options);
    $returnCode = $cmd->run($cmdinput, $output);
    # TODO: Error Handling

    # Generate Web Containers
    $cmd = $this->getApplication()->find('init:web');
    $cmdinput = new ArrayInput(array('command' => 'init:web') + $options);
    $cmd->run($cmdinput, $output);
    # TODO: Error Handling

  }
}
