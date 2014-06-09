<?php

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

include_once('setup.inc.php');

$console = new Application('DrupalCI - CommandLine', '0.1');

$app->boot();

// we should check if the setup is already done. if yes we should ask to use --force

$console
    ->register('setup')
    ->setDescription('Setups the Docker Enviroment with sane defaults for testing')
    ->setCode(function (InputInterface $input, OutputInterface $output) use ($app) {
        if (!$distro = getLinuxDistro())
        {
           $output->writeln('<error>ERROR</error>: Could not determine Linux distro');
           exit;
        }
        #$output->writeln('<info>INFO</info>');
        #$output->writeln('<error>ERROR</error>');
        $output->writeln("<info>INFO</info>: Running on $distro");
    });

$console
    ->register('build')
    ->setDescription('Build Containers');


$console
    ->register('test')
    ->setDescription('Running Tests');

return $console;
