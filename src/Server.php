<?php
/**
 * @file
 * Server class file.
 */

namespace Parallel;

class Server {
  
  /**
   * @var \Parallel\Main;
   */
  protected $main;
  
  // The socket path should always be absolute.
  protected $sock_path;
  protected $sock_url;
  //$sock_url = 'tcp://0.0.0.0:9999';
  protected $socket;
  
  protected $client_sockets = [];
  protected $client_sockets_filter = FALSE;
  protected $stat_total_all_time = 0;
  
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
      
      // TODO Do we need these? What do they even mean?
      \stream_set_blocking($new_client, FALSE);
      // \stream_set_timeout($new_client, 60);
      
      $this->client_sockets[] = $new_client;
    }
//     else if (DEBUG) {
//       $this->main->println_debug("Server: failed to accept new client.");
//     }
  }
  
  protected function read() : void {
    foreach ($this->client_sockets as &$client_sock) {
      if ($recv_buff = \fread($client_sock, 1024)) {
        $this->main->println_debug("Server: from client: $recv_buff");
      }
      if (\feof($client_sock)) {
        
        $this->main->println_debug("Server: closing client connection.");
        
        \fflush($client_sock);
        \fclose($client_sock);
        
        $client_sock = NULL;
        
        $this->client_sockets_filter = TRUE;
      }
    }
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
