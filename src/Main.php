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
   * @var \Parallel\Process
   */
  protected $mainProcess;
  
  /**
   * An array of sub processes.
   * 
   * Must remain empty for child processes.
   * 
   * @see \Parallel\Process
   * 
   * @var array
   */
  protected $subProcess = [];
  
  public function __construct() {
    $this->mainProcess = new Process();
  }
  
  // Starts the daemon.
  public function run() : int {
    global $argv, $argc;
    
    if ($argc > 1 && isset($argv[1]) && $argv[1] == 'test') {
      return $this->clientTest();
    }
    
    $this->println_debug("Main: start server.");
    
    $server = new Server($this);
    
    $ret = $server->run();

    $this->println_debug("Main: done with server.");
    return $ret;
  }
  
  // Performs a client test.
  // TODO Of course it needs to be moved to a unit test.
  public function clientTest() : int {
    $this->println_debug("Main: start client test.");
    
//     do {
      for ($i = 0; $i < 8; $i++) {
        $this->mainProcess->fork();
        // $this->mainProcess->forkSafe();
      }
      
//       if ($this->mainProcess->isChild) {
//         break;
//       }
//       \sleep(2);
//     }
//     while (true);
    
    $server = new Client($this);
    
    $ret = $server->run();
    
    $this->println_debug("Main: done with client test.");
    return $ret;
  }
  
  public function getProcess() : Process {
    return $this->mainProcess;
  }
  
  // Utility functions.
  public function println($msg) {
    print "({$this->mainProcess->pid}) $msg" . PHP_EOL;
  }
  
  public function println_debug($msg) {
    if (DEBUG) {
      $this->println($msg);
    }
  }
  
  public function log_error($msg) {
    fwrite(\STDERR, "({$this->mainProcess->pid}) $msg" . PHP_EOL);
  }
  
  public function stream_info($stream) {
    $this->println("Socket open: " . \stream_socket_get_name($stream, FALSE));
    
    if (\stream_supports_lock($stream)) {
      $this->println("Socket supports locks.");
    }
    else {
      $this->println("Socket does not support locks.");
    }
    
    if (\stream_is_local($stream)) {
      $this->println("Socket is local.");
    }
    else {
      $this->println("Socket is not local.");
    }
    
    if (\stream_isatty($stream)) {
      $this->println("Socket is tty.");
    }
    else {
      $this->println("Socket is not tty.");
    }
  }
}
