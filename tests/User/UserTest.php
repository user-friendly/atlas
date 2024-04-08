<?php
/**
 * @file
 * User Manager test file.
 */

namespace Smp\tests\User;

use Smp\User\User;

use Smp\tests\Test;

class UserTest extends Test {
  
  public function run(): int {
    
    {
      $context = ['a', 'b'];
      $user = new User(1, "tester", $context);
      
      $this->assert($user->getId() === 1, "New user has the correct id.");
      $this->assert($user->getUsername() === "tester", "New user has the correct username.");
      
      $new_context = $user->getContext();
      $this->assert($new_context[0] === "a" && $new_context[1] === "b",
        "New user has the correct context.");
    }
    
    return 0;
  }
}
