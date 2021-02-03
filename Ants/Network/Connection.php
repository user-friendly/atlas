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
   * Stream resource.
   * 
   * @var resource
   */
  protected $stream;
  
  protected $blockMode;
  
  protected $timeout;
  
  protected $readBuffer;
  
  protected $writeBuffer;
  
  protected $​chunkSize;
  
  /**
   * Connection constructor. 
   * 
   * @param resource $stream
   *   A stream resource, ready for I/O.
   */
  public function __construct($stream) {
    $this->logger = static::getLogger();
    
    if (is_resource($stream)) {
      throw new \RuntimeException("Connection needs a valid resource.");
    }
  }
  
  protected static $getters = [
    "blockMode", "timeout", "readBuffer", "writeBuffer", "chunkSize",
  ];
  
  public function __get(string $name) {
    if (in_array($name, static::$getters)) {
      return $this->$name;
    }
    return NULL;
  }
  
  public function __set(string $name, $value) : void {
    $method_name = "set" . \ucfirst($name);
    if (method_exists($this, $method_name)) {
      $this->$method_name();
    }
  }
  
  public function __isset(string $name) {
    if (in_array($name, static::$getters)) {
      return isset($this->$name);
    }
    return NULL;
  }
  
  protected function setBlockMode(bool $blocking) {
    $this->blockMode = $blocking;
    if (!\stream_set_blocking($this->stream, $this->blockMode)) {
      $this->logger->error("Failed to set stream blocking mode to: {$this->blockMode}");
    }
  }
  
  protected function setTimeout(int $timeout) {
    $this->timeout = $timeout;
    if (!\stream_set_timeout($this->stream, $this->timeout)) {
      $this->logger->error("Failed to set stream timeout to: {$this->timeout}");
    }
  }
  
  protected function setReadBuffer(int $size) {
    $this->readBuffer = $size;
    if (\stream_set_read_buffer($this->stream, $size) !== 0) {
      $this->logger->error("Failed to set stream read buffer to: {$this->readBuffer}");
    }
  }
  
  protected function setWriteBuffer(int $size) {
    $this->writeBuffer = $size;
    if (\stream_set_write_buffer($this->stream, $size) !== 0) {
      $this->logger->error("Failed to set stream write buffer to: {$this->writeBuffer}");
    }
  }
  
  protected function setChunkSize(int $size) {
    $this->chunkSize = $size;
    if (\stream_set_chunk_size($this->stream, $size) === FALSE) {
      $this->logger->error("Failed to set stream chunk size to: {$this->chunkSize}");
    }
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

