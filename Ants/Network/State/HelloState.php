<?php
/**
 * @file
 * HelloState class file.
 */

namespace Ants\Network\State;

use Ants\Network\Connection;

/**
 * HelloState class file.
 * 
 * This will be the state that establishes the initial "handshake" - it's to
 * initiate the application protocol. As with the VersionState, this message
 * format is not TLV but fixed in size.
 */
class HelloState extends BaseState {
  
  const MARK_HELLO = 0x0002;
  
  /**
   * "Hello mark" packed as a string of bytes.
   * 
   * @var string
   */
  protected $markHello;
  
  /**
   * {@inheritDoc}
   */
  public function __construct(Connection $conn) {
    parent::__construct($conn);
    
    $this->markHello = \pack('n', static::MARK_HELLO);
    
    // Send the hello mark immediately.
    $this->connection->append($this->markHello);
  }

  /**
   * {@inheritDoc}
   * 
   * @see \Ants\Network\State\StateInterface::handle()
   */
  public function handle(Connection $conn) : void {
    $binary = $conn->consume(\strlen($this->markHello));
    if (!$binary) {
      return;
    }
    $hello_mark_hex = \bin2hex($binary);
    if (static::MARK_HELLO === ((int) @\unpack("n", $binary)[1])) {
      $this->logger->debug("Hello mark received (0x{$hello_mark_hex})");
      $conn->setState(new VersionState($conn));
    }
    else {
      $this->logger->error("Invalid HELLO mark (0x{$hello_mark_hex})");
      $conn->setState(new HaltState($conn));
    }
  }
}
