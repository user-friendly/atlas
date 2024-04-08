<?php
/**
 * @file
 * Utility test case file.
 */

namespace Smp\tests;

use Smp\Utility;

class UtilityTest extends Test {
  public function run() : void {
    $ts = Utility::getTime();
    $mts = Utility::getTimeMicro();
    
    $this->assert(\is_int($ts), "Timestamp is int: {$ts}.");
    $this->assert(\is_float($mts), "Micro timestamp is float: {$mts}.");
  }
}
