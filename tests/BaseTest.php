<?php declare(strict_types=1);
/**
 * @file
 * Setup fixture.
 */

namespace Smp\tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

#[CoversNothing()]
abstract class BaseTest extends TestCase {
  public static function setUpBeforeClass() : void {
    // A bit of nasty code, since we don't have preprocessing.
    if (!defined("DEBUG")) {
      define("DEBUG", FALSE);
    }
  }
}
