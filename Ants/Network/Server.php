<?php
/**
 * @file
 * Server class file.
 */

namespace Ants\Network;

use Ants\Game;
use Ants\Logger\LoggerTrait;
use Ants\Process;

class Server {
  use LoggerTrait;
  
  /**
   * The main process of the program.
   * 
   * @var \Ants\Process;
   */
  protected $process;
  
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
   * @var \Ants\Network\Connection[]
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
  
  protected $users = [];
  
  public function __construct(Process $process) {
    $this->logger = static::getLogger();
    $this->process = $process;
    
    // The socket path should always be absolute.
    //$this->socketPath = \getcwd() . '/php-daemon.sock';
    //$this->socketUrl = "unix://{$this->socketPath}";
    $this->socketUrl = 'tcp://0.0.0.0:9999';
    
    $this->connectionCleanupLast = \time();
    
    $this->process->addSignalHandler([$this, "signalShutdown"]);
  }
  
  public function run() : int {
    $this->listen();
    
    while ($this->run) {
      
      $this->accept();
      
      $this->handleConnections();
      
      $this->cleanup();
      
      // Yield CPU time only when there's nothing to do. 
      if (empty($this->connections)) {
        $this->process->yield();
      }
    }
    
    $this->logger->debug("Main server loop done.");
    
    $this->shutdown();
    
    return Game::EXIT_SUCCESS;
  }
  
  protected function accept() : void {
    // TODO Should this method be part of the Connection class?
    //      As a static method - ::accpetConnection()
    if ($new_stream = @\stream_socket_accept($this->socket, 0)) {
      if (DEBUG) {
        $this->stats['client_connections']++;
      }
      $this->logger->debug("client connected to server");
      $this->connections[] = new Connection($new_stream, FALSE, $this->connectionTimeoutDefault);
    }
  }
  
  // TODO Can be abstracted away, for Server & Client.
  protected function handleConnections() {
    $line = NULL;
    $messages = [];
    
    foreach ($this->connections as $connection) {
      $connection->receive();
      
      if ($line = $connection->consume()) {
        $messages[] = "{$connection->remoteName}: $line";
      }
    }
    
    foreach ($this->connections as $connection) {
      foreach ($messages as $message) {
        $connection->append($message . "\n> ");
      }
    }
    
    foreach ($this->connections as $connection) {
      $connection->send();
    }
  }
  
  /**
   * Cleanup invalid client connections.
   */
  protected function cleanup() : void {
    if (\time() - $this->connectionCleanupLast < $this->connectionCleanupTime) {
      return;
    }
    
    foreach ($this->connections as &$connection) {
      if (!$connection->isValid()) {
        $connection = NULL;
      }
    }
    
    if (DEBUG) {
      $old_count = count($this->connections);
    }
    
    $this->connections = array_filter($this->connections);
    $this->connectionCleanupLast = \time();
    
    if (DEBUG) {
      $new_count = count($this->connections);
      $diff = $old_count - $new_count;
      if ($diff) {
        $this->logger->debug("clients cleaned up $diff");
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
  
  public function signalShutdown() : void {
      $this->run = false;
  }
  
  public function shutdown() : void {
    // TODO Do we need to do that for a Unix socket?
    if (!\stream_socket_shutdown($this->socket, STREAM_SHUT_RD)) {
      $this->logger->error("failed to shutdown socket.");
    }
    
    if (!\fclose($this->socket)) {
      $this->logger->error("failed to close socket.");
    }
    
    $this->logger->debug("total client connections: {$this->stats['client_connections']}");
    
    $this->logger->debug("shutdown.");
  }
}
