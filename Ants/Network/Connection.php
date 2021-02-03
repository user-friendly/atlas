<?php
/**
 * @file
 * Connection class file.
 */

namespace Ants\Network;

use Monolog\Logger;
use Ants\Logger\LoggerTrait;

/**
 * A stream connection class.
 */
class Connection {
  use LoggerTrait;

  /**
   * Ctor.
   */
  public function __construct() {
    $this->logger = static::getLogger();
  }
  
  public static function debugStreamInfo($stream, Logger $logger) {
    $logger->debug("Socket open: " . \stream_socket_get_name($stream, FALSE));
    
    if (\stream_supports_lock($stream)) {
      $logger->debug("Socket supports locks.");
    }
    else {
      $logger->debug("Socket does not support locks.");
    }
    
    if (\stream_is_local($stream)) {
      $logger->debug("Socket is local.");
    }
    else {
      $logger->debug("Socket is not local.");
    }
    
    if (\stream_isatty($stream)) {
      $logger->debug("Socket is tty.");
    }
    else {
      $logger->debug("Socket is not tty.");
    }
  }
}

