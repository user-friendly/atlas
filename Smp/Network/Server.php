<?php
/**
 * @file
 * Server class file.
 */

namespace Smp\Network;

use Smp\Logger\LoggerTrait;

class Server {
  use LoggerTrait;
  
  /**
   * The server UNIX socket path.
   * 
   * @var string
   */
  protected $socketPath;
  
  /**
   * The server socket URL.
   * 
   * @var string
   */
  protected $socketUrl;
  
  /**
   * The server socket resource.
   * 
   * @var string
   */
  protected $socket;
  
  /**
   * Client connections.
   * 
   * @var \Smp\Network\Connection[]
   */
  protected $connections = [];
  
  /**
   * Connections cleanup frequency (seconds)
   * 
   * @var integer
   */
  protected $connectionCleanupTime = 5;
  
  /**
   * Timestamp of the last cleanup.
   * 
   * @var integer
   */
  protected $connectionCleanupLast = 0;
  
  /**
   * Default client connection timeout.
   * 
   * @var integer
   */
  protected $connectionTimeoutDefault = 60;
  
  /**
   * Server statistics.
   * 
   * @var array
   */
  protected $stats = [
    // Total connections handled (includes invalid too).
    'client_connections' => 0,
  ];
  
  // Not really used (not displayed, but populated?).
  protected $stat_total_bytes_sent = 0;
  
  protected $run = true;
  
  public function __construct() {
    $this->logger = static::getLogger();
    
    // The socket path should always be absolute.
    //$this->socketPath = \getcwd() . '/php-daemon.sock';
    //$this->socketUrl = "unix://{$this->socketPath}";
    $this->socketUrl = 'tcp://0.0.0.0:9999';
    
    $this->connectionCleanupLast = \time();
    
    $this->listen();
  }
  
  /**
   * Runs network server loop.
   * 
   * @return bool
   *   Returns TRUE if there are connections. FALSE, if there a no connections.
   */
  public function run() : bool {
    $this->accept();
    
    // Move bytes between the OS network buffers and connection class buffers.
    foreach ($this->connections as $key => $connection) {
      $connection->receive()
        ->send();
    }
    
    $this->cleanup();
    
    return !empty($this->connections);
  }
  
  // TODO Method name is no good.
  public function getBuffers() {
    foreach ($this->connections as $key => $connection) {
      
    }
  }
  
  protected function accept() : void {
    // TODO Should this method be part of the Connection class?
    //      As a static method - ::accpetConnection()
    if ($new_stream = @\stream_socket_accept($this->socket, 0)) {
      if (DEBUG) {
        $this->stats['client_connections']++;
      }
      $this->logger->debug("Client connected to server.");
      $this->connections[] = new Connection($new_stream, FALSE, $this->connectionTimeoutDefault);
    }
  }
  
  /**
   * Cleanup invalid client connections.
   */
  protected function cleanup() : void {
    if (\time() - $this->connectionCleanupLast < $this->connectionCleanupTime) {
      return;
    }
    
    foreach ($this->connections as $key => &$connection) {
      if (!$connection->isValid()) {
        $connection = NULL;
        if (DEBUG) {
          $this->logger->debug("Remove connection: $key");
        }
      }
    }
    
    if (DEBUG) {
      $old_count = count($this->connections);
    }
    
    // Remove NULL elements from the list.
    $this->connections = array_filter($this->connections);
    $this->connectionCleanupLast = \time();
    
    if (DEBUG) {
      $new_count = count($this->connections);
      $diff = $old_count - $new_count;
      if ($diff) {
        $this->logger->debug("Clients cleaned up $diff");
      }
    }
  }
  
  protected function listen() : void {
    // TODO Should this method not part of the Connection class?
    //      Static method or not?
    
    // Old trick cowboys use.
    if (\file_exists($this->socketPath)  && !\unlink($this->socketPath)) {
      throw new \RuntimeException("Failed to bind - could not remove existing socket $this->socketPath.");
    }
    
    $errno = 0;
    $errstr = 'NULL';
    $this->socket = \stream_socket_server($this->socketUrl, $errno, $errstr,
      STREAM_SERVER_BIND | STREAM_SERVER_LISTEN
    );
    
    if (!$this->socket ) {
      throw new \RuntimeException("Failed to create $this->socketPath, error: $errstr ($errno).");
    }
    else {
      if (DEBUG) {
        Connection::debugStreamInfo($this->socket, $this->logger);
      }
      $this->logger->debug("Bound to socket: $this->socketUrl");
    }
  }
  
  public function shutdown() : void {
    // TODO Do we need to do that for a Unix socket?
    if (!\stream_socket_shutdown($this->socket, STREAM_SHUT_RD)) {
      $this->logger->error("Failed to shutdown socket.");
    }
    
    if (!\fclose($this->socket)) {
      $this->logger->error("Failed to close socket.");
    }
    
    $this->logger->debug("Client connections for this run: {$this->stats['client_connections']} ");
    
    $this->logger->debug("Graceful shutdown.");
  }
}
