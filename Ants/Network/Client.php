<?php
/**
 * @file
 * Client class file.
 */

namespace Ants\Network;

use Ants\Main;
use Ants\Logger\LoggerTrait;

class Client {
  use LoggerTrait;
  
  /**
   * @var \Ants\Main;
   */
  protected $main;
  
  // The socket path should always be absolute.
  protected $socket_path;
  protected $socket_url;
  
  /**
   * Connection.
   * 
   * @var \Ants\Network\Connection
   */
  protected $connection;
  
  /**
   * Default client connection timeout.
   * 
   * This is the stream I/O max wait time, in blocking mode only.
   *
   * @var integer
   */
  protected $connectionTimeoutDefault = 60;
  
  /**
   * Maximum time to wait for the server to accept the connection.
   * 
   * @var integer
   */
  protected $connectWaitTime = 60;
  
  // Max connect retries.
  protected $retries = 8;
  
  protected $stats = [
    'bytes_read' => 0,
    'bytes_written' => 0,
  ];
  
  public function __construct(\Ants\Main $main) {
    $this->logger = static::getLogger();
    $this->main = $main;
    
    // The socket path should always be absolute.
    $this->socket_path = \getcwd() . '/php-daemon.sock';
    $this->socket_url = "unix://{$this->socket_path}";
    
    $this->main->getProcess()->addSignalHandler([$this, "shutdown"]);
  }
  
  public function run() : int {
    // TODO Implement a ::reset() method, that resets the invariant.
    
    $this->connect();
    
    while ($this->connection->isValid()) {
      $this->handleConnection();
    }
    
    $this->shutdown();
    
    return Main::EXIT_SUCCESS;
  }
  
  // TODO Can be abstracted away, for Server & Client.
  protected function handleConnection() {
    $this->connection->receive();
    
    $this->connection->process();
    
    $this->connection->send();
  }
  
  protected function connect() : void {
    $this->logger->debug("connect to {$this->socket_url}");
    
    $socket = NULL;
    for ($retries = 0; $retries < $this->retries; $retries++) {
      $errno = 0;
      $errstr = 'NULL';
      $socket = @\stream_socket_client($this->socket_url, $errno, $errstr, $this->connectWaitTime, STREAM_CLIENT_CONNECT);
      if ($socket) {
        break;
      }
      else {
        $this->logger->error("failed to connect to $this->socket_url, error: $errstr ($errno).");
        $this->logger->debug("retry connection ($retries)...");
        $this->main->getProcess()->sleep(\mt_rand(50, 500) / 1000);
      }
    }
    if (!$socket) {
      throw new \RuntimeException("failed to connect to {$this->socket_url}. Give up.");
    }
    
    $this->connection = new Connection($socket, FALSE, $this->connectionTimeoutDefault);
    
    $this->logger->debug("connected after {$retries} retries.");
  }
  
  public function shutdown() {
    if ($this->connection->isValid()) {
      $this->logger->debug("closing connection");
      $this->connection->close();
    }
    // TODO Do the stats.
    // $this->logger->debug("shutdown. Total bytes sent: {$this->stats}");
  }
}
