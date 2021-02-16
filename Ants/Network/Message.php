<?php
/**
 * @file
 * Message class file.
 */

namespace Ants\Network;

/**
 * Application Message.
 */
class Message {
  // TODO Poor man's state handling is happening in the interpretRaw() method.
  //      Consider moving this whole class as a set of states, in the
  //      \Ants\Network\State namespace.
  
  /**
   * Empty tag.
   * 
   * TODO What does this mean?
   * 
   * @var number
   */
  const TAG_NULL    = 0x00;

  // Unused header tags.
  const TAG_HELLO   = 0x02;
  const TAG_BYE     = 0x04;
  
  // Unused type tags.
  const TAG_STRING  = 0x40;
  const TAG_INT     = 0x41;
  
  /**
   * Tag for serialized data.
   * 
   * @var number
   */
  const TAG_SERIAL  = 0x42;
  
  /**
   * Maximum value length.
   *
   * Should be about 2GB (or PHP_INT_MAX on a 32 bit system).
   *
   * @var number
   */
  const MAX_VALUE_LENGHT = 0x7FFFFFFF;
  
  protected $header = '';
  protected $headerSize = 5;
  protected $headerUnpackFormat = 'c1TAG/N1LENGTH';
  protected $headerPackFormat = 'c1N1';
  
  protected $tag;
  // Expected buffer size, as read from the header's value.
  protected $size = 0;
  // Raw buffer - this is the message's value.
  protected $buffer = '';
  
  // The actual message value.
  protected $value = NULL;
  
  /**
   * Message completion status.
   * 
   * @var boolean
   */
  protected $complete = FALSE;
  
  public static function create($data) {
    $message = new Message();
    $message->tag = static::TAG_SERIAL;
    $message->value = $data;
    $message->buffer = @\serialize($message->value);
    $message->size = \strlen($message->buffer);
    $message->header = \pack($message->headerPackFormat, $message->tag, $message->size);
  }
  
  public static function createEmpty() {
    return new Message();
  }
  
  public function feedRaw(string $binary) : bool {
    $this->buffer .= $binary;
    $this->interpretRaw();
  }
  
  // Gets the raw message. Used for sending messages.
  public function raw() : string {
    throw new \Exception("TODO Implement method.");
  }
  
  // Whether the message has been fully interpreted.
  public function isComplete() : bool {
    return FALSE;
  }
  
  /**
   * Get the message's value.
   * 
   * @return mixed|FALSE
   *   Returns the message's value or FALSE if the message is not complete yet.
   */
  public function getValue() {
    if (!$this->isComplete()) {
      return FALSE;
    }
    return $this->value;
  }
  
  /**
   * Class ctor.
   */
  protected function __construct() {
    $this->tag = static::TAG_NULL;
  }
  
  protected function interpretRaw() : void {
    // Gobble up the header, if possible.
    if (!$this->header && \strlen($this->buffer) >= $this->headerSize) {
      $this->header = \substr($this->buffer, 0, $this->headerSize);
      $this->buffer = \substr($this->buffer, $this->headerSize);
    }
    // Do nothing if the header is not ready.
    if (\strlen($this->header) < $this->headerSize) {
      return;
    }
    // Barf on unexpected header size.
    else if (\strlen($this->header) != $this->headerSize) {
      $actual_sz = \strlen($this->header);
      throw new \RuntimeException("Message header size is {$actual_sz}. Expected header size is {$this->headerSize}");
    }
    // Initialize the header.
    else if ($this->tag === static::TAG_NULL) {
      $parts = @\unpack($this->headerUnpackFormat, $this->header);
      if (!(isset($parts['TAG']) && isset($parts['LENGTH']))) {
        $this->complete = FALSE;
        return;
      }
      $this->tag = $parts['TAG'];
      $this->size = $parts['LENGTH'];
    }
    
    if ($this->tag !== static::TAG_SERIAL) {
      throw new \RuntimeException("Currently, only the serial tag can be handled.");
    }
    
    // Wait until the buffer fills up.
    if (\strlen($this->buffer) < $this->size) {
      return;
    }
    // Something's up! Buffer's size should be equal to the header's length value.
    if (\strlen($this->buffer) > $this->size) {
      $actual_sz = \strlen($this->buffer);
      throw new \RuntimeException("Expected buffer is size {$this->size}. Actual buffer size is {$actual_sz}.");
    }
    
    try {
      // TODO Limit classes that can be created.
      // $opts = ['allowed_classes' => []];
      $this->value = @\unserialize($this->buffer/*, $opts*/);
    }
    catch (\Exception $e) {
      throw new \RuntimeException("The unserialized object threw an exception. Message: {$e->getMessage()}");
    }
    
    if ($this->value === FALSE) {
      throw new \RuntimeException("Failed to unserialized the message body.");
    }
    
    $this->complete = TRUE;
    return;
  }
}
