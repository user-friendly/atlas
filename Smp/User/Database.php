<?php
/**
 * @file
 *
 * User Database class file.
 */

namespace Smp\User;

use Iterator;

use Smp\Logger\LoggerTrait;
use Smp\Logger\LoggerFactory;
use Smp\EntityStore;

/**
 * User database class.
 * 
 * Stores users in memory and, in the future, syncs with
 * persistent storage.
 */
class Database implements \Iterator, EntityStore {
  use LoggerTrait;
  
  protected User $users = [];
  
  /**
   * Class constructor.
   * 
   * @param User $user
   *   Sets the initial user. Usually, the admin user.
   */
  public function __construct(User $user) {
    LoggerFactory::init();
    $this->logger = static::getLogger();
    
    $this->users[0] = $user;
  }
  
  /**
   * Get the next available id.
   * 
   * It's expensive, but it will do for users.
   * 
   * @return int
   */
  public function getNextId() : int {
    return 1 + \max(\array_keys($this->users));
  }
  
  /**
   * {@inheritDoc}
   * @see \Smp\EntityStore::add()
   */
  public function add(User $user): bool {
    if (isset($this->users[$user->getId()])) {
      $this->logger->error("User id already present in DB, id: {$user->getId()}");
      return FALSE;
    }
    $this->users[$user->getId()] = $user;
    
    $this->logger->info("User added: `{$uid}@{$user->getUsername()}`.");
    return TRUE;
  }
  
  /**
   * {@inheritDoc}
   * @see \Smp\EntityStore::get()
   */
  public function get(int $id): User|FALSE {
    return isset($this->users[$id]) ? $this->users[$id] : FALSE;
  }
  
  /**
   * {@inheritDoc}
   * @see \Smp\EntityStore::delete()
   */
  public function delete(User|int $user): bool {
    $id = \is_object($user) ? $user->getId() : $user;
    $user = $this->get($id);
    
    if (FALSE === $user) {
      $this->logger->error("Cannot delete, user not found, id: {$id}");
      return FALSE;
    }
    
    unset($this->users[$id]);
    
    $this->logger->info("User deleted: `{$id}@{$user->getUsername()}`.");
    return TRUE;
  }
  
  /**
   * {@inheritDoc}
   * @see \Smp\EntityStore::update()
   */
  public function update(int $id, User $user): bool {
    $update = $this->get($id);
    
    if (FALSE === $update) {
      $this->logger->error("Cannot update, user not found, id: {$id}");
      return FALSE;
    }
    
    $update->setUsername($user->getUsername());
    $update->setPassword($user->getPassword());
    $update->setContext($user->getContext());
    $update->setState($user->getState());
    
    $this->logger->info("User updated: `{$id}@{$update->getUsername()}`.");
  }
  
  /**
   * (non-PHPdoc)
   *
   * @see Iterator::next()
   */
  public function next() : void {
    \next($this->users);
  }

  /**
   * (non-PHPdoc)
   *
   * @see Iterator::valid()
   */
  public function valid() : bool {
    return \current($this->users);
  }

  /**
   * (non-PHPdoc)
   *
   * @see Iterator::current()
   */
  public function current() : mixed {
    return \current($this->users);
  }

  /**
   * (non-PHPdoc)
   *
   * @see Iterator::rewind()
   */
  public function rewind() : void {
    \reset($this->users);
  }

  /**
   * (non-PHPdoc)
   *
   * @see Iterator::key()
   */
  public function key() : mixed {
    return \key($this->users);
  }
}
