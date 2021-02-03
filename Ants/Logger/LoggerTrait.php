<?php
/**
 * @file
 * LoggerTrait file.
 */

namespace Ants\Logger;

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
    $channel = $channel ?? __CLASS__;
    return LoggerFactory::getLogger($channel);
  }
}

