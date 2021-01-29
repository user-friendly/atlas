<?php

define('DEBUG', TRUE);

require __DIR__ . '/vendor/autoload.php';

use Parallel\Process;
use Symfony\Component\EventDispatcher\GenericEvent;

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
//$sock_url = 'tcp://0.0.0.0:9999';

// Fork before opening the server socket.
$spawn_count = 1; // \mt_rand(3, 8);
println("Spawn $spawn_count children");
for ($i = 0; $i < $spawn_count; $i++) {
  $process->forkSafe();
  if ($process->isChild) {
    break;
  }
}

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
    println("Bound to socket: $sock_url");
  }
  
  $shutdown = function () use ($server_sock, $process) {
    // TODO Do we need to do that for a Unix socket? It goes both ways...
    if (!\stream_socket_shutdown($server_sock, STREAM_SHUT_RD)) {
      log_error("Server: failed to shutdown socket.");
    }
    
    if (!\fclose($server_sock)) {
      log_error("Server: failed to close socket.");
    }
    
    println("Server: shutdown ({$process->pid}).");
  };
  
  $process->addSignalHandler(function (GenericEvent $event) use ($shutdown) {
    $shutdown();
  });
  
  $stat_total_all_time = 0;
  $clients = [];
  
  while (TRUE) {
    if ($new_client_sock = \stream_socket_accept($server_sock, 60)) {
      $stat_total_all_time++;
      println("Server: client connected to server! (stat: $stat_total_all_time)");
      
      // Don't block.
      \stream_set_blocking($new_client_sock, FALSE);
      
      $clients[] = $new_client_sock;
    }
//     else {
//       println("Server: failed to accept client (probably timeout).");
//     }
    
    $filter_socks = FALSE;
    // Do reads, writes and connection colsures.
    foreach ($clients as &$client_sock) {
      if ($recv_buff = \fread($client_sock, 1024)) {
        println("Server: from client: $recv_buff");
      }
      if (\feof($client_sock)) {
        println("Server: closing client connection.");
        \fflush($client_sock);
        \fclose($client_sock);
        $client_sock = NULL;
        $filter_socks = TRUE;
      }
    }
    if ($filter_socks) {
      $clients = array_filter($clients);
    }
    $process->yield();
  }
  $shutdown();
  
} // End server handler.

else {
  \sleep(\mt_rand(1, 2));
  
  $local_socket = FALSE;
  for ($rt = 0; $rt < 3; $rt++) {
    $errno = 0;
    $errstr = 'NULL';
    $local_socket = @\stream_socket_client($sock_url, $errno, $errstr, 60);
    if (!$local_socket) {
      log_error("Client: failed to connect to $sock_url, error: $errstr ($errno).");
      println("Client: retry connection ($rt)...");
      $process->sleep(0.050);
    }
  }
  if (!$local_socket) {
    log_error("Client: failed to connect to $sock_url. Give up.");
    exit(1);
  }
  
  \stream_set_blocking($local_socket, TRUE);
  
//   // Tease out the server for some time.
//   println("Client connected. Sleep some.");
//   \sleep(1);
  
  // Assume all is written out.
  println("Client: send message.");
  $bytes = \fwrite($local_socket, "Hello from the client ({$process->pid}), via a Unix socket!!!");
  println("Client: wrote $bytes to socket.");
  \fflush($local_socket);
  
  if (!\fclose($local_socket)) {
    log_error("Client: failed to close remote socket $sock_url");
    exit(1);
  }
  println("Client: shutdown.");
} // End client handler.





















