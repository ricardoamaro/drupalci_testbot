<?php

/**
 * @file
 * Contains \DrupalCI\Tests\Plugin\BuildSteps\generic\CommandTest.
 */


namespace DrupalCI\Tests\Plugin\BuildSteps\generic;


use Docker\Docker;
use DrupalCI\Plugin\BuildSteps\generic\Command;

class CommandTest extends \PHPUnit_Framework_TestCase {

  function testRun() {
    $job = $this->getMock('DrupalCI\Plugin\JobTypes\JobInterface');
    $http_client = $this->getMockBuilder('GuzzleHttp\Client')
      ->disableOriginalConstructor()
      ->getMock();
    $docker = new Docker($http_client);
    $job->expects($this->once())
      ->method('getDocker')
      ->will($this->returnValue($docker));
    $command = new Command([], 'command', []);
    $command->run($job, []);

  }
}
