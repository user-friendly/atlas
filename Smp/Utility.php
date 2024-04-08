<?php
/**
 * @file
 * Utility class file.
 */

namespace Smp;

/**
 * Utility class.
 */
class Utility {
  
  const NEW_LINE = "\r\n";
  
  /**
   * KB constant.
   * 
   * @var integer
   */
  const KILOBYTE = 1024;
  
  static protected $printFormatBinary = '%0' . (PHP_INT_SIZE * 8) . 'b';
  
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
   * Verbose integer binary printer.
   * 
   * @param int $binary
   * @return string
   */
  public static function formatBinary(int $binary) {
    $str = '';
    foreach (\array_chunk(\str_split(\sprintf(static::$printFormatBinary, $binary), 4), 2) as $index => $pair) {
      $offset = PHP_INT_SIZE - $index;
      $str .= "{$offset}:[{$pair[0]} {$pair[1]}] ";
    }
    return \rtrim($str);
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
  
  /**
   * Translate text.
   * 
   * @param string $text
   * @return string
   */
  public static function t($text) {
    return $text;
  }
  
  /**
   * Get the current timestamp.
   * 
   * A proxy to \time().
   * 
   * @return int
   */
  public static function getTime() : int {
    return \time();
  }
  
  /**
   * Get current micro timestamp.
   * 
   * A proxy to \microtime().
   * 
   * @return float
   */
  public static function getTimeMicro() : float {
    return \microtime(TRUE);
  }
}
