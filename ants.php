<?php
/**
 * @file
 * Main executable.
 */

define('DEBUG', TRUE);

require __DIR__ . '/vendor/autoload.php';

$return = (new \Ants\Main())->run();
if ($return !== 0) {
  exit($return);
}
