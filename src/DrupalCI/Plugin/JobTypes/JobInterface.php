<?php
/**
 * @file
 * Contains
 */
namespace DrupalCI\Plugin\JobTypes;

use Symfony\Component\Console\Output\OutputInterface;

interface JobInterface {

  public function getBuildVars();

  public function setBuildVars($build_vars);

  public function getBuildvar($build_var);

  public function setBuildVar($build_var, $value);

  public function getRequiredArguments();

  /**
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   */
  public function setOutput(OutputInterface $output);

  /**
   * @return \Symfony\Component\Console\Output\OutputInterface
   */
  public function getOutput();

  public function buildSteps();

  public function errorOutput($type = 'Error', $message = 'DrupalCI has encountered an error.');

  public function shellCommand($cmd);

  /**
   * @return \Docker\Docker
   */
  public function getDocker();

  public function getExecContainers();

  public function setExecContainers(array $containers);

  public function startContainer(&$container);

  public function getContainerConfiguration($image = NULL);

  public function startServiceContainerDaemons($type);

  public function getErrorState();

  public function getDefinition();

  public function setDefinition(array $job_definition);

  public function getDefaultArguments();

  public function getPlatformDefaults();

  public function getServiceContainers();

  public function setServiceContainers(array $service_containers);

  public function getWorkingDir();
}
