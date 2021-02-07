<?php
/**
 * @file
 * StateInterface source file.
 */

namespace Ants\Network\State;

use Ants\Network\Connection;

/**
 * Connection state interface.        
 */
interface StateInterface {
  /**
   * State constructor.
   * 
   * FIXME I don't like how the "state initializer" is also the class ctor.
   *       Consider a separate method - ::init($conn). Where the init() is
   *       invoked from? Could be in the Connection::setState() method, but
   *       that's logic outside the state classes. Maybe the BaseState class?
   * 
   * @param \Ants\Network\Connection $conn
   */
  public function __construct(Connection $conn);
  
  /**
   * Handle connection state.
   * 
   * @param \Ants\Network\Connection $conn
   */
  public function handle(Connection $conn) : void;
}
