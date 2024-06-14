<?php
/**
 * @file
 * 
 * Quit command class file.
 */

namespace Smp\Ui\Command;

use Smp\Utility;

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
    if (isset($this->context['user'])) {
      throw new \RuntimeException("TODO Implement command.");
    }
    else {
      $this->logger->error("User context is required, but was not found.");
    }
    
    return $this;
  }

}
