<?php declare(strict_types=1);
/**
 * @file
 * Utility test case file.
 */

namespace Smp\tests;

use Smp\Utility;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Utility::class)]
#[CoversFunction('getTime')]
#[CoversFunction('getTimeMicro')]
final class UtilityTest extends BaseTest {
  public function testGetTimestamps() : void {
    $ts = Utility::getTime();
    $mts = Utility::getTimeMicro();
    
    $this->assertIsInt($ts, "Timestamp is int: {$ts}.");
    $this->assertIsFloat($mts, "Micro timestamp is float: {$mts}.");
  }
}
