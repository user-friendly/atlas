<?php
/**
 * @file
 * User class.
 */

namespace Smp\User;

use Smp\Logger\LoggerFactory;
use Smp\Logger\LoggerTrait;
use Smp\Entity;

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
class User implements Entity {
  use LoggerTrait;
  
  /**
   * Minimum user id.
   * 
   * Applies for regular users. This ensures there are some
   * amount of reserved ids.
   * 
   * @var integer
   */
  const MIN_ID = 1000;
  
  protected $id = 0;
  protected $username = NULL;
  protected $password = NULL;
  protected $context = NULL;
  
  /**
   * User state.
   * 
   * The initial state should always be the login state.
   * 
   * @var UserState
   */
  protected UserState $state = UserState::Login;
  
  public function __construct(int $id, string $username) {
    LoggerFactory::init();
    $this->logger = static::getLogger();
    
    if ($id < $this::MIN_ID) {
      throw new \RuntimeException("User id must be greater than or equal to " . $this::MIN_ID . ".");
    }
    
    $this->id = $id;
    $this->username = $username;
  }
  
  /**
   * {@inheritDoc}
   * @see \Smp\Entity::getName()
   */
  public function getName(): string {
    return $this->getUsername();
  }
  
  /**
   * {@inheritDoc}
   * @see \Smp\Entity::getId()
   */
  public function getId() : int {
    return $this->id;
  }
  
  public function getUsername() : string {
    return $this->username;
  }
  
  public function setUsername($username) : self {
    // TODO Validate username. Throw an exception or return false?
    //      Should validation be this class' responsibility?
    $this->username = $username;
    return $this;
  }
  
  public function getPassword() : string|NULL {
    return $this->password;
  }
  
  public function setPassword($password) : self {
    // TODO Validate password. Throw an exception or return false?
    //      Should validation be this class' responsibility?
    $this->password = $password;
    return $this;
  }
  
  public function getContext() : array {
    return $this->context;
  }
  
  public function setContext($context) : self {
    $this->context = $context;
    return $this;
  }
  
  public function getState() : UserState {
    return $this->state;
  }
  
  public function setState(UserState $new_state) : self {
    $this->state = $new_state;
    return $this;
  }
}
