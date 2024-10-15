<?php
/**
 * @file
 * 
 * InputCli class file.
 */

namespace Smp\Ui;

use Smp\Logger\LoggerTrait;
use Smp\Logger\LoggerFactory;
use Smp\Ui\Command\Command;
use Smp\Ui\Command\Tokenizer;

/**
 * Text input handler.
 */
class InputText {
  use LoggerTrait;
  
  public function __construct() {
    LoggerFactory::init();
    $this->logger = static::getLogger();
    
    $this->logger->debug('Register all commands.');
    Command::registerAll();
  }
  
  public function processVariableInput($bytes) {
    $tokenizer = new Tokenizer();
    return $tokenizer->process($bytes);
  }
}
