<?php
/**
 * @file
 * ReceiveState class file.
 */

namespace Ants\Network\State;

use Ants\Network\Connection;
use Ants\Network\Message;
use Ants\PDO\Dummy;

/**
 * ReceiveState class.
 */
class ReceiveState extends BaseState {
  
  /**
   * Message to receive.
   * 
   * @var \Ants\Network\Message
   */
  protected $recvMessage;
  
  /**
   * Message to send.
   *
   * @var \Ants\Network\Message
   */
  protected $sendMessage;
  
  // TODO Test props.
  protected $testWaitTime = 1;
  protected $testTimer = 0;
  
  /**
   * {@inheritDoc}
   *
   * @see \Ants\Network\State\StateInterface::__construct()
   */
  public function __construct(Connection $conn) {
    parent::__construct($conn);
    
    $this->testWaitTime = \mt_rand(1, 2);
    $this->testTimer = \time();
    
    $this->sendMessage = Message::create(new Dummy());
    $this->recvMessage = Message::createEmpty();
  }
  
  /**
   * {@inheritDoc}
   *
   * @see \Ants\Network\State\StateInterface::handle()
   */
  public function handle(Connection $conn) : void {
    if ($this->sendMessage/*  && \time() - $this->testTimer > $this->testWaitTime */) {
      // Send the test message created at ctor() time.
      $conn->append($this->sendMessage->raw());
      // End test.
      // $conn->setState(new HaltState($conn));
      // Make sure this substate is not reached again.
      $this->sendMessage = NULL;
    }
    if (!$this->recvMessage->isComplete()) {
      $this->recvMessage->feedRaw($conn->consume());
      if ($this->recvMessage->isComplete()) {
        $this->logger->debug("Received test message: ");
        // var_dump
        ob_start();
        var_dump($this->recvMessage->getValue());
        $var_dump = ob_get_clean();
        $this->logger->debug("{$var_dump}");
        
        $conn->setState(new HaltState($conn));
      }
    }
  }
}
