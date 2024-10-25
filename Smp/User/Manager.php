<?php
/**
 * @file
 * User manager class file.
 */

namespace Smp\User;

throw new \Exception('Deprecated - do not use!');

use Smp\Logger\LoggerTrait;
use Smp\Logger\LoggerFactory;

class Manager implements \Iterator {
  use LoggerTrait;
  
  const TEMP_USERNAME = "anonymous";
  
  /**
   * A map of users.
   * 
   * Keys are user ids.
   * 
   * @var \Smp\User\User
   */
  protected $users = [];
  
  /**
   * Temporary passwords store.
   * 
   * @var array
   */
  protected $usersPass = [];
  
  /**
   * Serial user id.
   * 
   * @var integer
   */
  protected $nextId = 0;
  
  // Used by the iterator impl.
  private $position = 0;
  
  public function __construct() {
    LoggerFactory::init();
    $this->logger = static::getLogger();
    
    // TODO Should be read from a database.
    $this->nextId = 1;
  }
  
  protected function getNextId() : int {
    return $this->nextId++;
  }
  
  /**
   * Get an existing user.
   * 
   * @param mixed $uid
   * @return \Smp\User\User|FALSE
   *   Returns the user object or FALSE if one was not found.
   */
  public function get(int $uid) : User|FALSE {
    $uid = (string) $uid;
    if (isset($this->users[$uid]) && \is_object($this->users[$uid])) {
      return $this->users[$uid];
    }
    return FALSE;
  }
  
  /**
   * Create a new user.
   * 
   * @param string $username
   *   User's name. This will be the login name, not the game name.
   * @param string|NULL $password
   * @param array $context
   * @return \Smp\User\User|FALSE
   *   Returns the user object or FALSE if one was not created.
   */
  public function create(string $username, string|NULL $password, array $context = []) : User {
    $new_uid = (string) $this->getNextId();
    // Make sure there is no existing user with the given id.
    if (!$this->get($new_uid)) {
      $new_user = new User($new_uid, $username, $context);
      $this->users[$new_uid] = $new_user;
      $this->usersPass[$new_uid] = $password;
      $this->logger->info("Created new user: {$new_uid}@{$new_user->getUsername()}.");
      return $new_user;
    }
    $this->logger->error("User id already present in DB: {$new_uid}");
    return FALSE;
  }
  
  /**
   * Update an existing user.
   * 
   * @param mixed $user
   *   The User object, with updated fields.
   * @param string|NULL $password
   * @return \Smp\User\User|FALSE
   *   Returns the user object or FALSE if one was not updated or found.
   */
  public function update(User $user, string|NULL $password = NULL) : User|FALSE {
    $uid = $user->getId();
    // Validate user exists.
    $update_user = $this->get($uid);
    if (!$update_user) {
      $this->logger->error("User not found in DB: {$uid}@{$update_user->getUsername()}.");
      return FALSE;
    }
    
    $update_user = new User($uid, $user->getUsername(), $user->getContext());
    $this->users[$uid] = $update_user;
    if (NULL !== $password) {
      $this->usersPass[$uid] = $password;
    }
    
    $this->logger->info("Updated user: {$uid}@{$update_user->getUsername()}.");
    return $update_user;
  }
  
  /**
   * 
   * 
   * @param mixed $user
   *   Either the user id or User object.
   * @return bool
   *   Returns TRUE if a user was found and deleted, FALSE otherwise.
   */
  public function delete(User|int $user) : bool {
    // Get user id first.
    if (\is_object($user)) {
      $uid = $user->getId();
    }
    else {
      $uid = (string) $user;
    }
    // Validate user exists.
    $delete_user = $this->get($uid);
    if (!$delete_user) {
      return FALSE;
    }
    $this->logger->info("Delete user: {$uid}@{$delete_user->getUsername()}.");
    // Finally delete user.
    unset($this->users[$uid]);
    unset($this->usersPass[$uid]);
    return FALSE;
  }
  
  /**
   * Get user password.
   * 
   * @param mixed $uid
   * @return string|FALSE
   *   Returns the unsecured password or FALSE if one was not found.
   */
  public function getPass(int $uid) : string|NULL|FALSE {
    if ($this->get($uid) && array_key_exists($uid, $this->usersPass)) {
      return $this->usersPass[$uid];
    }
    return FALSE;
  }
  
  final public function current() {
    return \current($this->users);
  }
  
  final public function key() {
    return \key($this->users);
  }
  
  final public function next() {
    \next($this->users);
  }
  
  final public function rewind() {
    \reset($this->users);
  }
  
  final public function valid() {
    return \current($this->users);
  }
}
