<?php
/**
 * @file
 * 
 * Entity interface file.
 */

namespace Smp;

/**
 * Generic entity interface.
 * 
 * This should be the most base generic type of entity, suitable not
 * only for game objects, but any type of object.
 */
interface Entity {
  /**
   * Unique integer identifier.
   * 
   * @return int
   */
  public function getId() : int;
  
  /**
   * Machine name.
   * 
   * Should be the unique machine name of an
   * individual entity.
   * 
   * @return string
   */
  public function getName() : string;
}
