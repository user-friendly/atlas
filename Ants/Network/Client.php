<?php
/**
 * @file
 * A simple client.
 */

namespace Ants\Network;

use Ants\Game;
use Ants\Process;
use Ants\Logger\LoggerTrait;

/**
 * Client class.
 * 
 * Probably going to use it for testing only.
 */
class Client {
  use LoggerTrait;
  
  /**
   * @var \Ants\Game;
   */
  protected $process;
  
  // The socket path should always be absolute.
  protected $socketPath;
  protected $socketUrl;
  
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
    "bytes_read" => 0,
    "bytes_written" => 0,
  ];
  
  public function __construct(Process $process) {
    $this->logger = static::getLogger();
    $this->process = $process;
    
    // The socket path should always be absolute.
    //$this->socketPath = \getcwd() . "/php-daemon.sock";
    //$this->socketUrl = "unix://{$this->socketPath}";
    $this->socketUrl = 'tcp://0.0.0.0:9999';
    
    $this->process->addSignalHandler([$this, "shutdown"]);
  }
  
  public function run() : int {
    // TODO Implement a ::reset() method, that resets the invariant.
    
    $this->connect();
    
    while ($this->connection->isValid()) {
      $this->handleConnection();
    }
    
    $this->shutdown();
    
    return Game::EXIT_SUCCESS;
  }
  
  // TODO Can be abstracted away, for Server & Client.
  protected function handleConnection() {
    $this->connection->receive();
    
    $this->connection->process();
    
    $this->connection->send();
  }
  
  protected function connect() : void {
    $this->logger->debug("connect to {$this->socketUrl}");
    
    // TODO Why is the following block not part of the Connection class?
    //      Older me: maybe because the collection class is lower level, only concerning itself with I/O?
    //                Still, this could be a static/helper method as part of the connection.
    //                The client may be responsible for the URL only, which it gets from a config store.
    //                Yeah, the more I look at the `stream_socket_client()` call...
    $socket = NULL;
    for ($retries = 0; $retries < $this->retries; $retries++) {
      $errno = 0;
      $errstr = 'NULL';
      $socket = @\stream_socket_client($this->socketUrl, $errno, $errstr, $this->connectWaitTime, STREAM_CLIENT_CONNECT);
      if ($socket) {
        break;
      }
      else {
        $this->logger->error("failed to connect to $this->socketUrl, error: $errstr ($errno).");
        $this->logger->debug("retry connection ($retries)...");
        // FIXME A bunch of magic here.
        $this->process->sleep(\mt_rand(50, 500) / 1000);
      }
    }
    if (!$socket) {
      throw new \RuntimeException("failed to connect to {$this->socketUrl}. Give up.");
    }
    
    $this->connection = new Connection($socket, FALSE, 1 /* $this->connectionTimeoutDefault */);
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
