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

use Ants\Logger\LoggerFactory;
use Ants\Logger\LoggerTrait;

class Main {
  use LoggerTrait;
  
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
  
  public function __construct() {
    LoggerFactory::init();
    $this->logger = static::getLogger();
    
    $this->mainProcess = new Process();
  }
  
  // Starts the daemon.
  public function run() : int {
    global $argv, $argc;
    
    if ($argc > 1 && $argv[1] == 'swarm-test') {
      return $this->clientTest(
        isset($argv[2]) && \is_numeric($argv[2]) ? (int) $argv[2] : NULL
        );
    }
    
    $this->logger->debug("start server.");
    
    $server = new Network\Server($this);
    
    $ret = $server->run();

    $this->logger->debug("done with server.");
    return $ret;
  }
  
  // Performs a client test.
  // TODO Of course it needs to be moved to a unit test.
  public function clientTest(int $swarm_count = NULL) : int {
    $swarm_count = $swarm_count ?? 2;
    $this->logger->debug("start client test, swarm count: {$swarm_count}");
  
    for ($i = 0; $i < $swarm_count; $i++) {
      // $this->mainProcess->fork();
      $child_pid = $this->mainProcess->forkSafe();
      if ($child_pid !== NULL) {
        $this->subProcess["$child_pid"] = $child_pid;
      }
      else {
        // This is the child. Break out of the loop.
        break;
      }
    }
    
    if ($this->mainProcess->isChild) {
      try {
        $ret = (new Network\Client($this))->run();
      }
      catch (\RuntimeException $e) {
        $this->logger->error($e->getMessage());
        exit(Main::EXIT_FAILURE);
      }
    }
    else {
      while (count($this->subProcess)) {
        $status = NULL;
        $pid = \pcntl_wait($status);
        if ($pid > 0) {
          if (isset($this->subProcess[$pid])) {
            unset($this->subProcess[$pid]);
            
            // TODO Figure out why the above exit() with non-zero is not
            //      captured by the following block.
            if (\pcntl_wifexited($status)) {
              $this->logger->debug("child process $pid is done, with status $status");
            }
            else {
              $this->logger->error("child process $pid failed, with status $status");
            }
          }
          else {
            $this->logger->warning("A child process ($pid) ended, but was not accounted for to begin with.");
          }
        }
        else {
          //$this->mainProcess->yield();
          $this->mainProcess->sleep(2);
        }
      }
      
      $this->logger->debug("done with client test.");
      return Main::EXIT_SUCCESS;;
    }
    
    return $ret;
  }
  
  public function getProcess() : Process {
    return $this->mainProcess;
  }
}
