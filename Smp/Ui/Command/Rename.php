<?php
/**
 * @file
 * 
 * Rename command class file.
 */

namespace Smp\Ui\Command;

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
    if (isset($this->context['uid'])) {
      throw new \RuntimeException("TODO Implement command.");
    }
    else {
      $this->logger->error("User context is required, but was not found.");
    }
    
    return $this;
  }
}
