<?php

/**
 * @file
 * Console application for Drupal CI.
 */

namespace DrupalCI\Console;

use DrupalCI\Console\Command\Init\InitBaseContainersCommand;
use DrupalCI\Console\Command\Init\InitDatabaseContainersCommand;
use DrupalCI\Console\Command\Init\InitDependenciesCommand;
use DrupalCI\Console\Command\Init\InitDockerCommand;
use DrupalCI\Console\Command\Init\InitWebContainersCommand;
use Symfony\Component\Console\Application;
use DrupalCI\Console\Command\Init\InitAllCommand;
use DrupalCI\Console\Command\Init\InitConfigCommand;
use DrupalCI\Console\Command\BuildCommand;
use DrupalCI\Console\Command\CleanCommand;
use DrupalCI\Console\Command\RunCommand;
use DrupalCI\Console\Command\Config\ConfigListCommand;
use DrupalCI\Console\Command\Config\ConfigLoadCommand;
use DrupalCI\Console\Command\Config\ConfigResetCommand;
use DrupalCI\Console\Command\Config\ConfigSaveCommand;
use DrupalCI\Console\Command\Config\ConfigSetCommand;
use DrupalCI\Console\Command\Config\ConfigShowCommand;
use DrupalCI\Console\Command\Config\ConfigClearCommand;
use DrupalCI\Console\Command\Status\StatusCommand;
use PrivateTravis\PrivateTravisCommand;

class DrupalCIConsoleApp extends Application {

  /**
   * Constructor.
   *
   * We just add our commands here. We don't do any discovery or a container/
   * service model for simplicity.
   */
  public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN') {
    parent::__construct($name, $version);
    $commands = [
      new BuildCommand(),
      new CleanCommand(),
      new ConfigListCommand(),
      new ConfigLoadCommand(),
      new ConfigResetCommand(),
      new ConfigSaveCommand(),
      new ConfigSetCommand(),
      new ConfigShowCommand(),
      new ConfigClearCommand(),
      new InitAllCommand(),
      new InitBaseContainersCommand(),
      new InitDatabaseContainersCommand(),
      new InitDependenciesCommand(),
      new InitDockerCommand(),
      new InitConfigCommand(),
      new InitWebContainersCommand(),
      new RunCommand(),
      new StatusCommand(),
      new PrivateTravisCommand('travis'),
    ];
    $this->addCommands($commands);
  }

}
