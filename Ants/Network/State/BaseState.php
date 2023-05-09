<?php
/**
 * @file
 * BaseState class file.
 */

namespace Ants\Network\State;

use Ants\Logger\LoggerTrait;
use Ants\Network\Connection;
use Ants\Utility;

/**
 * Base state for all state implementations.       
 */
abstract class BaseState implements StateInterface {
  use LoggerTrait;
  
  /**
   * The connection this state is associated with.
   * 
   * TODO Consider if this member is needed.
   *
   * @var \Ants\Network\Connection
   */
  protected $connection;
  
  /**
   * {@inheritDoc}
   *
   * @see \Ants\Network\State\StateInterface::__construct()
   */
  public function __construct(Connection $conn) {
    $this->logger = static::getLogger();
    $this->connection = $conn;
    
    $state_name = Utility::get_class_name($this);
    $this->logger->debug("State changed to {$state_name} for {$conn->remoteName}");
  }
}
