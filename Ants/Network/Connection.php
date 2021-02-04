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
  
  const MARK_HELLO = 0x0002;
  const MARK_BYE   = 0x0004;

  /**
   * Stream resource.
   * 
   * @var resource
   */
  protected $stream;
  
  protected $blockMode;
  
  protected $timeout;
  
  /**
   * Stream read size.
   * 
   * @var int
   */
  protected $readSize = NULL;
  
  /**
   * Stream write size.
   * 
   * @var int
   */
  protected $writeSize = NULL;
  
  /**
   * Unbound read buffer.
   * 
   * @var string
   */
  protected $readBuffer = '';
  
  /**
   * Unbound write buffer.
   * 
   * @var string
   */
  protected $writeBuffer = '';
  
  protected static $getters = [
    "blockMode", "timeout", "readSize", "writeSize",
  ];
  
  /**
   * Connection constructor. 
   * 
   * @param resource $stream
   *   A stream resource, ready for I/O.
   */
  public function __construct($stream, $blockMode = TRUE, $timeout = 60, $readSize = 1500, $writeSize = 1500) {
    $this->logger = static::getLogger();
    
    if (!is_resource($stream)) {
      throw new \RuntimeException("Connection needs a valid resource.");
    }
    $this->stream = $stream;
    
    // Setup stream properties.
    $this->setBlockMode($blockMode);
    $this->setTimeout($timeout);
    $this->setReadSize($readSize);
    $this->setWriteSize($writeSize);
  }
  
  public function receive() {
    
  }
  
  public function process() {
    
  }
  
  public function send() {
    
  }
  
  protected function todoRenameMe() {
    // FIXME The handshake needs to be moved out of here and should not block.
    // Temporary set blocking mode, till the handshake is complete.
    \stream_set_blocking($new_stream, TRUE);
    \stream_set_timeout($new_stream, 1);
    if ($this->readHello($new_stream)) {
      
      \stream_set_blocking($new_stream, FALSE);
      \stream_set_timeout($new_stream, $this->client_socket_timeout);
      
      $this->logger->debug("handshake successful, say hello");
      // Send out a "nice to meet you".
      $this->writeBuffer($new_stream, "Hello from the daemon ({$this->main->getProcess()->pid})!");
      
      $this->client_sockets[] = $new_stream;
    }
    else {
      \fclose($new_stream);
    }
  }
  
  protected function readHello($socket) : bool {
    $hello = $this->readBuffer($socket, 2);
    if ($hello && \strlen($hello) == 2) {
      $mark = (int) @unpack("nSINT", $hello)['SINT'];
      if ($mark == static::MARK_HELLO) {
        return TRUE;
      }
      else {
        $hex_header = \dechex($mark);
        $this->logger->error("invalid HELLO mark (0x{$hex_header}), disconnecting client.");
      }
    }
    else {
      $this->logger->error("failed to read HELLO mark, disconnecting client.");
    }
    return FALSE;
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
      $this->fclose();
      throw new \RuntimeException($error['message'] ? $error['message'] : 'Unknown stream read error.');
    }
    // TODO In DEBUG mode, save byte count to stats
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
    $binary = $this->fwrite($binary);
    if (\strlen($binary) !== $binary) {
      $error = \error_get_last();
      \error_clear_last();
      $this->fclose();
      throw new \RuntimeException($error['message'] ? $error['message'] : 'Unknown stream write error.');
    }
    // TODO In DEBUG mode, save byte count to stats
    return $binary;
  }
  
  /**
   * Check whether the connection is valid.
   * 
   * A valid connection is one that has a stream resources and is connected to
   * a client.
   * 
   * @return boolean
   *   True if the connection is valid, false otherwise.
   */
  public function isValid() {
    return \is_resource($this->stream) && !\feof($this->stream);
  }

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
  
  /**
   * Checks whether the stream reached EOF.
   * 
   * @see \feof()
   * 
   * @return boolean
   */
  protected function feof() {
    return \feof($this->stream);
  }
  
  /**
   * Closes the stream.
   * 
   * @see \fclose()
   * 
   * @return boolean
   */
  protected function fclose() {
    return \fclose($this->stream);
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
  
  protected function setReadSize(int $size) {
    $this->readSize = $size;
  }
  
  protected function setWriteSize(int $size) {
    $this->writeSize = $size;
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

