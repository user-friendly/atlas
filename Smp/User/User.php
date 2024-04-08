<?php
/**
 * @file
 * User class.
 */

namespace Smp\User;

use Smp\Logger\LoggerFactory;
use Smp\Logger\LoggerTrait;

/**
 * For the time being, do not store the password in the
 * user object. Seems like it could be a security issue.
 * Since the user Manager will handle logins, let it
 * handle passwords too.
 */

/**
 * Server user class.
 */
class User {
  use LoggerTrait;
  
  protected $id = 0;
  protected $username = NULL;
  protected $context = NULL;
  
  public function __construct(int $id, string $username, array $context = []) {
    LoggerFactory::init();
    $this->logger = static::getLogger();
    
    $this->id = $id;
    $this->username = $username;
    $this->context = $context;
  }
  
  public function getId() : int {
    return $this->id;
  }
  
  public function getUsername() : string {
    return $this->username;
  }
  
  public function getContext() : array {
    return $this->context;
  }
}

