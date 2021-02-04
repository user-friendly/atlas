<?php
/**
 * @file
 * Client class file.
 */

namespace Ants\Network;

use Ants\Logger\LoggerTrait;
use Ants\PDO\Dummy;

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
   * This is the socket I/O max wait time. 
   *
   * @var integer
   */
  protected $connectionTimeoutDefault = 10;
  
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
    
    //$this->socket_url = 'tcp://0.0.0.0:9999';
    
    $this->main->getProcess()->addSignalHandler([$this, "shutdown"]);
  }
  
  public function run() : int {
    // TODO Implement a ::reset() method, that resets the invariant.
    
    $this->connect();
    
    while ($this->connection->isValid()) {
      $this->handleConnection();
    }
    
    // At this point, this object is not longer an invariant.
    $this->shutdown();
    
    return \Ants\Main::EXIT_SUCCESS;
  }
  
  // TODO Can be abstracted away, for Server & Client.
  protected function handleConnection() {
    $this->connection->receive();
    
    $this->connection->process();
    
    $this->connection->send();
  }
  
  protected function connect() : void {
    $this->logger->debug("connect to {$this->socket_url}");
    
    $this->socket = FALSE;
    for ($retries = 0; $retries < $this->retries; $retries++) {
      $errno = 0;
      $errstr = 'NULL';
      $this->socket = @\stream_socket_client($this->socket_url, $errno, $errstr, 60, STREAM_CLIENT_CONNECT);
      if ($this->socket) {
        break;
      }
      else {
        $this->logger->error("failed to connect to $this->socket_url, error: $errstr ($errno).");
        $this->logger->debug("retry connection ($retries)...");
        $this->main->getProcess()->sleep(\mt_rand(50, 500) / 1000);
      }
    }
    if (!$this->socket) {
      throw new \RuntimeException("failed to connect to {$this->socket_url}. Give up.");
    }
    
    $this->connection = new Connection($this->socket, TRUE, $this->connectionTimeoutDefault);
    
    $this->logger->debug("connected after {$retries} retries.");
  }
  
  public function shutdown() {
    if (!\fclose($this->socket)) {
      $this->logger->error("failed to close remote socket $this->socket_url");
    }
    // TODO Do the stats. It's fun.
    // $this->logger->debug("shutdown. Total bytes sent: {$this->stats}");
  }
}
