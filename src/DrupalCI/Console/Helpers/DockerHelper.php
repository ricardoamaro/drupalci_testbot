<?php

/**
 * @file
 * DrupalCI Docker helper class.
 */

namespace DrupalCI\Console\Helpers;

use DrupalCI\Console\Helpers\DrupalCIHelperBase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DockerHelper extends DrupalCIHelperBase {

  /**
   * {@inheritdoc}
   */
  public function locateBinary() {
    $binary = parent::locate_binary('docker');
    return !empty($binary) ? $binary : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isInstalled() {
    return (binary) $this->locateBinary();
  }

  /**
   * {@inheritdoc}
   */
  public function getVersion() {
    return shell_exec("docker -v");
  }

  /**
   * {@inheritdoc}
   */
  public function printVersion($output) {
    $output->writeln("<comment>Docker version:</comment> " . $this->getVersion());
  }

  /**
   * {@inheritdoc}
   */
  public function getShortVersion() {
    if (preg_match('/[\d]+[\.][\d]+/', $this->getVersion(), $matches)) {
      return $matches[0];
    }
    else {
      // TODO: Throw exception
      return -1;
    }
  }

  public function getStatus(InputInterface $input, OutputInterface $output) {
    $output->writeln("<info>Checking Docker Version ... </info>");
    if ($this->isInstalled()) {
      $this->printVersion($output);
      if (version_compare($this->getShortVersion(), '1.0.0') < 0) {
        $this->minVersionError($output);
      }
    }
    else {
      $this->notFoundError($output);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function installDocker(OutputInterface $output) {
    if ($this->locateBinary()) {
      $output->writeln("<error>ERROR: Docker already installed.</error>");
      $this->printVersion($output);
      # TODO: Docker already installed.  Throw an exception.
    }
    else {
      $output->writeln("<info>Installing Docker ...</info>");
      exec('curl -s get.docker.io | sh 2>&1 | egrep -i -v "Ctrl|docker installed"', $install_output, $result_code);
      if ($result_code != 0) {
        $output->writeln("<error>ERROR: Docker Installation returned a non-successful return code.");
        $output->writeln("<info>Result:</info>");
        $output->writeln($install_output);
      }
      else {
        $output->writeln($install_output);
        $output->writeln("<info>Docker Installation complete.</info>");
        $this->printVersion($output);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function notFoundError(OutputInterface $output) {
    $output->writeln("<error>ERROR: Docker not found.</error>");
    $output->writeln("Unable to locate the docker binary.  Has Docker been installed on this host?");
    $output->writeln("If so, please ensure the docker binary location exists on your $PATH, and that the current user has sufficient permissions to run Docker.");
    #$output->writeln("If Docker is not yet installed, you may attempt to have DrupalCI install docker using the <info>drupalci docker::update</info> command.");
  }

  /**
   * {@inheritdoc}
   */
  public function minVersionError(OutputInterface $output) {
    $output->writeln("<error>ERROR: Obsolete Docker version.</error>");
    $output->writeln("The version of Docker located on this machine does not meet DrupalCI's minimum version requirement.");
    $output->writeln("DrupalCI requires Docker 1.0.0 or greater. Please upgrade Docker.");
    #$output->writeln('You may attempt to have DrupalCI install docker using the <info>drupalci docker::update</info> command.');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkForUpdate() {
    echo "TODO";
  }

  /**
   * {@inheritdoc}
   */
  protected function updateDocker() {
    echo "TODO";
  }

}