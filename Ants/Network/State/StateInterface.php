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
   * @param \Ants\Network\Connection $conn
   */
  public function __construct(Connection $conn);
  
  /**
   * Handle connection state.
   * 
   * @param \Ants\Network\Connection $conn
   */
  public function handle(Connection $conn);
}
