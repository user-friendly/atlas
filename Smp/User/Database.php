<?php
/**
 * @file
 *
 * User Database class file.
 */

namespace Smp\User;

use Countable;
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
class Database implements \Iterator, Countable, EntityStore {
  use LoggerTrait;
  
  /**
   * @var User[]
   */
  protected $users = [];
  
  /**
   * Class constructor.
   */
  public function __construct() {
    LoggerFactory::init();
    $this->logger = static::getLogger();
  }
  
  /**
   * Get the next available id.
   * 
   * It's expensive, but it will do for users.
   * 
   * FIXME This will be a horrible bug if not all
   *       users are loaded from persistent storage.
   * 
   * @return int
   */
  public function getNextId() : int {
    return 1 + \max(\array_merge([0], \array_keys($this->users)));
  }
  
  /**
   * Add user to the store.
   * 
   * @param User $entity
   * 
   * @see \Smp\EntityStore::add()
   */
  public function add($entity): bool {
    if (isset($this->users[$entity->getId()])) {
      $this->logger->error("User id already present in DB, id: {$entity->getId()}");
      return FALSE;
    }
    $this->users[$entity->getId()] = $entity;
    
    $this->logger->info("User added: `{$entity->getId()}@{$entity->getUsername()}`.");
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
  public function delete(mixed $entity): bool {
    $id = \is_object($entity) ? $entity->getId() : $entity;
    $entity = $this->get($id);
    
    if (FALSE === $entity) {
      $this->logger->error("Cannot delete, user not found, id: {$id}");
      return FALSE;
    }
    
    unset($this->users[$id]);
    
    $this->logger->info("User deleted: `{$id}@{$entity->getUsername()}`.");
    return TRUE;
  }
  
  /**
   * Update user in the store.
   * 
   * @param int $id
   * @param User $entity
   * 
   * @see \Smp\EntityStore::update()
   */
  public function update(int $id, $entity): bool {
    $update = $this->get($id);
    
    if (FALSE === $update) {
      $this->logger->error("Cannot update, user not found, id: {$id}");
      return FALSE;
    }
    
    $update->setUsername($entity->getUsername());
    $update->setPassword($entity->getPassword());
    $update->setContext($entity->getContext());
    $update->setState($entity->getState());
    
    $this->logger->info("User updated: `{$id}@{$update->getUsername()}`.");
    return TRUE;
  }
  
  /**
   * @see Iterator::next()
   */
  public function next() : void {
    \next($this->users);
  }

  /**
   * @see Iterator::valid()
   */
  public function valid() : bool {
    return FALSE !== \current($this->users);
  }

  /**
   * @see Iterator::current()
   */
  public function current() : mixed {
    return \current($this->users);
  }

  /**
   * @see Iterator::rewind()
   */
  public function rewind() : void {
    \reset($this->users);
  }

  /**
   * @see Iterator::key()
   */
  public function key() : mixed {
    return \key($this->users);
  }
  
  /**
   * @see Countable::count()
   */
  public function count(): int {
    return \count($this->users);
  }
}
