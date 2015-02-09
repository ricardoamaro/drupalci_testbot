<?php

/**
 * @file
 * Command class for init.
 */

namespace DrupalCI\Console\Command\Init;

//use Symfony\Component\Console\Command\Command as SymfonyCommand;
use DrupalCI\Console\Command\DrupalCICommandBase;
use DrupalCI\Console\Helpers\ConfigHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Finder\Finder;

class InitConfigCommand extends DrupalCICommandBase {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('init:config')
      ->setDescription('Create the default ~/.drupalci directory and default configuration sets.')
      ->addOption(
        'force', '', InputOption::VALUE_NONE, 'Delete all existing configuration sets and reset the DrupalCI environment to its defaults.'
      )
    ;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output) {
    $output->writeln("<info>Executing init:config</info>");

    # Check whether ~/.drupalci directory exists, and force option not called
    # TODO: Parameterize the drupalci directory

    $homedir = getenv('HOME');

    if (file_exists($homedir . "/.drupalci") && !($input->getOption('force'))) {
      # Output 'configuration directory already exists, use --force to reset the DrupalCI environment' message.
      $output->writeln('<error>WARNING: The ~/.drupalci configuration directory already exists.</error>');
      $output->writeln('<comment>Use the --force option to reset your DrupalCI environment back to default.</comment>');
      $output->writeln('<comment>Note that this will wipe out all files in your ~/.drupalci directory, including existing configuration sets.</comment>');
      return;
    }
    else {
      if ($input->getOption('force')) {
        $helper = $this->getHelper('question');
        $output->writeln("<info>This will wipe out all files in your ~/.drupalci directory, including existing configuration sets.</info>");
        $message = "<question>Are you sure you wish to continue with this action? (y/n)</question> ";
        $question = new ConfirmationQuestion($message, false);
        if (!$helper->ask($input, $output, $question)) {
          return;
        }

        // Forcing re-initialization of the DrupalCI environment.
        // Delete existing directory.
        $finder = new Finder();
        $iterator = $finder->files()->in($homedir . '/.drupalci');

        foreach ($iterator as $file) {
          unlink($file);
        }
      }
      // We now have a clean environment.
      // Create directories
      $configsdir = $homedir . "/.drupalci/configs";
      $configlink = $homedir . "/.drupalci/config";
      if (!file_exists($configsdir)) {
        mkdir($configsdir, 0777, true);
        $output->writeln("<info>Created $configsdir directory.</info>");
      }
      else {
        $output->writeln("<info>Re-using existing $configsdir directory.</info>");
      }

      // Copy default files over to the configs directory
      // TODO: Currently using placeholder files.  Populate file contents.
      $finder = new Finder();
      $directory = "./configsets";
      // TODO: This means we can only execute the command from the drupalci
      // directory.  Need to be able to run from anywhere - determine how to
      // get the current script execution directory (not the /bin symlink!)
      // and construct an absolute directory path above.
      $iterator = $finder->files()->in($directory);
      foreach ($iterator as $file) {
        copy($file->getRealPath(), $configsdir . "/" . $file->getFileName() );
      }
      $output->writeln("<info>Created default configuration sets.</info>");

      $helper = new ConfigHelper();
      // Copy a default setting file over to the current config
      $helper->activateConfig('d8_core_full_php5.5_mysql');
      $output->writeln("<info>Created initial config set at </info><comment>$configlink</comment>");
    }
  }
}
