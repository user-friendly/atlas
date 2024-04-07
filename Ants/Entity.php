<?php
/**
 * @file
 * Entity class file.
 */

namespace Ants;

/**
 * Base entity.
 * 
 * NOTE This class will either be a base abstract class or
 *      the entity class (using composition) for the entity system.
 */
class Entity {
  /**
   * Unique number identifier.x
   * 
   * @var int
   */
  protected $id;
  
  // protected $serialId;
  
  /**
   * Machine name.
   * 
   * @var string
   */
  protected $name;
}
