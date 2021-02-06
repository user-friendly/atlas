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

function report_memory() {
  global $log;
  
  $mem = memory_get_usage(TRUE);
  $mem_text = Utility::formatSize($mem);
  $log->warning("Memory useage (real): {$mem_text} ({$mem})");
}

function report_buffer($buffer) {
  global $log;
  
  $str_size = strlen($buffer);
  $str_len = Utility::formatSize($str_size);
  $log->warning("String length: {$str_len} ({$str_size})");
}

// 64 MB
$size = 1 << 31; // (1 << 20) * 512;
$end = $size - 1;
$buffer = '';

$size_text = Utility::formatSize($size);
$log->warning("Create a string using index offset, with length: {$size_text}");

// $log->warning("Hit enter to continue.");
// readline();

$buffer[$end] = chr(0x04);
$buffer[1 << 16] = 'a';

report_memory();
report_buffer($buffer);

$log->warning("String manipulations.");
$log->warning("Hit enter to continue.");
readline();

$t_start = 1 << 16;
$buffer = substr_replace($buffer, "bb", $t_start, 5);

report_memory();
report_buffer($buffer);

$log->warning("Cut it in half.");
$log->warning("Hit enter to continue.");
readline();

$buffer = \substr($buffer, (int) $size / 2);

// $log->warning("Pad the string.");
// $log->warning("Hit enter to continue.");
// readline();

// for ($i = 0; $i < $end; $i++) {
//   $buffer[$i] = chr(mt_rand(0, 255));
// }

report_memory();
report_buffer($buffer);

$log->warning("Test done. Hit enter to continue.");
readline();
