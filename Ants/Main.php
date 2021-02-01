<?php
/**
 * @file
 * Program entry point.
 */

namespace Ants;

// A bit of nasty code, since we don't have preprocessing.
if (!defined("DEBUG")) {
  define("DEBUG", FALSE);
}

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Main {
  const EXIT_SUCCESS = 0;
  const EXIT_FAILURE = 1;
  
  /**
   * @var \Ants\Process
   */
  protected $mainProcess;
  
  /**
   * An array of sub processes.
   * 
   * Must remain empty for child processes.
   * 
   * @see \Ants\Process
   * 
   * @var array
   */
  protected $subProcess = [];

  /**
   * Local logger.
   * 
   * @var \Monolog\Logger
   */
  protected $logger;
  
  /**
   * @var \Monolog\Logger[]
   */
  protected $loggers = [];
  
  /**
   * @var \Monolog\Handler\HandlerInterface[]
   */
  protected $loggersHandlersDefault;
  
  public function __construct() {
    $this->mainProcess = new Process();
    
    // Get log level from configurations (include cmd line args) and override
    // this value.
    $log_level = DEBUG ? Logger::DEBUG : Logger::WARNING;
    $log_file = DEBUG ? STDOUT : STDERR;
    
    $this->loggersHandlersDefault = [
      // Doesn't seem like a good idea to split them like this.
      // new StreamHandler(STDERR, Logger::WARNING, FALSE),
      // In prod, only warnings and above should be logged. On dev all can go
      // through STDERR.
      new StreamHandler($log_file, $log_level),
    ];
    
    $this->logger = $this->createLogger("Main");
    
    // FIXME Remove test.
    include __DIR__ . "../../tests/LoggerTest.php";
  }
  
  // TODO Move to logger factory.
  public function createLogger(string $name) : Logger {
    $new_logger = new Logger($name, $this->loggersHandlersDefault);
    $this->loggers[] = $new_logger;
    return $new_logger;
  }
  
  // Starts the daemon.
  public function run() : int {
    global $argv, $argc;
    
    if ($argc > 1 && isset($argv[1]) && $argv[1] == 'swarm-test') {
      return $this->clientTest();
    }
    
    $this->println_debug("Main: start server.");
    
    $server = new Network\Server($this);
    
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
    
    $server = new Network\Client($this);
    
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
