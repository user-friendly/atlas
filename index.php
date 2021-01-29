<?php

define('DEBUG', TRUE);

require __DIR__ . '/vendor/autoload.php';

use Parallel\Process;

$process = new Process();

function println($msg) {
  global $process;
  print "({$process->pid}) $msg" . PHP_EOL;
}

function log_error($msg) {
  global $process;
  fwrite(\STDERR, "({$process->pid}) $msg" . PHP_EOL);
}

function __stream_info($stream) {
  return; 
  
  println("Socket open: " . \stream_socket_get_name($stream, FALSE));
  
  if (\stream_supports_lock($stream)) {
    println("Socket supports locks.");
  }
  else {
    println("Socket does not support locks.");
  }
  
  if (\stream_is_local($stream)) {
    println("Socket is local.");
  }
  else {
    println("Socket is not local.");
  }
  
  if (\stream_isatty($stream)) {
    println("Socket is tty.");
  }
  else {
    println("Socket is not tty.");
  }
}

// The socket path should always be absolute.
$sock_path = __DIR__ . '/php-daemon.sock';
$sock_url = "unix://$sock_path";

// Fork before opening the server socket.
$process->forkSafe();
// Make another one.
$process->forkSafe();
// Make another one.
$process->forkSafe();

if (!$process->isChild) {
  //Old trick cowboys use.
  if (\file_exists($sock_path)  && !\unlink($sock_path)) {
    log_error("Failed to bind - could not remove existing socket $sock_path.");
    exit(1);
  }
  
  $errno = 0;
  $errstr = 'NULL';
  $server_sock = \stream_socket_server($sock_url, $errno, $errstr,
    STREAM_SERVER_BIND | STREAM_SERVER_LISTEN
    );
  
  if (!$server_sock) {
    log_error("Failed to create $sock_path, error: $errstr ($errno).");
    exit(1);
  }
  else {
    println("Bound to socket: ");
    __stream_info($server_sock);
  }
  
  $wait_accept_read_close = function () use ($server_sock) {
    if ($client_sock = @\stream_socket_accept($server_sock, 5)) {
      println("Client connected to server! Client sock info follows:");
      __stream_info($client_sock);
      
      println("Ready to read from client!");
      // Block, until data is read.
      while ($recv_buff = \fread($client_sock, 1024)) {
        println("Client: $recv_buff");
        
        if (\feof($client_sock)) {
          println("Client closed connection");
          break;
        }
      }
      \fflush($client_sock);
      \fclose($client_sock);
    }
    else {
      println("Failed to accept client (probably timeout). Shutdown now.");
    }
  };
  
  // Wait for 5 clients.
  for ($i = 0; $i < 5; $i++) {
    $wait_accept_read_close();
  }
  
  // TODO Do we need to do that for a Unix socket? It goes both ways...
  if (!\stream_socket_shutdown($server_sock, STREAM_SHUT_RD)) {
    log_error("Failed to shutdown socket.");
  }
  
  if (!\fclose($server_sock)) {
    log_error("Failed to close socket.");
  }
  
  println("Server shutdown ({$process->pid}).");
} // End server handler.
else {
  
  $local_socket = FALSE;
  for ($rt = 0; $rt < 3; $rt++) {
    $errno = 0;
    $errstr = 'NULL';
    $local_socket = @\stream_socket_client($sock_url, $errno, $errstr, 5);
    if (!$local_socket) {
      log_error("Failed to connect to $sock_url, error: $errstr ($errno).");
      println("Retry connection ($rt)...");
      $process->sleep(0.050);
    }
  }
  if (!$local_socket) {
    log_error("Failed to connect to $sock_url. Give up.");
    exit(1);
  }
  
//   // Tease out the server for some time.
//   println("Client connected. Sleep some.");
//   \sleep(1);
  
  // Assume all is written out.
  println("Client send message.");
  \fwrite($local_socket, "Hello from the client ({$process->pid}), via a Unix socket!!!");
  \fflush($local_socket);
  
  if (!\fclose($local_socket)) {
    log_error("Failed to close remote socket $sock_url");
    exit(1);
  }
  println("Client shutdown.");
} // End client handler.





















