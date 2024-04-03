<?php
/**
 * @file
 * Program entry point.
 */

namespace Ants;

// A bit of nasty code, since we don't have preprocessing.
if (!defined("DEBUG")) {
  define("DEBUG", FALSE);
}

use Ants\Logger\LoggerFactory;
use Ants\Logger\LoggerTrait;

/**
 * Game class.
 * 
 * This is equivalent to an application class.
 */
class Game {
  use LoggerTrait;
  
  const VERSION = '1.0.0';
  
  const EXIT_SUCCESS = 0;
  const EXIT_FAILURE = 1;
  
  /**
   * @var \Ants\Process
   */
  protected $process;
  
  /**
   * Is the game running?
   * 
   * @var boolean
   */
  protected $run = true;
  
  public function __construct() {
    LoggerFactory::init();
    $this->logger = static::getLogger();
    
    $this->process = new Process();
    $this->process->addSignalHandler([$this, "signalShutdown"]);
  }
  
  // Starts the daemon.
  public function run() : int {
    $this->logger->debug("Start game.");
    
    // Initialize & start listening for connections.
    $server = new Network\Server($this->process);
    
    while ($this->run) {
      // Process network messages.
      if (!$server->run()) {
        // No active connections? Give some cpu time back.
        $this->process->yield();
      }
    }
    
    // Graceful shutdown.
    $server->shutdown();

    $this->logger->debug("Graceful shutdown.");
    return $this::EXIT_SUCCESS;
  }
  
  public function signalShutdown() : void {
    $this->run = false;
  }
}
