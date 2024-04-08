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
    // Connected, but rejected for some reason.
    'client_rejections' => 0,
  ];
  
  // Not really used (not displayed, but populated?).
  protected $stat_total_bytes_sent = 0;
  
  protected $run = true;
  
  /**
   * Shutdown message.
   * 
   * Use this message when forcefully shutting down the server to
   * notify users, right before they get forcefully disconnected.
   * There is zero wait time between the message and disconnect.
   * 
   * @var string
   */
  protected $shutDownMessage = NULL;
  
  public function __construct() {
    $this->logger = static::getLogger();
    
    // The socket path should always be absolute.
    //$this->socketPath = \getcwd() . '/php-daemon.sock';
    //$this->socketUrl = "unix://{$this->socketPath}";
    //$this->socketUrl = 'tcp://0.0.0.0:9999';
    $this->socketUrl = 'tcp://[::]:9999';
    
    $this->connectionCleanupLast = \time();
    
    $this->listen();
  }
  
  /**
   * Runs network server loop.
   */
  public function run() {
    $this->accept();
    
    // Move bytes between the OS network buffers and connection class buffers.
    foreach ($this->connections as $key => $connection) {
      $connection->receive()
        ->send();
      
      yield $key => $connection;
    }
    
    $this->cleanup();
  }
  
  protected function accept() : void {
    $peer_name = NULL;
    if ($new_stream = @\stream_socket_accept($this->socket, 0, $peer_name)) {
      $new_connection = new Connection($new_stream, FALSE, $this->connectionTimeoutDefault);
      
      if (isset($this->connections[$peer_name])) {
        $this->logger->warning("Client already connected from remote point: {$peer_name}");
        $new_connection->close();
        $this->stats['client_rejections']++;
        return;
      }
      
      $this->connections[$peer_name] = $new_connection;
      
      $this->logger->info("Client connected to server: {$peer_name}");
      
      $this->stats['client_connections']++;
    }
  }
  
  /**
   * Cleanup invalid client connections.
   * 
   * @param boolean $timeout
   *   Whether to respect the timeout. Defaults to TRUE.
   *   Set to FALSE to force a cleanup.
   */
  protected function cleanup($timeout = TRUE) : void {
    if ($timeout && (\time() - $this->connectionCleanupLast < $this->connectionCleanupTime)) {
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
        $this->logger->info("Clients cleaned up $diff");
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
        // FIXME Why is the helper methond in the Connection class?
        Connection::debugStreamInfo($this->socket, $this->logger);
      }
      $this->logger->info("Bound to socket: $this->socketUrl");
    }
  }
  
  /**
   * Set shutdown message.
   *
   * Use this message when forcefully shutting down the server to
   * notify users, right before they get forcefully disconnected.
   * There is zero wait time between the message and disconnect.
   * 
   * Ideally, there should be no connected users by the time the
   * server is ready to shutdown. However, in case of system
   * kill signal at least be nice to the users.
   *
   * @param string $msg
   */
  public function setShutdownMessage($msg) {
    $this->shutDownMessage = $msg;
  }
  
  /**
   * Shutdown server.
   * 
   * Execute the shutdown when the server is ready to be stopped.
   * This should happen after all persistent data has been saved,
   * all users were given enough time to prepare and there are no
   * outstanding operations.
   */
  public function shutdown() : void {
    // Force shutdown on all clients.
    foreach ($this->connections as $key => $connection) {
      $address = $connection->getAddress();
      $this->logger->notice("Disconnect client: {$address}");
      
      if ($this->shutDownMessage) {
        $connection->append($this->shutDownMessage);
      }
      $connection->close(TRUE);
    }
    // Force cleanup.
    $this->cleanup(FALSE);
    
    if (!\stream_socket_shutdown($this->socket, STREAM_SHUT_RD)) {
      $this->logger->error("Failed to shutdown socket.");
    }
    
    if (!\fclose($this->socket)) {
      $this->logger->error("Failed to close socket.");
    }
    
    $this->logger->info("Client connections for this run: {$this->stats['client_connections']} ");
    $this->logger->info("Client connections rejected for this run: {$this->stats['client_rejections']} ");
    
    $this->logger->info("Shutdown complete.");
  }
}
