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
  
  /**
   * Stream native read buffer size.
   * 
   * @var int
   */
  protected $readBuffer = NULL;
  
  /**
   * Stream native write buffer size.
   * 
   * @var int
   */
  protected $writeBuffer = NULL;
  
  /**
   * Stream native chunk size size.
   * 
   * @var int
   */
  protected $​chunkSize = NULL;
  
  /**
   * Connection constructor. 
   * 
   * @param resource $stream
   *   A stream resource, ready for I/O.
   */
  public function __construct($stream, $blockMode = TRUE, $timeout = 60) {
    $this->logger = static::getLogger();
    
    if (!is_resource($stream)) {
      throw new \RuntimeException("Connection needs a valid resource.");
    }
    $this->stream = $stream;
    
    // Setup stream properties.
    $this->setBlockMode($blockMode);
    $this->setTimeout($timeout);
    
    // No idea what the following buffers will change, in terms of performance.
    // Just let them be the default.
    //$this->setReadBuffer($readBuffer);
    // Doesn't seem to work (on my machine, PHP 7.3).
    // $this->setWriteBuffer($writeBuffer);
    // It seems chunk size sets the maximum packet size. If a packet larger than
    // chunk is received, it will unblock a blocked \fread() and a chunk sized
    // string will be returned.
    //$this->setChunkSize($chunkSize);
  }
  
  public function doReadTestAndDie() {
    $this->logger->debug("Consume 512 bytes...");
    $msg  = \fread($this->stream, 512);
    $bytes = \strlen($msg);
    $this->logger->debug("Read $bytes.");
    
    $this->logger->debug("First read of 512 bytes...");
    $msg  = \fread($this->stream, 512);
    $bytes = \strlen($msg);
    $this->logger->debug("Read $bytes.");
    
    die();
  }
  
  /**
   * Read some binary data from stream.
   * 
   * Do not use this method to send data unless you know what you're doing.
   * 
   * @see \fread()
   * @see Connection::fread()
   * 
   * @param int $size
   * @throws \RuntimeException
   * @return string
   *   Returned binary data.
   */
  public function readSome(int $size = 1500) : string {
    $binary = $this->fread($size);
    if ($binary === FALSE) {
      $error = \error_get_last();
      \error_clear_last();
      throw new \RuntimeException($error['message'] ? $error['message'] : 'Unknown stream read error.');
    }
    // if (DEBUG) save bytes to stats 
    return $binary;
  }
  
  /**
   * Write some binary data to stream.
   * 
   * Do not use this method to send data unless you know what you're doing.
   * 
   * @see \fwrite()
   * @see Connection::fwrite()
   * 
   * @param string|array $binary
   *   Bytes to be written, as a string or as an array of bytes. 
   * @throws \RuntimeException
   *   In case there's a mismatch between bytes to be written and bytes written.
   * @return int
   *   Number of bytes written.
   */
  public function sendSome($binary) : int {
    if (is_array($binary)) {
      $binary = \implode($binary);
    }
    else if (!is_string($binary)) {
      throw new \RuntimeException("Binary bust be a string or an array of characters");
    }
    $bytes = $this->fwrite($binary);
    if (\strlen($binary) !== $bytes) {
      $error = \error_get_last();
      \error_clear_last();
      throw new \RuntimeException($error['message'] ? $error['message'] : 'Unknown stream write error.');
    }
    // if (DEBUG) save bytes to stats 
    return $bytes;
  }
  
  /**
   * Binary-safe stream read.
   * 
   * @see \fread()
   * 
   * @param int $size
   * @return string
   */
  protected function fread(int $size = 1500) : string {
    return @\fread($this->stream, $size);
  }
  
  /**
   * Binary-safe stream write.
   * 
   * @see \fwrite()
   * 
   * @param string $binary
   * @return number|bool
   */
  protected function fwrite(string $binary) {
    return @\fwrite($this->stream, $binary, \strlen($binary));
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
      $this->$method_name($value);
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
    if (\stream_set_read_buffer($this->stream, $this->readBuffer) !== 0) {
      $this->logger->error("Failed to set stream read buffer to: {$this->readBuffer}");
    }
  }
  
  protected function setWriteBuffer(int $size) {
    $this->writeBuffer = $size;
    if (\stream_set_write_buffer($this->stream, $this->writeBuffer) !== 0) {
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

