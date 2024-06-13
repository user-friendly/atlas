<?php
/**
 * @file
 * User class.
 */

namespace Smp\User;

use Smp\Logger\LoggerFactory;
use Smp\Logger\LoggerTrait;

/**
 * For the time being, do not store the password in the
 * user object. Seems like it could be a security issue.
 * Since the user Manager will handle logins, let it
 * handle passwords too.
 */

/**
 * User class.
 * 
 * Users are objects representing a human. Synonymous to "account".
 */
class User {
  use LoggerTrait;
  
  protected $id = 0;
  protected $username = NULL;
  protected $context = NULL;
  
  /**
   * User state.
   * 
   * The initial state should always be the login state.
   * 
   * @var UserState
   */
  protected UserState $state = UserState::Login;
  
  /**
   * A unique identifier.
   * 
   * @var string
   */
  protected $uuid = NULL;
  
  public function __construct(int $id, string $username, array $context = []) {
    LoggerFactory::init();
    $this->logger = static::getLogger();
    
    $this->id = $id;
    $this->username = $username;
    $this->context = $context;
  }
  
  /**
   * User identifier.
   * 
   * This is the temp/in-memory object identifier.
   * 
   * @return int
   */
  public function getId() : int {
    return $this->id;
  }
  
  public function getUUID() : string {
    return $this->uuid;
  }
  
  public function getUsername() : string {
    return $this->username;
  }
  
  public function getContext() : array {
    return $this->context;
  }
  
  public function getState() : UserState {
    return $this->state;
  }
  
  public function setState(UserState $new_state) : void {
    $this->state = $new_state;
  }
}
