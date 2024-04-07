<?php
/**
 * @file
 * Simple TCP network client.
 */

namespace SmpTest\Network;

use SmpTest\Logger;

class Client {
  const NEW_LINE = "\r\n";
  
  protected $logger = NULL;
  
  protected $address = 'localhost';
  protected $port = 9999;
  protected $stream_address = 'tcp://localhost:9999';
  protected $stream_timeout = 30;
  
  // File pointer.
  protected $fp = NULL;
  
  protected $input_timeout = 1;
  protected $input_time = 0;
  protected $input_time_last = 0;
  
  protected $sendBuffer = [];
  protected $recvBuffer = NULL;
  
  function __construct($address, $port) {
    $this->logger = Logger::getChannel(static::class);
    
    if (\gethostbyname($address) === $address) {
      $this->logger->warning("Could not resolve hostname. Assuming IP given.");
      if (!\filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $this->logger->warning("Invalid IPv4 address: {$address}. Trying IPv6...");
        
        if (!\filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
          throw new \Exception("Invalid IPv6 address: {$address}");
        }
        else {
          $address = "[{$address}]";
        }
      }
    }
    $this->address = $address;
    
    if (!\filter_var($port, FILTER_VALIDATE_INT)) {
      throw new \Exception("Invalid port: {$port}");
    }
    
    $this->port = \abs(\intval($port));
    
    $this->stream_address = "tcp://{$address}:{$port}";
  }
  
  public function connect() {
    $this->logger->info("Connecting to: {$this->stream_address}");
    $errno = NULL;
    $errstr = NULL;
    $this->fp = \stream_socket_client($this->stream_address, $errno, $errstr, $this->stream_timeout);
    if (!$this->fp) {
      throw new \Exception("Socket error: $errstr ($errno)");
    }
    \stream_set_blocking($this->fp, FALSE);
    \stream_set_timeout($this->fp, $this->stream_timeout);
  }
  
  public function close() {
    if ($this->fp) {
      $this->logger->debug("Stream not closed, flush it.");
      \fflush($this->fp);
    }
    \fclose($this->fp);
    if (!$this->fp) {
      $this->logger->debug("Stream is not CLOSED!");
    }
  }
  
  public function addTestLoginCommands() {
    $cmds = [
      "test-local",
      "test-local",
      "look",
      "quit",
      "yes",
    ];
    foreach ($cmds as $cmd) {
      $this->addCommand($cmd);
    }
  }
  
  public function addCommand($str) {
    $this->sendBuffer[] = $str;
  }
  
  protected function clearCommands() {
    $this->sendBuffer = [];
  }
  
  protected function getCommandString() {
    return \implode(static::NEW_LINE, $this->sendBuffer) . static::NEW_LINE;
  }
  
  public function test() {
    $this->logger->info("Send test payload to server. Printing payload to STDOUT.");
    
    $this->addTestLoginCommands();
    $sendBuffer = $this->getCommandString();
    
    echo "send: " . $sendBuffer . PHP_EOL;
    
    \fwrite($this->fp, $sendBuffer);
    
    $this->clearCommands();
    
    \fflush($this->fp);
    
    $reply = FALSE;
    $this->input_time = \time();
    $this->input_time_last = 0;
    
    while (!\feof($this->fp) && ($this->input_timeout > ($this->input_time_last - $this->input_time))) {
      $this->recvBuffer = \fgets($this->fp, 1024);
      if ($this->recvBuffer !== FALSE) {
        $reply = TRUE;
        
        $sz = \strlen($this->recvBuffer);
        $hex = \bin2hex($this->recvBuffer);
        $this->logger->debug("received {$sz} bytes: {$hex}");
        
        echo "received: " . $this->recvBuffer . PHP_EOL;
      }
      $this->input_time_last = \time();
    }
    echo "receiving done" . PHP_EOL;
    
    if (!$reply) {
      $this->logger->info("No reply from server.");
      $this->logger->info("Time spent waiting for a reply: " . ($this->input_time_last - $this->input_time));
    }
  }
}
