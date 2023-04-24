<?php

define('DEBUG', TRUE);

require __DIR__ . '/vendor/autoload.php';

global $argv, $argc;
if ($argc > 1) {
  $class = "Ants\\tests\\{$argv[1]}";
  $test = new $class();
  return $test->run();
}
else {
  echo "No test to run.\n";
  return 0;
}
