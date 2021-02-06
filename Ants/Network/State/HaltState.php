<?php
/**
 * @file
 * HaltState class file.
 */

namespace Ants\Network\State;

use Ants\Network\Connection;
use Ants\Logger\LoggerTrait;

/**
 * HaltState class.
 */
class HaltState implements StateInterface {
  use LoggerTrait;
  
  /**
   * {@inheritDoc}
   *
   * @see \Ants\Network\State\StateInterface::__construct()
   */
  public function __construct(Connection $conn) {
    $this->logger = static::getLogger();
  }

  /**
   * {@inheritDoc}
   *
   * @see \Ants\Network\State\StateInterface::handle()
   */
  public function handle(Connection $conn) {
    if ($conn->isValid()) {
      $conn->close();
      $this->logger->debug("Disconnect connection.");
    }
  }
}
