<?php

/**
 * @file
 * Command class for init.
 */

namespace DrupalCI\Console\Command\Init;

//use Symfony\Component\Console\Command\Command as SymfonyCommand;
use DrupalCI\Console\Command\DrupalCICommandBase;
use DrupalCI\Console\Helpers\ContainerHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ChoiceQuestion;

class InitDatabaseContainersCommand extends DrupalCICommandBase {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('init:database')
      ->setDescription('Build initial DrupalCI database containers')
      ->addArgument('container_name', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Docker container image(s) to build.')
    ;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output) {
    $output->writeln("<info>Executing init:database</info>");

    # Generate array of general arguments to pass downstream
    $options = array();
    $options['--quiet'] = $input->getOption('quiet');
    $options['--verbose'] = $input->getOption('verbose');
    $options['--ansi'] = $input->getOption('ansi');
    $options['--no-ansi'] = $input->getOption('no-ansi');
    $options['--no-interaction'] = $input->getOption('no-interaction');

    $helper = new ContainerHelper();
    $containers = $helper->getDbContainers();
    $container_names = array_keys($containers);

    $names = array();
    if ($names = $input->getArgument('container_name')) {
      // We've been passed a container name, validate it
      foreach ($names as $key => $name) {
        if (!in_array($name, $container_names)) {
          // Not a valid db container.  Remove it and warn the user
          unset($names[$key]);
          $output->writeln("<error>Received an invalid db container name. Skipping build of the $name container.");
        }
      }
    }
    else {
      if ($options['--no-interaction']) {
        // Non-interactive mode.  Default to MySql 5.5
        $names = array('mysql-5.5');
      }
      else {
        $names = $this->getDbContainerNames($container_names, $input, $output);
        if (in_array('all', $names)) {
          $names = $container_names;
        }
      }
    }

    if (empty($names)) {
      $output->writeln("<error>No valid database container names provided.  Aborting.");
      return;
    }
    else {
      $cmd = $this->getApplication()->find('build');
      $arguments = array(
        'command' => 'build',
        'container_name' => $names
      );
      $cmdinput = new ArrayInput($arguments + $options);
      $returnCode = $cmd->run($cmdinput, $output);
      // TODO: Error handling
    }
    $output->writeln('');
  }

  protected function getDbContainerNames($containers, InputInterface $input, OutputInterface $output) {
    # Prompt the user
    $helper = $this->getHelperSet()->get('question');
    $containers[] = 'all';
    $question = new ChoiceQuestion(
      '<fg=cyan;bg=blue>Please select the numbers corresponding to which DrupalCI database environments to support.  Separate multiple entries with commas. (Default: [0])</fg=cyan;bg=blue>',
      $containers,
      '2'
    );
    $question->setMultiselect(true);

    $results = $helper->ask($input, $output, $question);

    return $results;
  }
}
