<?php
/**
 * @file
 * Program entry point.
 */

namespace Ants;

use Ants\Logger\LoggerFactory;
use Ants\Logger\LoggerTrait;

class User {
  use LoggerTrait;
  
  protected $id = 0;
  protected $username = NULL;
  
  static protected $serialid = 1;
  
  public function __construct(string $username) {
    LoggerFactory::init();
    $this->logger = static::getLogger();
    
    $this->id = $this::$serialid++;
    $this->username = $username;
  }
  
  public function getId() : int {
    return $this->id;
  }
  
  public function getUsername() : string {
    return $this->username;
  }
  
}

