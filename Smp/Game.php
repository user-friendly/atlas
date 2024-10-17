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
use Smp\User\Manager as UserManager;
use Smp\Ui\InputText;

// TODO Add timeout for users. After a certain period of inactivity, kick the user.

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
  
  protected $users = NULL;
  
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
    
    $this->users = new UserManager();
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
    
    $textUi = new InputText();
    
    // FIXME Test code - remove it.
    $commands = [];
    
    $broadcast = [];
    
    $this->logger->debug("Start main game loop.");
    while ($this->run) {
      // Accept new connections, receive & send, cleanup disconnected clients (once every N seconds).
      $this->server->run();
      
      if (0 < count($this->clientMap)) {
        foreach ($this->server->input() as $net_id => $input) {
          $input_tokens = $textUi->processVariableInput($input);
          
          if (empty($input_tokens)) {
            continue;
          }
          
          // TODO Can be initialized in handleClientConnected().
          if (!isset($commands[$net_id])) {
            $commands[$net_id] = [];
          }
          
          $commands[$net_id] = \array_merge($commands[$net_id], $input_tokens);
        }
        
        foreach ($commands as $net_id => &$cmd_fifo) {
          $user = $this->users->get($this->clientMap[$net_id]);
          
          // Process user commands, up to a number.
          $max = 4;
          for ($i = 0; $i < $max && !empty($cmd_fifo); $i++) {
            $cmd = \array_shift($cmd_fifo);
            
            if ($cmd[0] == 'say') {
              $broadcast[] = "{$user->getId()}@{$user->getUsername()} says: " . \implode(' ', \array_slice($cmd, 1)) . "\n";
            }
            else {
              $this->server->output($net_id, "No such command `$cmd[0]`.\n");
            }
          }
        }
        
        if (!empty($broadcast)) {
          while ($msg = \array_shift($broadcast)) {
            foreach ($this->clientMap as $net_id => $uid) {
              $this->server->output($net_id, $msg);
            }
          }
        }
        
      }
      
      $this->process->yield();
    }
    
    // Graceful shutdown.
    $this->server->shutdown();

    $this->logger->debug("Shutdown complete.");
    return $this::EXIT_SUCCESS;
  }
  
  public function handleClientConnected($net_id) {
    if (DEBUG) {
      $this->logger->debug("New user connected, client id: $net_id");
    }
    
    if (isset($this->clientMap[$net_id])) {
      // Hard fail here, this should never happen.
      throw new \RuntimeException("Client already present in user map: {$net_id}");
    }
    
    $anon_user = $this->users->createTempUser();
    
    $this->clientMap[$net_id] = $anon_user->getId();
  }
  
  public function handleClientDisconnected($net_id) {
    if (DEBUG) {
      $this->logger->debug("User disconnected, client id: $net_id");
    }
    
    if (isset($this->clientMap[$net_id])) {
      $this->logger->warning("User's client unexpectedly disconnected, client id: $net_id");
      // TODO Some some user forced disconnect here, if user is present in the map.
      //      If the client disconnected (for whatever reason) before being removed
      //      from the map, this would indicated a forced disconnection.
    }
    
    $this->users->delete($this->clientMap[$net_id]);
    
    // Finally, remove the map entry.
    unset($this->clientMap[$net_id]);
    
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
