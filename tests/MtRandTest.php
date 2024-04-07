<?php
/**
 * @file
 * Client test.
 */

namespace Smp\tests;

use Smp\Logger\LoggerFactory;
use Smp\Logger\LoggerTrait;
use Smp\Game;

class MtRandTest implements Test {
  use LoggerTrait;
  
  public function __construct() {
    LoggerFactory::init();
    $this->logger = static::getLogger();
  }
  
  public function run(): int {
    foreach(range(1, 3) as $s) {
      $this->runWithSeed($s);
    }
    
    return Game::EXIT_SUCCESS;
  }
  
  protected function runWithSeed($seed, $iterations = 3) {
    \mt_srand($seed);
    
    foreach(range(1, $iterations) as $i) {
      $context = [
        'seed' => $seed,
        'number' => \mt_rand(),
      ];
      $this->logger->debug('next random', $context);
    }
  }
}
