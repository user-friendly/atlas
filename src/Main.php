<?php
/**
 * @file
 * Program entry point.
 */

namespace Parallel;

class Main {
  const EXIT_SUCCESS = 0;
  const EXIT_FAILURE = 1;
  
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
   * Main class ctor.
   * 
   * @param int $argc
   * @param array $argv
   */
  public function __construct(int $argc = 0, array $argv = []) {
    $this->updateProcessIds();
  }
  
  public function execute() : int {
    
    $cpid = \pcntl_fork();
    if ($cpid == -1) {
      return $this::EXIT_FAILURE;
    }
    else if ($cpid) {
      print "Child process created: $cpid\nExiting...\n";
      sleep(60);
    }
    else {
      // Child process setup.
      $this->updateProcessIds();
      sleep(60);
      echo "Process {$this->pid}(parent {$this->ppid}) is done sleeping. Exiting...\n";
    }
    return $this::EXIT_SUCCESS;
  }
  
  protected function updateProcessIds() {
    $this->pid = \posix_getpid();
    $this->ppid = \posix_getppid();
    
    if (DEBUG) {
      print "Process id {$this->pid}, parent process id {$this->ppid}.\n";
    }
  }
  
  protected function setupSignalHandler()
  {
      pcntl_signal(SIGTERM, [$this, 'signalHandler']);
      pcntl_signal(SIGHUP, [$this, 'signalHandler']);
  }
  
  protected function signalHandler(int $signo, $siginfo) {
    if (DEBUG && is_array($siginfo)) {
      var_dump($siginfo);
    }
    switch ($signo) {
      case SIGTERM:
        // Termination requested.
        // Try to gracefully shutdown the deamon and its children.
        if (DEBUG) {
          print "Signal received: SIGTERM\n";
          print "Shutdown initiated...\n";
          
          // TODO Wait on child processes to terminate, but not forever.
          //      Send signals?
          
          exit($this::EXIT_SUCCESS);
        }
        
        break;
      default:
        // Unknown signal received.
        break;
    }
  }
}



























