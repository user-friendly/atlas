<?php
namespace SmpTest;

define('DEBUG', TRUE);

interface Test {
  public function run() : int;
}

spl_autoload_register(function ($class_name) {
  $filename = \str_replace('SmpTest\\', '', $class_name);
  require $filename . '.php';
});

global $argv, $argc;
if ($argc > 1) {
  $class = "SmpTest\\{$argv[1]}";
  $test = new $class();
  return $test->run();
}
else {
  echo "No test to run.\n";
  return 0;
}
