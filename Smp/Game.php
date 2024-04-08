<?php
/**
 * @file
 * Program entry point.
 */

namespace Smp;

// A bit of nasty code, since we don't have preprocessing.
if (!defined("DEBUG")) {
  define("DEBUG", FALSE);
}

use Smp\Logger\LoggerFactory;
use Smp\Logger\LoggerTrait;

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
   * @var \Smp\Process
   */
  protected $process;
  
  /**
   * Control for main game loop.
   * 
   * @var boolean
   */
  protected $run = true;
  
  /**
   * The network server.
   * 
   * @var Network\Server
   */
  protected $server = NULL;
  
  public function __construct() {
    LoggerFactory::init();
    $this->logger = static::getLogger();
    
    $this->process = new Process();
    $this->process->addSignalHandler([$this, "signalShutdown"]);
  }
  
  /**
   * Run the main game loop.
   * 
   * @return int
   */
  public function run() : int {
    
    // Initialize & start listening for connections.
    $this->server = new Network\Server($this->process);
    
    $this->logger->debug("Start main game loop.");
    while ($this->run) {
      // Process network messages.
      foreach($this->server->run() as $address => $connection) {
        // TODO Do the communications with the reset of the game here.
      }
      
      $this->process->yield();
    }
    
    // Graceful shutdown.
    $this->server->shutdown();

    $this->logger->debug("Shutdown complete.");
    return $this::EXIT_SUCCESS;
  }
  
  /**
   * System signal handler.
   */
  public function signalShutdown() : void {
    $this->run = false;
    if ($this->server) {
      $this->server->setShutdownMessage(Utility::t('Server is forced to shutdown. Sorry & Bye!'));
    }
    $this->logger->warning("Received system shutdown signal.");
  }
}
