<?php
/**
 * @file
 * Server class file.
 */

namespace Smp\Network;

use Smp\Logger\LoggerTrait;

/**
 * Server class.
 * 
 * FIXME Re-examine the cleanup and when the disconnection event handler is fired.
 *       The notification is triggered during the cleanup op, and the cleanup op is
 *       done once every N number of seconds (5?).
 *       
 * FIXME This whole class needs to be simplified and probably the conenctions class
 *       can be integrated into this one.
 * 
 * NOTE Server connection consume chunks of data, up to (default) 1 KB.
 *      The command/logical handing of raw data should consider that.
 */
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
   * Client address map.
   * 
   * @var array
   */
  protected $connectionsAddressMap = [];
  
  /**
   * 
   * 
   * @var integer
   */
  protected $connectionCurrentId = 0;
  
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
   * Connection to disconnect.
   * 
   * Used for graceful disconnect.
   * 
   * @var array
   */
  protected $connectionShutdown = [];
  
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
  
  protected $run = true;
  
  protected $listenerConnected = NULL;
  protected $listenerDisconnected = NULL;
  
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
  
  protected function accept() : void {
    $peer_name = NULL;
    if ($new_stream = @\stream_socket_accept($this->socket, 0, $peer_name)) {
      $new_connection = new Connection($new_stream, FALSE, $this->connectionTimeoutDefault);
      
      $key = $this->getNextClientId();
      // TODO Make a server config to control multiple host connections.
      // $address = $new_connection->getIp();
      $address = $new_connection->getAddress();
      
      if (isset($this->connectionsAddressMap[$address])) {
        $this->logger->warning("Client already connected from remote address: {$address}");
        $new_connection->close();
        $this->stats['client_rejections']++;
        return;
      }
      
      $this->connections[$key] = $new_connection;
      $this->connectionsAddressMap[$address] = $key;
      
      $this->logger->info("Client connected to server: {$peer_name}");
            
      $this->stats['client_connections']++;
      
      // Finally, notify listener.
      \call_user_func($this->listenerConnected, $key);
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
    // FIXME Magic variable.
    $max_ticks = 3;
    // $ticks is ::run() cycles passed without any output.
    foreach ($this->connectionShutdown as $key => $ticks) {
      if ($this->connections[$key]->isValid() && $ticks > $max_ticks) {
        $this->close($key);
        unset($this->connectionShutdown[$key]);
      }
    }    
    
    if ($timeout && (\time() - $this->connectionCleanupLast < $this->connectionCleanupTime)) {
      return;
    }
    
    foreach ($this->connections as $key => &$connection) {
      if (!$connection->isValid()) {
        
        $address = \array_search($key, $this->connectionsAddressMap);
        if ($address) {
          $this->connectionsAddressMap[$address] = NULL;
        }
        
        \call_user_func($this->listenerDisconnected, $key, isset($this->connectionShutdown[$key]));
        
        unset($this->connectionShutdown[$key]);
        $connection = NULL;
        
        if (DEBUG) {
          $this->logger->debug("Remove connection: $key");
        }
      }
    }
    
    if (DEBUG) {
      $old_count = \count($this->connections);
    }
    
    // Remove NULL elements from the list.
    $this->connections = array_filter($this->connections);
    $this->connectionsAddressMap = array_filter($this->connectionsAddressMap);
    $this->connectionCleanupLast = \time();
    
    if (DEBUG) {
      $new_count = \count($this->connections);
      $diff = $old_count - $new_count;
      if ($diff) {
        $this->logger->info("Clients cleaned up $diff");
      }
    }
  }
  
  protected function listen() : void {
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
  
  protected function getNextClientId() {
    return ++$this->connectionCurrentId;
  }
  
  /**
   * Runs network server loop.
   */
  public function run() {
    $this->accept();
    
    // Move bytes between the OS network buffers and connection class buffers.
    foreach ($this->connections as $key => $connection) {
      // FIXME Could just move the connection to shutdown out of ::connections.
      if (isset($this->connectionShutdown[$key])) {
        if (0 == $connection->getSendSize()) {
          $this->connectionShutdown[$key]++;
        }
        else {
          $this->connectionShutdown[$key] = 1;
        }
      }
      else {
        $connection->receive();
      }
      $connection->send();
    }
    
    $this->cleanup();
  }
  
  public function input() {
    foreach ($this->connections as $key => $connection) {
      // FIXME Could just move the connection to shutdown out of ::connections.
      if (isset($this->connectionShutdown[$key])) {
        continue;
      }
      if ($input = $connection->consume()) {
        yield $key => $input;
      }
    }
  }
  
  public function output($key, $output) {
    @$this->connections[$key]?->append($output);
  }
  
  
  /**
   * Gracefully disconnect a client.
   * 
   * The client might not be immediately disconnected, but after
   * all output buffer has been flushed and on the next ::run().
   * 
   * @param string $key
   * @return bool
   *   True if the request was successful.
   */
  public function disconnect($key) : bool {
    if (isset($this->connections[$key])) {
      if (DEBUG) {
        $this->logger->debug("Disconnect connection: $key");
      }
      $this->connectionShutdown[$key] = 1;
      return TRUE;
    }
    if (DEBUG) {
      $this->logger->debug("Cannot disconnect connection. Key not found: $key");
    }
    return FALSE;
  }
  
  /**
   * Shutdown a connection.
   *
   * Unlike diconnect(), closes stream immediately.
   *
   * @param string $key
   */
  public function close($key) : bool {
    if (isset($this->connections[$key])) {
      if (DEBUG) {
        $this->logger->debug("Close connection: $key");
      }
      return $this->connections[$key]->close(TRUE);
    }
    if (DEBUG) {
      $this->logger->debug("Cannot close connection. Key not found: $key");
    }
    return FALSE;
  }
  
  public function setListenerConnected($callback) {
    $this->listenerConnected = $callback;
  }
  
  public function setListenerDisconnected($callback) {
    $this->listenerDisconnected = $callback;
  }
  
  // Only the Game class handles events. No need to make it generic.
  // public function removeListener(string $event, $callback) {}
  
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
