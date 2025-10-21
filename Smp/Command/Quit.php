<?php
/**
 * @file
 * 
 * Quit command class file.
 */

namespace Smp\Command;

use Smp\Utility;
use Smp\Game;

/**
 * Quit command.
 * 
 * FIXME Clear queued commands after this one.
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
   * @see \Smp\Command\Command::__construct()
   */
  public function __construct() {
    parent::__construct(static::name(), Utility::t('Quit the game.'));
  }
  
  /**
   * {@inheritdoc}
   * @see \Smp\Command\Command::execute()
   */
  public function execute(): self {
    if (isset($this->context['uid']) && isset($this->context['game'])) {
      /**
       * @var Game $game
       */
      $game = $this->context['game'];
      $uid = $this->context['uid'];
      $boradcast = isset($this->context['broadcast']) ? $this->context['broadcast'] : TRUE;
      
      // Do some cleanup and stuff... maybe even add a command or something.
      
      // FIXME Return a message command maybe? And have the broadcast flag there.
      if ($boradcast) {
        $outputUi = $game->getOutputUi();
        $quitter = $game->getUsers()->get($uid)->getUsername();
        foreach ($game->getUsers() as $recipient_id => $user) {
          if ($uid == $recipient_id) {
            $outputUi->println($recipient_id, "Bye! Hope to see you soon.");
          }
          else {
            $outputUi->println($recipient_id, "\nPlayer {$quitter} has left this universe.");
          }
        }
      }
      $game->requestDisconnect($uid);
      $this->logger->warning("FIXME User $uid ($quitter) requested to logout, however any queued commands will still be executed!");
    }
    else {
      $this->logger->error("User, & Game context is required, but was not found.");
    }
    return $this;
  }

}
