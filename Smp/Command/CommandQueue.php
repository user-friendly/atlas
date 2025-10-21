<?php
/**
 * @file
 *
 * Command queue class file.
 * 
 * TODO Might want to consider a priority queue, for admins.
 */

namespace Smp\Command;

use Smp\Logger\LoggerTrait;
use Smp\Logger\LoggerFactory;

/**
 * Command queue class.
 * 
 * It's a very simple class. Shame to admit, it's mostly
 * for IDE code completion.
 */
class CommandQueue {
  use LoggerTrait;
  
  protected $commands = [];
  
  /**
   * Constructor.
   */
  public function __construct() {
    LoggerFactory::init();
    $this->logger = static::getLogger();
  }
  
  public function enqueue(Command $command) : self {
    $this->commands[] = $command;
    return $this;
  }
  
  public function dequeue() : Command|NULL {
    return \array_shift($this->commands);
  }
}
