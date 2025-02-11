<?php
/**
 * @file
 * 
 * Talk command class file.
 */

namespace Smp\Ui\Command;

use Smp\Game;
use Smp\Utility;

/**
 * Talk command.
 * 
 * Context keys:
 *   'uid':  User id. User associated with the command. User saying the thing.
 *   'game': Game instance. API frontend for almost all things.
 */
final class Talk extends Command {
  public const id = 'talk';
  
  public static function name() : string {
    return Utility::t('talk');
  }
  
  public static function aliases(): array {
    return [
      Utility::t('say'),
      Utility::t('speak'),
    ];
  }
  
  /**
   * {@inheritdoc}
   * @see \Smp\Ui\Command\Command::__construct()
   */
  public function __construct() {
    parent::__construct(static::name(), Utility::t('Say something.'));
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
      $user = $game->getUsers()->get($uid);
      $outputUi = $game->getOutputUi();
      
      $name = "$uid@{$user->getUsername()}";
      $text = \trim(\implode(' ', $this->getArguments()));
      
      $this->logger->info("User says '{$text}'.");
        
      if (!$text) {
        $outputUi->println($uid, "Com' on now, you have to say something?");
        return $this;
      }
      
      foreach ($game->getUsers() as $recipient_id => $user) {
        if ($recipient_id == 0) {
          continue;
        }
        
        if ($uid != $recipient_id) {
          $outputUi->print($recipient_id, "\n$name says '$text'");
        }
        else {
          $outputUi->print($recipient_id, "You say '$text'");
        }
      }
    }
    else {
      $this->logger->error("User context is required, but was not found.");
    }
    return $this;
  }
}
