<?php
/**
 * @file
 * UserState enumerator.
 */

namespace Smp\User;

/**
 * User State
 */
enum UserState {
  /**
   * User is at the login screen.
   */
  case Login;
  
  /**
   * User is authenticated and has an associated game entity.
   */
  case Game;
}
