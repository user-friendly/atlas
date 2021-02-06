<?php
/**
 * @file
 * HelloState class file.
 */

namespace Ants\Network\State;

use Ants\Logger\LoggerTrait;
use Ants\Network\Connection;

/**
 * HelloState class file.
 */
class HelloState implements StateInterface {
  use LoggerTrait;
  
  const MARK_HELLO = 0x0002;
  
  /**
   * "Hello mark" packed as a string of bytes.
   * 
   * @var string
   */
  protected $markHello;
  
//   /**
//    * State's connection.
//    * 
//    * @var \Ants\Network\Connection
//    */
//   protected $connection;
  
  /**
   * {@inheritDoc}
   */
  public function __construct(Connection $conn) {
    $this->logger = static::getLogger();
    $this->markHello = \pack('n', static::MARK_HELLO);
    
    // Send the hello mark immediately.
    $conn->append($this->markHello);
  }

  /**
   * {@inheritDoc}
   * 
   * @see \Ants\Network\State\StateInterface::handle()
   */
  public function handle(Connection $conn) {
    $binary = $conn->peek(\strlen($this->markHello));
    if (!$binary) {
      return;
    }
    $hello_mark_hex = \bin2hex($binary);
    if (static::MARK_HELLO === ((int) @unpack("n", $binary)[1])) {
      $conn->setState(new VersionState($conn));
      
      $this->logger->debug("Hello mark received (0x{$hello_mark_hex})");
    }
    else {
      $conn->setState(new HaltState($conn));
      
      $this->logger->error("Invalid HELLO mark (0x{$hello_mark_hex})");
    }
  }
}
