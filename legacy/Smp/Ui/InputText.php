<?php
/**
 * @file
 * 
 * InputText class file.
 * 
 * TODO This should be the UI adapter between the user
 *      and the game (game <- user).
 */

namespace Smp\Ui;

use Smp\Logger\LoggerTrait;
use Smp\Logger\LoggerFactory;
use Smp\Ui\Command\Command;
use Smp\Ui\Command\Tokenizer;

/**
 * Output handler for text UI.
 */
class InputText {
  use LoggerTrait;
  
  public function __construct() {
    LoggerFactory::init();
    $this->logger = static::getLogger();
    
    $this->logger->debug('Register all commands.');
    Command::registerAll();
  }
  
  public function processInput($bytes) {
    $tokenizer = new Tokenizer();
    return $tokenizer->process($bytes);
  }
  
  /**
   * Translate tokens to a command.
   * 
   * @param array $tokens
   *   An array of tokens (basically an array of strings).
   *   @see InputText::processVariableInput()
   * @return \Smp\Ui\Command\Command|NULL
   */
  public function processCommand(array $tokens) {
    if ($command = Command::create($tokens[0])) {
      $command->setArguments(\array_slice($tokens, 1));
      return $command;
    }
    return NULL;
  }
}
