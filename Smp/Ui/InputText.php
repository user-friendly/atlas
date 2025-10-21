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
use Smp\Ui\Command\Message;

/**
 * Output handler for text UI.
 */
class InputText {
  use LoggerTrait;
  
  protected $tokenizer;
  
  public function __construct() {
    LoggerFactory::init();
    $this->logger = static::getLogger();
    
    $this->logger->debug('Register all commands.');
    Command::registerAll();
    
    $this->tokenizer = new Tokenizer();
  }
  
  public function processInput($bytes, $uid, $context) {
    $inputs = $this->tokenizer->process($bytes);
    // TODO This is a weird way of passing down the error state.
    if (!$inputs) {
      $command = new Message();
      $command->setContext(\array_merge($context, ['uid' => NULL, 'to_uids' => [$uid]]));
      $command->setArguments(["Command is malformed. Are you up to something?"]);
      yield $command;
      $inputs = [];
    }
    foreach ($inputs as $tokens) {
      if ($command = Command::create($tokens[0])) {
        $command->setArguments(\array_slice($tokens, 1));
      } else {
        $command = new Message();
        $command->setContext(\array_merge($context, ['uid' => NULL, 'to_uids' => [$uid]]));
        $command->setArguments(["Command '{$tokens[0]}' not found."]);
      }
      yield $command;
    }
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
    
    return NULL;
  }
}
