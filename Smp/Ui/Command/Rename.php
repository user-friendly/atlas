<?php
/**
 * @file
 * 
 * Rename command class file.
 */

namespace Smp\Ui\Command;

use Smp\Game;
use Smp\Utility;

/**
 * Rename command.
 * 
 * Context keys:
 *   'user': The user to perform the quit command on.
 */
final class Rename extends Command {
  public const id = 'rename';
  
  public static function name() : string {
    return Utility::t('rename');
  }
  
  /**
   * {@inheritdoc}
   * @see \Smp\Ui\Command\Command::__construct()
   */
  public function __construct() {
    parent::__construct(static::name(), Utility::t('Rename your character.'));
  }
  
  /**
   * {@inheritdoc}
   * @see \Smp\Ui\Command\Command::execute()
   */
  public function execute(): self {
    if (isset($this->context['uid']) && isset($this->context['game'])) {
      if (empty($this->args)) {
        throw new \RuntimeException("No username provided.");
      }
      
      /**
       * @var Game $game
       */
      $game = $this->context['game'];
      $user = $game->getUsers()->get($this->context['uid']);
      
      if (!$user) {
        throw new \RuntimeException("Could not update username. Do you even exist?");
      }
      
      // FIXME Validate username.
      $new_username = $this->args[0];
      
      $user->setUsername($new_username);
      
      $game->getOutputUi()->println($user->getId(), "Your new name is '{$user->getUsername()}'.");
    }
    else {
      $this->logger->error("Missing contexts: uid, game.");
    }
    
    return $this;
  }
}
