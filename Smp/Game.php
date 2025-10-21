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


use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\GenericEvent;

use Smp\Logger\LoggerFactory;
use Smp\Logger\LoggerTrait;
use Smp\Ui\InputText;
use Smp\Ui\OutputText;
use Smp\Command\CommandQueue;
use Smp\Command\Message;
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
  
  static protected $instance = NULL;
  
  const VERSION = '1.0.0-alpha3';
  
  const EXIT_SUCCESS = 0;
  const EXIT_FAILURE = 1;
  
  /**
   * Termination signals.
   *
   * @var array
   */
  static protected $termSignals = [
    SIGTERM,
    SIGINT,
    SIGHUP,
    SIGQUIT,
  ];
  
  /**
   * Process id.
   *
   * @var int
   */
  protected $pid;
  
  /**
   * Parent process id.
   *
   * @var int
   */
  protected $ppid;
  
  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcher;
   */
  protected $signalDispatcher;
  protected $signalTermReceived = FALSE;
  
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
   * Desired ticks per second.
   * 
   * These are the requested ticks per second of the engine - it is not a guarantee.
   * Should be set to a sensible value, probably in the range of 30 - 120.   
   * 
   * @var integer
   */
  protected $tickSecond = 240;
  /**
   * Desired nanoseconds per tick.
   * 
   * Must be calculated off of desired ticks per second.
   * 
   * @var integer
   */
  protected $tickNano = 0;
  
  /**
   * Nanoseconds time of the last tick.
   * 
   * Excludes busy-wait time.
   * 
   * @var integer
   */
  protected $tickTime = 0;
  
  /**
   * Total ticks ticked.
   * 
   * @var integer
   */
  protected $tickCount = 0;
  
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
  
  /**
   * Profiler.
   * 
   * @var GameStats
   */
  protected $stats;
  
  static public function getInstance() : Game {
    return self::$instance;
  }
  
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
  
  /**
   * Setup process signal handling.
   */
  protected function setupSignalHandling() {
    $this->signalDispatcher = new EventDispatcher();
    
    // See https://wiki.php.net/rfc/async_signals
    if (!\pcntl_async_signals()) {
      \pcntl_async_signals(true);
    }
    
    $generic_handler = [$this, 'signalHandler'];
    $signals = [
      // Cannot be handled/ignored.
      // SIGKILL,
      SIGUSR1,
    ];
    $signals = \array_merge(static::$termSignals, $signals);
    foreach ($signals as $signo) {
      \pcntl_signal($signo, $generic_handler);
    }
    
    // Register the Game shutdown handler.
    $this->signalDispatcher->addListener("parallel.process.signal", [$this, "signalShutdown"]);
    
    // Update process ids.
    $this->pid = \posix_getpid();
    $this->ppid = \posix_getppid();
    $this->logger->debug("Process ids {$this->pid}({$this->ppid})");
  }
  
  public function __construct() {
    if (self::$instance) {
      throw new \RuntimeException("There can be only ONE instance of the game running.");
    }
    
    LoggerFactory::init();
    $this->logger = static::getLogger();
    
    $this->setupSignalHandling();
    
    $this->userFactory = new UserFactory();
    
    $this->users = new UserDatabase();
    
    $this->inputUi = new InputText();
    $this->outputUi = new OutputText();
    
    // TODO Is this queue supposed to be here?
    $this->commands = new CommandQueue();
    
    // Tick setup.
    $this->tickNano = (int) (Utility::NANO_SECOND / $this->tickSecond);
    
    // Profiler should be setup last.
    $this->stats = new GameStats($this->tickSecond);
    
    self::$instance = $this;
  }
  
  /**
   * Run the main game loop.
   * 
   * @return int
   */
  public function run() : int {
    $run_time_start = \time();
    
    $this->logger->info("Simple MUD in PHP.");
    $this->logger->info("Version: " . $this::VERSION);
    
    // Initialize & start listening for connections.
    $this->server = new Network\Server();
    
    $this->server->setListenerConnected(array($this, 'handleClientConnected'));
    $this->server->setListenerDisconnected(array($this, 'handleClientDisconnected'));
    
    $this->logger->debug("Requested tps: " . Utility::formatNumber($this->tickSecond));
    $this->logger->debug("Requested nano per tick: " . Utility::formatNumber($this->tickNano));
    
    $this->tickCount = 0;
    
    $this->logger->debug("Run game...");
    // *** START Main Game Loop ***
    while ($this->run) {
      $time_tick_start = Utility::getTimeHR();
      
      // Profile step.
      $this->stats->profileGameLoop($this->tickTime);
      
      // Accept new connections, receive & send, cleanup disconnected clients (once every N seconds).
      $this->server->run();
      
      // *** START Handle RAW INPUT ***
      if (0 < count($this->clientToUser)) {
        foreach ($this->server->input() as $cid => $input) {
          
          $uid = $this->clientToUser[$cid];
          $inputs = [];
          
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
          
          foreach ($this->inputUi->processInput($input, $uid, $context) as $command) {
            if ($command) {
              $this->commands->enqueue($command);
            }
          }
        }
      } // *** END Handle RAW INPUT ***
      
      // *** START Handle UI inputs. ***
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
      // *** END Handle UI inputs. ***
      
      // *** START Handle Game mechanics. ***
      // TODO Implement. Hah, easy!
      // *** END Handle Game mechanics. ***
      
      // *** START Handle RAW OUTPUT ***
      if (0 < count($this->userToClient)) {
        foreach ($this->outputUi->processOutput() as $uid => $text) {
          $this->server->output($this->userToClient[$uid], $text);
        }
      }
      // *** END Handle RAW OUTPUT ***
      
      $this->tickCount += 1;
      
      $this->tickTime = Utility::getTimeHR() - $time_tick_start;
      
      // Busy-wait requested ticks per second, if finished early.
      if ($this->tickNano > $this->tickTime) {
        $sleep_till = Utility::getTimeHR() + ($this->tickNano - $this->tickTime);
        // \time_nanosleep(0, ($this->tickNano - $this->tickTime));
        $wait_counter = 0;
        while($sleep_till > Utility::getTimeHR()) {
          $wait_counter += 1;
        }
      }
    } // *** END Main Game Loop ***
    
    $run_time = \time() - $run_time_start;
    
    $actual_ticks_per_second = $this->tickCount / \max($run_time, 1);
    $this->logger->debug("Actual tps ~ " . Utility::formatNumber($actual_ticks_per_second));
    $this->logger->debug("Total ticks should be ~ " . Utility::formatNumber($run_time * $this->tickSecond));
    
    $this->logger->info("Total ticks: " . Utility::formatNumber($this->tickCount));
    $run_time_h = Utility::formatSeconds($run_time);
    $this->logger->info("Runtime: $run_time_h ($run_time s)");
    
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
    
    $welcome_message = [
      "Welcome to SMP!",
      "",
      "You can use the following commands: say, quit, rename.",
      "",
    ];
    
    $command = new Message();
    $command->setContext(\array_merge(['game' => $this, 'uid' => NULL, 'to_uids' => [$uid]]));
    $command->setArguments($welcome_message);
    $this->commands->enqueue($command);
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
  
  // Has to be public? What a drag.
  public function signalHandler(int $signo, $siginfo) : void {
    $this->signalDispatcher->dispatch(new GenericEvent($signo, [$siginfo]), "parallel.process.signal");
    
    if (in_array($signo, static::$termSignals)) {
      $this->logger->warning("Forced shutdown of {$this->pid}({$this->ppid}).");
      // Termination requested.
      // TODO Try to gracefully shutdown the deamon and its children.
      // TODO Do not wait on child processes. Notify them instead of the manager
      //      process termination.
      //      Edit: signals seem to be passed to the child processes
      // FIXME This does not look good. What are the standard shutdown Unix procedures?
      if ($this->signalTermReceived) {
        exit($this::EXIT_SUCCESS);
      }
      else {
        $this->signalTermReceived = TRUE;
      }
    }
    
    // All other signals handled here.
    switch ($signo) {
      case SIGUSR1:
        $this->logger->debug("Received SIGUSR1.");
        break;
      case SIGALRM:
        $this->logger->debug("Received SIGALARM.");
        break;
      default:
        // Unknown signal received.
        break;
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
