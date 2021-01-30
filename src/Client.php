<?php
/**
 * @file
 * Client class file.
 */

namespace Parallel;

class Client {
  /**
   * @var \Parallel\Main;
   */
  protected $main;
  
  // The socket path should always be absolute.
  protected $sock_path;
  protected $sock_url;
  //$this->sock_url = 'tcp://0.0.0.0:9999';
  protected $socket;
  
  // Max connect retries.
  protected $retries = 3;
  
  protected $stat_total_bytes_sent = 0;
  
  public function __construct(Main $main) {
    $this->main = $main;
    
    // The socket path should always be absolute.
    $this->sock_path = \getcwd() . '/php-daemon.sock';
    $this->sock_url = "unix://{$this->sock_path}";
    
    // $this->sock_url = 'tcp://0.0.0.0:9999';
    
    // $this->main->getProcess()->addSignalHandler([$this, "shutdown"]);
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
    $this->socket = FALSE;
    for ($rt = 0; $rt < $this->retries; $rt++) {
      $errno = 0;
      $errstr = 'NULL';
      $this->socket = @\stream_socket_client($this->sock_url, $errno, $errstr, 60,
        // NOTE I don't know why, but without the persistent flag, there are
        //      there is one additional "empty" connection that's registered by
        //      the server/daemon.
        STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT
        );
      if (!$this->socket) {
        $this->main->log_error("Client: failed to connect to $this->sock_url, error: $errstr ($errno).");
        $this->main->println_debug("Client: retry connection ($rt)...");
        $this->main->getProcess()->sleep(0.050);
      }
    }
    if (!$this->socket) {
      throw new \RuntimeException("Client: failed to connect to {$this->sock_url}. Give up.");
    }
    
    // \stream_set_blocking($this->socket, TRUE);
    \stream_set_timeout($this->socket, 60);
//     if (0 != \stream_set_write_buffer($this->socket, 0)) {
//       $this->main->log_error("Client: failed to set write buffer to 0.");
//     }
  }
  
  protected function shutdown() {
    if (!\fclose($this->socket)) {
      $this->main->log_error("Client: failed to close remote socket $this->sock_url");
    }
    $this->main->println_debug("Client: shutdown. Total bytes sent: {$this->stat_total_bytes_sent}");
  }
  
  protected function write($data) {
    $bytes = \fwrite($this->socket, $data);
    $this->stat_total_bytes_sent += $bytes;
    return $bytes;
  }
  
  protected function sendTestPayload() : void {
    $this->main->println_debug("Client: send hello message.");
    $this->write($this->getMessage_Hello());
    
    for ($i = 0; $i < 3; $i++) {
      $this->main->println_debug("Client: send date message.");
      $this->write($this->getMessage_Date());
      \fflush($this->socket);
      $this->main->getProcess()->sleep(1);
    }
  }
  
  protected function getMessage_Hello() {
    return "\n\nHello from the client ({$this->main->getProcess()->pid}), via a Unix socket!!!\n";
  }
  
  protected function getMessage_Date() {
    return "\t({$this->main->getProcess()->pid}) Date is: " . \date(DATE_ATOM) . "\n";
  }
}
