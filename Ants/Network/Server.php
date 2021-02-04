<?php
/**
 * @file
 * Server class file.
 */

namespace Ants\Network;

use Ants\Main;
use Ants\Logger\LoggerTrait;
use Ants\PDO\Dummy;

class Server {
  use LoggerTrait;
  
  /**
   * @var \Ants\Main;
   */
  protected $main;
  
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
   * Default client connection timeout.
   * 
   * @var integer
   */
  protected $connectionTimeoutDefault = 60;
  
  /**
   * Server statistics.
   * 
   * Used only enabled in DEBUG mode.
   * 
   * @var array
   */
  protected $stats = [
    'total_client_connections' => 0,
  ];
  
  // Not really used (not displayed, but populated?).
  protected $stat_total_bytes_sent = 0;
  
  public function __construct(Main $main) {
    $this->logger = static::getLogger();
    $this->main = $main;
    
    // The socket path should always be absolute.
    $this->socketPath = \getcwd() . '/php-daemon.sock';
    $this->socketUrl = "unix://{$this->socketPath}";
    
    //$this->sock_url = 'tcp://0.0.0.0:9999';
    
    $this->main->getProcess()->addSignalHandler([$this, "shutdown"]);
  }
  
  public function run() : int {
    $this->listen();
    
    while (TRUE) {
      
      $this->accept();
      
      $this->handleConnections();
      
      $this->handleCleanup();
      
      // Yield CPU time only when there's nothing to do. 
      if (empty($this->connections)) {
        $this->main->getProcess()->yield();
      }
    }
    
    $this->shutdown();
    
    return Main::EXIT_SUCCESS;
  }
  
  protected function accept() : void {
    if ($new_stream = @\stream_socket_accept($this->socket, 60)) {
      if (DEBUG) {
        $this->stats['total_client_connections']++;
      }
      $this->logger->debug("client connected to server");
      $this->connections[] = new Connection($new_stream);
    }
  }
  
  protected function handleConnections() {
    foreach ($this->connections as $connection) {
      $connection->receive();
    }
    
    foreach ($this->connections as $connection) {
      $connection->process();
    }
    
    foreach ($this->connections as $connection) {
      $connection->send();
    }
  }
  
  /**
   * Clenup invalid client connections.
   */
  protected function handleCleanup() : void {
    // FIXME Do cleanup less frequently. Set a timer, or something.
    foreach ($this->connections as &$connection) {
      if (!$connection->isValid()) {
        $connection = NULL;
      }
    }
    if (DEBUG) $old_count = count($this->connections);
    $this->connections = array_filter($this->connections);
    if (DEBUG) $new_count = count($this->connections);
    $this->logger->debug("clients cleaned up " . ($old_count - $new_count));
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
    
    if (DEBUG) {
      Connection::debugStreamInfo($this->socket, $this->logger);
    }
    
    if (!$this->socket ) {
      throw new \RuntimeException("Failed to create $this->socketPath, error: $errstr ($errno).");
    }
    else {
      $this->logger->debug("Bound to socket: $this->socketUrl");
    }
  }
  
  public function shutdown() : void {
    // TODO Do we need to do that for a Unix socket? It goes both ways...
    if (!\stream_socket_shutdown($this->socket, STREAM_SHUT_RD)) {
      $this->logger->error("failed to shutdown socket.");
    }
    
    if (!\fclose($this->socket)) {
      $this->logger->error("failed to close socket.");
    }
    
    $this->logger->debug("total client connections: {$this->stats['total_client_connections']}");
    
    $this->logger->debug("shutdown.");
  }
}
