<?php
/**
 * @file
 * 
 * Quit command class file.
 */

namespace Smp\Ui\Command;

use Smp\Utility;
use Smp\Game;

/**
 * Quit command.
 * 
 * Context keys:
 *   'user': The user to perform the quit command on.
 */
final class Quit extends Command {
  public const id = 'quit';
  
  public static function name() : string {
    return Utility::t('quit');
  }
  
  public static function aliases(): array {
    return [
      Utility::t('exit'),
      Utility::t('logout'),
      Utility::t('disconnect'),
    ];
  }
  
  /**
   * {@inheritdoc}
   * @see \Smp\Ui\Command\Command::__construct()
   */
  public function __construct() {
    parent::__construct(static::name(), Utility::t('Quit the game.'));
  }
  
  /**
   * {@inheritdoc}
   * @see \Smp\Ui\Command\Command::execute()
   */
  public function execute(): self {
    if (isset($this->context['uid']) && isset($this->context['game'])) {
      /**
       * @var Game $game
       */
      $game = $this->context['game'];
      $uid = $this->context['uid'];
      
      // Do some cleanup and stuff... maybe even add a command or something.
      
      $game->getOutputUi()->println($uid, "Bye!");
      $game->requestDisconnect($uid);
    }
    else {
      $this->logger->error("User, & Game context is required, but was not found.");
    }
    return $this;
  }

}
