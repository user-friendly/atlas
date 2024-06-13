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
    
    $this->server->setListenerConnected(array($this, 'handleClientConnected'));
    $this->server->setListenerDisconnected(array($this, 'handleClientDisconnected'));
    
    $this->logger->debug("Start main game loop.");
    while ($this->run) {
      $this->server->run();
      
      $this->process->yield();
    }
    
    // Graceful shutdown.
    $this->server->shutdown();

    $this->logger->debug("Shutdown complete.");
    return $this::EXIT_SUCCESS;
  }
  
  public function handleClientConnected($key) {
    if (DEBUG) {
      $this->logger->debug("New user connected, client id: $key");
    }
  }
  
  public function handleClientDisconnected($key) {
    if (DEBUG) {
      $this->logger->debug("User disconnected, client id: $key");
    }
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
