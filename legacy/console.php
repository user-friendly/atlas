#!/usr/bin/env php
<?php

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Smp\Cli\TestCommand;

require_once __DIR__ . '/vendor/autoload_runtime.php';

return static function (array $context): Application {
  
  $default = new Command('user');
  $default->addArgument('list', null, 'List users in persistent database.');
  $default->setCode(static function (InputInterface $input, OutputInterface $output): void {
    $output->writeln('TODO Implement.');
  });
  
  $test = new TestCommand();
  
  $app = new Application();
  $app->add($default);
  $app->add($test);
  
  return $app;
};
