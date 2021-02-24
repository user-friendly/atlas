<?php
/**
 * @file
 * LoggerTrait file.
 */

namespace Ants\Logger;

use Ants\Utility;

/**
 * LoggerTrait.  
 */
trait LoggerTrait {
  /**
   * @var \Monolog\Logger
   */
  protected $logger;
  
  /**
   * Get a logger with a specific channel.
   * 
   * @param string $channel
   * @return \Monolog\Logger
   */
  public static function getLogger(string $channel = NULL) : \Monolog\Logger {
    $channel = $channel ?? Utility::get_class_name(static::class);
    return LoggerFactory::getLogger($channel);
  }
}

