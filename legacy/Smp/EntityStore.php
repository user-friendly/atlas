<?php
/**
 * @file
 * 
 * EntityStore class file.
 */

namespace Smp;

/**
 * EntityStore interface.
 * 
 * Interface should be used for all manner of internal in-memory
 * databases.
 * 
 * The association between entity ids and object instances
 * MUST NOT change on updates. This is due to objects being passed
 * by reference. An update otherwise would be delete/create and
 * would require all classes that have references to be notified.
 * Seems like a nightmare to do. 
 */
interface EntityStore extends \Iterator, \Countable {
  /**
   * Create.
   * 
   * @param $entity
   * @return bool
   *   TRUE if the entity was added successfully to the store, FALSE otherwise.
   */
  public function add($entity) : bool;
  
  /**
   * Read.
   * 
   * @param int $id
   * @return Entity|FALSE
   *   FALSE if the entity was not found.
   */
  public function get(int $id) : Entity|FALSE;
  
  /**
   * Update.
   * 
   * The entity in the store MUST NOT be replaced but updated,
   * using the data in the supplied $entity.
   * 
   * @param int $id
   * @param mixed $entity
   *   Note the ids of each entity MUST NOT be updated.
   * @return bool
   *   TRUE if the entity was updated successfully, FALSE otherwise.
   */
  public function update(int $id, $entity) : bool;
  
  /**
   * Delete.
   * 
   * @param Entity|int $entity
   * @return bool
   *   TRUE if the entity was removed from the store successfully,
   *   FALSE otherwise.
   */
  public function delete(mixed $entity) : bool;
}

