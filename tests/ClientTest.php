<?php
/**
 * @file
 * Client test.
 */

namespace Ants\tests;

use Ants\Logger\LoggerFactory;
use Ants\Logger\LoggerTrait;
use Ants\Network\Client;
use Ants\Main;

class ClientTest implements Test {
  use LoggerTrait;
  
  public function __construct() {
    LoggerFactory::init();
    $this->logger = static::getLogger();
  }
  
  public function run(): int {
    $main = new Main();
    
    try {
      return (new Client($main))->run();
    }
    catch (\RuntimeException $e) {
      $this->logger->error($e->getMessage());
      return Main::EXIT_FAILURE;
    }
  }
}
