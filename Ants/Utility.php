<?php
/**
 * @file
 * Utility class file.
 */

namespace Ants;

/**
 * Utility class.
 */
class Utility {
  const KILOBYTE = 1024;
  
  /**
   * Generates a string representation for the given byte count.
   *
   * @param int $size
   *   A size in bytes.
   *
   * @return string
   *   A string representation of the size.
   */
  public static function formatSize(int $size) {
    $absolute_size = abs($size);
    if ($absolute_size < static::KILOBYTE) {
      return "{$size} byte" . ($absolute_size > 1 ? "s" : NULL);
    }
    foreach (['KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'] as $unit) {
      $absolute_size /= static::KILOBYTE;
      $rounded_size = \round($absolute_size, 2);
      if ($rounded_size < static::KILOBYTE) {
        break;
      }
    }
    return "{$rounded_size} {$unit}";
  }
  
  /**
   * Gets the non-FQN class name of an object.
   * 
   * @param object|string $thing
   *   Can either be a string of a FQN class or an object.
   *    
   * @return string|FALSE
   */
  public static function get_class_name($thing) {
    if (is_object($thing)) {
      $thing = \get_class($thing);
    }
    else if (!is_string($thing)) {
      return FALSE;
    }
    if ($pos = strrpos($thing, '\\')) {
      return substr($thing, $pos + 1);
    }
    return $thing;
  }
}

