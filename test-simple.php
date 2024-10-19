<?php

define('DEBUG', TRUE);

require __DIR__ . '/vendor/autoload.php';

echo "\nRunning simple test: {$argv[1]}\n\n";

global $argv, $argc;
if ($argc > 1) {
  $class = "Smp\\tests\\{$argv[1]}";
  $test = new $class();
  return $test->run();
}
else {
  echo "No test to run.\n";
  return 0;
}
