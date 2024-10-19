<?php
/**
 * @file
 * Simple MUD test client.
 */

/**
 * Simple MUD test client.
 * 
 * Used for testing purposes and should not be dependent on any external libraries.
 * Use only stock PHP function so it's maximally portable and backwards compatible. 
 */

namespace SmpTest;

use SmpTest\Network\SimpleClient;

class ClientTestSimple implements Test {
  public function run() : int {
    global $argc, $argv;
    
    if ($argc < 4) {
      throw new \Exception("No host & port specified.");
    }
    
    $address = $argv[2];
    $port = $argv[3];
    
    $client = new SimpleClient($address, $port);
    $client->connect();
    
    $client->test();
    
    $client->close();
    
    return 0;
  }
}
