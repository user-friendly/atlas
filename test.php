#!/usr/bin/env php
<?php

define('DEBUG', TRUE);

require __DIR__ . '/vendor/autoload.php';

global $argv, $argc;
if ($argc > 1) {
  $class = "Ants\\tests\\{$argv[1]}";
  $test = new $class();
  $test->run();
}
else {
  echo "No test to run.\n";
}
