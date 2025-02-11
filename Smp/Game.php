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
use Smp\Ui\InputText;
use Smp\Ui\OutputText;
use Smp\Ui\Command\CommandQueue;
use Smp\Ui\Command\Command;
use Smp\Ui\Command\Message;

use Smp\User\Factory as UserFactory;
use Smp\User\Database as UserDatabase;
use Smp\User\UserState;

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
  
  /**
   * @var UserFactory
   */
  protected $userFactory;
  
  /**
   * @var UserDatabase
   */
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
  
  /**
   * Creates a temp user.
   * 
   * Used for new connections.
   * 
   * @return int|FALSE
   */
  protected function createAnonUser() : int|FALSE {
    $anon_user = $this->userFactory->createTemporary(
      $this->users->getNextId()
    );
    
    $anon_user->setState(UserState::Login);
    $ok = $this->users->add($anon_user);
    
    if (!$ok) {
      return FALSE;
    }
    return $anon_user->getId();
  }
  
  public function __construct() {
    LoggerFactory::init();
    $this->logger = static::getLogger();
    
    $this->process = new Process();
    $this->process->addSignalHandler([$this, "signalShutdown"]);
    
    $this->userFactory = new UserFactory();
    
    $this->users = new UserDatabase(
      $this->userFactory->createTemporary()
    );
    
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
            // Tokenize input. Basically, breaks it down to commands.
            $inputs = $this->inputUi->processInput($input);
          } catch (\Exception $e) {
            $this->logger->error("Failed to process input from user (client id $cid, user id: $uid).", ['exceptoin' => $e]);
            continue;
          }
          
          $context = [
            // Client id.
            'cid' => $cid,
            // User id.
            'uid' => $uid,
            // Entity id.
            'eid' => NULL,
            // Main app reference.
            'game' => $this,
          ];
          
          foreach ($inputs as $tokens) {
            if ($command = $this->inputUi->processCommand($tokens)) {
              $command->setContext($context);
              $this->commands->enqueue($command);
            }
            else {
              $command = new Message();
              $command->setContext(\array_merge($context, ['uid' => NULL, 'to_uids' => [$uid]]));
              $command->setArguments(["Command '{$tokens[0]}' not found."]);
              $this->commands->enqueue($command);
              
              if (DEBUG) {
                $this->logger->debug("User ($uid) executed an invalid command: {$tokens[0]}");
              }
            }
          }
        }
      }
      
      // *** Handle UI inputs. ***
      // TODO Text batching of command execution.
      // TODO Execute commands based on cycles left (of the frame) instead of ... magic const.
      $max = 8;
      for ($cmds_executed = 0; $cmds_executed < $max && ($command = $this->commands->dequeue()); $cmds_executed++) {
        $context = $command->getContext();
        $uid = @$context['uid'];
        try {
          // TODO Consider chains of commands. Instead of ::execute() returning $this,
          //      have it return either a boolean or a new Command instance and
          //      ::enqueue().
          $command->execute();
          if (DEBUG) {
            $this->logger->debug("Command executed (uid $uid): {$command->getId()}");
          }
        }
        catch (\Exception $e) {
          $this->logger->error("Error executing command '{$command->getId()}': {$e->getMessage()}");
          if (isset($context['uid'])) {
            $this->outputUi->println($context['uid'], "Could not execute command: {$e->getMessage()}");
          }
        }
      }
      if (DEBUG && $cmds_executed > 0) {
        $this->logger->debug("Executed a batch of $cmds_executed commands.");
      }
      
      // *** Handle RAW OUTPUT ***
      if (0 < count($this->userToClient)) {
        foreach ($this->outputUi->processOutput() as $uid => $text) {
          $this->server->output($this->userToClient[$uid], $text);
        }
      }
      
      $this->process->yield();
    } // run()
    
    // Graceful shutdown.
    $this->server->shutdown();
    
    $this->logger->debug("Shutdown complete.");
    return $this::EXIT_SUCCESS;
  }
  
  public function getUsers() : UserDatabase {
    return $this->users;
  }
  
  public function getOutputUi() : OutputText {
    return $this->outputUi;
  }
  
  public function handleClientConnected($net_id) {
    if (DEBUG) {
      $this->logger->debug("New user connected, client id: $net_id");
    }
    
    if (isset($this->clientToUser[$net_id])) {
      // Hard fail here, this should never happen.
      throw new \RuntimeException("Client already present in user map: {$net_id}");
    }
    
    $uid = $this->createAnonUser();
    
    if (FALSE === $uid) {
      $this->server->output($net_id, Utility::t("Internal server error. Disconnecting you, sorry."));
      $this->server->disconnect($net_id);
      return;
    }
    
    // Link user to client.
    $this->associateUserClient($uid, $net_id);
    
    // FIXME Convert to some sort of a command/game event?
    $this->outputUi->println($uid, "Welcome to SMP!");
    $this->outputUi->println($uid, "");
    $this->outputUi->println($uid, "You can use the following commands: say, quit, rename.");
    $this->outputUi->println($uid, "");
  }
  
  public function requestDisconnect($uid) : bool {
    if (isset($this->userToClient[$uid])) {
      $this->server->disconnect($this->userToClient[$uid]);
      return TRUE;
    }
    return FALSE;
  }
  
  public function handleClientDisconnected($net_id, $expected = FALSE) {
    if (DEBUG) {
      $this->logger->debug("User disconnected, client id: $net_id");
    }
    
    if (FALSE == $expected) {
      $this->logger->warning("User's client unexpectedly disconnected, client id: $net_id");
      // TODO Some some user forced disconnect here, if user is present in the map.
      //      If the client disconnected (for whatever reason) before being removed
      //      from the map, this would indicated a forced disconnection.
      
      // TODO Notify players?
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
