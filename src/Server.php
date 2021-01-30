<?php
/**
 * @file
 * Server class file.
 */

namespace Parallel;

class Server {
  const MARK_HELLO = 0x0002;
  const MARK_BYE   = 0x0004;
  
  /**
   * @var \Parallel\Main;
   */
  protected $main;
  
  // The socket path should always be absolute.
  protected $sock_path;
  protected $sock_url;
  //$sock_url = 'tcp://0.0.0.0:9999';
  protected $socket;
  
  protected $readLength = 1024;
  // Not used at the moment.
  protected $writeLength = 1024;
  
  protected $client_sockets = [];
  // In seconds.
  protected $client_socket_timeout = 60;
  protected $client_sockets_filter = FALSE;
  
  protected $stat_total_all_time = 0;
  
  // Not really used (not displayed, but populated?).
  protected $stat_total_bytes_sent = 0;
  
  public function __construct(Main $main) {
    $this->main = $main;
    
    // The socket path should always be absolute.
    $this->sock_path = \getcwd() . '/php-daemon.sock';
    $this->sock_url = "unix://{$this->sock_path}";
    
    // $this->sock_url = 'tcp://0.0.0.0:9999';
    
    $this->main->getProcess()->addSignalHandler([$this, "shutdown"]);
  }
  
  public function run() : int {
    $this->listen();
    
    while (TRUE) {
      
      $this->accept();
      
      $this->read();
      
      $this->cleanupClients();
      
      // Yield CPU time only when there's nothing to do. 
      if (empty($this->client_sockets)) {
        $this->main->getProcess()->yield();
      }
    }
    
    $this->shutdown();
    
    return Main::EXIT_SUCCESS;
  }
  
  protected function accept() : void {
    if ($new_client = @\stream_socket_accept($this->socket, 0)) {
      $this->stat_total_all_time++;
      $this->main->println_debug("Server: client connected to server! (stat: {$this->stat_total_all_time})");
      
      
      // FIXME The handshake needs to be moved out of here and should not block.
      // Temporary set blocking mode, till the handshake is complete.
      \stream_set_blocking($new_client, TRUE);
      \stream_set_timeout($new_client, 1);
      if ($this->readHello($new_client)) {
        
        \stream_set_blocking($new_client, FALSE);
        \stream_set_timeout($new_client, $this->client_socket_timeout);

        $this->main->println_debug("Server: handshake successful, say hello");
        // Send out a "nice to meet you".
        $this->writeBuffer($new_client, "Hello from the daemon ({$this->main->getProcess()->pid})!");
        
        $this->client_sockets[] = $new_client;
      }
      else {
        \fclose($new_client);
      }
    }
//     else if (DEBUG) {
//       $this->main->println_debug("Server: failed to accept new client.");
//     }
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
        $this->main->log_error("Server: invalid HELLO mark (0x{$hex_header}), disconnecting client.");
      }
    }
    else {
      $this->main->log_error("Server: failed to read HELLO mark, disconnecting client.");
    }
    return FALSE;
  }
 
  // The following read/write are bulk I/O methods.
  
  protected function read() : void {
    foreach ($this->client_sockets as &$client_sock) {
      if ($recv_buff = \fread($client_sock, 1024)) {
        $this->main->println_debug("Server: from client: $recv_buff");
      }
      
      // TODO Move to a separate method?
      if (\feof($client_sock)) {
        
        $this->main->println_debug("Server: closing client connection.");
        
        \fflush($client_sock);
        \fclose($client_sock);
        
        $client_sock = NULL;
        
        $this->client_sockets_filter = TRUE;
      }
    }
  }
  
  protected function write() : void {}
  
  // The following read/write are singular I/O methods.
  
  protected function readBuffer($socket, $length = NULL) : string {
    return \fread($socket, $length ?? $this->readLength);
  }
  
  protected function writeBuffer($socket, string $data, int $length = NULL) {
    $bytes = \fwrite($socket, $data, $length ?? \strlen($data));
    \fflush($socket);
    $this->stat_total_bytes_sent += $bytes;
    return $bytes;
  }
  
  // TODO Cleanup should be less frequent?
  protected function cleanupClients() : void {
    if (!$this->client_sockets_filter) {
      return;
    }
    
    if (DEBUG) $old_count = count($this->client_sockets);
    $this->client_sockets = array_filter($this->client_sockets);
    if (DEBUG) $new_count = count($this->client_sockets);
    $this->client_sockets_filter = FALSE;
    
    $this->main->println_debug("Server: clients cleaned up " . ($old_count - $new_count));
    
  }
  
  protected function listen() : void {
    //Old trick cowboys use.
    if (\file_exists($this->sock_path)  && !\unlink($this->sock_path)) {
      throw new \RuntimeException("Failed to bind - could not remove existing socket $this->sock_path.");
    }
    
    $errno = 0;
    $errstr = 'NULL';
    $this->socket = \stream_socket_server($this->sock_url, $errno, $errstr,
      STREAM_SERVER_BIND | STREAM_SERVER_LISTEN
      );
    
    if (!$this->socket ) {
      throw new \RuntimeException("Failed to create $this->sock_path, error: $errstr ($errno).");
    }
    else {
      $this->main->println_debug("Bound to socket: $this->sock_url");
    }
  }
  
  public function shutdown() : void {
    // TODO Do we need to do that for a Unix socket? It goes both ways...
    if (!\stream_socket_shutdown($this->socket, STREAM_SHUT_RD)) {
      log_error("Server: failed to shutdown socket.");
    }
    
    if (!\fclose($this->socket)) {
      log_error("Server: failed to close socket.");
    }
    
    $this->main->println_debug("Server: shutdown.");
  }
}
