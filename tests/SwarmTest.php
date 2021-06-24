<?php
/**
 * @file
 * Server/client stress test.
 */

namespace Ants\tests;

use Ants\Main;
use Ants\Process;
use Ants\Logger\LoggerFactory;
use Ants\Logger\LoggerTrait;
use Ants\Network\Client;

class SwarmTest implements Test {
  use LoggerTrait;
  
  /**
   * @var \Ants\Process
   */
  protected $process;
  
  /**
   * @var \Ants\Main
   */
  protected $main;
  
  /**
   * @var []
   */
  protected $subProcess = [];
  
  /**
   * @var int
   */
  protected $swarmCount = 8;
  
  public function __construct() {
    LoggerFactory::init();
    $this->logger = static::getLogger();
    
    $this->process = new Process();
    $this->main = new Main();
  }
  
  public function run() : int {
    
    $child_pid = $this->process->fork();
    
    if ($this->process->isChild) {
      $this->main->run();
    }
    else {
      // Add main app's pid to subprocesses.
      $this->subProcess["$child_pid"] = $child_pid;
      // Start spawning clients.
      $this->spawnClients();
      // Wait on all subprocesses to complete.
      $this->waitSubProcess();
    }
    
    return Main::EXIT_SUCCESS;
  }
  
  protected function spawnClients() {
    $this->logger->debug("start client test, swarm count: {$this->swarmCount}");
    
    for ($i = 0; $i < $this->swarmCount; $i++) {
      $child_pid = $this->process->fork();
      if (!$this->process->isChild) {
        $this->logger->debug("spawned child: $child_pid");
        $this->subProcess["$child_pid"] = $child_pid;
      }
      else {
        // This is the child. Break out of the loop.
        break;
      }
    }
    
    // Child processes path.
    if ($this->process->isChild) {
      try {
        $ret = (new Client($this->main))->run();
      }
      catch (\RuntimeException $e) {
        $this->logger->error($e->getMessage());
        return Main::EXIT_FAILURE;
      }
    }
  }
  
  protected function waitSubProcess() {
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
        $this->process->yield();
        //$this->mainProcess->sleep(2);
      }
    }
  }
}
