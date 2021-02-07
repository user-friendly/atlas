<?php
/**
 * @file
 * HaltState class file.
 */

namespace Ants\Network\State;

use Ants\Network\Connection;

/**
 * HaltState class.
 */
class HaltState extends BaseState {
  
  /**
   * {@inheritDoc}
   *
   * @see \Ants\Network\State\StateInterface::__construct()
   */
  public function __construct(Connection $conn) {
    parent::__construct($conn);
  }

  /**
   * {@inheritDoc}
   *
   * @see \Ants\Network\State\StateInterface::handle()
   */
  public function handle(Connection $conn) : void {
    if ($conn->isValid()) {
      $conn->close();
      $this->logger->debug("Disconnect connection.");
    }
  }
}
