<?php
/**
 * @file
 * DebugState class file.
 */

namespace Ants\Network\State;

use Ants\Network\Connection;

class DebugState extends BaseState {
  
  const CR = "\x0D";
  const LF = "\x0A";
  const CRLF = "\x0D\x0A";
  
  protected string $prompt = "> ";
  
  
  /**
   * {@inheritDoc}
   * @see \Ants\Network\State\BaseState::__construct()
   */
  public function __construct(Connection $conn) {
      parent::__construct($conn);
      
      $conn->append("This server is in DEBUG mode. All non-special input will be echoed back." . static::CRLF);
      $this->printHelp($conn);
  }

  /*
   * {@inheritDoc}
   */
  public function handle(Connection $conn): void {
    $buffer = $conn->consume();
    if (\strlen($buffer) > 1) {
      // Remove telnet's line feed.
      if ($buffer[-2] == static::CR && $buffer[-1] == static::LF) {
        $buffer = substr($buffer, 0, -2);
      }
      
      if ($buffer === "quit" || $buffer === "exit") {
        $conn->append("bye!" . static::CRLF);
        $conn->setState(new HaltState($conn));
      }
      elseif ($buffer === "help") {
        $this->printHelp($conn);
      }
      else {
        $conn->append($buffer . static::CRLF);
        $conn->append($this->prompt);
      }
    }
  }
  
  protected function printHelp(Connection $conn) : void {
    $conn->append("Available commands:" . static::CRLF);
    $conn->append("\t`help` to display this help message." . static::CRLF);
    $conn->append("\t`quit` or `exit` to end the session." . static::CRLF);
    $conn->append($this->prompt);
  }
}

