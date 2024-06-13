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
  
  /**
   * Maps network clients to users.
   * 
   * The key is the client id. Value is user id.
   * 
   * @var array
   */
  protected $clientMap = [];
  
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
    $this->server = new Network\Server();
    
    $this->server->setListenerConnected(array($this, 'handleClientConnected'));
    $this->server->setListenerDisconnected(array($this, 'handleClientDisconnected'));
    
    $tmp_messageInterval = 5;
    $tmp_messageLast = Utility::getTime();
    
    $this->logger->debug("Start main game loop.");
    while ($this->run) {
      $this->server->run();
      
      if (0 < count($this->clientMap)) {
        foreach ($this->server->input() as $key => $input) {
          if (DEBUG) {
            $this->logger->debug("User [$key] input: {$input}");
          }
        }
        
        if (Utility::getTime() > $tmp_messageLast + $tmp_messageInterval) {
          // FIXME Test method.
          foreach ($this->clientMap as $key => $uid) {
            $this->server->output($key, "This is a test message. Your user id is: {$uid}\n" );
          }
          $tmp_messageLast = Utility::getTime();
        }
      }
      
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
    
    if (isset($this->clientMap[$key])) {
      // Hard fail here, this should never happen.
      throw new \RuntimeException("Client already present in user map: {$key}");
    }
    
    // FIXME Tie in with the user system.
    $this->clientMap[$key] = $key;
  }
  
  public function handleClientDisconnected($key) {
    if (DEBUG) {
      $this->logger->debug("User disconnected, client id: $key");
    }
    
    if (isset($this->clientMap[$key])) {
      $this->logger->warning("User's client unexpectedly disconnected, client id: $key");
      // TODO Some some user forced disconnect here, if user is present in the map.
      //      If the client disconnected (for whatever reason) before being removed
      //      from the map, this would indicated a forced disconnection.
    }
    
    // Finally, remove the map entry.
    unset($this->clientMap[$key]);
    
    if (DEBUG) {
      if (empty($this->clientMap)) {
        $this->logger->debug("There are no more users connected to the server.");
      }
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
