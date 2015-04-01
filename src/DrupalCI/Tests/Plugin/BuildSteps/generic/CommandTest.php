<?php

/**
 * @file
 * Contains \DrupalCI\Tests\Plugin\BuildSteps\generic\CommandTest.
 */


namespace DrupalCI\Tests\Plugin\BuildSteps\generic;

use Docker\Container;
use Docker\Docker;
use DrupalCI\Plugin\BuildSteps\generic\Command;

class CommandTest extends \PHPUnit_Framework_TestCase {

  function testRun() {
    $cmd = ['test_command', 'test_argument'];
    $instance = new Container([]);

    $body = $this->getMock('Guzzle\Stream\StreamInterface');
    $body->expects($this->once())
      ->method('getContents');

    $response = $this->getMock('GuzzleHttp\Message\ResponseInterface');
    $response->expects($this->once())
      ->method('getBody')
      ->will($this->returnValue($body));

    $container_manager = $this->getMockBuilder('Docker\Manager\ContainerManager')
      ->disableOriginalConstructor()
      ->getMock();
    $container_manager->expects($this->once())
      ->method('find')
      ->will($this->returnValue($instance));
    $container_manager->expects($this->once())
      ->method('exec')
      ->with($instance, $cmd, TRUE, TRUE, TRUE, TRUE)
      ->will($this->returnValue(1));
    $container_manager->expects($this->once())
      ->method('execstart')
      ->will($this->returnValue($response));

    $docker = $this->getMockBuilder('Docker\Docker')
      ->disableOriginalConstructor()
      ->getMock();
    $docker->expects($this->once())
      ->method('getContainerManager')
      ->will($this->returnValue($container_manager));

    $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

    $job = $this->getMock('DrupalCI\Plugin\JobTypes\JobInterface');
    $job->expects($this->once())
      ->method('getDocker')
      ->will($this->returnValue($docker));
    $job->expects($this->once())
      ->method('getExecContainers')
      ->will($this->returnValue(['php' => [['id' => 'dockerci/php-5.4']]]));
    $job->expects($this->any())
      ->method('getOutput')
      ->will($this->returnValue($output));

    $command = new Command([], 'command', []);
    $command->run($job, [implode(' ', $cmd)]);
  }
}
