<?php
/**
 * @file
 * Connection class file.
 */

// FIXME Inconsistent error handling - are you gonna throw exceptions or return
//       FALSE?!

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
  
  // FIXME Temporary
  protected $state = 0;
  
  /**
   * Connection constructor. 
   * 
   * @param resource $stream
   *   A stream resource, ready for I/O.
   */
  public function __construct($stream, $blockMode = TRUE, $timeout = 60, $readSize = 1500, $writeSize = 1500) {
    $this->logger = static::getLogger();
    
    if (!is_resource($stream)) {
      throw new \RuntimeException("Connection needs a valid stream resource.");
    }
    $this->stream = $stream;
    
    // Setup stream properties.
    $this->setBlockMode($blockMode);
    $this->setTimeout($timeout);
    $this->setReadSize($readSize);
    $this->setWriteSize($writeSize);
  }
  
  /**
   * Close the stream connection.
   * 
   * Optionally, flush the write buffer.
   * 
   * @param bool $flush
   *   Attempt to send any remaining data. Depending on what blocking mode the
   *   stream is in, flushing is potentially a blocking operation.
   * 
   * @return bool
   *   Returns TRUE if the stream was closed successfully. FALSE otherwise.
   */
  public function close(bool $flush = FALSE) : bool {
    if ($flush) {
      // Attempt to flush the write buffer.
      while ($this->writeBuffer) {
        $this->send();
      }
    }
    return $this->fclose();
  }
  
  /**
   * Receive data.
   */
  public function receive() : void {
    $read_buff = $this->readSome($this->readSize);
    
    if (\strlen($read_buff)) {
      $this->readBuffer .= $read_buff;
      
      if (DEBUG) {
        $sz = \strlen($read_buff);
        $hex = \bin2hex($read_buff);
        $this->logger->debug("received {$sz} bytes: {$hex}");
      }
    }
  }
  
  /**
   * Process connection state.
   */
  public function process() : void {
    switch ($this->state) {
      case 0:
        $this->append(\pack('n', static::MARK_HELLO));
        $this->state = 1;
        
        $this->logger->debug("Hello mark sent");
        break;
      case 1:
        // FIXME Magic number.
        $binary = $this->consume(2);
        if ($binary !== FALSE) {
          $hello_mark_hex = \bin2hex($binary);
          if (static::MARK_HELLO === ((int) @unpack("nSINT", $binary)['SINT'])) {
            // Received Hello from remote end.
            $this->state = 2;
            
            $this->logger->debug("Hello mark received (0x{$hello_mark_hex})");
          }
          else {
            $this->logger->error("invalid HELLO mark (0x{$hello_mark_hex}), disconnecting");
            $this->fclose();
          }
        }
        break;
      case 2:
        // ...guess state 2 is just this message.
        $this->logger->debug("handshake successful");
        $this->state = 3;
        break;
      case 3:
        // TODO What now?
        $this->append(\pack('n', static::MARK_BYE));
        $this->state = 4;
        break;
      case 4:
        // FIXME Magic number.
        $binary = $this->peek(2);
        if (static::MARK_BYE === ((int) @unpack("nSINT", $binary)['SINT'])) {
          $bye_mark_hex = \bin2hex($binary);
          $this->logger->debug("Bye mark received (0x{$bye_mark_hex})");
          $this->close();
        }
        break;
      default:
        $this->fclose();
        throw new \RuntimeException("Unhandled connection state.");
        break;
    }
  }
  
  /**
   * Sends data to the receiver.
   */
  public function send() : void {
    if (\strlen($this->writeBuffer) > 0) {
      // Peek the packet to be sent.
      $binary = \substr($this->writeBuffer, 0, $this->writeSize);
      // TODO Catch exception?
      $this->sendSome($binary);
      // Consume bytes from the write buffer. Should be an expensive memory
      // allocation operation.
      $this->writeBuffer = \substr($this->writeBuffer, $this->writeSize);
    }
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
    $bytes_written = $this->fwrite($binary);
    if (\strlen($binary) !== $bytes_written) {
      $error = \error_get_last();
      \error_clear_last();
      $this->fclose();
      throw new \RuntimeException($error['message'] ? $error['message'] : 'Unknown stream write error.');
    }
    // TODO In DEBUG mode, save byte count to stats
    return $bytes_written;
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
   * Consumes exactly $size bytes from the read buffer.
   * 
   * @param int $size
   * @return string|boolean
   *   Returns the binary string or FALSE if buffer was less than $size.
   */
  protected function consume(int $size) {
    if (\strlen($this->readBuffer) >= $size) {
      $binary = \substr($this->readBuffer, 0, $size);
      $this->readBuffer = \substr($this->readBuffer, $size);
      return $binary;
    }
    return FALSE;
  }
  
  /**
   * Peek exactly $size bytes from the read buffer.
   * 
   * Unlike \Ants\Network\Connection::consume(), the read buffer is not altered.
   * 
   * @param int $size
   * @return string|boolean
   *   Returns the binary string or FALSE if buffer was less than $size.
   */
  protected function peek(int $size) {
    if (\strlen($this->readBuffer) >= $size) {
      return \substr($this->readBuffer, 0, $size);
    }
    return FALSE;
  }
  
  /**
   * Append to the write buffer.
   * 
   * @param string $binary
   */
  protected function append(string $binary) {
    $this->writeBuffer .= $binary;
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
    return \fread($this->stream, $size);
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
    return \fwrite($this->stream, $binary, \strlen($binary));
  }
  
  /**
   * Checks whether the stream reached EOF.
   * 
   * @see \feof()
   * 
   * @return boolean
   */
  public function feof() {
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
