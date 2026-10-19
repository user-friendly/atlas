<?php
/**
 * @file
 * 
 * User Factory class file.
 */

namespace Smp\User;

use Smp\Logger\LoggerFactory;
use Smp\Logger\LoggerTrait;

/**
 * Factory class.
 */
class Factory {
  use LoggerTrait;
  
  const TEMP_USERNAME = "anonymous";
  
  /**
   */
  public function __construct() {
    LoggerFactory::init();
    $this->logger = static::getLogger();
  }
  
  public function create(
    int $id,
    string $username,
    string $password = NULL,
    array $context = [],
    UserState $state = UserState::Login
  ) {
    $new_user = new User($id, $username);
    $new_user->setPassword($password);
    $new_user->setContext($context);
    $new_user->setState($state);
    return $new_user;
  }
  
  /**
   * Create a temporary user.
   * 
   * @param number $id
   *   Optional. User id. Defaults to User::MIN_ID.
   * @return \Smp\User\User
   */
  public function createTemporary($id = User::MIN_ID) {
    return $this->create($id, $this::TEMP_USERNAME);
  }
}
