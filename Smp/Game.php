<?php
/**
 * @file
 * 
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
use Smp\Ui\OutputText;
use Smp\Ui\Command\CommandQueue;
use Smp\Ui\Command\Quit;

// TODO Add timeout for users. After a certain period of inactivity, kick the user.

/**
 * Game class.
 * 
 * This is equivalent to an application class.
 */
class Game {
  use LoggerTrait;
  
  const VERSION = '1.0.0-alpha1';
  
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
  protected $server;
  
  protected $users;
  
  protected $inputUi;
  protected $outputUi;
  
  // FIXME Commands? Name is ambiguous.
  protected $commands;
  
  /**
   * Maps network/game clients to users.
   * 
   * The key is the client id. Value is user id.
   * 
   * @var array
   */
  protected $clientToUser = [];
  
  /**
   * Maps user to network/game clients.
   *
   * The key is the user id. Value is client id.
   *
   * @var array
   */
  protected $userToClient = [];
  
  protected function associateUserClient($uid, $cid) {
    $this->userToClient[$uid] = $cid;
    $this->clientToUser[$cid] = $uid;
  }
  
  protected function removeUserClient($uid = NULL, $cid = NULL) {
    if ($uid !== NULL) {
      unset($this->clientToUser[$this->userToClient[$uid]]);
      unset($this->userToClient[$uid]);
      return;
    }
    if ($cid !== NULL) {
      unset($this->userToClient[$this->clientToUser[$cid]]);
      unset($this->clientToUser[$cid]);
    }
  }
  
  public function __construct() {
    LoggerFactory::init();
    $this->logger = static::getLogger();
    
    $this->process = new Process();
    $this->process->addSignalHandler([$this, "signalShutdown"]);
    
    $this->users = new UserManager();
    
    $this->inputUi = new InputText();
    $this->outputUi = new OutputText();
    
    // TODO Is this queue supposed to be here?
    $this->commands = new CommandQueue();
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
    
    $this->logger->debug("Start main game loop.");
    while ($this->run) {
      // Accept new connections, receive & send, cleanup disconnected clients (once every N seconds).
      $this->server->run();
      
      // *** Handle RAW INPUT ***
      if (0 < count($this->clientToUser)) {
        foreach ($this->server->input() as $cid => $input) {
          
          $uid = $this->clientToUser[$cid];
          $inputs = [];
          
          try {
            $inputs = $this->inputUi->processInput($input);
          } catch (\Exception $e) {
            $this->logger->error("Failed to process input from user (client id $cid, user id: $uid).", ['exceptoin' => $e]);
            continue;
          }
          
          if (empty($inputs)) {
            // Prints the prompt.
            $this->outputUi->print($uid);
            continue;
          }
          
          foreach ($inputs as $tokens) {
            if ($command = $this->inputUi->processCommand($tokens)) {
              $command->setContext([
                // Client id.
                'cid' => $cid,
                // User id.
                'uid' => $uid,
                // Entity id.
                'eid' => NULL,
              ]);
              $this->commands->enqueue($command);
            }
            else {
              $this->outputUi->println($uid, "Command '{$tokens[0]}' not found.");
            }
          }
        }
      }
      
      // *** Handle UI inputs. ***
      // TODO Text batching of command execution.
      $max = 4;
      
      for ($i = 0; $i < $max && ($command = $this->commands->dequeue()); $i++) {
        try {
          $command->execute();
        }
        catch (\Exception $e) {
          $context = $command->getContext();
          if (isset($context['uid'])) {
            if ($command->getId() === Quit::id && isset($context['cid'])) {
              $this->server->output($context['cid'], "Bye!");
              $this->server->close($context['cid']);
            }
            else {
              $this->outputUi->println($context['uid'], "Could not execute command: {$e->getMessage()}");
            }
          }
        }
        
        if (DEBUG) {
          $this->logger->debug("Executed a batch of $max commands.");
        }
      }
      
      // *** Handle RAW OUTPUT ***
      if (0 < count($this->userToClient)) {
        foreach ($this->outputUi->processOutput() as $uid => $text) {
          $this->server->output($this->userToClient[$uid], $text);
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
    
    if (isset($this->clientToUser[$net_id])) {
      // Hard fail here, this should never happen.
      throw new \RuntimeException("Client already present in user map: {$net_id}");
    }
    
    // Create a temporary user.
    $anon_user = $this->users->createTempUser();
    
    // Link user to client.
    $this->associateUserClient($anon_user->getId(), $net_id);
    
    // FIXM Convert to some sort of a command/game event?
    $this->outputUi->println($anon_user->getId(), "Welcome to SMP!");
    $this->outputUi->println($anon_user->getId(), "");
    $this->outputUi->println($anon_user->getId(), "You can use the following commands: say, quit, rename.");
    $this->outputUi->println($anon_user->getId(), "");
  }
  
  public function handleClientDisconnected($net_id) {
    if (DEBUG) {
      $this->logger->debug("User disconnected, client id: $net_id");
    }
    
    if (isset($this->clientToUser[$net_id])) {
      $this->logger->warning("User's client unexpectedly disconnected, client id: $net_id");
      // TODO Some some user forced disconnect here, if user is present in the map.
      //      If the client disconnected (for whatever reason) before being removed
      //      from the map, this would indicated a forced disconnection.
    }
    
    // TODO So the user Manager is just an in-memory store, eh?
    //      This operation should be moved somewhere else.
    //      This class must only notify the rest of the engine
    //      of the client closure event.
    $this->users->delete($this->clientToUser[$net_id]);
    
    // Dissociate user & client.
    $this->removeUserClient(NULL, $net_id);
    
    if (DEBUG) {
      if (empty($this->clientToUser)) {
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
