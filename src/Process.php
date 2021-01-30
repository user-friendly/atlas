<?php
/**
 * @file
 * Process class file.
 */

namespace Parallel;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\GenericEvent;

class Process {
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
   * @var boolean
   */
  protected $isChild = FALSE;
  
  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcher;
   */
  protected $signalDispatcher;
  
  // TODO This is silly.
  static protected $getters = [
    'pid', 'ppid', 'isChild',
  ];
  
  public function __get($name) {
    if (in_array($name, static::$getters)) {
      return $this->$name;
    }
    return NULL;
  }
  
  public function __isset($name) {
    if (in_array($name, static::$getters)) {
      return isset($this->$name);
    }
    return NULL;
  }
  
  /**
   * Termination signals.
   *
   * @var array
   */
  static protected $termination_signals = [
    SIGTERM,
    SIGINT,
    SIGHUP,
    SIGQUIT,
  ];
  
  const NANO_SECOND = 1000000000;
  static public function sleep(float $time) : void {
    \time_nanosleep(
      (int) $time,
      (int) (($time - (int) $time) * self::NANO_SECOND)
      );
  }
  
  /**
   * Poor man's yield.
   */
  static public function yield() {
    static::sleep(0.025);
  }
  
  /**
   * Main class ctor.
   *
   * @param int $argc
   * @param array $argv
   */
  public function __construct() {
    $this->signalDispatcher = new EventDispatcher();
    
    $this->setupSignalHandler();
    $this->updateProcessIds();
  }
  
  /**
   * Create a new child process.
   * 
   * @see \pcntl_fork()
   * @throws \RuntimeException
   * @return int
   *   Either 0 for the child process or the child's pid for the parent process.
   */
  public function fork() : int {
    $child_pid = \pcntl_fork();
    if ($child_pid == -1) {
      $errno = \pcntl_get_last_error();
      throw new \RuntimeException(
          "Failed to create child process. \pcntl_fork() error: $errno",
          $errno
        );
    }
    else if ($child_pid) {
      if (DEBUG) {
        print "Child process created: $child_pid\n";
      }
    }
    else {
      // Child process setup.
      $this->updateProcessIds();
      // Only this method should set child status, other than the default.
      $this->isChild = TRUE;
    }
    return $child_pid;
  }
  
  // TODO Misleading name.
  /**
   * Safe fork.
   * 
   * @throws \RuntimeException
   * @return int|NULL
   *    Eqivalent to ::fork(), except NULL is returned in case this is a child
   *    process.
   */
  public function forkSafe() {
    if ($this->isChild) {
      return NULL;
    }
    return $this->fork();
  }
  
  public function execute($sleep = 60) : int {
    while (true) {
      static::yield();
    }
    
    if (DEBUG) {
      print "Process {$this->pid}(parent {$this->ppid}) is done.\n";
    }
    
    return Main::EXIT_SUCCESS;
  }
  
  public function addSignalHandler($handler) {
    $this->signalDispatcher->addListener("parallel.process.signal", $handler);
  }
  
  public function removeSignalHandler($handler) {
    $this->signalDispatcher->removeListener("parallel.process.signal", $handler);
  }
  
  protected function updateProcessIds() {
    $this->pid = \posix_getpid();
    $this->ppid = \posix_getppid();
    
    if (DEBUG) {
      print "Update process ids to {$this->pid}({$this->ppid})\n";
    }
  }
  
  protected function setupSignalHandler()
  {
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
    $signals = \array_merge(static::$termination_signals, $signals);
    foreach ($signals as $signo) {
      \pcntl_signal($signo, $generic_handler);
    }
  }
  
  // Has to be public? What a drag.
  public function signalHandler(int $signo, $siginfo) : void {
//     if (DEBUG && is_array($siginfo)) {
//       print "Siginfo: \n";
//       var_dump($siginfo);
//     }
    
    $this->signalDispatcher->dispatch(new GenericEvent($signo, [$siginfo]), "parallel.process.signal");
    
    if (in_array($signo, static::$termination_signals)) {
      // Termination requested.
      // Try to gracefully shutdown the deamon and its children.
      if (DEBUG) {
        print "Forced shutdown of {$this->pid}({$this->ppid}).\n";
        
        // Do not wait on child processes. Notify them instead of the manager
        // process termination.
      }
      exit(Main::EXIT_SUCCESS);
    }
    
    // All other signals handled here.
    switch ($signo) {
      case SIGUSR1:
        if (DEBUG) {
          print "Received SIGUSR1.\n";
        }
        break;
      case SIGALRM:
        if (DEBUG) {
          print "Received SIGALARM.\n";
        }
        break;
      default:
        // Unknown signal received.
        break;
    }
  }
}