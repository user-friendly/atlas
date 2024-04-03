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
 * Game class.
 * 
 * This is equivalent to an application class.
 */
class Game {
  use LoggerTrait;
  
  const VERSION = '1.0.0';
  
  const EXIT_SUCCESS = 0;
  const EXIT_FAILURE = 1;
  
  /**
   * @var \Ants\Process
   */
  protected $process;
  
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
    
    $this->process = new Process();
  }
  
  // Starts the daemon.
  public function run() : int {
    $this->logger->debug("start server.");
    
    $server = new Network\Server($this->process);
    
    $ret = $server->run();

    $this->logger->debug("done with server.");
    return $ret;
  }
}
