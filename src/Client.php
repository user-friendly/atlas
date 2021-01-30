<?php
/**
 * @file
 * Client class file.
 */

namespace Parallel;

use Parallel\PDO\Dummy;

class Client {
  /**
   * @var \Parallel\Main;
   */
  protected $main;
  
  // The socket path should always be absolute.
  protected $socket_path;
  protected $socket_url;
  //$this->sock_url = 'tcp://0.0.0.0:9999';
  protected $socket;
  // In seconds.
  protected $socket_timeout = 60;
  
  protected $readLength = 1024;
  // Not used at the moment.
  protected $writeLength = 1024;
  
  // Max connect retries.
  protected $retries = 3;
  
  protected $stat_total_bytes_sent = 0;
  
  public function __construct(Main $main) {
    $this->main = $main;
    
    // The socket path should always be absolute.
    $this->socket_path = \getcwd() . '/php-daemon.sock';
    $this->socket_url = "unix://{$this->socket_path}";
    
    // $this->sock_url = 'tcp://0.0.0.0:9999';
    
    $this->main->getProcess()->addSignalHandler([$this, "shutdown"]);
  }
  
  public function run() : int {
    // TODO Implement a ::reset() method, that resets the invariant.
    
    $this->connect();
    
    $this->sendTestPayload();
    
    // At this point, this object is not longer an invariant.
    $this->shutdown();
    
    return Main::EXIT_SUCCESS;
  }
  
  protected function connect() : void {
    $this->main->println_debug("Client: connect to {$this->socket_url}");
    
    $this->socket = FALSE;
    for ($rt = 0; $rt < $this->retries; $rt++) {
      $errno = 0;
      $errstr = 'NULL';
      $this->socket = @\stream_socket_client($this->socket_url, $errno, $errstr, 60,
        // NOTE I don't know why, but without the persistent flag, there are
        //      there is one additional "empty" connection that's registered by
        //      the server/daemon.
        STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT
        );
      if (!$this->socket) {
        $this->main->log_error("Client: failed to connect to $this->socket_url, error: $errstr ($errno).");
        $this->main->println_debug("Client: retry connection ($rt)...");
        $this->main->getProcess()->sleep(0.050);
      }
    }
    if (!$this->socket) {
      throw new \RuntimeException("Client: failed to connect to {$this->socket_url}. Give up.");
    }
    
    // For the time being.
    \stream_set_blocking($this->socket, TRUE);
    \stream_set_timeout($this->socket, $this->socket_timeout);
    
//     if (0 != \stream_set_write_buffer($this->socket, 0)) {
//       $this->main->log_error("Client: failed to set write buffer to 0.");
//     }
  }
  
  protected function shutdown() {
    if (!\fclose($this->socket)) {
      $this->main->log_error("Client: failed to close remote socket $this->socket_url");
    }
    $this->main->println_debug("Client: shutdown. Total bytes sent: {$this->stat_total_bytes_sent}");
  }
  
  protected function write($data) {
    $bytes = \fwrite($this->socket, $data);
    $this->stat_total_bytes_sent += $bytes;
    return $bytes;
  }
  
  protected function writeHello() {
    $this->write(pack('n', Server::MARK_HELLO));
  }
  
  protected function read() : string {
    return \fread($this->socket, $this->readLength);
  }
  
  protected function sendTestPayload() : void {
    $this->writeHello();
    // If the server doesn't close the connection, we're good.
    $this->main->println_debug("Client: handshake successful, wait for hello.");
    
    if ($recv_buff = $this->read()) {
      $this->main->println_debug("Client: received from server: $recv_buff");
    }
    
    $this->write($this->getMessage_Hello());
    
//     $this->write("recv pdo:\n\n");
//     $this->write();
//     $this->write("\n\n");
    
//     for ($i = 0; $i < 3; $i++) {
//       $this->main->println_debug("Client: send date message.");
//       $this->write($this->getMessage_Date());
//       \fflush($this->socket);
//       $this->main->getProcess()->sleep(1);
//     }
  }
  
  protected function getMessage_Hello() {
    return "\n\nHello from pid {$this->main->getProcess()->pid}, via a Unix socket.\n";
  }
  
  protected function getMessage_Date() {
    return "\t({$this->main->getProcess()->pid}) Date is: " . \date(DATE_ATOM) . "\n";
  }
  
  protected function getData() {
    return new Dummy();
  }
}
