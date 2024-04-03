<?php
/**
 * @file
 * Client test.
 */

namespace Ants\tests;

use Ants\Logger\LoggerFactory;
use Ants\Logger\LoggerTrait;
use Ants\Network\Client;
use Ants\Game;

class ClientTest implements Test {
  use LoggerTrait;
  
  public function __construct() {
    LoggerFactory::init();
    $this->logger = static::getLogger();
  }
  
  public function run(): int {
    $main = new Game();
    
    try {
      return (new Client($main))->run();
    }
    catch (\RuntimeException $e) {
      $this->logger->error($e->getMessage());
      return Game::EXIT_FAILURE;
    }
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
        return Game::EXIT_FAILURE;
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
      return Game::EXIT_SUCCESS;
    }
    
    return $ret;
  }
}
