<?php
namespace Smp\tests\Simple;

use Fiber;

use Smp\tests\Simple\Test;

class Fibers extends Test {
  /**
  * {@inheritDoc}
  * @see \Smp\tests\Simple\Test::run()
  */
  public function run(): int {
    $b = function () {
      \Fiber::suspend("B suspended");
    };
    
    $a = function () use ($b) {
      $b();
      $this->logger->info("A sleeps for 1 seconds...");
      \sleep(1);
      \Fiber::suspend("A suspended");
    };
    
    $fiber = new \Fiber(function () use ($a) {
      $a();
      \Fiber::suspend("local suspended");
      return "Fiber finished.";
    });
    
    $value = $fiber->start();
    $this->logger->info("Initial value: {$value}");
    for ($i = 0; !$fiber->isTerminated() && $i < 256; $i++) {
      $value = $fiber->resume();
      $this->logger->info("Resumed value: {$value}");
    }
    
    $return = $fiber->getReturn();
    $this->logger->info("Initial value: {$return}");
    
    return 0;
  }
}
