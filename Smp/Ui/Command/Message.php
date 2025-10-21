<?php

/**
 * @file
 * 
 * Message command class file.
 */

namespace Smp\Ui\Command;

use Smp\Game;
use Smp\Utility;

/**
 * Message command.
 * 
 * TODO Currently an unregistered command, until permissions are implemented.
 * 
 * Context keys:
 *   'uid':     Optional. User id. User associated with the command. User sending the message.
 *   'to_uids': Array of uids. An array of recipients. 
 *   'game':    Game instance. API frontend for almost all things.
 */
final class Message extends Command {
  public const id = 'message';
  
  public static function name() : string {
    return Utility::t('message');
  }
  
   public static function aliases(): array {
    return [
      Utility::t('info'),
    ];
  }
  
  /**
   * {@inheritdoc}
   * @see \Smp\Ui\Command\Command::__construct()
   */
  public function __construct() {
    parent::__construct(static::name(), Utility::t('Send an informational message to a user.'));
  }
  
  /**
   * {@inheritdoc}
   * @see \Smp\Ui\Command\Command::execute()
   */
  public function execute(): self {
    if (isset($this->context['to_uids']) && isset($this->context['game'])) {
      /**
       * @var Game $game
       */
      $game = $this->context['game'];
      // TODO Loading the user is not really needed here.
      //      Could create another context: to_single_uid.
      $to_users = \array_map(fn($to_uid) => $game->getUsers()->get($to_uid), $this->context['to_uids']);
      $user = isset($this->context['uid']) ? $game->getUsers()->get($this->context['uid']) : NULL;
      
      $outputUi = $game->getOutputUi();
      
      $prefix = '';
      if ($user) {
        $prefix = $user->getUsername() . ' ';
      }
      
      $message = \implode("\n", $this->getArguments());
      
      foreach ($to_users as $user) {
        $outputUi->println($user->getId(), "{$prefix}{$message}");
      }
    }
    else {
      $this->logger->error("Missing contexts: to_uids, game.");
    }
    return $this;
  }
}
