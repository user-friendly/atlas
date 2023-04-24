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

/**
 * Application's main class.
 * 
 * FIXME The run() is starting a deamon, but this class in not responsible for the server.
 *       What is this class supposed to do?
 *       Is it suppposed to be the entry point for the app, providing basic info,
 *       starting the deamon, starting a client, or even another app?
 *       Should definitely keep the version.
 */
class Main {
  use LoggerTrait;
  
  const VERSION = '1.0.0';
  
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
    $this->logger->debug("start server.");
    
    $server = new Network\Server($this);
    
    $ret = $server->run();

    $this->logger->debug("done with server.");
    return $ret;
  }
  
  // Performs a client test.
  // TODO Of course it needs to be moved to a unit test.
  public function clientTest(int $swarm_count = NULL) : int {
    $swarm_count = $swarm_count ?? 8;
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
    
    // Child processes path.
    if ($this->mainProcess->isChild) {
      try {
        $ret = (new Network\Client($this))->run();
      }
      catch (\RuntimeException $e) {
        $this->logger->error($e->getMessage());
        return Main::EXIT_FAILURE;
      }
    }
    // Main process path.
    else {
      while (count($this->subProcess)) {
        $pcntl_status = NULL;
        $pid = \pcntl_wait($pcntl_status);
        if ($pid > 0) {
          if (isset($this->subProcess[$pid])) {
            unset($this->subProcess[$pid]);
            
            if (\pcntl_wifexited($pcntl_status)) {
              // Actual status.
              $status = pcntl_wexitstatus($pcntl_status);
              if ($status === 0) {
                $this->logger->debug("child process $pid is done, with status $status");
              }
              else {
                $this->logger->error("child process $pid failed, with status $status");
              }
            }
            else {
              $this->logger->error("child process $pid failed, did not exit normally, PCNTL status $pcntl_status");
            }
          }
          else {
            $this->logger->warning("A child process ($pid) ended, but was not accounted for to begin with.");
          }
        }
        else {
          $this->mainProcess->yield();
          //$this->mainProcess->sleep(2);
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

