<?php
/**
 * @file
 *
 * Example Chat Service?!
 */

namespace Smp\Game;

use Smp\Logger\LoggerTrait;
use Smp\Logger\LoggerFactory;

/**
 * Output handler for text UI.
 */
class ExampleChatService {
  use LoggerTrait;
  
  public function __construct() {
    LoggerFactory::init();
    $this->logger = static::getLogger();
  }
}