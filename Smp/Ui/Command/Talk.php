<?php
/**
 * @file
 * 
 * Talk command class file.
 */

namespace Smp\Ui\Command;

use Smp\Utility;

/**
 * Talk command.
 * 
 * Context keys:
 *   'user': The user to perform the quit command on.
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
    if (isset($this->context['user'])) {
      throw new \RuntimeException("TODO Implement command.");
    }
    else {
      $this->logger->error("User context is required, but was not found.");
    }
    
    return $this;
  }
}
