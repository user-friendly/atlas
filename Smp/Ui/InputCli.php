<?php
/**
 * @file
 * 
 * InputCli class file.
 */

namespace Smp\Ui;

use Smp\Logger\LoggerTrait;
use Smp\Logger\LoggerFactory;

/**
 * Command line interface input handler.
 */
class InputCli {
  use LoggerTrait;
  
  /**
   * Command line character limit.
   * 
   * @var int
   */
  const LINE_MAX = 1024;
  
  /**
   * A list of new line characters. 
   * 
   * These delimit a command.
   * 
   * @var string
   */
  const NEW_LINE = "\n;";
  
  /**
   * A list of quote characters.
   * 
   * @var string
   */
  const QUOTES = "\"'";
  
  /**
   * A list of white space characters.
   * 
   * @var string
   */
  const WHITE_SPACE = " \t";
  
  /**
   * Default constructor.
   */
  public function __construct() {
    LoggerFactory::init();
    $this->logger = static::getLogger();
  }
  
  public function process(string $input) : self {
    return $this;
  }
  
  /**
   * Returns an array of commands.
   * 
   * If there are no commands in the output queue, or a command was invalid, returns an empty array.
   * 
   * @return array<int, Smp\Ui\Command\Command>
   */
  public function getResult() : array {
    return [];
  }
}
