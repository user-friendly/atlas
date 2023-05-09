<?php
/**
 * @file
 * EchoState class file.
 */

namespace Ants\Network\State;

use Ants\Network\Connection;

/**
 * @TODO Finish this class.
 */
class EchoState extends BaseState {
  /*
   * {@inheritDoc}
   */
  public function handle(Connection $conn): void {
    $buffer = $conn->consume();
    
    $conn->append(
      
    );
  }
}

