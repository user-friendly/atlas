<?php
/**
 * @file
 * Server/Client test code bucket.
 */

namespace Ants\Tests;

class Server {
  protected function accept() : void {
    
    $this->logger->debug("Generating a lot of data...");
    $total_bytes = 0;
    $packet_size = 1024*1024;
    $payload = [];
    for ($i = 0; $i < 4; $i++) {
      $binary = Dummy::generateDummyPayload_String($packet_size);
      $payload[] = $binary;
      $total_bytes += \strlen($binary);
    }
    $this->logger->debug("Done generating data. Total bytes: {$total_bytes}.");
    
    if ($new_client = @\stream_socket_accept($this->socket, 60)) {
      $this->stat_total_all_time++;
      $this->logger->debug("client connected to server! (stat: {$this->stat_total_all_time})");
      
      
      \stream_set_blocking($new_client, TRUE);
      \stream_set_timeout($new_client, 60);
      
      $conn = new Connection($new_client);
      
      foreach ($payload as $binary) {
        $this->logger->debug("Send packet of {$packet_size} bytes");
        $conn->sendSome($binary);
        \sleep(1);
      }
      $this->logger->debug("Done with test");
      
      die();
      
      // FIXME The handshake needs to be moved out of here and should not block.
      // Temporary set blocking mode, till the handshake is complete.
      \stream_set_blocking($new_client, TRUE);
      \stream_set_timeout($new_client, 1);
      if ($this->readHello($new_client)) {
        
        \stream_set_blocking($new_client, FALSE);
        \stream_set_timeout($new_client, $this->client_socket_timeout);
        
        $this->logger->debug("handshake successful, say hello");
        // Send out a "nice to meet you".
        $this->writeBuffer($new_client, "Hello from the daemon ({$this->main->getProcess()->pid})!");
        
        $this->client_sockets[] = $new_client;
      }
      else {
        \fclose($new_client);
      }
    }
    //     else if (DEBUG) {
    //       $this->logger->debug("failed to accept new client.");
    //     }
  }
}

class Connection {
  public function doReadTestAndDie() {
    $bytes_read = 0;
    $report_size = 1024*1024;
    $payload = '';
    while ($binary = $this->readSome()) {
      $payload .= $binary;
      $bytes_read += \strlen($binary);
      if ($bytes_read >= $report_size) {
        $this->logger->debug("Read {$report_size} bytes.");
        $bytes_read = 0;
      }
    }
    $this->logger->debug("Read {$report_size} bytes.");
    
    $total_bytes = \strlen($payload);
    $this->logger->debug("Total bytes read {$total_bytes}.");
    
    die();
    
    // sleep(3600);
    
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
}
