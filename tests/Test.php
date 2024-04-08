<?php
namespace Smp\tests;

use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;

use Smp\Utility;
use Smp\Logger\LoggerTrait;

abstract class Test {
  use LoggerTrait;
  
  public function __construct() {
    $channel = Utility::get_class_name(static::class);
    $stream_handler = new StreamHandler(STDERR, Logger::DEBUG);
    $formatter = new LineFormatter(NULL, "U", TRUE, TRUE);
    $stream_handler->setFormatter($formatter);
    $this->logger = new \Monolog\Logger($channel, [$stream_handler]);
  }
  
  /**
   * Simple assert.
   * 
   * @param bool $assertion
   * @param string $msg
   * @return bool
   */
  public function assert($assertion, $msg) : bool {
    if ($assertion) {
      $this->logger->info("Pass: {$msg}");
    }
    else {
      $bt = debug_backtrace();
      $caller = array_shift($bt);
      $this->logger->error("Fail: {$msg} [{$caller['file']}:{$caller['line']}]");
    }
    return $assertion;
  }
  
  abstract public function run() : int;
}
