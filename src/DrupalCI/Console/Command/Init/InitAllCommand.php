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
      ->addOption('dbtype', '', InputOption::VALUE_OPTIONAL, 'Database types to support')
      ->addOption('phptype', '', InputOption::VALUE_OPTIONAL, 'PHP Versions to support')
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

    $arguments = array(
      'command' => 'init:base',
      'container_name' => array('drupalci/db-base', 'drupalci/web-base'),
    );
    $cmdinput = new ArrayInput($arguments + $options);
    $returnCode = $cmd->run($cmdinput, $output);

    # Generate Database Containers
    $cmd = $this->getApplication()->find('init:database');

    $arguments = array(
      'command' => 'init:datase',
      );

    $dbtype = $input->getOption('dbtype');
    if(isset($dbtype)) {
      $arguments['container_name'] = array($dbtype);
    }

    $cmdinput = new ArrayInput($arguments + $options);
    $returnCode = $cmd->run($cmdinput, $output);
    # TODO: Error Handling

    # Generate Web Containers
    $cmd = $this->getApplication()->find('init:web');

    $arguments = array(
      'command' => 'init:web',
    );

    $phptype = $input->getOption('phptype');
    if(isset($phptype)) {
      $arguments['container_name'] = array($phptype);
    }

    $cmdinput = new ArrayInput($arguments + $options);
    $returnCode = $cmd->run($cmdinput, $output);
    # TODO: Error Handling

    # Generate Base Config
    $cmd = $this->getApplication()->find('init:config');
    $cmdinput = new ArrayInput(array('command' => 'init:config') + $options);
    $returnCode = $cmd->run($cmdinput, $output);
    # TODO: Error Handling

  }
}
