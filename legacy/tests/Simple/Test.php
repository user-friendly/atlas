<?php
namespace Smp\tests\Simple;

use Smp\Logger\LoggerFactory;
use Smp\Logger\LoggerTrait;

abstract class Test {
  use LoggerTrait;
  
  public function __construct() {
    LoggerFactory::init();
    $this->logger = static::getLogger();
  }
  
  public function run(): int {
    throw new \Exception("TODO Implement test.");
  }
}
