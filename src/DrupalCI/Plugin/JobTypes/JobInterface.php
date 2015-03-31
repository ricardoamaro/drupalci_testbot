<?php
/**
 * @file
 * Contains
 */
namespace DrupalCI\Plugin\JobTypes;

interface JobInterface {

  public function getBuildVars();

  public function setBuildVars($build_vars);

  public function getBuildvar($build_var);

  public function setBuildVar($build_var, $value);

  public function setOutput($output);

  public function buildSteps();

  public function errorOutput($type = 'Error', $message = 'DrupalCI has encountered an error.');

  public function shellCommand($cmd);

  public function getDocker();

  public function getExecContainers();

  public function startContainer(&$container);

  public function getContainerConfiguration($image = NULL);

  public function startServiceContainerDaemons($type);

  public function getErrorState();

  public function getDefinition();

}
