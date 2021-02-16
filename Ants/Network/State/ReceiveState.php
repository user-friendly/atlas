<?php
/**
 * @file
 * ReceiveState class file.
 */

namespace Ants\Network\State;

use Ants\Network\Connection;

/**
 * ReceiveState class.
 */
class ReceiveState extends BaseState {
  
  /**
   * Substate indicator for the header.
   * 
   * A message starts with a header - tag & length. This substate indicates that
   * the current receive state is reading the tag and length. 
   * 
   * @var integer
   */
  const SUBSTATE_HEADER = 1;
  
  /**
   * Substate indicator for the value.
   * 
   * This substate indicates the receive state is reading the message's body.
   * 
   * @var integer
   */
  const SUBSTATE_VALUE = 2;
  
  // NOTE May not be needed.
  // const MARK_BYE   = 0x0004;
  
  /**
   * Substate state.
   * 
   * @var int
   */
  protected $substate;
  
  protected $tagSize = 2;
  protected $lengthSize = 2;
  // NOTE Figure out communications between 32/64 bit platforms.
  protected $maxValue = PHP_INT_MAX;
  
  /**
   * {@inheritDoc}
   *
   * @see \Ants\Network\State\StateInterface::__construct()
   */
  public function __construct(Connection $conn) {
    parent::__construct($conn);
    
    // After the version binary comes the first message's header.
    $this->substate = static::SUBSTATE_HEADER;
    
    $this->testTimer = \time();
  }

  protected $stayTime = 3;
  protected $testTimer = 0;
  
  /**
   * {@inheritDoc}
   *
   * @see \Ants\Network\State\StateInterface::handle()
   */
  public function handle(Connection $conn) : void {
    if (\time() - $this->testTimer > $this->stayTime) {
      $this->logger->debug("Ooookey, nothing to do. Bye!");
      $conn->setState(new HaltState($conn));
    }
  }

}
