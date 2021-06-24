<?php
/**
 * @file
 * Main executable.
 */

// FIXME Remove, at first alpha version.
define('DEBUG', TRUE);

require __DIR__ . '/vendor/autoload.php';

$return = (new \Ants\Main())->run();
if ($return !== 0) {
  exit($return);
}
