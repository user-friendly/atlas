<?php
/**
 * @file
 * Hammer the memory test.
 */

define('DEBUG', TRUE);

use Ants\Utility;
use Ants\Logger\LoggerFactory;

require __DIR__ . "/../vendor/autoload.php";

LoggerFactory::init();
$log = LoggerFactory::getLogger("memtest");

// 64 MB
$size = (1 << 20) * 64;
$end = $size - 1;
$buffer = '';

$size_text = Utility::formatSize($size);
$log->warning("Create a string using index offset, with length: {$size_text}");

$log->warning("Hit enter to continue.");
readline();

$buffer[$end] = chr(0x04);
$buffer[1 << 16] = 'a';

$mem = memory_get_usage(TRUE);
$mem_text = Utility::formatSize($mem);
$log->warning("Memory useage (real): {$mem_text}");
$str_len = Utility::formatSize(strlen($buffer));
$log->warning("String length: {$str_len}");

$log->warning("Pad the string.");
$log->warning("Hit enter to continue.");
readline();

for ($i = 0; $i < $end; $i++) {
  $buffer[$i] = chr(mt_rand(0, 255));
}

$log->warning("Memory useage (real): {$mem_text}");

$log->warning("Test done. Hit enter to continue.");
readline();
